<?php
/** Encabezado. Antes de incluir, define $titulo, $seccion y $pagina. */
if (!isset($titulo)) {
    $titulo = 'Haircut Home Studio';
}
if (!isset($seccion)) {
    $seccion = 'public';
}
if (!isset($pagina)) {
    $pagina = '';
}
if (!isset($layout)) {
    $layout = 'app';
}
$usuario = usuario_actual();
$es_admin_ui = ($seccion === 'admin');
$back_href = url('index.php');
if ($es_admin_ui) {
    $rol_ui = $usuario['rol'] ?? '';
    if ($rol_ui === 'admin' && $pagina !== 'servicios') {
        $back_href = url('admin/servicios.php');
    } elseif ($rol_ui === 'peluquero' && $pagina !== 'panel') {
        $back_href = url('admin/dashboard.php');
    }
}
$es_home = ($layout === 'home');
$body_class = ['has-home-header'];
if ($es_home) {
    $body_class[] = 'home-page';
} else {
    $body_class[] = 'app-page';
}
if ($pagina === 'login') {
    $body_class[] = 'login-page';
}
if ($pagina === 'nueva') {
    $body_class[] = 'book-page';
}
if ($pagina === 'disponibilidad') {
    $body_class[] = 'avail-page';
}
if ($es_admin_ui) {
    $body_class[] = 'admin-page';
} elseif ($es_home) {
    $body_class[] = 'user-motion';
}
$body_attr = ' class="' . implode(' ', $body_class) . '"';
$css_href = url('style.css') . '?v=sug-1';
$logo_href = url('img/logo.png') . '?v=logo-fix-3';
$favicon_href = url('img/favicon.png') . '?v=logo-fix-3';
$login_correo = url('auth/login.php') . '?modo=correo';
$login_registro = url('auth/login.php') . '?modo=registro';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($titulo); ?></title>
<link rel="icon" type="image/png" href="<?php echo h($favicon_href); ?>">
<link rel="shortcut icon" href="<?php echo h($favicon_href); ?>">
<link rel="apple-touch-icon" href="<?php echo h($logo_href); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?php echo h($css_href); ?>">
<script>document.documentElement.classList.add('js-reveal');window.HHS_BASE=<?php echo json_encode(app_base()); ?>;</script>
</head>
<body<?php echo $body_attr; ?>>

<div id="app-loader" class="app-loader" hidden>
<div class="app-loader-spin" aria-hidden="true">
<span></span><span></span><span></span><span></span>
<span></span><span></span><span></span><span></span>
<span></span><span></span><span></span><span></span>
</div>
<p>Cargando...</p>
</div>

<header class="home-top">
<div class="container container-fluid">
<div class="home-top-left">
<a href="<?php echo h(url('index.php')); ?>" class="home-brand-link" aria-label="HairCut Home Studio — Inicio">
<span class="home-brand-mark-wrap">
<img class="home-brand-logo" src="<?php echo h($logo_href); ?>" alt="">
</span>
<span class="home-brand-name">HairCut Home Studio</span>
</a>
</div>
<div class="header-actions">
<?php if (!$usuario): ?>
<a href="<?php echo h($login_correo); ?>" class="btn-nav btn-nav-outline">Iniciar sesión</a>
<a href="<?php echo h($login_registro); ?>" class="btn-nav btn-nav-fill">Registrarse</a>
<?php else: ?>
<span class="nav-user nav-pill"><?php echo h($usuario['nombre']); ?></span>
<?php endif; ?>
<?php if (!$es_home): ?>
<a href="<?php echo h($back_href); ?>" class="header-back" id="header-back" aria-label="Volver atrás" title="Volver atrás">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
</a>
<?php endif; ?>
<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-acc" aria-label="Abrir menú">
<span></span><span></span><span></span>
</button>
</div>
<nav class="home-top-right side-nav" id="nav-acc">
<div class="side-nav-head">
<a href="<?php echo h(url('index.php')); ?>" class="side-nav-brand">
<img src="<?php echo h($logo_href); ?>" alt="">
<span>HairCut Home Studio</span>
</a>
<button type="button" class="side-nav-close" aria-label="Cerrar menú">&times;</button>
</div>
<p class="side-nav-title">Menú</p>
<?php if (!$usuario): ?>
<div class="side-nav-auth">
<a href="<?php echo h($login_correo); ?>" class="btn-nav btn-nav-outline">Iniciar sesión</a>
<a href="<?php echo h($login_registro); ?>" class="btn-nav btn-nav-fill">Registrarse</a>
</div>
<?php endif; ?>
<?php if ($usuario && ($usuario['rol'] ?? '') === 'peluquero'): ?>
<a href="<?php echo h(url('admin/dashboard.php')); ?>" class="nav-link<?php echo $pagina === 'panel' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">▣</span> Estadísticas</a>
<a href="<?php echo h(url('admin/disponibilidad.php')); ?>" class="nav-link<?php echo $pagina === 'disponibilidad' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">▦</span> Disponibilidad</a>
<a href="<?php echo h(url('admin/solicitudes.php')); ?>" class="nav-link<?php echo $pagina === 'solicitudes' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">✉</span> Solicitudes</a>
<a href="<?php echo h(url('admin/horas.php')); ?>" class="nav-link<?php echo $pagina === 'horas' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">◷</span> Horas</a>
<a href="<?php echo h(url('auth/logout.php')); ?>" class="nav-link nav-logout"><span class="nav-ico" aria-hidden="true">→</span> Cerrar sesión</a>
<?php elseif ($usuario && ($usuario['rol'] ?? '') === 'admin'): ?>
<a href="<?php echo h(url('admin/servicios.php')); ?>" class="nav-link<?php echo $pagina === 'servicios' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">✂</span> Servicios</a>
<a href="<?php echo h(url('admin/catalogo.php')); ?>" class="nav-link<?php echo $pagina === 'catalogo' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">▣</span> Catálogo</a>
<a href="<?php echo h(url('auth/logout.php')); ?>" class="nav-link nav-logout"><span class="nav-ico" aria-hidden="true">→</span> Cerrar sesión</a>
<?php elseif ($usuario): ?>
<a href="<?php echo h(url('index.php')); ?>" class="nav-link<?php echo $pagina === 'inicio' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">⌂</span> Inicio</a>
<a href="<?php echo h(url('usuario/productos.php')); ?>" class="nav-link<?php echo $pagina === 'productos' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">◈</span> Productos</a>
<a href="<?php echo h(url_reservar()); ?>" class="nav-link<?php echo $pagina === 'nueva' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">✎</span> Reservar</a>
<a href="<?php echo h(url('usuario/mis-reservas.php')); ?>" class="nav-link<?php echo $pagina === 'mis' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">◷</span> Mis reservas</a>
<a href="<?php echo h(url('auth/logout.php')); ?>" class="nav-link nav-logout"><span class="nav-ico" aria-hidden="true">→</span> Cerrar sesión</a>
<?php else: ?>
<a href="<?php echo h(url('index.php')); ?>" class="nav-link<?php echo $pagina === 'inicio' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">⌂</span> Inicio</a>
<a href="<?php echo h(url('usuario/productos.php')); ?>" class="nav-link<?php echo $pagina === 'productos' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">◈</span> Productos</a>
<a href="<?php echo h(url_reservar()); ?>" class="nav-link<?php echo $pagina === 'nueva' ? ' is-current' : ''; ?>"><span class="nav-ico" aria-hidden="true">✎</span> Reservar</a>
<?php endif; ?>
</nav>
<div class="side-nav-overlay" hidden></div>
</div>
</header>

<main>
<div class="container container-fluid">
<?php
mostrar_flash();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
