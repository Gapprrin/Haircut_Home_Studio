<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['product']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['product']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php ($imageUrl = app(\App\Services\MediaService::class)->url($product->imagen)); ?>
<article <?php echo e($attributes->class(['product-card'])); ?>>
    <div class="product-photo">
        <?php if($imageUrl): ?>
            <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($product->nombre); ?>">
        <?php else: ?>
            <span class="product-placeholder">Sin imagen</span>
        <?php endif; ?>
    </div>
    <h3><?php echo e($product->nombre); ?></h3>
    <p class="product-desc"><?php echo e($product->descripcion); ?></p>
    <p class="product-price"><?php echo e(\App\Support\Format::precio($product->precio)); ?></p>
</article><?php /**PATH /app/resources/views/components/product-card.blade.php ENDPATH**/ ?>