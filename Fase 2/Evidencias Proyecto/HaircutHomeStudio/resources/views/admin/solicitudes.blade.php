@extends('layouts.app', ['title' => 'Haircut Studio - Solicitudes', 'section' => 'admin', 'page' => 'solicitudes'])

@section('content')
<section class="card">
    <h2>Solicitudes Pendientes</h2>
    @if($solicitudes->isEmpty())
        <p>No hay solicitudes pendientes.</p>
    @else
        <table class="stack-table">
            <thead><tr><th>Cliente</th><th>Servicio</th><th>Lugar</th><th>Fecha / Hora</th><th>Foto</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach($solicitudes as $solicitud)
                <tr>
                    <td data-label="Cliente">{{ $solicitud->usuario->nombre }}</td>
                    <td data-label="Servicio">{{ $solicitud->servicio->nombre }}</td>
                    <td data-label="Lugar">{{ \App\Support\Format::lugar($solicitud->lugar) }}</td>
                    <td data-label="Fecha / Hora">{{ $solicitud->fecha->format('d/m') }} · {{ substr($solicitud->hora, 0, 5) }}</td>
                    <td data-label="Foto">
                        @if($solicitud->foto)<a href="{{ app(\App\Services\MediaService::class)->url($solicitud->foto) }}" target="_blank">Referencia</a>@endif
                        @php($simulation = $solicitud->generacionesIa->first())
                        @if($simulation)<a href="{{ route('ai.image', [$simulation, 'output']) }}" target="_blank">Simulación IA</a>@endif
                        @if(!$solicitud->foto && !$simulation)<span class="hint">(sin foto)</span>@endif
                    </td>
                    <td data-label="Acción">
                        <div class="actions">
                            <form method="post" action="{{ route('peluquero.solicitudes.update', $solicitud) }}">@csrf @method('PATCH')<input type="hidden" name="accion" value="confirmar"><button type="submit" class="btn-primary">Confirmar</button></form>
                            <form method="post" action="{{ route('peluquero.solicitudes.update', $solicitud) }}">@csrf @method('PATCH')<input type="hidden" name="accion" value="rechazar"><button type="submit" class="btn-secondary">Rechazar</button></form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="hint">Al confirmar, la hora queda reservada de forma definitiva. Al rechazar, el cupo se libera de inmediato.</p>
    @endif
</section>
@endsection