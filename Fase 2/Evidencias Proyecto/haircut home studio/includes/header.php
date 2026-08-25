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
}
$mostrar_acerca = $es_home || $seccion === 'cliente' || $pagina === 'acerca';
$body_attr = ' class="' . implode(' ', $body_class) . '"';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($titulo); ?></title>
<link rel="icon" type="image/png" sizes="32x32" href="img/favicon.png">
<link rel="shortcut icon" href="img/favicon.png">
<link rel="apple-touch-icon" href="img/logo-senoras.png">
<link rel="stylesheet" href="style.css?v=cat-center-1">
</head>
<body<?php echo $body_attr; ?>>

<header class="home-top">
<div class="container container-wide">
<div class="home-top-left">
<img class="home-brand-mark" src="img/logo-senoras.png" alt="">
<a href="index.php" class="home-brand">Haircut Home Studio</a>
<span class="home-branch">Melipilla</span>
</div>
<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-acc" aria-label="Abrir menú">
<span></span><span></span><span></span>
</button>
<nav class="home-top-right side-nav" id="nav-acc">
<button type="button" class="side-nav-close" aria-label="Cerrar menú">&times;</button>
<p class="side-nav-title">Menú</p>
<?php if ($usuario && $usuario['rol'] === 'admin'): ?>
<a href="admin-dashboard.php" class="nav-admin<?php echo $pagina === 'panel' ? ' is-current' : ''; ?>">Estadísticas</a>
<a href="admin-disponibilidad.php" class="<?php echo $pagina === 'disponibilidad' ? 'is-current' : ''; ?>">Disponibilidad</a>
<a href="admin-solicitudes.php" class="<?php echo $pagina === 'solicitudes' ? 'is-current' : ''; ?>">Solicitudes</a>
<a href="admin-horas.php" class="<?php echo $pagina === 'horas' ? 'is-current' : ''; ?>">Horas</a>
<a href="admin-servicios.php" class="<?php echo $pagina === 'servicios' ? 'is-current' : ''; ?>">Servicios</a>
<a href="admin-catalogo.php" class="<?php echo $pagina === 'catalogo' ? 'is-current' : ''; ?>">Catálogo</a>
<a href="admin-reportes.php" class="<?php echo $pagina === 'reportes' ? 'is-current' : ''; ?>">Reportes</a>
<div class="nav-session">
<span class="nav-user nav-user-admin"><?php echo h($usuario['nombre']); ?></span>
<a href="logout.php" class="nav-logout">Cerrar sesión</a>
</div>
<?php elseif ($usuario): ?>
<a href="nueva-reserva.php" class="<?php echo $pagina === 'nueva' ? 'is-current' : ''; ?>">Reservar</a>
<a href="mis-reservas.php" class="<?php echo $pagina === 'mis' ? 'is-current' : ''; ?>">Mis reservas</a>
<?php if ($mostrar_acerca): ?>
<a href="acerca.php" class="<?php echo $pagina === 'acerca' ? 'is-current' : ''; ?>">Acerca de nosotros</a>
<?php endif; ?>
<div class="nav-session">
<span class="nav-user <?php echo !empty($usuario['es_invitado']) ? 'nav-user-guest' : 'nav-user-in'; ?>"><?php echo h($usuario['nombre']); ?></span>
<a href="logout.php" class="nav-logout">Cerrar sesión</a>
</div>
<?php else: ?>
<a href="nueva-reserva.php" class="<?php echo $pagina === 'nueva' ? 'is-current' : ''; ?>">Reservar</a>
<a href="login.php" class="home-login<?php echo $pagina === 'login' ? ' is-current' : ''; ?>">Iniciar sesión</a>
<?php if ($mostrar_acerca): ?>
<a href="acerca.php" class="<?php echo $pagina === 'acerca' ? 'is-current' : ''; ?>">Acerca de nosotros</a>
<?php endif; ?>
<?php endif; ?>
</nav>
<div class="side-nav-overlay" hidden></div>
</div>
</header>

<main>
<div class="container<?php echo ($es_home || $es_admin_ui) ? ' container-wide' : ''; ?>">
<?php mostrar_flash(); ?>
