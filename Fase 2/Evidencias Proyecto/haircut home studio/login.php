<?php
require_once __DIR__ . '/includes/funciones.php';

$modo = $_GET['modo'] ?? $_POST['modo'] ?? '';
$next = $_GET['next'] ?? $_POST['next'] ?? 'nueva-reserva.php';
$error = '';
$nombre_auto = nombre_automatico($modo === 'facebook' ? 'facebook' : 'invitada');

if (usuario_actual()) {
    header('Location: ' . destino_post_login(usuario_actual()));
    exit;
}

if ($modo === 'invitada') {
    asegurar_invitada();
    flash('ok', 'Entraste como invitada. Lo que reserves queda guardado.');
    header('Location: ' . destino_post_login(['rol' => 'cliente']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($accion === 'login') {
        $email = trim($_POST['email'] ?? '');
        $st = db()->prepare('SELECT * FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            olvidar_invitada();
            $_SESSION['usuario_id'] = (int) $user['id'];
            header('Location: ' . destino_post_login($user));
            exit;
        }
        $error = 'Correo o contraseña incorrectos.';
        $modo = 'correo';
    }

    if (in_array($accion, ['facebook', 'registro'], true)) {
        $email = trim($_POST['email'] ?? '');
        $nombre = trim($_POST['nombre'] ?? $nombre_auto);

        if ($nombre === '') {
            $nombre = nombre_automatico($accion === 'facebook' ? 'facebook' : 'invitada');
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
                if ($accion === 'facebook' && password_verify($pass, $existe['password'])) {
                    olvidar_invitada();
                    $_SESSION['usuario_id'] = (int) $existe['id'];
                    header('Location: ' . destino_post_login($existe));
                    exit;
                }
                $error = 'Ese correo ya está registrado. Entra con tu contraseña.';
                $modo = 'correo';
            }
        }

        if ($error === '') {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $origen = $accion === 'facebook' ? 'facebook' : 'email';
            $ins = db()->prepare(
                'INSERT INTO usuarios (nombre, email, password, rol, es_invitado, origen)
                 VALUES (?, ?, ?, "cliente", 0, ?)'
            );
            $ins->execute([$nombre, $email, $hash, $origen]);
            olvidar_invitada();
            $_SESSION['usuario_id'] = (int) db()->lastInsertId();
            flash('ok', 'Listo. Ya puedes reservar.');
            header('Location: ' . destino_post_login(['rol' => 'cliente']));
            exit;
        }
        if ($error !== '' && $modo !== 'correo') {
            $modo = $accion === 'registro' ? 'registro' : $accion;
        }
        $nombre_auto = $nombre !== '' ? $nombre : $nombre_auto;
    }
}

$titulo = 'Haircut Home Studio - Entrar';
$seccion = 'public';
$pagina = 'login';
$next_q = urlencode($next);
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card">
<?php if ($modo === ''): ?>
<div class="auth-head">
<span class="auth-dot" aria-hidden="true"></span>
<h2>Entrar</h2>
</div>
<p class="auth-sub">Reserva ahora: entras sola como invitada y tu hora queda guardada. También puedes usar tu cuenta.</p>
<?php if ($error): ?><p class="alert alert-error"><?php echo h($error); ?></p><?php endif; ?>
<div class="auth-choices">
<a class="auth-choice auth-guest" href="login.php?modo=invitada&amp;next=<?php echo $next_q; ?>">Entrar como invitada</a>
<a class="auth-choice auth-fb" href="login.php?modo=facebook&amp;next=<?php echo $next_q; ?>">Continuar con Facebook</a>
<a class="auth-choice" href="login.php?modo=correo&amp;next=<?php echo $next_q; ?>">Entrar con correo</a>
</div>
<p class="auth-foot">¿Primera vez con correo? <a href="login.php?modo=registro&amp;next=<?php echo $next_q; ?>">Crear cuenta</a></p>

<?php elseif ($modo === 'facebook'): ?>
<div class="auth-head">
<span class="auth-dot" aria-hidden="true"></span>
<h2>Facebook</h2>
</div>
<p class="auth-sub">Usa el correo de tu cuenta de Facebook. El nombre se crea solo; tú eliges la contraseña.</p>
<?php if ($error): ?><p class="alert alert-error"><?php echo h($error); ?></p><?php endif; ?>
<form method="post" class="auth-form">
<input type="hidden" name="accion" value="facebook">
<input type="hidden" name="next" value="<?php echo h($next); ?>">
<div class="auth-field">
<input type="text" name="nombre" value="<?php echo h($nombre_auto); ?>" readonly placeholder="Nombre">
</div>
<div class="auth-field">
<input type="email" id="fb-email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="Correo de Facebook">
</div>
<div class="auth-field">
<input type="password" id="fb-pass" name="password" required minlength="6" placeholder="Contraseña">
</div>
<div class="auth-field">
<input type="password" id="fb-pass2" name="password2" required minlength="6" placeholder="Confirmar contraseña">
</div>
<button type="submit" class="btn btn-primary auth-submit">Entrar y reservar</button>
<p class="auth-foot"><a href="login.php?next=<?php echo $next_q; ?>">Volver</a></p>
</form>

<?php elseif ($modo === 'correo'): ?>
<div class="auth-head">
<span class="auth-dot" aria-hidden="true"></span>
<h2>Entrar</h2>
</div>
<p class="auth-sub">Ingresa con tu correo para ver tus reservas y agendar.</p>
<?php if ($error): ?><p class="alert alert-error"><?php echo h($error); ?></p><?php endif; ?>
<form method="post" class="auth-form">
<input type="hidden" name="accion" value="login">
<input type="hidden" name="next" value="<?php echo h($next); ?>">
<div class="auth-field">
<input type="email" id="login-email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="Correo electrónico">
</div>
<div class="auth-field">
<input type="password" id="login-pass" name="password" required placeholder="Contraseña">
</div>
<button type="submit" class="btn btn-primary auth-submit">Entrar</button>
<p class="auth-foot">¿No tienes cuenta? <a href="login.php?modo=registro&amp;next=<?php echo $next_q; ?>">Crear cuenta</a></p>
<p class="auth-foot"><a href="login.php?next=<?php echo $next_q; ?>">Volver</a></p>
</form>

<?php else: ?>
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
<p class="auth-foot">¿Ya tienes cuenta? <a href="login.php?modo=correo&amp;next=<?php echo $next_q; ?>">Entrar</a></p>
</form>
<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
