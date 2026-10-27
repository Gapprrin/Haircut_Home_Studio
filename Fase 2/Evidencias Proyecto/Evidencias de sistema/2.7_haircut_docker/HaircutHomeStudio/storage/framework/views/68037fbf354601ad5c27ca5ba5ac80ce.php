<?php $__env->startSection('content'); ?>
<section class="auth-card">
    <div class="auth-head"><span class="auth-dot" aria-hidden="true"></span><h2><?php echo e($registro ? 'Crear cuenta' : 'Entrar'); ?></h2></div>
    <p class="auth-sub"><?php echo e($registro ? 'Regístrate y reserva con atención personalizada en Melipilla.' : 'Ingresa con tu usuario o correo para ver tus reservas y agendar.'); ?></p>

    <?php if($registro): ?>
        <form method="post" action="<?php echo e(route('register.store')); ?>" class="auth-form">
            <?php echo csrf_field(); ?>
            <div class="auth-field"><input type="text" name="nombre" value="<?php echo e(old('nombre', $nombreSugerido)); ?>" readonly placeholder="Nombre"></div>
            <div class="auth-field"><input type="email" name="email" required value="<?php echo e(old('email')); ?>" placeholder="Correo electrónico" autocomplete="email"></div>
            <div class="auth-field"><input type="password" name="password" required minlength="6" placeholder="Contraseña" autocomplete="new-password"></div>
            <div class="auth-field"><input type="password" name="password_confirmation" required minlength="6" placeholder="Confirmar contraseña" autocomplete="new-password"></div>
            <button type="submit" class="btn btn-primary auth-submit">Crear cuenta</button>
            <p class="auth-foot">¿Ya tienes cuenta? <a href="<?php echo e(route('login')); ?>">Entrar</a></p>
        </form>
    <?php else: ?>
        <form method="post" action="<?php echo e(route('login.store')); ?>" class="auth-form">
            <?php echo csrf_field(); ?>
            <div class="auth-field"><input type="text" name="email" required value="<?php echo e(old('email')); ?>" placeholder="Usuario o correo (ej. ana)" autocomplete="username"></div>
            <div class="auth-field"><input type="password" name="password" required placeholder="Contraseña" autocomplete="current-password"></div>
            <button type="submit" class="btn btn-primary auth-submit">Entrar</button>
            <p class="auth-foot">¿No tienes cuenta? <a href="<?php echo e(route('login', ['modo' => 'registro'])); ?>">Crear cuenta</a></p>
        </form>
    <?php endif; ?>
</section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Haircut Home Studio - Entrar', 'page' => 'login'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /app/resources/views/auth/login.blade.php ENDPATH**/ ?>