<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservationWorkflowService;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReservationWorkflowService $workflow) {}

    public function index(): View
    {
        $this->workflow->actualizarRealizadas();
        $inicioMes = now()->startOfMonth();
        $inicioGrafico = $inicioMes->copy()->subMonths(5);

        $reservasMes = Reserva::query()
            ->with('servicio')
            ->whereBetween('fecha', [$inicioMes->toDateString(), $inicioMes->copy()->endOfMonth()->toDateString()])
            ->get();
        $confirmadas = $reservasMes->whereIn('estado', ['confirmada', 'realizada']);

        $meses = collect(range(0, 5))->mapWithKeys(function (int $indice) use ($inicioGrafico): array {
            $fecha = CarbonImmutable::instance($inicioGrafico)->addMonths($indice);

            return [$fecha->format('Y-n') => [
                'label' => Format::MESES[$fecha->month],
                'anio' => $fecha->year,
                'total' => 0,
                'ok' => 0,
            ]];
        })->all();

        $ultimas = Reserva::query()->with('servicio')->whereDate('fecha', '>=', $inicioGrafico->toDateString())->get();
        foreach ($ultimas as $reserva) {
            $key = $reserva->fecha->format('Y-n');
            if (! isset($meses[$key])) {
                continue;
            }
            $meses[$key]['total']++;
            if (in_array($reserva->estado, ['confirmada', 'realizada'], true)) {
                $meses[$key]['ok']++;
            }
        }

        $topServicios = $ultimas
            ->whereIn('estado', ['confirmada', 'realizada'])
            ->groupBy('servicio_id')
            ->map(fn ($grupo) => ['nombre' => $grupo->first()->servicio->nombre, 'n' => $grupo->count()])
            ->sortByDesc('n')
            ->take(5)
            ->values();

        return view('admin.dashboard', [
            'pendientes' => Reserva::query()->where('estado', 'pendiente')->count(),
            'hoy' => Reserva::query()->whereDate('fecha', now()->toDateString())->whereIn('estado', ['pendiente', 'confirmada', 'realizada'])->count(),
            'confirmadasMes' => $confirmadas->count(),
            'ingresosMes' => $confirmadas->sum(fn (Reserva $reserva) => (float) $reserva->servicio->precio),
            'meses' => $meses,
            'maxMes' => max(1, ...array_column($meses, 'total')),
            'topServicios' => $topServicios,
            'maxTop' => max(1, (int) $topServicios->max('n')),
        ]);
    }
}
