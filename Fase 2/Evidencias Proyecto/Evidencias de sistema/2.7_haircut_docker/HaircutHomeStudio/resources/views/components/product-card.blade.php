@props(['product'])
@php($imageUrl = app(\App\Services\MediaService::class)->urlProducto($product->imagen, $product->categoria?->nombre, $product->producto))
<article {{ $attributes->class(['product-card']) }}>
    <div class="product-photo">
        @if($imageUrl)
            <button
                type="button"
                class="product-photo-trigger"
                style="display:block;width:100%;height:100%;padding:0;border:0;background:transparent;cursor:zoom-in"
                data-gallery-open
                data-gallery-src="{{ $imageUrl }}"
                data-gallery-alt="{{ $product->producto }}"
                data-no-loader
                aria-label="Ver {{ $product->producto }} en grande"
            >
                <img src="{{ $imageUrl }}" alt="{{ $product->producto }}" loading="lazy">
            </button>
        @else
            <span class="product-placeholder">Sin imagen</span>
        @endif
    </div>
    <h3>{{ $product->producto }}</h3>
    <p class="product-desc">{{ $product->descripcion }}</p>
    <p class="product-price">{{ \App\Support\Format::precio($product->precio) }}</p>
</article>
