<?php
/**
 * Funciones compartidas del proyecto.
 * Incluye este archivo al inicio de cada página PHP.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$MESES_ES = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

$DIAS_ES = [
    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
    5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
];

/** Escapa HTML para evitar XSS al imprimir datos de la BD */
function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function mostrar_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $clase = $f['tipo'] === 'ok' ? 'alert-ok' : 'alert-error';
    echo '<div class="alert ' . $clase . '">' . h($f['mensaje']) . '</div>';
}

function usuario_actual(bool $reset = false): ?array
{
    static $cache = false;
    if ($reset) {
        $cache = false;
    }
    if ($cache !== false) {
        return $cache;
    }
    restaurar_invitada();
    if (empty($_SESSION['usuario_id'])) {
        $cache = null;
        return null;
    }
    try {
        $st = db()->prepare('SELECT id, nombre, email, rol, es_invitado FROM usuarios WHERE id = ?');
        $st->execute([$_SESSION['usuario_id']]);
        $cache = $st->fetch() ?: null;
    } catch (Throwable $e) {
        $st = db()->prepare('SELECT id, nombre, email, rol FROM usuarios WHERE id = ?');
        $st->execute([$_SESSION['usuario_id']]);
        $cache = $st->fetch() ?: null;
        if ($cache) {
            $cache['es_invitado'] = 0;
        }
    }
    if (!$cache) {
        unset($_SESSION['usuario_id']);
    }
    return $cache;
}

function secreto_invitada(): string
{
    return hash('sha256', DB_USER . '|' . DB_NAME . '|hhs-guest');
}

function recordar_invitada(int $id): void
{
    $sig = hash_hmac('sha256', (string) $id, secreto_invitada());
    $valor = $id . '.' . $sig;
    setcookie('hhs_guest', $valor, [
        'expires' => time() + 60 * 60 * 24 * 90,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['hhs_guest'] = $valor;
}

function olvidar_invitada(): void
{
    setcookie('hhs_guest', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE['hhs_guest']);
}

function restaurar_invitada(): void
{
    if (!empty($_SESSION['usuario_id'])) {
        return;
    }
    $raw = (string) ($_COOKIE['hhs_guest'] ?? '');
    if (!preg_match('/^(\d+)\.([a-f0-9]{32,})$/', $raw, $m)) {
        return;
    }
    $id = (int) $m[1];
    $esperado = hash_hmac('sha256', (string) $id, secreto_invitada());
    if (!hash_equals($esperado, $m[2])) {
        return;
    }
    try {
        $st = db()->prepare('SELECT id FROM usuarios WHERE id = ? AND es_invitado = 1 LIMIT 1');
        $st->execute([$id]);
        if ($st->fetch()) {
            $_SESSION['usuario_id'] = $id;
        }
    } catch (Throwable $e) {
        // sin esquema aún
    }
}

function crear_invitada(): array
{
    $nombre = nombre_automatico('invitada');
    $email = 'invitada.' . bin2hex(random_bytes(6)) . '@guest.haircut.local';
    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $ins = db()->prepare(
        'INSERT INTO usuarios (nombre, email, password, rol, es_invitado, origen)
         VALUES (?, ?, ?, "cliente", 1, "invitada")'
    );
    $ins->execute([$nombre, $email, $hash]);
    $id = (int) db()->lastInsertId();
    $_SESSION['usuario_id'] = $id;
    recordar_invitada($id);
    $u = usuario_actual(true);
    if (!$u) {
        throw new RuntimeException('No se pudo crear la invitada.');
    }
    return $u;
}

function asegurar_invitada(): array
{
    $u = usuario_actual();
    if ($u && ($u['rol'] ?? '') === 'cliente') {
        if (!empty($u['es_invitado'])) {
            recordar_invitada((int) $u['id']);
        }
        return $u;
    }
    return crear_invitada();
}

function require_cliente(): array
{
    $u = usuario_actual();
    if ($u && $u['rol'] === 'admin') {
        header('Location: admin-dashboard.php');
        exit;
    }
    if (!$u) {
        $u = crear_invitada();
    }
    return $u;
}

function destino_post_login(?array $user = null): string
{
    if ($user && ($user['rol'] ?? '') === 'admin') {
        return 'admin-dashboard.php';
    }
    $next = $_POST['next'] ?? $_GET['next'] ?? 'nueva-reserva.php';
    $next = str_replace(["\r", "\n"], '', (string) $next);
    if (!preg_match('/^(nueva-reserva|mis-reservas|index)\.php(\?.*)?$/', $next)) {
        return 'nueva-reserva.php';
    }
    return $next;
}

function url_reservar(?int $serv = null): string
{
    $dest = 'nueva-reserva.php' . ($serv ? ('?serv=' . $serv) : '');
    $u = usuario_actual();
    if ($u && ($u['rol'] ?? '') === 'admin') {
        return 'admin-dashboard.php';
    }
    return $dest;
}

function nombre_automatico(string $origen = 'invitada'): string
{
    $n = random_int(1000, 9999);
    if ($origen === 'facebook') {
        return 'Invitada FB ' . $n;
    }
    return 'Invitada ' . $n;
}

function duracion_horas(int $minutos): int
{
    return max(1, (int) ceil($minutos / 60));
}

function duracion_texto(int $minutos): string
{
    $h = duracion_horas($minutos);
    return $h === 1 ? '1 h' : $h . ' h';
}

function duracion_aprox(int $minutos): string
{
    $m = max(1, $minutos);
    if ($m % 60 === 0) {
        $h = (int) ($m / 60);
        return $h === 1 ? '1 hora' : $h . ' horas';
    }
    return $m . ' min';
}

function asegurar_esquema(): void
{
    static $ok = false;
    if ($ok) {
        return;
    }
    try {
        $pdo = db();
    } catch (Throwable $e) {
        return;
    }

    $cols = $pdo->query('SHOW COLUMNS FROM usuarios')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('es_invitado', $cols, true)) {
        $pdo->exec('ALTER TABLE usuarios ADD es_invitado TINYINT(1) NOT NULL DEFAULT 0');
    }
    if (!in_array('origen', $cols, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD origen VARCHAR(20) NOT NULL DEFAULT 'email'");
    }

    $scols = $pdo->query('SHOW COLUMNS FROM servicios')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('precio', $scols, true)) {
        $pdo->exec('ALTER TABLE servicios ADD precio DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER duracion_min');
    }

    $pdo->exec("UPDATE servicios s JOIN categorias c ON c.id = s.categoria_id SET s.duracion_min = 60 WHERE c.slug = 'corte'");
    $pdo->exec("UPDATE servicios s JOIN categorias c ON c.id = s.categoria_id SET s.duracion_min = 120 WHERE c.slug = 'color'");

    $pdo->exec("UPDATE servicios SET precio = 15000 WHERE nombre LIKE '%damas%' AND precio = 0");
    $pdo->exec("UPDATE servicios SET precio = 18000 WHERE nombre LIKE '%brushing%' AND precio = 0");
    $pdo->exec("UPDATE servicios SET precio = 12000 WHERE nombre LIKE '%infantil%' AND precio = 0");
    $pdo->exec("UPDATE servicios SET precio = 28000 WHERE nombre LIKE '%global%' AND precio = 0");
    $pdo->exec("UPDATE servicios SET precio = 45000 WHERE nombre LIKE '%Balayage%' AND precio = 0");
    $pdo->exec("UPDATE servicios SET precio = 22000 WHERE nombre LIKE 'Retoque%' AND (precio = 0 OR precio = 15000)");
    $pdo->exec("UPDATE servicios SET precio = 35000 WHERE nombre LIKE 'Color fantas%' AND (precio = 0 OR precio = 15000)");
    $pdo->exec("UPDATE servicios SET precio = 25000 WHERE nombre LIKE '%Olaplex%' AND (precio = 0 OR precio = 15000)");
    $pdo->exec("UPDATE servicios SET precio = 30000 WHERE nombre LIKE '%Botox%' AND (precio = 0 OR precio = 15000)");
    $pdo->exec("UPDATE servicios SET precio = 18000 WHERE nombre LIKE '%Masaje%' AND (precio = 0 OR precio = 15000)");
    $pdo->exec("UPDATE servicios SET precio = 40000 WHERE nombre LIKE '%liso%' AND (precio = 0 OR precio = 15000)");
    $pdo->exec('UPDATE servicios SET precio = 15000 WHERE precio = 0');
    $pdo->exec("UPDATE servicios SET nombre = 'Retoque de raíz' WHERE nombre LIKE 'Retoque de ra%'");
    $pdo->exec("UPDATE servicios SET nombre = 'Color fantasía' WHERE nombre LIKE 'Color fantas%'");
    $pdo->exec("UPDATE categorias SET nombre = 'Corte' WHERE slug = 'corte' AND nombre IN ('Cortes', 'cortes')");

    $ok = true;
}

function require_admin(): array
{
    $u = usuario_actual();
    if (!$u || $u['rol'] !== 'admin') {
        flash('error', 'Acceso solo para administradores.');
        header('Location: login.php');
        exit;
    }
    return $u;
}

function configuracion(): array
{
    $row = db()->query('SELECT * FROM configuracion LIMIT 1')->fetch();
    if (!$row) {
        return [
            'hora_inicio'    => '10:30:00',
            'hora_fin'       => '19:30:00',
            'dias_atencion'  => '2,3,4,5,6',
        ];
    }
    return $row;
}

function dias_atencion_array(): array
{
    $cfg = configuracion();
    $partes = array_filter(array_map('intval', explode(',', $cfg['dias_atencion'])));
    return $partes ?: [1, 2, 3, 4, 5, 6];
}

function es_dia_off(string $fecha): bool
{
    $st = db()->prepare('SELECT 1 FROM dias_off WHERE fecha = ?');
    $st->execute([$fecha]);
    return (bool) $st->fetchColumn();
}

function es_dia_atencion(string $fecha): bool
{
    $n = (int) date('N', strtotime($fecha));
    return in_array($n, dias_atencion_array(), true);
}

function meses_visibles(): array
{
    return db()->query('SELECT anio, mes FROM meses_visibles ORDER BY anio, mes')->fetchAll();
}

function mes_es_visible(int $anio, int $mes): bool
{
    $st = db()->prepare('SELECT 1 FROM meses_visibles WHERE anio = ? AND mes = ?');
    $st->execute([$anio, $mes]);
    return (bool) $st->fetchColumn();
}

function categorias_con_servicios(): array
{
    $cats = db()->query('SELECT * FROM categorias ORDER BY id')->fetchAll();
    $st = db()->prepare('SELECT * FROM servicios WHERE categoria_id = ? AND activo = 1 ORDER BY id');
    foreach ($cats as &$c) {
        $st->execute([$c['id']]);
        $c['servicios'] = $st->fetchAll();
    }
    unset($c);
    return $cats;
}

function servicio_por_id(int $id): ?array
{
    $st = db()->prepare(
        'SELECT s.*, c.nombre AS categoria, c.slug AS categoria_slug
         FROM servicios s
         JOIN categorias c ON c.id = s.categoria_id
         WHERE s.id = ?'
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/**
 * Marca como realizadas las reservas confirmadas cuya hora ya pasó.
 */
function actualizar_reservas_realizadas(): void
{
    db()->exec(
        "UPDATE reservas
         SET estado = 'realizada'
         WHERE estado = 'confirmada'
           AND TIMESTAMP(fecha, hora) < NOW()"
    );
}

function horas_ocupadas(string $fecha, ?int $excepto_id = null): array
{
    $sql = "SELECT r.hora, s.duracion_min
            FROM reservas r
            JOIN servicios s ON s.id = r.servicio_id
            WHERE r.fecha = ? AND r.estado IN ('pendiente', 'confirmada')";
    $params = [$fecha];
    if ($excepto_id) {
        $sql .= ' AND r.id <> ?';
        $params[] = $excepto_id;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    $horas = [];
    foreach ($st->fetchAll() as $r) {
        $start = strtotime(substr($r['hora'], 0, 5));
        $bloques = duracion_horas((int) $r['duracion_min']);
        for ($i = 0; $i < $bloques; $i++) {
            $horas[] = date('H:i', $start + ($i * 3600));
        }
    }
    return array_values(array_unique($horas));
}

function generar_slots(int $duracion_min = 60): array
{
    $cfg = configuracion();
    $ini = substr((string) $cfg['hora_inicio'], 0, 5);
    $fin = substr((string) $cfg['hora_fin'], 0, 5);
    if (!preg_match('/^\d{2}:\d{2}$/', $ini)) {
        $ini = '10:30';
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $fin)) {
        $fin = '19:30';
    }
    $inicio = strtotime('1970-01-01 ' . $ini . ':00');
    $finTs  = strtotime('1970-01-01 ' . $fin . ':00');
    $slots  = [];
    $paso   = 60 * 60;
    $bloque = max(60, $duracion_min) * 60;
    while ($inicio + $bloque <= $finTs) {
        $slots[] = date('H:i', $inicio);
        $inicio += $paso;
    }
    return $slots;
}

function slots_disponibles(string $fecha, int $servicio_id): array
{
    $hoy = date('Y-m-d');
    if ($fecha < $hoy) {
        return [];
    }
    if (!es_dia_atencion($fecha) || es_dia_off($fecha)) {
        return [];
    }
    $anio = (int) date('Y', strtotime($fecha));
    $mes  = (int) date('n', strtotime($fecha));
    if (!mes_es_visible($anio, $mes)) {
        return [];
    }

    $serv = servicio_por_id($servicio_id);
    $dur  = $serv ? (int) $serv['duracion_min'] : 60;
    $bloques = duracion_horas($dur);
    $todos = generar_slots($dur);
    $ocupadas = horas_ocupadas($fecha);

    $libres = [];
    foreach ($todos as $h) {
        $ok = true;
        for ($i = 0; $i < $bloques; $i++) {
            $check = date('H:i', strtotime($h) + ($i * 3600));
            if (in_array($check, $ocupadas, true)) {
                $ok = false;
                break;
            }
        }
        if (!$ok) {
            continue;
        }
        if ($fecha === $hoy && $h <= date('H:i')) {
            continue;
        }
        $libres[] = $h;
    }
    return $libres;
}

function estado_dia_reserva(string $fecha, int $servicio_id): string
{
    $hoy = date('Y-m-d');
    if ($fecha < $hoy) {
        return 'pasado';
    }
    $anio = (int) date('Y', strtotime($fecha));
    $mes  = (int) date('n', strtotime($fecha));
    if (!mes_es_visible($anio, $mes)) {
        return 'libre';
    }
    if (!es_dia_atencion($fecha) || es_dia_off($fecha)) {
        return 'libre';
    }
    if (!slots_disponibles($fecha, $servicio_id)) {
        return 'ocupado';
    }
    return 'disponible';
}

function filtrar_franja(array $horas, string $franja): array
{
    return $horas;
}

/** Filas de calendario (lunes a domingo). Cada celda es el día o null. */
function calendario_mes(int $anio, int $mes): array
{
    $dias_mes = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $mes)));
    $dow = (int) date('N', strtotime(sprintf('%04d-%02d-01', $anio, $mes)));
    $celdas = array_fill(0, $dow - 1, null);
    for ($d = 1; $d <= $dias_mes; $d++) {
        $celdas[] = $d;
    }
    while (count($celdas) % 7 !== 0) {
        $celdas[] = null;
    }
    return array_chunk($celdas, 7);
}

function estado_clase(string $estado): string
{
    $map = [
        'pendiente'  => 'status-pendiente',
        'confirmada' => 'status-confirmada',
        'realizada'  => 'status-realizada',
        'cancelada'  => 'status-cancelada',
        'rechazada'  => 'status-rechazada',
        'off'        => 'status-off',
        'disponible' => 'status-disponible',
    ];
    return $map[$estado] ?? 'status-pendiente';
}

function estado_label(string $estado): string
{
    $map = [
        'pendiente'  => 'Pendiente',
        'confirmada' => 'Confirmada',
        'realizada'  => 'Realizada',
        'cancelada'  => 'Cancelada',
        'rechazada'  => 'Rechazada',
    ];
    return $map[$estado] ?? $estado;
}

function guardar_foto(array $archivo, string $prefijo = 'ref_'): ?string
{
    if (empty($archivo['tmp_name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($archivo['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('La foto no puede superar 3 MB.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    $exts = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($exts[$mime])) {
        throw new RuntimeException('Formato de imagen no permitido.');
    }
    $dir = __DIR__ . '/../uploads/fotos';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $prefijo = preg_replace('/[^a-z0-9_]/i', '', $prefijo) ?: 'ref_';
    $nombre = $prefijo . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $exts[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], $dir . '/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la foto.');
    }
    return $nombre;
}

function borrar_foto(?string $nombre): void
{
    if (!$nombre || !preg_match('/^[a-zA-Z0-9._-]+$/', $nombre)) {
        return;
    }
    $path = __DIR__ . '/../uploads/fotos/' . $nombre;
    if (is_file($path)) {
        @unlink($path);
    }
}

function ultima_reserva_cliente(int $usuario_id): ?array
{
    $st = db()->prepare(
        "SELECT r.*, s.nombre AS servicio, s.categoria_id, c.slug AS categoria_slug
         FROM reservas r
         JOIN servicios s ON s.id = r.servicio_id
         JOIN categorias c ON c.id = s.categoria_id
         WHERE r.usuario_id = ?
         ORDER BY r.creado_en DESC
         LIMIT 1"
    );
    $st->execute([$usuario_id]);
    return $st->fetch() ?: null;
}

function notificar_cliente(array $usuario, string $asunto, string $cuerpo): void
{
    $headers = "MIME-Version: 1.0\r\nContent-type: text/plain; charset=UTF-8\r\nFrom: Haircut Studio <no-reply@localhost>\r\n";
    @mail($usuario['email'], $asunto, $cuerpo, $headers);
}

function precio_clp($valor): string
{
    return '$' . number_format((float) $valor, 0, ',', '.');
}

/** Crea la tabla de productos si aún no existe (instalaciones ya hechas). */
function asegurar_tabla_productos(): void
{
    db()->exec(
        "CREATE TABLE IF NOT EXISTS productos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL,
            descripcion TEXT,
            precio DECIMAL(10,2) NOT NULL DEFAULT 0,
            imagen VARCHAR(255) DEFAULT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            orden INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB"
    );

    $n = (int) db()->query('SELECT COUNT(*) FROM productos')->fetchColumn();
    if ($n > 0) {
        return;
    }

    $ins = db()->prepare(
        'INSERT INTO productos (nombre, descripcion, precio, imagen, activo, orden) VALUES (?, ?, ?, NULL, 1, ?)'
    );
    $demo = [
        ['Shampoo de color', 'Cuidado para cabello teñido', 12990, 1],
        ['Acondicionador nutritivo', 'Hidratación y brillo', 11990, 2],
        ['Tratamiento Olaplex', 'Reparación de fibra capilar', 24990, 3],
        ['Leave-in protector', 'Protección térmica diaria', 9990, 4],
        ['Aceite capilar', 'Nutrición y anti-frizz', 14990, 5],
        ['Ampolleta de reparación', 'Dosis de tratamiento intensivo', 7990, 6],
    ];
    foreach ($demo as $p) {
        $ins->execute([$p[0], $p[1], $p[2], $p[3]]);
    }
}

function productos_activos(): array
{
    asegurar_tabla_productos();
    return db()->query(
        'SELECT * FROM productos WHERE activo = 1 ORDER BY orden, id'
    )->fetchAll();
}

function productos_todos(): array
{
    asegurar_tabla_productos();
    return db()->query(
        'SELECT * FROM productos ORDER BY orden, id'
    )->fetchAll();
}

function siguiente_orden_producto(): int
{
    asegurar_tabla_productos();
    $usados = db()->query('SELECT orden FROM productos ORDER BY orden')->fetchAll(PDO::FETCH_COLUMN);
    $usados = array_map('intval', $usados);
    $n = 1;
    while (in_array($n, $usados, true)) {
        $n++;
    }
    return $n;
}

if (basename($_SERVER['PHP_SELF'] ?? '') !== 'instalar.php') {
    try {
        asegurar_esquema();
    } catch (Throwable $e) {
        // La instalación se encarga si aún no hay base
    }
}
