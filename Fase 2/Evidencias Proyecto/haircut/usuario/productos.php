<?php
require_once __DIR__ . '/../includes/funciones.php';

$titulo = 'Productos — Haircut Home Studio';
$seccion = 'public';
$pagina = 'productos';
require __DIR__ . '/../includes/header.php';

$productos = productos_activos();
?>

<section class="productos-page">
<div class="productos-head">
<p class="home-kicker">Catálogo completo</p>
<h1>Productos del estudio</h1>
<p class="productos-lead">Conoce los productos que tenemos disponibles en Haircut Home Studio. Puedes verlos y consultarlos con nosotras durante tu visita.</p>
<div class="productos-alert" role="note">
<strong>Compra presencial.</strong> Los productos solo se pueden adquirir de forma presencial en el estudio; no realizamos ventas online.
</div>
<div class="productos-foot actions">
<a href="<?php echo h(url('index.php')); ?>" class="btn btn-secondary">Volver al inicio</a>
<a href="<?php echo h(url_reservar()); ?>" class="btn btn-primary">Reservar hora</a>
</div>
</div>

<?php if ($productos): ?>
<div class="productos-grid">
<?php foreach ($productos as $p): ?>
<article class="product-card product-card-full">
<div class="product-photo">
<?php if (!empty($p['imagen'])): ?>
<img src="<?php echo h(foto_url($p['imagen'])); ?>" alt="<?php echo h($p['nombre']); ?>">
<?php else: ?>
<span class="product-placeholder">Sin imagen</span>
<?php endif; ?>
</div>
<h3><?php echo h($p['nombre']); ?></h3>
<p class="product-desc"><?php echo h($p['descripcion']); ?></p>
<p class="product-price"><?php echo precio_clp($p['precio']); ?></p>
</article>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="home-empty">Aún no hay productos publicados en el catálogo.</p>
<?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
