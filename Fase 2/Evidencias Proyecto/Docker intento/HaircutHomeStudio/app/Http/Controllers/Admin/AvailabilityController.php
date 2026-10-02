<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiaOff;
use App\Models\MesVisible;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function edit(Request $request): View
    {
        $anio = max(2026, $request->integer('anio', (int) now()->format('Y')));
        $mes = $request->integer('mes', (int) now()->format('n'));
        if ($mes < 1 || $mes > 12) {
            $mes = (int) now()->format('n');
        }

        $configuracion = $this->availability->configuracion();
        $inicio = CarbonImmutable::create($anio, $mes, 1);
        $fin = $inicio->endOfMonth();
        $offs = DiaOff::query()->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->get()->map(fn (DiaOff $dia) => $dia->fecha->day)->all();
        $visibles = MesVisible::query()->get()->mapWithKeys(
            fn (MesVisible $visible) => [sprintf('%04d-%02d', $visible->anio, $visible->mes) => true]
        )->all();
        $anioTecho = max(2035, $anio, (int) now()->format('Y') + 9, (int) MesVisible::query()->max('anio'));

        return view('admin.disponibilidad', [
            'anio' => $anio,
            'mes' => $mes,
            'configuracion' => $configuracion,
            'diasAtencion' => $this->availability->diasAtencion(),
            'offs' => $offs,
            'semanas' => $this->availability->calendario($anio, $mes),
            'visibles' => $visibles,
            'anioTecho' => $anioTecho,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'min:2026', 'max:2100'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'dias' => ['nullable', 'array'],
            'dias.*' => ['integer', 'between:2,6'],
            'off' => ['nullable', 'array'],
            'off.*' => ['integer', 'between:1,31'],
            'meses_visibles' => ['nullable', 'array'],
            'meses_visibles.*' => ['date_format:Y-m'],
        ]);

        DB::transaction(function () use ($datos): void {
            $configuracion = $this->availability->configuracion();
            $dias = array_values(array_unique(array_map('intval', $datos['dias'] ?? [])));
            sort($dias);
            $configuracion->update([
                'hora_inicio' => $datos['hora_inicio'].':00',
                'hora_fin' => $datos['hora_fin'].':00',
                'dias_atencion' => $dias ? implode(',', $dias) : '2,3,4,5,6',
            ]);

            $inicio = CarbonImmutable::create($datos['anio'], $datos['mes'], 1);
            $fin = $inicio->endOfMonth();
            DiaOff::query()->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])->delete();
            foreach (array_unique($datos['off'] ?? []) as $numeroDia) {
                if ($numeroDia > $inicio->daysInMonth) {
                    continue;
                }
                $fecha = $inicio->setDay($numeroDia);
                if (! in_array($fecha->dayOfWeekIso, [1, 7], true)) {
                    DiaOff::firstOrCreate([
                        'fecha' => $fecha->toDateString(),
                    ], ['configuracion_id' => $configuracion->id]);
                }
            }

            $meses = collect($datos['meses_visibles'] ?? [])
                ->push(now()->format('Y-m'))
                ->unique()
                ->filter(function (string $valor): bool {
                    $fecha = CarbonImmutable::createFromFormat('!Y-m', $valor);

                    return $fecha->greaterThanOrEqualTo(now()->startOfMonth());
                });
            MesVisible::query()->delete();
            foreach ($meses as $valor) {
                [$anio, $mes] = array_map('intval', explode('-', $valor));
                MesVisible::create(['configuracion_id' => $configuracion->id, 'anio' => $anio, 'mes' => $mes]);
            }
        });

        return redirect()->route('peluquero.disponibilidad.edit', ['anio' => $datos['anio'], 'mes' => $datos['mes']])
            ->with('success', 'Disponibilidad guardada.');
    }
}
