<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservationWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HoursController extends Controller
{
    public function __construct(private readonly ReservationWorkflowService $workflow) {}

    public function index(): View
    {
        $this->workflow->actualizarRealizadas();
        $citas = Reserva::query()
            ->with(['usuario', 'servicio'])
            ->whereDate('fecha', '>=', now()->toDateString())
            ->whereIn('estado', ['confirmada', 'realizada'])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get();

        $porDia = [];
        foreach ($citas->groupBy(fn (Reserva $reserva) => $reserva->fecha->toDateString()) as $fecha => $lista) {
            $items = [];
            foreach ($lista as $reserva) {
                if ($reserva->fecha->isFuture() && $reserva->estado === 'realizada') {
                    continue;
                }
                $inicio = CarbonImmutable::instance($reserva->fecha)->setTimeFromTimeString($reserva->hora);
                $fin = $inicio->addMinutes(max(60, $reserva->servicio->duracion_min));
                $items[] = [
                    'ids' => [$reserva->id],
                    'cliente' => $reserva->usuario->nombre,
                    'servicio' => $reserva->servicio->nombre,
                    'lugar' => $reserva->lugar,
                    'inicio' => $inicio,
                    'fin' => $fin,
                    'estado' => $reserva->estado,
                    'estado_ui' => $this->estadoUi($inicio, $fin, $reserva->estado),
                    'duracion' => max(60, $reserva->servicio->duracion_min),
                ];
            }
            $items = array_values(array_filter($items, fn (array $item) => $item['estado_ui'] !== 'oculto'));
            if ($items !== []) {
                $porDia[$fecha] = $items;
            }
        }

        return view('admin.horas', compact('porDia'));
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'accion' => ['required', Rule::in(['borrar', 'noshow'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('reservas', 'id')],
        ]);
        $estado = $datos['accion'] === 'noshow' ? 'no_asistio' : 'cancelada';
        Reserva::query()->whereKey($datos['ids'])->whereIn('estado', ['confirmada', 'realizada'])->update(['estado' => $estado]);

        return back()->with('success', $estado === 'no_asistio'
            ? 'Marcada como no llegó. Queda en el informe.'
            : 'Hora quitada de la agenda.');
    }

    private function estadoUi(CarbonImmutable $inicio, CarbonImmutable $fin, string $estado): string
    {
        if (now()->greaterThanOrEqualTo($fin->addMinutes(5))) {
            return 'oculto';
        }
        if (now()->greaterThanOrEqualTo($fin)) {
            return 'finalizado';
        }
        if (now()->greaterThanOrEqualTo($inicio)) {
            return 'realizando';
        }

        return $estado;
    }
}
