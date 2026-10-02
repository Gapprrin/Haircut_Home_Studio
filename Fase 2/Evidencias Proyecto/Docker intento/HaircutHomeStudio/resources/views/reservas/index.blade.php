@extends('layouts.app', ['title' => 'Haircut Home Studio - Mis reservas', 'page' => 'mis'])

@section('content')
<section class="card">
    <h2>Mis Reservas</h2>
    @if($reservas->isEmpty())
        <p>Aún no tienes reservas. <a href="{{ route('reservas.create') }}">Crea la primera</a>.</p>
    @else
        <table class="stack-table">
            <thead><tr><th>Fecha / Hora</th><th>Servicio</th><th>Lugar</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach($reservas as $reserva)
                <tr>
                    <td data-label="Fecha / Hora">{{ $reserva->fecha->format('d/m/Y') }} · {{ substr($reserva->hora, 0, 5) }}</td>
                    <td data-label="Servicio">{{ $reserva->servicio->nombre }}</td>
                    <td data-label="Lugar">{{ \App\Support\Format::lugar($reserva->lugar) }}</td>
                    <td data-label="Estado"><span class="status {{ \App\Support\Format::estadoClase($reserva->estado) }}">{{ \App\Support\Format::estado($reserva->estado) }}</span></td>
                    <td data-label="Acción">
                        @if($reserva->puedeCancelar())
                            <form method="post" action="{{ route('reservas.cancel', $reserva) }}" onsubmit="return confirm('¿Cancelar esta reserva?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-secondary">Cancelar</button>
                            </form>
                        @elseif(in_array($reserva->estado, ['pendiente', 'confirmada'], true))
                            <span class="hint">Ya no se puede cancelar (faltan menos de 10 min)</span>
                        @else
                            <span class="hint">(sin acciones)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="hint">El pago es presencial. Puedes cancelar hasta 10 minutos antes de la hora.</p>
    @endif
</section>
@endsection