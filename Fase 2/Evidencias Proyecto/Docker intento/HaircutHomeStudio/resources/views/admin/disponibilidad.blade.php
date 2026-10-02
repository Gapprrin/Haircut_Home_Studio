@extends('layouts.app', ['title' => 'Haircut Studio - Disponibilidad', 'section' => 'admin', 'page' => 'disponibilidad'])

@php
    $currentKey = now()->format('Y-m');
    $previous = \Carbon\CarbonImmutable::create($anio, $mes, 1)->subMonth();
    $next = \Carbon\CarbonImmutable::create($anio, $mes, 1)->addMonth();
    $years = range(2026, $anioTecho);
@endphp

@section('content')
<section class="avail">
    <header class="avail-intro"><div><h2>Disponibilidad</h2><p class="avail-sub">Marca días libres, define el horario y elige qué meses siguientes pueden ver las clientas. El mes actual se marca solo.</p></div></header>
    <form method="post" action="{{ route('peluquero.disponibilidad.update') }}" class="avail-form">
        @csrf @method('PUT')
        <input type="hidden" name="anio" value="{{ $anio }}"><input type="hidden" name="mes" value="{{ $mes }}">
        <article class="avail-card">
            <h3>Días libres</h3>
            <p class="hint">Marca feriados, vacaciones u otros días sin atención. Los días naranjos quedan bloqueados para las reservas.</p>
            <div class="avail-monthbar">
                <a class="avail-nav" href="{{ route('peluquero.disponibilidad.edit', ['anio' => $previous->year, 'mes' => $previous->month]) }}" aria-label="Mes anterior">‹</a>
                <div class="avail-jump">
                    <select data-availability-month aria-label="Mes">@foreach(\App\Support\Format::MESES as $number => $name)<option value="{{ $number }}" @selected($number === $mes)>{{ $name }}</option>@endforeach</select>
                    <select data-availability-year aria-label="Año">@foreach(array_reverse($years) as $year)<option value="{{ $year }}" @selected($year === $anio)>{{ $year }}</option>@endforeach</select>
                    <span data-availability-url="{{ route('peluquero.disponibilidad.edit') }}"></span>
                </div>
                <a class="avail-nav" href="{{ route('peluquero.disponibilidad.edit', ['anio' => $next->year, 'mes' => $next->month]) }}" aria-label="Mes siguiente">›</a>
            </div>
            <div class="avail-cal-head" aria-hidden="true">@foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $day)<span>{{ $day }}</span>@endforeach</div>
            <div class="avail-cal" role="grid" aria-label="Días no disponibles">
                @foreach($semanas as $week)
                    @foreach($week as $day)
                        @if($day === null)<span class="avail-empty"></span>
                        @else
                            @php($closed = in_array(\Carbon\CarbonImmutable::create($anio, $mes, $day)->dayOfWeekIso, [1, 7], true))
                            <label class="avail-day {{ $closed ? 'is-cerrado' : '' }}">
                                <input type="checkbox" @if(!$closed) name="off[]" value="{{ $day }}" @endif @checked($closed || in_array($day, $offs, true)) @disabled($closed)>
                                <span>{{ $day }}</span>
                            </label>
                        @endif
                    @endforeach
                @endforeach
            </div>
            <p class="hint">Lunes y domingo quedan siempre ocupados. Solo se guardan los cambios del mes mostrado.</p>
        </article>

        <article class="avail-card">
            <h3>Horario de atención</h3>
            <div class="avail-times">
                <div class="field"><label for="h-ini">Desde</label><input type="time" id="h-ini" name="hora_inicio" value="{{ old('hora_inicio', substr($configuracion->hora_inicio, 0, 5)) }}" required></div>
                <div class="field"><label for="h-fin">Hasta</label><input type="time" id="h-fin" name="hora_fin" value="{{ old('hora_fin', substr($configuracion->hora_fin, 0, 5)) }}" required></div>
            </div>
            <p class="hint">De martes a viernes vale este horario. El sábado se atiende hasta las <strong>14:30</strong>.</p>
            <p class="avail-label">Días de atención</p>
            <div class="chip-grid">
                @foreach(\App\Support\Format::DIAS as $number => $name)
                    @php($closed = in_array($number, [1, 7], true))
                    <label class="chip {{ $closed ? 'is-cerrado' : '' }}"><input type="checkbox" @if(!$closed) name="dias[]" value="{{ $number }}" @endif @checked(!$closed && in_array($number, $diasAtencion, true)) @disabled($closed)><span>{{ $name }}</span></label>
                @endforeach
            </div>
        </article>

        <article class="avail-card">
            <h3>Meses visibles</h3>
            <p class="hint">El mes actual siempre está visible. Marca los meses futuros que quieras abrir para reservas.</p>
            <div class="vis-picker">
                <div class="vis-picker-head"><label class="year-menu-label" for="visible-year">Año</label><select id="visible-year" data-visible-year>@foreach(array_reverse($years) as $year)<option value="{{ $year }}" @selected($year === now()->year)>{{ $year }}</option>@endforeach</select></div>
                <div data-visible-panels>
                    @foreach($years as $year)
                        <div class="vis-picker-list" data-visible-panel="{{ $year }}" @hidden($year !== now()->year)>
                            @foreach(\App\Support\Format::MESES as $number => $name)
                                @php($key = sprintf('%04d-%02d', $year, $number))
                                @php($isCurrent = $key === $currentKey)
                                <label class="vis-month {{ $isCurrent ? 'is-now' : '' }}">
                                    @if($isCurrent)<input type="hidden" name="meses_visibles[]" value="{{ $key }}">@endif
                                    <input type="checkbox" name="{{ $isCurrent ? '' : 'meses_visibles[]' }}" value="{{ $key }}" @checked($isCurrent || isset($visibles[$key])) @disabled($isCurrent)>
                                    <span class="vis-check" aria-hidden="true"></span><span class="vis-name">{{ $name }}{{ $isCurrent ? ' · actual' : '' }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
        <div class="avail-save"><button type="submit" class="btn btn-primary">Guardar</button></div>
    </form>
</section>
@endsection