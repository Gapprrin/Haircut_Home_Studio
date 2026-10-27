<?php
require_once __DIR__ . '/includes/funciones.php';

$titulo = 'Haircut Home Studio - Melipilla';
$seccion = 'public';
$pagina = 'inicio';
$layout = 'home';
require __DIR__ . '/includes/header.php';

$productos = array_slice(productos_activos(), 0, 3);
$reserva_url = url_reservar();
$maps_url = 'https://www.google.com/maps/place/HairCut+Home+Studio/@-33.6828171,-71.1894721,17z/data=!3m1!4b1!4m6!3m5!1s0x9662ff607ff20d81:0xafcf5d95bed90e7c!8m2!3d-33.6828171!4d-71.1894721!16s%2Fg%2F11hcyscny1?hl=es';

$collage = [
    ['src' => 'img/collage/03.jpg', 'alt' => 'Balayage liso'],
    ['src' => 'img/collage/05.jpg', 'alt' => 'Balayage platino'],
    ['src' => 'img/collage/08.jpg', 'alt' => 'Corte undercut rubio'],
    ['src' => 'img/collage/01.png', 'alt' => 'Ondas cobrizas'],
    ['src' => 'img/collage/07.jpg', 'alt' => 'Balayage miel'],
    ['src' => 'img/collage/04.jpg', 'alt' => 'Mechas y rulos'],
    ['src' => 'img/collage/06.png', 'alt' => 'Color fantasía azul'],
    ['src' => 'img/collage/10.jpg', 'alt' => 'Corte y color rojo'],
    ['src' => 'img/collage/02.jpg', 'alt' => 'Mechas y ondas'],
    ['src' => 'img/collage/09.jpg', 'alt' => 'Color violeta y rosa'],
];

$reseñas = [
    ['texto' => 'Maravillosa la atención y por supuesto el profesionalismo de Ricardo. Top top.', 'nombre' => 'Carolina Escalante', 'inicial' => 'C', 'tiempo' => 'Hace 2 años', 'estrellas' => 5],
    ['texto' => 'Excelente, 100% recomendable.', 'nombre' => 'Cliente verificada', 'inicial' => 'N', 'tiempo' => 'Google', 'estrellas' => 5],
    ['texto' => 'Excelente atención. Buenos precios. Ricos productos.', 'nombre' => 'M. M.', 'inicial' => 'M', 'tiempo' => 'Google', 'estrellas' => 5],
    ['texto' => 'Muy buena experiencia, atención cercana y resultados impecables.', 'nombre' => 'Cliente local', 'inicial' => 'A', 'tiempo' => 'Google', 'estrellas' => 5],
    ['texto' => 'Ricardo es muy profesional, siempre quedo feliz con mi cabello.', 'nombre' => 'Visitante frecuente', 'inicial' => 'V', 'tiempo' => 'Google', 'estrellas' => 5],
    ['texto' => 'Ambiente acogedor y personalizado, ideal para relajarse mientras te cuidan.', 'nombre' => 'Clienta Melipilla', 'inicial' => 'K', 'tiempo' => 'Google', 'estrellas' => 5],
    ['texto' => 'Buenos precios y productos de calidad. Volveré sin duda.', 'nombre' => 'J. A. S. F.', 'inicial' => 'J', 'tiempo' => 'Google', 'estrellas' => 4],
    ['texto' => 'Atención excelente y resultados hermosos en coloración.', 'nombre' => 'M. Q. C.', 'inicial' => 'M', 'tiempo' => 'Google', 'estrellas' => 5],
];
?>

<div class="home-page-wrap">

<section class="home-intro">
<h1 class="home-intro-title">Belleza, cosmética y cuidado personal</h1>
<p class="home-intro-lead">En Haircut Home Studio hacemos <strong>cortes, lavado y brushing, color (canas, visos, mechas, balayage, baby lights y fantasía), masajes, botox, liso y peinados</strong>. ¿Qué esperas para tus consultas?</p>
<div class="home-intro-actions">
<a href="<?php echo h($reserva_url); ?>" class="btn btn-primary">Reservar hora</a>
<a href="<?php echo h(url('usuario/productos.php')); ?>" class="btn btn-secondary">Ver productos</a>
</div>
</section>

<section class="home-gallery" aria-label="Trabajos del estudio">
<div class="home-mosaic">
<?php foreach ($collage as $i => $foto): ?>
<figure class="home-mosaic-item home-mosaic-item-<?php echo $i + 1; ?>">
<img src="<?php echo h(url($foto['src'])); ?>" alt="<?php echo h($foto['alt']); ?>" loading="lazy">
</figure>
<?php endforeach; ?>
</div>
</section>

<section class="home-products-section" id="productos">
<div class="home-section-head">
<div>
<p class="home-kicker">Vitrina del estudio</p>
<h2>Productos destacados</h2>
<p class="home-presencial-note">Los productos se adquieren <strong>solo de forma presencial</strong> en el estudio.</p>
</div>
<a href="<?php echo h(url('usuario/productos.php')); ?>" class="btn btn-secondary home-products-link">Ver todos los productos</a>
</div>

<?php if ($productos): ?>
<div class="home-products-grid">
<?php foreach ($productos as $p): ?>
<article class="product-card">
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
<p class="home-empty">Pronto publicaremos productos en la vitrina.</p>
<?php endif; ?>

<div class="home-products-foot">
<a href="<?php echo h(url('usuario/productos.php')); ?>" class="btn btn-primary">Ver más productos</a>
</div>
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
<div>
<p class="home-reviews-score"><strong>4,6</strong> <span class="home-stars" aria-hidden="true">★★★★<span class="star-half">★</span></span></p>
<p class="home-reviews-count">25 reseñas en Google</p>
</div>
</div>
</div>

<div class="carousel carousel-auto carousel-reviews" id="carousel-reviews" data-autoplay="6000" data-per-view="3">
<button type="button" class="carousel-btn carousel-btn-prev" aria-label="Reseñas anteriores">
<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M15.5 5.5 9 12l6.5 6.5 1.4-1.4L11.8 12l5.1-5.1z"/></svg>
</button>
<div class="carousel-viewport">
<div class="carousel-track">
<?php foreach ($reseñas as $r): ?>
<article class="review-card carousel-slide">
<div class="review-card-top">
<span class="home-stars" aria-hidden="true"><?php echo str_repeat('★', (int) ($r['estrellas'] ?? 5)); ?></span>
<span class="review-source"><span class="review-dot"></span> Google</span>
</div>
<p class="review-quote">“<?php echo h($r['texto']); ?>”</p>
<div class="review-author">
<span class="review-avatar"><?php echo h($r['inicial']); ?></span>
<div>
<p class="review-name"><?php echo h($r['nombre']); ?></p>
<p class="review-time"><?php echo h($r['tiempo']); ?></p>
</div>
</div>
</article>
<?php endforeach; ?>
</div>
</div>
<button type="button" class="carousel-btn carousel-btn-next" aria-label="Siguientes reseñas">
<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="m8.5 5.5 6.5 6.5-6.5 6.5-1.4-1.4 5.1-5.1-5.1-5.1z"/></svg>
</button>
<div class="carousel-dots" role="tablist" aria-label="Reseñas"></div>
</div>

<div class="home-reviews-actions">
<a href="<?php echo h($maps_url); ?>" class="btn btn-outline" target="_blank" rel="noopener">Ver todas en Google Maps</a>
<a href="<?php echo h($reserva_url); ?>" class="btn btn-primary">Reservar hora</a>
</div>
</section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
