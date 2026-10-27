<?php
require_once __DIR__ . '/../includes/funciones.php';

$modo = $_GET['modo'] ?? $_POST['modo'] ?? '';
if ($modo === 'facebook') {
    $modo = 'correo';
}
$next = normalizar_next((string) ($_GET['next'] ?? $_POST['next'] ?? 'usuario/nueva-reserva.php'));
$error = '';
$nombre_auto = nombre_automatico('email');

if (usuario_actual()) {
    ir(destino_post_login(usuario_actual()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($accion === 'login') {
        $email = login_identificador($_POST['email'] ?? '');
        $st = db()->prepare('SELECT * FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['usuario_id'] = (int) $user['id'];
            flash('ok', 'Sesión iniciada.');
            ir(destino_post_login($user));
        }
        $error = 'Usuario o contraseña incorrectos.';
        $modo = 'correo';
    }

    if ($accion === 'registro') {
        $email = trim($_POST['email'] ?? '');
        $nombre = trim($_POST['nombre'] ?? $nombre_auto);

        if ($nombre === '') {
            $nombre = nombre_automatico('email');
        }
        if (strlen($pass) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($pass !== $pass2) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo no es válido.';
        } else {
            $st = db()->prepare('SELECT * FROM usuarios WHERE email = ?');
            $st->execute([$email]);
            $existe = $st->fetch();
            if ($existe) {
                $error = 'Ese correo ya está registrado. Entra con tu contraseña.';
                $modo = 'correo';
            }
        }

        if ($error === '') {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = db()->prepare(
                'INSERT INTO usuarios (nombre, email, password, rol, es_invitado, origen)
                 VALUES (?, ?, ?, "cliente", 0, "email")'
            );
            $ins->execute([$nombre, $email, $hash]);
            $_SESSION['usuario_id'] = (int) db()->lastInsertId();
            flash('ok', 'Listo. Ya puedes reservar.');
            ir(destino_post_login(['rol' => 'cliente']));
        }
        if ($error !== '' && $modo !== 'correo') {
            $modo = 'registro';
        }
        $nombre_auto = $nombre !== '' ? $nombre : $nombre_auto;
    }
}

$titulo = 'Haircut Home Studio - Entrar';
$seccion = 'public';
$pagina = 'login';
$next_q = urlencode($next);
$es_registro = ($modo === 'registro');
require __DIR__ . '/../includes/header.php';
?>

<section class="auth-card">
<?php if ($es_registro): ?>
<div class="auth-head">
<span class="auth-dot" aria-hidden="true"></span>
<h2>Crear cuenta</h2>
</div>
<p class="auth-sub">Regístrate y reserva con atención personalizada en Melipilla.</p>
<?php if ($error): ?><p class="alert alert-error"><?php echo h($error); ?></p><?php endif; ?>
<form method="post" class="auth-form">
<input type="hidden" name="accion" value="registro">
<input type="hidden" name="next" value="<?php echo h($next); ?>">
<div class="auth-field">
<input type="text" name="nombre" value="<?php echo h($nombre_auto); ?>" readonly placeholder="Nombre">
</div>
<div class="auth-field">
<input type="email" id="reg-email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="Correo electrónico">
</div>
<div class="auth-field">
<input type="password" id="reg-pass" name="password" required minlength="6" placeholder="Contraseña">
</div>
<div class="auth-field">
<input type="password" id="reg-pass2" name="password2" required minlength="6" placeholder="Confirmar contraseña">
</div>
<button type="submit" class="btn btn-primary auth-submit">Crear cuenta</button>
<p class="auth-foot">¿Ya tienes cuenta? <a href="<?php echo h(url('auth/login.php') . '?modo=correo&next=' . $next_q); ?>">Entrar</a></p>
</form>
<?php else: ?>
<div class="auth-head">
<span class="auth-dot" aria-hidden="true"></span>
<h2>Entrar</h2>
</div>
<p class="auth-sub">Ingresa con tu usuario o correo para ver tus reservas y agendar.</p>
<?php if ($error): ?><p class="alert alert-error"><?php echo h($error); ?></p><?php endif; ?>
<form method="post" class="auth-form">
<input type="hidden" name="accion" value="login">
<input type="hidden" name="next" value="<?php echo h($next); ?>">
<div class="auth-field">
<input type="text" id="login-email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="Usuario o correo (ej. ana)">
</div>
<div class="auth-field">
<input type="password" id="login-pass" name="password" required placeholder="Contraseña">
</div>
<button type="submit" class="btn btn-primary auth-submit">Entrar</button>
<p class="auth-foot">¿No tienes cuenta? <a href="<?php echo h(url('auth/login.php') . '?modo=registro&next=' . $next_q); ?>">Crear cuenta</a></p>
</form>
<?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
