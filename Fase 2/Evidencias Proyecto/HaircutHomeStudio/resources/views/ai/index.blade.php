@extends('layouts.app', ['title' => 'Simulador de estilo - Haircut Home Studio', 'page' => 'ai'])

@php
    $statusLabels = [
        'pending' => 'En espera',
        'processing' => 'Generando',
        'completed' => 'Lista',
        'failed' => 'No se pudo generar',
        'purged' => 'Imágenes eliminadas',
        'expired' => 'Expirada',
    ];
    $failureMessages = [
        'invalid_api_key' => 'La conexión con Gemini no está configurada correctamente.',
        'provider_access_denied' => 'El proyecto de Gemini no tiene acceso al modelo de imágenes. El administrador debe revisar la facturación y las restricciones de la clave.',
        'provider_quota' => 'Gemini alcanzó temporalmente su cuota. Intenta más tarde.',
        'connection_error' => 'No fue posible conectar con Gemini. Intenta más tarde.',
    ];
    $generationLabel = fn ($generation) => $generation->servicio?->nombre
        ?? ($presets[$generation->preset]['label'] ?? 'Simulación');
    $media = app(\App\Services\MediaService::class);
    $previousServiceId = (int) old('servicio_id', $selected?->servicio_id ?? 0);
    $activeServiceCategory = $serviceCategories
        ->first(fn ($category) => $category->servicios->contains('id', $previousServiceId))?->slug
        ?? $serviceCategories->first()?->slug;
@endphp

@section('content')
<section class="ai-studio">
    <header class="ai-heading">
        <div>
            <p class="home-kicker">Consulta visual</p>
            <h1>Simulador de estilo</h1>
            <p>Prueba un cambio de cabello antes de reservar. El resultado es una referencia visual y puede variar según tu cabello.</p>
        </div>
        <div class="ai-quota" aria-label="Cuota disponible">
            <strong>{{ $quota['unlimited'] ? '∞' : $quota['remaining_30d'] }}</strong>
            <span>{{ $quota['unlimited'] ? 'cuenta de prueba' : 'disponibles' }}<br>{{ $quota['unlimited'] ? 'sin límite' : 'en 30 días' }}</span>
        </div>
    </header>

    @if($demoMode)
        <div class="ai-notice is-demo"><strong>Modo demostración.</strong> El flujo funciona sin consumir API, pero el resultado repite la foto original hasta configurar Gemini.</div>
    @endif
    @unless($available)
        <div class="ai-notice"><strong>Simulador temporalmente deshabilitado.</strong> Falta activar el proveedor en la configuración del servidor.</div>
    @endunless

    @if($selected)
        <section class="ai-result" @if(in_array($selected->status, ['pending', 'processing'], true)) data-ai-status-url="{{ route('ai.status', $selected) }}" @endif>
            <div class="ai-section-heading">
                <div><p class="home-kicker">Resultado</p><h2>{{ $generationLabel($selected) }}</h2></div>
                <span class="ai-status is-{{ $selected->status }}">{{ $statusLabels[$selected->status] ?? $selected->status }}</span>
            </div>

            @if($selected->estaLista() && $selected->expires_at->isFuture())
                <div class="ai-compare">
                    <figure><img src="{{ route('ai.image', [$selected, 'input']) }}" alt="Fotografía original"><figcaption>Original</figcaption></figure>
                    <figure><img src="{{ route('ai.image', [$selected, 'output']) }}" alt="Simulación de {{ $generationLabel($selected) }}"><figcaption>Simulación IA</figcaption></figure>
                </div>
                <p class="ai-disclaimer">La simulación no garantiza un resultado idéntico. El profesional evaluará color base, textura y condición del cabello.</p>
                <div class="actions ai-result-actions">
                    @if(!$selected->reserva_id && auth()->user()->rol === 'cliente')
                        <a href="{{ route('reservas.create', array_filter([
                            'ai_generation' => $selected->id,
                            'cat' => $selected->servicio?->categoria?->slug,
                            'serv' => $selected->servicio_id,
                        ])) }}" class="btn btn-primary">Usar al reservar</a>
                    @elseif($selected->reserva_id)
                        <span class="ai-linked">Vinculada a tu reserva</span>
                    @else
                        <span class="ai-linked">Resultado de prueba administrativa</span>
                    @endif
                    <form method="post" action="{{ route('ai.media.destroy', $selected) }}" onsubmit="return confirm('¿Eliminar ahora la foto original y la simulación?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary">Eliminar imágenes</button>
                    </form>
                </div>
                <p class="hint">Eliminación automática: {{ $selected->expires_at->format('d/m/Y H:i') }}.</p>
            @elseif(in_array($selected->status, ['pending', 'processing'], true))
                <div class="ai-processing"><span class="ai-processing-mark" aria-hidden="true"></span><div><strong>Preparando tu simulación</strong><p>Esta página se actualizará cuando termine.</p></div></div>
            @elseif($selected->status === 'failed')
                <div class="ai-notice"><strong>No se pudo completar.</strong> {{ $failureMessages[$selected->error_code] ?? 'Gemini no devolvió una simulación válida. Puedes intentar nuevamente más tarde.' }} La solicitud consumió un cupo para proteger el presupuesto.</div>
            @else
                <div class="ai-notice">Las imágenes de esta simulación ya no están disponibles.</div>
            @endif
        </section>
    @endif

    <form method="post" action="{{ route('ai.store') }}" enctype="multipart/form-data" class="ai-form" data-ai-form>
        @csrf
        <section class="ai-photo-panel">
            <div class="ai-section-heading"><div><p class="home-kicker">Fotografía</p><h2>Tu foto</h2></div><span>JPG o PNG · máx. 6 MB</span></div>
            <input type="file" id="ai-photo" name="photo" accept="image/jpeg,image/png" class="ai-photo-input" required @disabled(!$available || !$quota['allowed'])>
            <label for="ai-photo" class="ai-photo-drop" data-ai-photo-drop>
                <span class="ai-upload-icon" aria-hidden="true">＋</span>
                <strong>Seleccionar fotografía</strong>
                <span>Rostro visible, buena luz y cabello completo</span>
            </label>
            <div class="ai-photo-preview" data-ai-photo-preview hidden><img alt="Vista previa de la fotografía"></div>
            <label for="ai-photo" class="ai-photo-change" data-ai-photo-change hidden>Cambiar fotografía</label>
            <p class="ai-photo-error" data-ai-photo-error hidden></p>
        </section>

        <section class="ai-presets-panel">
            <div class="ai-section-heading"><div><p class="home-kicker">Servicio</p><h2>¿Qué quieres probar?</h2></div><span>{{ $serviceCount }} opciones</span></div>
            <div class="ai-category-tabs" role="tablist" aria-label="Categorías de servicio">
                @foreach($serviceCategories as $category)
                    @php($isActiveCategory = $category->slug === $activeServiceCategory)
                    <button type="button"
                        id="ai-category-tab-{{ $category->slug }}"
                        class="ai-category-tab {{ $isActiveCategory ? 'is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $isActiveCategory ? 'true' : 'false' }}"
                        aria-controls="ai-category-panel-{{ $category->slug }}"
                        data-ai-category-tab="{{ $category->slug }}">
                        <img src="{{ $media->url($category->imagen) ?? asset('img/iconos/'.($category->slug === 'color' ? 'color.png' : 'cat-corte.png')) }}" alt="">
                        <span><strong>{{ $category->nombre }}</strong><small>{{ $category->servicios->count() }} opciones</small></span>
                    </button>
                @endforeach
            </div>
            <div class="ai-service-groups">
                @foreach($serviceCategories as $category)
                    @php($isActiveCategory = $category->slug === $activeServiceCategory)
                    <section class="ai-service-group"
                        id="ai-category-panel-{{ $category->slug }}"
                        role="tabpanel"
                        aria-labelledby="ai-category-tab-{{ $category->slug }}"
                        data-ai-category-panel="{{ $category->slug }}"
                        @if(!$isActiveCategory) hidden @endif>
                        <div class="ai-service-panel-summary">
                            <p>{{ $category->slug === 'color' ? 'Coloración, luces y retoques disponibles.' : 'Cortes y terminaciones disponibles.' }}</p>
                            <span>Selecciona una opción</span>
                        </div>
                        <div class="ai-preset-grid">
                            @foreach($category->servicios as $service)
                                <label class="ai-preset" data-service="{{ $service->id }}">
                                    <input type="radio" name="servicio_id" value="{{ $service->id }}" @checked($previousServiceId === $service->id) @required($isActiveCategory) @disabled(!$available || !$quota['allowed'])>
                                    <img class="ai-service-icon" src="{{ $media->url($service->imagen) ?? asset('img/iconos/'.($category->slug === 'color' ? 'color.png' : 'corte.png')) }}" alt="">
                                    <span><strong>{{ $service->nombre }}</strong><small>{{ $service->descripcion ?: 'Vista previa orientativa del servicio.' }}</small></span>
                                    <em>{{ $category->nombre }}</em>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </section>

        <div class="ai-consent-row">
            <label><input type="checkbox" name="consent" value="1" required @disabled(!$available || !$quota['allowed'])> Autorizo el procesamiento temporal de esta fotografía para crear la simulación.</label>
            <p>La foto se guarda de forma privada, sin metadatos de ubicación, y se elimina automáticamente.</p>
        </div>

        <div class="ai-submit-row">
            <div>
                @if($quota['unlimited'])
                    <strong>Cuenta de prueba sin límite de imágenes</strong>
                @else
                    <strong>{{ $quota['remaining_24h'] }} disponibles en 24 h</strong><span> · {{ $quota['remaining_30d'] }} en 30 días</span>
                @endif
                @if(!$quota['allowed'])<p>{{ $quota['reason'] }}</p>@endif
            </div>
            <button type="submit" class="btn btn-primary" @disabled(!$available || !$quota['allowed'])>Generar simulación</button>
        </div>
    </form>

    @if($generations->isNotEmpty())
        <section class="ai-history">
            <div class="ai-section-heading"><div><p class="home-kicker">Historial</p><h2>Simulaciones recientes</h2></div></div>
            <div class="ai-history-grid">
                @foreach($generations as $generation)
                    <a href="{{ route('ai.show', $generation) }}" class="ai-history-item {{ $selected?->id === $generation->id ? 'is-current' : '' }}">
                        @if($generation->estaLista() && $generation->expires_at->isFuture())<img src="{{ route('ai.image', [$generation, 'output']) }}" alt="">@else<span class="ai-history-placeholder">✦</span>@endif
                        <span><strong>{{ $generationLabel($generation) }}</strong><small>{{ $statusLabels[$generation->status] ?? $generation->status }} · {{ $generation->created_at->format('d/m H:i') }}</small></span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</section>
@endsection