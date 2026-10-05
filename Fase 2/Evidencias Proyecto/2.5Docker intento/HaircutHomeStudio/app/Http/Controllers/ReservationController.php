<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\Categoria;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Services\AvailabilityService;
use App\Services\ReservationWorkflowService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ReservationWorkflowService $workflow,
    ) {}

    public function index(Request $request): View
    {
        $this->workflow->actualizarRealizadas();

        $reservas = Reserva::query()
            ->with('servicio')
            ->where('usuario_id', $request->user()->id)
            ->whereNotIn('estado', ['cancelada', 'rechazada', 'no_asistio'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get();

        return view('reservas.index', compact('reservas'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $categorias = Categoria::query()
            ->with(['servicios' => fn ($query) => $query->where('activo', true)->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->filter(fn (Categoria $categoria) => $categoria->servicios->isNotEmpty())
            ->values();

        $servicio = Servicio::query()->with('categoria')->where('activo', true)->find($request->integer('serv'));
        $categoria = $categorias->firstWhere('slug', $request->string('cat')->toString())
            ?? ($servicio ? $categorias->firstWhere('id', $servicio->categoria_id) : null)
            ?? $categorias->first();
        $servicio = $servicio && $servicio->categoria_id === $categoria?->id
            ? $servicio
            : $categoria?->servicios->first();

        if (! $categoria || ! $servicio) {
            return redirect()->route('home')->with('error', 'Elige un servicio para continuar.');
        }

        $anio = $request->integer('anio', (int) now()->format('Y'));
        $mes = $request->integer('mes', (int) now()->format('n'));
        if (! $this->availability->mesEsVisible($anio, $mes)) {
            $anio = (int) now()->format('Y');
            $mes = (int) now()->format('n');
        }

        $fecha = $this->fechaSeleccionada($request->string('fecha')->toString(), $anio, $mes);
        if ($fecha) {
            $anio = $fecha->year;
            $mes = $fecha->month;
        }

        $slots = $fecha ? $this->availability->slotsDisponibles($fecha, $servicio) : [];
        $hora = in_array($request->string('hora')->toString(), $slots, true)
            ? $request->string('hora')->toString()
            : '';
        $todosSlots = $fecha ? $this->availability->generarSlots(60, $fecha) : [];
        $sugerencias = $fecha
            ? $this->availability->sugerencias($servicio, $fecha)
            : ['misma' => null, 'otra' => null, 'largo' => $this->availability->duracionHoras($servicio->duracion_min) >= 3];
        $semanas = $this->availability->calendario($anio, $mes);
        $estados = [];
        foreach ($semanas as $semana) {
            foreach (array_filter($semana) as $dia) {
                $fechaDia = CarbonImmutable::create($anio, $mes, $dia);
                $estados[$dia] = $this->availability->estadoDia($fechaDia, $servicio);
            }
        }
        $aiGeneration = $this->selectedAiGeneration($request);

        return view('reservas.create', [
            'categorias' => $categorias,
            'categoria' => $categoria,
            'servicio' => $servicio,
            'anio' => $anio,
            'mes' => $mes,
            'fecha' => $fecha?->toDateString() ?? '',
            'hora' => $hora,
            'lugar' => $request->string('lugar')->toString() === 'domicilio' ? 'domicilio' : 'salon',
            'slots' => $slots,
            'todosSlots' => $todosSlots,
            'sugerencias' => $sugerencias,
            'semanas' => $semanas,
            'estados' => $estados,
            'mesesVisibles' => $this->availability->mesesVisiblesCliente(),
            'aiGeneration' => $aiGeneration,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'servicio_id' => ['required', 'integer', Rule::exists('servicios', 'id')->where('activo', true)],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'lugar' => ['required', Rule::in(['salon', 'domicilio'])],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],
            'ai_generation_id' => ['nullable', 'integer'],
        ], [
            'foto.image' => 'Solo se permiten imágenes (JPG, PNG o WEBP). No PDF.',
            'foto.max' => 'La foto no puede superar 3 MB.',
        ]);

        $servicio = Servicio::query()->with('categoria')->findOrFail($datos['servicio_id']);
        $foto = null;
        $imagenSimulada = null;

        try {
            DB::transaction(function () use ($request, $datos, $servicio, &$foto, &$imagenSimulada): void {
                Reserva::query()->whereDate('fecha', $datos['fecha'])->lockForUpdate()->get();
                if (! in_array($datos['hora'], $this->availability->slotsDisponibles($datos['fecha'], $servicio), true)) {
                    throw new DomainException('La hora seleccionada ya no está disponible.');
                }

                $aiGeneration = null;
                if (! empty($datos['ai_generation_id'])) {
                    $aiGeneration = AiGeneration::query()
                        ->whereKey($datos['ai_generation_id'])
                        ->where('usuario_id', $request->user()->id)
                        ->where('status', 'completed')
                        ->where('expires_at', '>', now())
                        ->lockForUpdate()
                        ->first();
                    if (! $aiGeneration?->output_path || ! Storage::disk('local')->exists($aiGeneration->output_path)) {
                        throw ValidationException::withMessages([
                            'ai_generation_id' => 'La simulación seleccionada ya no está disponible.',
                        ]);
                    }
                }

                if ($aiGeneration) {
                    $extension = pathinfo($aiGeneration->output_path, PATHINFO_EXTENSION);
                    $imagenSimulada = 'reservas/simulaciones/'.Str::uuid().'.'.($extension ?: 'jpg');
                    if (! Storage::disk('local')->copy($aiGeneration->output_path, $imagenSimulada)) {
                        throw ValidationException::withMessages([
                            'ai_generation_id' => 'No fue posible adjuntar la simulación a la reserva.',
                        ]);
                    }
                }

                $foto = $request->file('foto')?->store('fotos', 'public');
                Reserva::create([
                    'usuario_id' => $request->user()->id,
                    'servicio_id' => $servicio->id,
                    'fecha' => $datos['fecha'],
                    'hora' => $datos['hora'],
                    'foto' => $foto,
                    'imagen_simulada' => $imagenSimulada,
                    'lugar' => $datos['lugar'],
                    'estado' => 'pendiente',
                ]);
            });
        } catch (DomainException) {
            $this->removeUncommittedImages($foto, $imagenSimulada);

            return back()->withInput()->with('error', 'Esa hora ya no está disponible. Elige otra.');
        } catch (Throwable $exception) {
            $this->removeUncommittedImages($foto, $imagenSimulada);
            throw $exception;
        }

        return redirect()->route('reservas.index')->with('success', 'Reserva enviada. El pago es presencial.');
    }

    public function cancel(Request $request, Reserva $reserva): RedirectResponse
    {
        if ($reserva->usuario_id !== $request->user()->id) {
            abort(404);
        }

        if (! $reserva->puedeCancelar()) {
            return back()->with('error', 'Ya no se puede cancelar.');
        }

        $reserva->update(['estado' => 'cancelada']);

        return back()->with('success', 'Reserva cancelada. El cupo quedó libre.');
    }

    public function simulatedImage(Request $request, Reserva $reserva): Response
    {
        abort_unless($reserva->usuario_id === $request->user()->id || $request->user()->esPeluquero(), 404);
        abort_unless($reserva->imagen_simulada && Storage::disk('local')->exists($reserva->imagen_simulada), 404);

        $bytes = Storage::disk('local')->get($reserva->imagen_simulada);
        $mimeType = @getimagesizefromstring($bytes)['mime'] ?? 'application/octet-stream';

        return response($bytes, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    private function fechaSeleccionada(string $valor, int $anio, int $mes): ?CarbonImmutable
    {
        if ($valor === '') {
            return null;
        }

        try {
            $fecha = CarbonImmutable::createFromFormat('!Y-m-d', $valor);
        } catch (\Throwable) {
            return null;
        }

        if ($fecha->format('Y-m-d') !== $valor || ! $this->availability->mesEsVisible($fecha->year, $fecha->month)) {
            return null;
        }

        return $fecha;
    }

    private function selectedAiGeneration(Request $request): ?AiGeneration
    {
        if ($request->integer('ai_generation') < 1) {
            return null;
        }

        $generation = AiGeneration::query()
            ->whereKey($request->integer('ai_generation'))
            ->where('usuario_id', $request->user()->id)
            ->where('status', 'completed')
            ->where('expires_at', '>', now())
            ->first();

        return $generation?->output_path && Storage::disk('local')->exists($generation->output_path)
            ? $generation
            : null;
    }

    private function removeUncommittedImages(?string $foto, ?string $imagenSimulada): void
    {
        if ($foto) {
            Storage::disk('public')->delete($foto);
        }
        if ($imagenSimulada) {
            Storage::disk('local')->delete($imagenSimulada);
        }
    }
}
