@extends('layouts.app', ['title' => 'Reserva aquí', 'page' => 'nueva'])

@php
    $media = app(\App\Services\MediaService::class);
    $baseQuery = [
        'cat' => $categoria->slug,
        'serv' => $servicio->id,
        'lugar' => $lugar,
        'fecha' => $fecha,
        'hora' => $hora,
        'anio' => $anio,
        'mes' => $mes,
    ];
    $bookingUrl = function (array $changes = []) use ($baseQuery) {
        $query = array_filter(array_merge($baseQuery, $changes), fn ($value) => $value !== '' && $value !== null);
        return route('reservas.create', $query);
    };
    $monthPosition = collect($mesesVisibles)->search(fn ($item) => (int) $item['anio'] === $anio && (int) $item['mes'] === $mes);
    $previousMonth = $monthPosition !== false ? ($mesesVisibles[$monthPosition - 1] ?? null) : null;
    $nextMonth = $monthPosition !== false ? ($mesesVisibles[$monthPosition + 1] ?? null) : null;
    $slotSet = array_flip($slots);
    $durationHours = max(1, (int) ceil($servicio->duracion_min / 60));
    $range = [];
    if ($hora !== '') {
        $start = \Carbon\CarbonImmutable::createFromFormat('H:i', $hora);
        for ($i = 0; $i < $durationHours; $i++) $range[$start->addHours($i)->format('H:i')] = true;
    }
    $hourLabel = fn (string $value) => \Carbon\CarbonImmutable::createFromFormat('H:i', $value)->format('H:i');
    $longService = $durationHours >= 3;
    $hourGroups = $longService
        ? ['Mañana' => array_values(array_filter($todosSlots, fn ($value) => $value < '13:00')), 'Tarde' => array_values(array_filter($todosSlots, fn ($value) => $value >= '13:00'))]
        : ['' => $todosSlots];
    $endHour = $hora !== '' ? \Carbon\CarbonImmutable::createFromFormat('H:i', $hora)->addHours($durationHours)->format('H:i') : '';
    $fallbackIcons = ['corte' => 'corte.png', 'color' => 'color.png', 'tratamientos' => 'tratamiento.png'];
@endphp

@section('content')
<section class="book">
    <div class="book-head"><h2>Reserva aquí</h2></div>

    <div class="book-block">
        <h3 class="book-section-title">Seleccionar servicio</h3>
        <div class="book-icon-grid">
            @foreach($categorias as $item)
                @php
                    $selected = $item->id === $categoria->id;
                    $icon = $media->url($item->imagen) ?? asset('img/iconos/'.($fallbackIcons[$item->slug] ?? 'tratamiento.png'));
                @endphp
                @if($selected)
                    <span class="book-svc is-on"><span class="book-svc-ico"><img class="icon-pack" src="{{ $icon }}" alt=""></span><span class="book-svc-name">{{ $item->nombre }}</span></span>
                @else
                    <a class="book-svc" href="{{ $bookingUrl(['cat' => $item->slug, 'serv' => $item->servicios->first()->id, 'hora' => '']) }}" data-no-loader>
                        <span class="book-svc-ico"><img class="icon-pack" src="{{ $icon }}" alt=""></span><span class="book-svc-name">{{ $item->nombre }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <div class="book-block">
        <h3 class="book-section-title">Tipo de servicio</h3>
        <div class="book-icon-grid">
            @foreach($categoria->servicios as $item)
                @php
                    $selected = $item->id === $servicio->id;
                    $icon = $media->url($item->imagen) ?? asset('img/iconos/'.($fallbackIcons[$categoria->slug] ?? 'tratamiento.png'));
                @endphp
                @if($selected)
                    <span class="book-svc is-on"><span class="book-svc-ico"><img class="icon-pack" src="{{ $icon }}" alt=""></span><span class="book-svc-name">{{ $item->nombre }}</span></span>
                @else
                    <a class="book-svc" href="{{ $bookingUrl(['serv' => $item->id, 'hora' => '']) }}" data-no-loader>
                        <span class="book-svc-ico"><img class="icon-pack" src="{{ $icon }}" alt=""></span><span class="book-svc-name">{{ $item->nombre }}</span>
                    </a>
                @endif
            @endforeach
        </div>
        @if($servicio->descripcion)<p class="book-duration"><strong>{{ $servicio->nombre }}:</strong> {{ $servicio->descripcion }}</p>@endif
    </div>

    <div class="book-block">
        <h3 class="book-section-title">Seleccionar fecha</h3>
        <div class="book-month">
            @if($previousMonth)
                <a class="book-nav" href="{{ $bookingUrl(['anio' => $previousMonth['anio'], 'mes' => $previousMonth['mes'], 'fecha' => '', 'hora' => '']) }}" aria-label="Mes anterior">‹</a>
            @else
                <span class="book-nav is-off" aria-hidden="true"></span>
            @endif
            <div class="book-month-pill">{{ \App\Support\Format::MESES[$mes] }} {{ $anio }}</div>
            @if($nextMonth)
                <a class="book-nav" href="{{ $bookingUrl(['anio' => $nextMonth['anio'], 'mes' => $nextMonth['mes'], 'fecha' => '', 'hora' => '']) }}" aria-label="Mes siguiente">›</a>
            @else
                <span class="book-nav is-off" aria-hidden="true"></span>
            @endif
        </div>
        <table class="book-cal">
            <tr><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th></tr>
            @foreach($semanas as $semana)
                <tr>
                @foreach($semana as $day)
                    <td>
                    @if($day)
                        @php
                            $dayDate = sprintf('%04d-%02d-%02d', $anio, $mes, $day);
                            $state = $estados[$day];
                            $selected = $dayDate === $fecha;
                            $class = 'd st-'.$state.($selected ? ' is-sel' : '');
                        @endphp
                        @if(in_array($state, ['disponible', 'ocupado'], true) && !$selected)
                            <a class="{{ $class }}" href="{{ $bookingUrl(['fecha' => $dayDate, 'hora' => '', 'anio' => $anio, 'mes' => $mes]) }}">{{ $day }}</a>
                        @else
                            <span class="{{ $class }}">{{ $day }}</span>
                        @endif
                    @endif
                    </td>
                @endforeach
                </tr>
            @endforeach
        </table>
        <ul class="book-legend"><li><span class="book-dot is-free"></span> Disponible</li><li><span class="book-dot is-busy"></span> No disponible</li></ul>
    </div>

    @if($fecha !== '')
        <div class="book-block">
            <h3 class="book-section-title">Seleccionar hora</h3>
            <p class="hint">{{ $longService ? 'En colores largos se recomienda la mañana. ' : '' }}El bloque se reserva desde la hora que elijas hacia adelante.</p>
            @if($sugerencias['misma'] || $sugerencias['otra'])
                <p class="book-suggest">Hora más cercana:
                    @if($sugerencias['misma'])
                        <a href="{{ $bookingUrl(['hora' => $sugerencias['misma']]) }}" data-no-loader>{{ $hourLabel($sugerencias['misma']) }}</a>
                    @else<span>no hay en este día</span>@endif
                    @if($sugerencias['otra'])
                        · o <a href="{{ $bookingUrl(['fecha' => $sugerencias['otra']['fecha'], 'hora' => $sugerencias['otra']['hora'], 'anio' => substr($sugerencias['otra']['fecha'], 0, 4), 'mes' => (int) substr($sugerencias['otra']['fecha'], 5, 2)]) }}" data-no-loader>{{ \Carbon\CarbonImmutable::parse($sugerencias['otra']['fecha'])->format('d/m') }} · {{ $hourLabel($sugerencias['otra']['hora']) }}</a>
                    @endif
                </p>
            @endif
            <div class="book-hours">
                @forelse($hourGroups as $group => $hours)
                    @if($group !== '')<p class="book-hours-kicker">{{ $group === 'Mañana' ? 'Mañana · recomendada' : $group }}</p>@endif
                    @foreach($hours as $slot)
                        @php
                            $free = isset($slotSet[$slot]);
                            $selectedStart = $slot === $hora;
                            $class = 'hour-pill';
                            if ($selectedStart) $class .= ' is-on';
                            elseif (isset($range[$slot])) $class .= ' is-range';
                            elseif ($free) $class .= ' is-free'.($longService && $slot < '13:00' ? ' is-am' : '').(($sugerencias['misma'] ?? '') === $slot ? ' is-near' : '');
                            else $class .= ' is-busy';
                        @endphp
                        @if($free && !$selectedStart)<a class="{{ $class }}" href="{{ $bookingUrl(['hora' => $slot]) }}">{{ $hourLabel($slot) }}</a>@else<span class="{{ $class }}">{{ $hourLabel($slot) }}</span>@endif
                    @endforeach
                @empty
                    <p class="hint">No hay horario configurado para este servicio.</p>
                @endforelse
            </div>
            <ul class="book-legend"><li><span class="book-dot is-free"></span> Disponible</li><li><span class="book-dot is-range"></span> Tu bloque</li><li><span class="book-dot is-busy"></span> No disponible</li></ul>
        </div>
    @endif

    <form method="post" action="{{ route('reservas.store') }}" class="book-reserve" enctype="multipart/form-data">
        @csrf
        <div class="book-card">
            <div class="book-photo" id="book-photo">
                <input class="book-photo-input" type="file" id="book-foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
                <label class="book-photo-drop" for="book-foto" id="book-photo-drop">
                    <span class="book-photo-ico" aria-hidden="true">＋</span><span class="book-photo-title">Agregar imagen</span><span class="book-photo-sub">Arrástrala aquí o tócala para elegir · JPG, PNG o WEBP</span>
                </label>
                <div class="book-photo-preview" id="book-photo-preview" hidden><img alt="Vista previa de la imagen"><button type="button" class="book-photo-clear" id="book-photo-clear" aria-label="Quitar imagen">&times;</button></div>
                <p class="book-photo-msg" id="book-photo-msg" hidden></p>
            </div>
        </div>
        <div class="book-block">
            <h3 class="book-section-title">Lugar de atención</h3>
            <div class="book-icon-grid book-lugar-grid">
                @foreach(['salon' => ['En el salón', 'lugar-salon.png'], 'domicilio' => ['A domicilio', 'lugar-domicilio.png']] as $key => [$label, $icon])
                    @if($lugar === $key)
                        <span class="book-svc book-lugar is-on"><span class="book-svc-ico"><img class="icon-pack" src="{{ asset('img/iconos/'.$icon) }}" alt=""></span><span class="book-svc-name">{{ $label }}</span></span>
                    @else
                        <a class="book-svc book-lugar" href="{{ $bookingUrl(['lugar' => $key]) }}" data-no-loader><span class="book-svc-ico"><img class="icon-pack" src="{{ asset('img/iconos/'.$icon) }}" alt=""></span><span class="book-svc-name">{{ $label }}</span></a>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="book-foot">
            <input type="hidden" name="servicio_id" value="{{ $servicio->id }}">
            <input type="hidden" name="fecha" value="{{ $fecha }}">
            <input type="hidden" name="hora" value="{{ $hora }}">
            <input type="hidden" name="lugar" value="{{ $lugar }}">
            <p class="book-foot-summary"><span class="book-foot-kicker">Resumen:</span> 1 servicio · {{ \App\Support\Format::lugar($lugar) }} · {{ \App\Support\Format::duracion($servicio->duracion_min) }}@if($hora) · {{ $hourLabel($hora) }} – {{ $hourLabel($endHour) }}@endif <strong class="book-foot-price">{{ \App\Support\Format::precio($servicio->precio) }}</strong></p>
            <div class="book-foot-actions"><button type="submit" class="btn book-btn-now" @disabled($fecha === '' || $hora === '')>Reservar</button><a href="{{ route('home') }}" class="btn book-btn-cancel" data-no-loader>Cancelar</a></div>
        </div>
    </form>
</section>
@endsection