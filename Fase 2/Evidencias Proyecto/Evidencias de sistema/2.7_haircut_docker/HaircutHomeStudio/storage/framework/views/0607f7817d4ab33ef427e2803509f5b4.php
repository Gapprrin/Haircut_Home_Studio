<?php $__env->startSection('content'); ?>
<section class="productos-page">
    <div class="productos-head">
        <p class="home-kicker">Catálogo completo</p>
        <h1>Productos del estudio</h1>
        <p class="productos-lead">Conoce los productos que tenemos disponibles en Haircut Home Studio. Puedes verlos y consultarlos con nosotras durante tu visita.</p>
        <div class="productos-alert" role="note"><strong>Compra presencial.</strong> Los productos solo se pueden adquirir de forma presencial en el estudio; no realizamos ventas online.</div>
        <div class="productos-foot actions"><a href="<?php echo e(route('home')); ?>" class="btn btn-secondary">Volver al inicio</a><a href="<?php echo e(route('reservas.create')); ?>" class="btn btn-primary">Reservar hora</a></div>
    </div>
    <?php if($productos->isNotEmpty()): ?>
        <div class="productos-grid"><?php $__currentLoopData = $productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card','data' => ['product' => $producto,'class' => 'product-card-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto),'class' => 'product-card-full']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $attributes = $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $component = $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
    <?php else: ?>
        <p class="home-empty">Aún no hay productos publicados en el catálogo.</p>
    <?php endif; ?>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Productos — Haircut Home Studio', 'page' => 'productos'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/resources/views/productos/index.blade.php ENDPATH**/ ?>