<?php
require_once __DIR__ . '/includes/funciones.php';

$titulo = 'Haircut Home Studio - Melipilla';
$seccion = 'public';
$pagina = 'inicio';
$layout = 'home';
require __DIR__ . '/includes/header.php';

$productos = productos_activos();
$reserva_url = url_reservar();
?>

<div class="home-wrap">

<div class="home-main">
<div class="home-hero-wrap">
<img class="home-hero" src="img/hero-banner.jpg" alt="Color personalizado en Haircut Home Studio">
<img class="home-logo" src="img/logo-senoras.png" alt="Logo Haircut Home Studio">
</div>

<div class="home-profile">
<div class="home-profile-head">
<div>
<h2 class="home-title">Haircut Home Studio</h2>
<div class="home-rating" aria-label="Valoración 5.0">
<span class="home-stars">★★★★★</span>
<span class="home-rating-text">5.0 · Melipilla</span>
</div>
</div>
<div class="home-social" aria-label="Redes sociales">
<a href="https://www.instagram.com/haircut.homestudio/" target="_blank" rel="noopener" title="Instagram @haircut.homestudio">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm5 4.8A4.2 4.2 0 1 0 16.2 12 4.2 4.2 0 0 0 12 7.8zm6.4-.9a1 1 0 1 0 1 1 1 1 0 0 0-1-1zM12 9.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5z"/></svg>
</a>
<a href="https://wa.me/56954182516" target="_blank" rel="noopener" title="WhatsApp">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12.04 2C6.58 2 2.15 6.4 2.15 11.83a9.78 9.78 0 0 0 1.32 4.92L2 22l5.4-1.41a10 10 0 0 0 4.64 1.18h.04c5.46 0 9.89-4.4 9.89-9.84C21.97 6.4 17.5 2 12.04 2zm5.76 13.91c-.24.68-1.4 1.3-1.94 1.38-.5.07-1.12.1-1.81-.11-.42-.13-.95-.31-1.64-.6-2.88-1.25-4.76-4.15-4.9-4.34-.14-.2-1.15-1.53-1.15-2.92s.73-2.07 1-2.36c.24-.27.52-.34.7-.34h.5c.16 0 .37 0 .57.44.24.52.8 2 .87 2.14.07.14.12.3 0 .48-.1.2-.16.3-.3.47-.15.16-.31.36-.44.49-.15.14-.3.3-.13.58.16.27.73 1.2 1.57 1.95 1.08.96 1.99 1.26 2.27 1.4.28.14.45.12.61-.07.17-.2.7-.81.88-1.09.19-.27.37-.23.62-.14.26.1 1.63.77 1.91.91.28.14.47.2.54.32.07.12.07.68-.17 1.36z"/></svg>
</a>
<a href="https://www.facebook.com/haircuthomestudio/" target="_blank" rel="noopener" title="Facebook Hair Cut Home Studio">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.6l.4-3H13v-2c0-.6.4-1 1-1z"/></svg>
</a>
</div>
</div>

<p class="home-lead">MELIPILLA te esperamos para tus consultas. Agenda online y vive una atención personalizada, de martes a sábado.</p>

<div class="actions">
<a href="<?php echo h($reserva_url); ?>" class="btn btn-primary">Reservar ahora</a>
<a href="https://wa.me/56954182516" class="btn btn-secondary" target="_blank" rel="noopener">Consultas por WhatsApp</a>
</div>
</div>
</div>

<aside class="home-side">
<div class="home-side-card">
<div class="home-map-frame">
<iframe class="home-map" title="Mapa de HairCut Home Studio en Melipilla"
 src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d500!2d-71.1894721!3d-33.6828171!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9662ff607ff20d81%3A0xafcf5d95bed90e7c!2sHairCut%20Home%20Studio!5e0!3m2!1ses!2scl!4v1"
 loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</div>

<ul class="home-contact">
<li>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 14.5 9 2.5 2.5 0 0 1 12 11.5z"/></svg>
<a href="https://www.google.com/maps/place/HairCut+Home+Studio/@-33.6828171,-71.1894721,17z/data=!3m1!4b1!4m6!3m5!1s0x9662ff607ff20d81:0xafcf5d95bed90e7c!8m2!3d-33.6828171!4d-71.1894721!16s%2Fg%2F11hcyscny1?hl=es" target="_blank" rel="noopener">Prof. Ricardo Luengo Mardones, Melipilla, Región Metropolitana</a>
</li>
<li>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M16.5 14.4c-.4 0-.8-.1-1.1-.3l-1.3.4c.6.9 1.5 1.6 2.6 2l.4-1.3c-.2-.3-.4-.6-.6-.8zm-9-9C6.7 5 6 5.7 6 6.5 6 13.4 10.6 18 17.5 18c.8 0 1.5-.7 1.5-1.5v-2.1l-3.2-.7-1.1 1.5c-3.3-.7-5.8-3.2-6.5-6.5l1.5-1.1L8.9 4H6.5z"/></svg>
<a href="tel:+56954182516">+56 9 5418 2516</a>
</li>
<li>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12.04 2C6.58 2 2.15 6.4 2.15 11.83a9.78 9.78 0 0 0 1.32 4.92L2 22l5.4-1.41a10 10 0 0 0 4.64 1.18h.04c5.46 0 9.89-4.4 9.89-9.84C21.97 6.4 17.5 2 12.04 2z"/></svg>
<a href="https://wa.me/56954182516" target="_blank" rel="noopener">¡Contáctanos por WhatsApp!</a>
</li>
<li>
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 11h-4V7h2v4h2z"/></svg>
<details class="home-hours">
<summary>Ver horario</summary>
<p>Lunes: cerrado</p>
<p>Martes a sábado: 10:30 – 19:30</p>
<p>Domingo: cerrado</p>
<p class="hint">Consulta también por atención a domicilio.</p>
</details>
</li>
</ul>
</div>
</aside>

<section class="home-catalog home-products">
<h2>Catálogo de productos</h2>
<p class="home-presencial">Comprar presencial.</p>

<div class="carousel carousel-cover" id="carousel-productos">
<button type="button" class="carousel-btn carousel-btn-prev" data-dir="-1" aria-label="Anterior">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
</button>
<div class="carousel-viewport">
<div class="carousel-track">
<?php foreach ($productos as $p): ?>
<article class="product-card">
<div class="product-photo">
<?php if (!empty($p['imagen'])): ?>
<img src="uploads/fotos/<?php echo h($p['imagen']); ?>" alt="<?php echo h($p['nombre']); ?>">
<?php else: ?>
<span>Espacio para imagen</span>
<?php endif; ?>
</div>
<h3><?php echo h($p['nombre']); ?></h3>
<p class="product-desc"><?php echo h($p['descripcion']); ?></p>
<p class="product-price"><?php echo precio_clp($p['precio']); ?></p>
</article>
<?php endforeach; ?>
</div>
</div>
<button type="button" class="carousel-btn carousel-btn-next" data-dir="1" aria-label="Siguiente">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M9.5 5.5 8.1 6.9 13.2 12l-5.1 5.1 1.4 1.4L16 12z"/></svg>
</button>
</div>
</section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
