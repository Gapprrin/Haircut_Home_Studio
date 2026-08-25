<?php
/**
 * Instalador para XAMPP.
 * Abre: http://localhost/NOMBRE-CARPETA/instalar.php
 * Crea la base, las tablas y los datos de ejemplo.
 */
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'haircut_studio');

$mensaje = '';
$error = '';
$hecho = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $sql_path = __DIR__ . '/database/haircut_studio.sql';
        if (!is_readable($sql_path)) {
            throw new RuntimeException('No se encontró database/haircut_studio.sql');
        }

        $sql = file_get_contents($sql_path);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');

        // Quita comentarios de línea y ejecuta sentencia por sentencia
        $lineas = preg_split("/\r\n|\n|\r/", $sql);
        $buffer = '';
        $omitir = false;
        foreach ($lineas as $linea) {
            $trim = trim($linea);
            if ($trim === '' || str_starts_with($trim, '--')) {
                continue;
            }
            if (!$omitir && (stripos($trim, 'CREATE DATABASE') === 0 || stripos($trim, 'USE ') === 0)) {
                $omitir = !str_ends_with(rtrim($linea), ';');
                continue;
            }
            if ($omitir) {
                $omitir = !str_ends_with(rtrim($linea), ';');
                continue;
            }
            $buffer .= $linea . "\n";
            if (str_ends_with(rtrim($linea), ';')) {
                $pdo->exec($buffer);
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            $pdo->exec($buffer);
        }

        $fotos = __DIR__ . '/uploads/fotos';
        if (!is_dir($fotos)) {
            mkdir($fotos, 0777, true);
        }

        $hecho = true;
        $mensaje = 'Base de datos instalada. Ya puedes entrar con las cuentas de demo.';
    } catch (Throwable $e) {
        $error = 'Error al instalar: ' . $e->getMessage();
    }
}

$ya_existe = false;
try {
    $check = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $ya_existe = (bool) $check->query('SHOW TABLES LIKE "usuarios"')->fetch();
} catch (Throwable $e) {
    $ya_existe = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalar Haircut Studio</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site">
<div class="container">
<h1>Haircut<span class="accent-dot">.</span>Studio</h1>
</div>
</header>
<main>
<div class="container">
<section class="card">
<h2>Instalación en XAMPP</h2>
<p class="lead">Este script crea la base <strong>haircut_studio</strong>, las tablas y datos de ejemplo.</p>

<ol class="hint">
<li>Deja Apache y MySQL encendidos en el panel de XAMPP.</li>
<li>Copia esta carpeta a <code>C:\xampp\htdocs\</code> si aún no está ahí.</li>
<li>Pulsa el botón de abajo. Si reinstalas, se borran y recrean las tablas.</li>
</ol>

<?php if ($mensaje): ?>
<div class="alert alert-ok"><?php echo htmlspecialchars($mensaje); ?></div>
<p><a class="btn btn-primary" href="login.php">Ir a iniciar sesión</a></p>
<p class="hint">Admin: admin@haircut.cl / admin123<br>Cliente: cliente@haircut.cl / cliente123</p>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<p class="hint">Revisa que MySQL esté activo y que el usuario sea <code>root</code> sin contraseña (por defecto en XAMPP). Si cambiaste la clave, edita <code>config/db.php</code> e <code>instalar.php</code>.</p>
<?php endif; ?>

<?php if (!$hecho): ?>
<?php if ($ya_existe): ?>
<p class="hint">Ya hay una instalación. Si pulsas reinstalar se perderán los datos actuales.</p>
<?php endif; ?>
<form method="post">
<div class="actions">
<button type="submit" class="btn-primary"><?php echo $ya_existe ? 'Reinstalar base de datos' : 'Instalar ahora'; ?></button>
<?php if ($ya_existe): ?>
<a class="btn btn-secondary" href="index.php">Entrar al sitio</a>
<?php endif; ?>
</div>
</form>
<?php endif; ?>
</section>
</div>
</main>
</body>
</html>
