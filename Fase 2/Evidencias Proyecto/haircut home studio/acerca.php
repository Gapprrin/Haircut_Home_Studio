<?php
require_once __DIR__ . '/includes/funciones.php';

$titulo = 'Acerca de nosotros — Haircut Home Studio';
$seccion = 'public';
$pagina = 'acerca';
require __DIR__ . '/includes/header.php';
?>

<section class="card acerca-card">
<h2>Acerca de nosotros</h2>
<p class="lead">Haircut Home Studio · Melipilla</p>
<p>Pronto encontrarás aquí nuestra historia, el equipo y lo que nos mueve. Estamos preparando este texto para compartirlo contigo.</p>
<p>Mientras tanto puedes <a href="<?php echo h(url_reservar()); ?>">reservar tu hora</a> o escribirnos por WhatsApp.</p>
<div class="actions">
<a class="btn btn-primary" href="index.php">Volver al inicio</a>
<a class="btn btn-secondary" href="https://wa.me/56954182516" target="_blank" rel="noopener">WhatsApp</a>
</div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
