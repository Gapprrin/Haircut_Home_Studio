<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateHairPreview;
use App\Models\AiGeneration;
use App\Models\Categoria;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AiQuotaService;
use App\Services\PhotoSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiStudioController extends Controller
{
    public function __construct(
        private readonly AiQuotaService $quota,
        private readonly PhotoSanitizer $sanitizer,
    ) {}

    public function index(Request $request): View
    {
        return $this->view($request);
    }

    public function show(Request $request, AiGeneration $generation): View
    {
        $this->ensureOwner($request, $generation);

        return $this->view($request, $generation);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->available()) {
            return back()->with('error', 'El simulador todavía no está habilitado.');
        }

        $data = $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'max:6144',
                'dimensions:min_width=512,min_height=512,max_width=4096,max_height=4096',
            ],
            'servicio_id' => ['required', 'integer', Rule::exists('servicios', 'id')->where('activo', true)],
            'consent' => ['accepted'],
        ], [
            'photo.required' => 'Selecciona una fotografía.',
            'photo.image' => 'El archivo debe ser una imagen válida.',
            'photo.mimes' => 'La fotografía debe estar en formato JPG o PNG.',
            'photo.max' => 'La fotografía no puede superar 6 MB.',
            'photo.dimensions' => 'La fotografía debe medir entre 512 y 4096 píxeles por lado.',
            'servicio_id.required' => 'Selecciona un servicio.',
            'servicio_id.exists' => 'El servicio seleccionado no está disponible.',
            'consent.accepted' => 'Debes aceptar el procesamiento temporal de la fotografía.',
        ]);

        $servicio = Servicio::query()
            ->with('categoria')
            ->whereKey($data['servicio_id'])
            ->where('activo', true)
            ->whereHas('categoria', fn ($query) => $query->whereIn('slug', ['corte', 'color']))
            ->first();
        if (! $servicio) {
            throw ValidationException::withMessages([
                'servicio_id' => 'El servicio seleccionado no está disponible para simulación.',
            ]);
        }

        $sanitized = $this->sanitizer->sanitize($request->file('photo')->get());
        $existing = AiGeneration::query()
            ->where('usuario_id', $request->user()->id)
            ->where('servicio_id', $servicio->id)
            ->where('input_hash', $sanitized['hash'])
            ->where('status', 'completed')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing?->output_path && Storage::disk('local')->exists($existing->output_path)) {
            return redirect()->route('ai.show', $existing)
                ->with('success', 'Ya tenías esta simulación; reutilizamos el resultado sin consumir otro cupo.');
        }

        $inputPath = null;
        try {
            $generation = DB::transaction(function () use ($request, $servicio, $sanitized, &$inputPath): AiGeneration {
                $usuario = User::query()->lockForUpdate()->findOrFail($request->user()->id);
                $quota = $this->quota->status($usuario);
                if (! $quota['allowed']) {
                    throw ValidationException::withMessages(['quota' => $quota['reason']]);
                }

                $inputPath = 'ai/input/'.Str::uuid().'.'.$sanitized['extension'];
                if (! Storage::disk('local')->put($inputPath, $sanitized['bytes'])) {
                    throw ValidationException::withMessages(['photo' => 'No fue posible guardar la fotografía.']);
                }

                return AiGeneration::create([
                    'usuario_id' => $usuario->id,
                    'servicio_id' => $servicio->id,
                    'preset' => 'service:'.$servicio->id,
                    'input_path' => $inputPath,
                    'input_hash' => $sanitized['hash'],
                    'status' => 'pending',
                    'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                    'expires_at' => now()->addHours(max(1, (int) config('ai.retention_hours'))),
                ]);
            });
        } catch (\Throwable $exception) {
            if ($inputPath) {
                Storage::disk('local')->delete($inputPath);
            }
            throw $exception;
        }

        if ((bool) config('ai.process_sync')) {
            GenerateHairPreview::dispatchSync($generation->id);
        } else {
            GenerateHairPreview::dispatch($generation->id)->onQueue('ai');
        }

        return redirect()->route('ai.show', $generation);
    }

    public function status(Request $request, AiGeneration $generation): JsonResponse
    {
        $this->ensureOwner($request, $generation);

        return response()->json([
            'status' => $generation->status,
            'ready' => $generation->estaLista() && $generation->expires_at->isFuture(),
            'show_url' => route('ai.show', $generation),
        ]);
    }

    public function image(Request $request, AiGeneration $generation, string $kind): Response
    {
        $this->ensureImageAccess($request, $generation);
        abort_if($generation->expires_at->isPast(), 410);

        $path = $kind === 'input' ? $generation->input_path : $generation->output_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $bytes = Storage::disk('local')->get($path);
        $mimeType = @getimagesizefromstring($bytes)['mime'] ?? 'application/octet-stream';

        return response($bytes, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    public function destroyMedia(Request $request, AiGeneration $generation): RedirectResponse
    {
        $this->ensureOwner($request, $generation);
        Storage::disk('local')->delete(array_filter([$generation->input_path, $generation->output_path]));
        $generation->update([
            'input_path' => null,
            'output_path' => null,
            'status' => 'purged',
        ]);

        return redirect()->route('ai.index')->with('success', 'Las imágenes se eliminaron. El registro de uso se conserva para aplicar la cuota.');
    }

    private function view(Request $request, ?AiGeneration $selected = null): View
    {
        $generations = AiGeneration::query()
            ->with('servicio.categoria')
            ->where('usuario_id', $request->user()->id)
            ->latest('id')
            ->limit(12)
            ->get();
        $serviceCategories = Categoria::query()
            ->whereIn('slug', ['corte', 'color'])
            ->whereHas('servicios', fn ($query) => $query->where('activo', true))
            ->with(['servicios' => fn ($query) => $query->where('activo', true)->orderBy('id')])
            ->orderBy('id')
            ->get();
        $selected?->loadMissing('servicio.categoria');

        return view('ai.index', [
            'available' => $this->available(),
            'demoMode' => config('ai.provider') === 'fake',
            'presets' => (array) config('ai.presets'),
            'serviceCategories' => $serviceCategories,
            'serviceCount' => $serviceCategories->sum(fn (Categoria $category) => $category->servicios->count()),
            'quota' => $this->quota->status($request->user()),
            'generations' => $generations,
            'selected' => $selected,
        ]);
    }

    private function available(): bool
    {
        return (bool) config('ai.enabled')
            && (config('ai.provider') === 'fake' || filled(config('ai.api_key')));
    }

    private function ensureOwner(Request $request, AiGeneration $generation): void
    {
        abort_unless($generation->usuario_id === $request->user()->id, 404);
    }

    private function ensureImageAccess(Request $request, AiGeneration $generation): void
    {
        $isOwner = $generation->usuario_id === $request->user()->id;
        $isAssignedStaff = $request->user()->esPeluquero() && $generation->reserva_id !== null;
        abort_unless($isOwner || $isAssignedStaff, 404);
    }
}
