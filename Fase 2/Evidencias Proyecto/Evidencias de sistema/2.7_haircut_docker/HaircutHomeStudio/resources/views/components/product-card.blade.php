@props(['product'])
@php($imageUrl = app(\App\Services\MediaService::class)->urlProducto($product->imagen, $product->categoria?->nombre, $product->producto))
<article {{ $attributes->class(['product-card']) }}>
    <div class="product-photo">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $product->producto }}">
        @else
            <span class="product-placeholder">Sin imagen</span>
        @endif
    </div>
    <h3>{{ $product->producto }}</h3>
    <p class="product-desc">{{ $product->descripcion }}</p>
    <p class="product-price">{{ \App\Support\Format::precio($product->precio) }}</p>
</article>