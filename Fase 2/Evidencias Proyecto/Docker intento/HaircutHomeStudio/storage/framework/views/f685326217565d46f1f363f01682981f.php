<?php $__env->startSection('content'); ?>
<div class="home-page-wrap">
    <section class="home-intro">
        <h1 class="home-intro-title">Belleza, cosmética y cuidado personal</h1>
        <p class="home-intro-lead">En Haircut Home Studio hacemos <strong>cortes, lavado y brushing, color (canas, visos, mechas, balayage, baby lights y fantasía), masajes, botox, liso y peinados</strong>. ¿Qué esperas para tus consultas?</p>
        <div class="home-intro-actions">
            <a href="<?php echo e(route('reservas.create')); ?>" class="btn btn-primary">Reservar hora</a>
            <a href="<?php echo e(route('productos.index')); ?>" class="btn btn-secondary">Ver productos</a>
        </div>
    </section>

    <section>HOLA GENTE QUE PASA</section>

    <section class="home-gallery" aria-label="Trabajos del estudio">
        <div class="home-mosaic">
            <?php $__currentLoopData = $collage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $foto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <figure class="home-mosaic-item home-mosaic-item-<?php echo e($index + 1); ?>">
                    <img src="<?php echo e(asset($foto['src'])); ?>" alt="<?php echo e($foto['alt']); ?>" loading="lazy">
                </figure>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    <section class="home-products-section" id="productos">
        <div class="home-section-head">
            <div>
                <p class="home-kicker">Vitrina del estudio</p>
                <h2>Productos destacados</h2>
                <p class="home-presencial-note">Los productos se adquieren <strong>solo de forma presencial</strong> en el estudio.</p>
            </div>
            <a href="<?php echo e(route('productos.index')); ?>" class="btn btn-secondary home-products-link">Ver todos los productos</a>
        </div>
        <?php if($productos->isNotEmpty()): ?>
            <div class="home-products-grid">
                <?php $__currentLoopData = $productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (isset($component)) { $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card','data' => ['product' => $producto]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $attributes = $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $component = $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <p class="home-empty">Pronto publicaremos productos en la vitrina.</p>
        <?php endif; ?>
        <div class="home-products-foot"><a href="<?php echo e(route('productos.index')); ?>" class="btn btn-primary">Ver más productos</a></div>
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
                <?php $__currentLoopData = $resenas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resena): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <article class="review-card carousel-slide">
                        <div class="review-card-top"><span class="home-stars" aria-hidden="true"><?php echo e(str_repeat('★', $resena['estrellas'])); ?></span><span class="review-source"><span class="review-dot"></span> Google</span></div>
                        <p class="review-quote">“<?php echo e($resena['texto']); ?>”</p>
                        <div class="review-author"><span class="review-avatar"><?php echo e($resena['inicial']); ?></span><div><p class="review-name"><?php echo e($resena['nombre']); ?></p><p class="review-time"><?php echo e($resena['tiempo']); ?></p></div></div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div></div>
            <button type="button" class="carousel-btn carousel-btn-next" aria-label="Siguientes reseñas">›</button>
            <div class="carousel-dots" role="tablist" aria-label="Reseñas"></div>
        </div>
        <div class="home-reviews-actions">
            <a href="https://www.google.com/maps/place/HairCut+Home+Studio/@-33.6828171,-71.1894721,17z" class="btn btn-outline" target="_blank" rel="noopener">Ver todas en Google Maps</a>
            <a href="<?php echo e(route('reservas.create')); ?>" class="btn btn-primary">Reservar hora</a>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Haircut Home Studio - Melipilla', 'page' => 'inicio', 'layout' => 'home'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/resources/views/home.blade.php ENDPATH**/ ?>