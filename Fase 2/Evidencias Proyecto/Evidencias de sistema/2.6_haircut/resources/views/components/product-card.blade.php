@props(['product'])
@php($imageUrl = app(\App\Services\MediaService::class)->url($product->imagen))
<article {{ $attributes->class(['product-card']) }}>
    <div class="product-photo">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $product->nombre }}">
        @else
            <span class="product-placeholder">Sin imagen</span>
        @endif
    </div>
    <h3>{{ $product->nombre }}</h3>
    <p class="product-desc">{{ $product->descripcion }}</p>
    <p class="product-price">{{ \App\Support\Format::precio($product->precio) }}</p>
</article>