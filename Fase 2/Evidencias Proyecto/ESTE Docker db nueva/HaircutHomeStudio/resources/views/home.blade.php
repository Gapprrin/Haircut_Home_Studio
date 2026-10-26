@extends('layouts.app', ['title' => 'Haircut Home Studio - Melipilla', 'page' => 'inicio', 'layout' => 'home'])

@section('content')
<div class="home-page-wrap">
    <section class="home-intro">
        <h1 class="home-intro-title">Belleza, cosmética y cuidado personal</h1>
        <p class="home-intro-lead">En Haircut Home Studio hacemos <strong>cortes, lavado y brushing, color (canas, visos, mechas, balayage, baby lights y fantasía), masajes, botox, liso y peinados</strong>. ¿Qué esperas para tus consultas?</p>
        <div class="home-intro-actions">
            <a href="{{ route('reservas.create') }}" class="btn btn-primary">Reservar hora</a>
            <a href="{{ route('productos.index') }}" class="btn btn-secondary">Ver productos</a>
        </div>
    </section>

    <section class="home-gallery" aria-label="Trabajos del estudio">
        <div class="home-mosaic">
            @foreach($collage as $index => $foto)
                <figure class="home-mosaic-item home-mosaic-item-{{ $index + 1 }}">
                    <button type="button" class="home-mosaic-trigger" data-gallery-open data-gallery-index="{{ $index }}" data-no-loader aria-label="Ver {{ $foto['alt'] }} en grande">
                        <img src="{{ asset($foto['src']) }}" alt="{{ $foto['alt'] }}" loading="lazy">
                    </button>
                </figure>
            @endforeach
        </div>
    </section>

    <div class="gallery-modal" id="gallery-modal" hidden data-no-loader>
        <div class="gallery-modal-backdrop" data-gallery-close></div>
        <div class="gallery-modal-dialog" role="dialog" aria-modal="true" aria-label="Vista ampliada de la galería">
            <button type="button" class="gallery-modal-close" data-gallery-close aria-label="Cerrar">×</button>
            <button type="button" class="gallery-modal-nav is-prev" data-gallery-prev aria-label="Imagen anterior">‹</button>
            <figure class="gallery-modal-figure">
                <img src="" alt="" id="gallery-modal-image">
                <figcaption id="gallery-modal-caption"></figcaption>
            </figure>
            <button type="button" class="gallery-modal-nav is-next" data-gallery-next aria-label="Imagen siguiente">›</button>
        </div>
    </div>

    <section class="home-products-section" id="productos">
        <div class="home-section-head">
            <div>
                <p class="home-kicker">Vitrina del estudio</p>
                <h2>Productos destacados</h2>
                <p class="home-presencial-note">Los productos se adquieren <strong>solo de forma presencial</strong> en el estudio.</p>
            </div>
            <a href="{{ route('productos.index') }}" class="btn btn-secondary home-products-link">Ver todos los productos</a>
        </div>
        @if($PRODUCTO->isNotEmpty())
            <div class="home-products-grid">
                @foreach($PRODUCTO as $producto)<x-product-card :product="$producto" />@endforeach
            </div>
        @else
            <p class="home-empty">Pronto publicaremos productos en la vitrina.</p>
        @endif
        <div class="home-products-foot"><a href="{{ route('productos.index') }}" class="btn btn-primary">Ver más productos</a></div>
    </section>

    <section class="home-reviews" id="reseñas">
        <div class="home-reviews-head">
            <div class="home-reviews-intro">
                <p class="home-kicker">Experiencias de nuestras clientas</p>
                <h2>¿Por qué nos eligen?</h2>
                <p>Nos esforzamos por crear un espacio libre de estrés, con atención cercana y resultados que te hagan sentir bien contigo misma.</p>
            </div>
            <div class="home-reviews-badge" aria-label="Valoración 4,6 de 5 en Google con 25 reseñas">
                <span class="home-reviews-g">G</span>
                <div><p class="home-reviews-score"><strong>4,6</strong> <span class="home-stars" aria-hidden="true">★★★★<span class="star-half">★</span></span></p><p class="home-reviews-count">25 reseñas en Google</p></div>
            </div>
        </div>
        <div class="carousel carousel-auto carousel-reviews" data-autoplay="6000" data-per-view="3">
            <button type="button" class="carousel-btn carousel-btn-prev" aria-label="Reseñas anteriores">‹</button>
            <div class="carousel-viewport"><div class="carousel-track">
                @foreach($resenas as $resena)
                    <article class="review-card carousel-slide">
                        <div class="review-card-top"><span class="home-stars" aria-hidden="true">{{ str_repeat('★', $resena['estrellas']) }}</span><span class="review-source"><span class="review-dot"></span> Google</span></div>
                        <p class="review-quote">“{{ $resena['texto'] }}”</p>
                        <div class="review-author"><span class="review-avatar">{{ $resena['inicial'] }}</span><div><p class="review-name">{{ $resena['nombre'] }}</p><p class="review-time">{{ $resena['tiempo'] }}</p></div></div>
                    </article>
                @endforeach
            </div></div>
            <button type="button" class="carousel-btn carousel-btn-next" aria-label="Siguientes reseñas">›</button>
            <div class="carousel-dots" role="tablist" aria-label="Reseñas"></div>
        </div>
        <div class="home-reviews-actions">
            <a href="https://www.google.com/maps/place/HairCut+Home+Studio/@-33.6828171,-71.1894721,17z" class="btn btn-outline" target="_blank" rel="noopener">Ver todas en Google Maps</a>
            <a href="{{ route('reservas.create') }}" class="btn btn-primary">Reservar hora</a>
        </div>
    </section>
</div>
@endsection