<?php
/**
 * Funciones compartidas del proyecto.
 * Incluye este archivo al inicio de cada página PHP.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $subs = ['admin' => true, 'usuario' => true, 'auth' => true, 'html' => true];
    $nombre = basename($dir);
    if ($dir !== '' && $dir !== '/' && $dir !== '\\' && $dir !== '.' && isset($subs[$nombre])) {
        $dir = rtrim(str_replace('\\', '/', dirname($dir)), '/');
    }
    if ($dir === '' || $dir === '/' || $dir === '\\' || $dir === '.') {
        $base = '/';
        return $base;
    }
    if ($dir[0] !== '/') {
        $dir = '/' . $dir;
    }
    $base = $dir . '/';
    return $base;
}

function url(string $path = ''): string
{
    if ($path !== '' && (preg_match('#^https?://#i', $path) === 1 || strncmp($path, '//', 2) === 0)) {
        return $path;
    }
    $hash = '';
    $query = '';
    if (strpos($path, '#') !== false) {
        [$path, $hash] = explode('#', $path, 2);
        $hash = '#' . $hash;
    }
    if (strpos($path, '?') !== false) {
        [$path, $query] = explode('?', $path, 2);
        $query = '?' . $query;
    }
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = rtrim(app_base(), '/');
    $href = ($base === '' ? '' : $base) . '/' . $path;
    $href = preg_replace('#/+#', '/', $href);
    if ($href === '' || $href[0] !== '/') {
        $href = '/' . ltrim((string) $href, '/');
    }
    return $href . $query . $hash;
}

function ir(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

function foto_url(string $archivo): string
{
    return url('uploads/fotos/' . ltrim($archivo, '/'));
}

function normalizar_next(string $next): string
{
    $next = str_replace(["\r", "\n"], '', $next);
    $next = ltrim($next, '/');
    $prefijo = trim(app_base(), '/');
    if ($prefijo !== '' && strpos($next, $prefijo . '/') === 0) {
        $next = substr($next, strlen($prefijo) + 1);
    }
    $qs = '';
    $file = $next;
    if (strpos($next, '?') !== false) {
        [$file, $qs] = explode('?', $next, 2);
        $qs = '?' . $qs;
    }
    $file = ltrim($file, '/');
    $alias = [
        'index.php' => 'index.php',
        'nueva-reserva.php' => 'usuario/nueva-reserva.php',
        'mis-reservas.php' => 'usuario/mis-reservas.php',
        'productos.php' => 'usuario/productos.php',
        'dashboard.php' => 'usuario/nueva-reserva.php',
        'usuario/dashboard.php' => 'usuario/nueva-reserva.php',
        'usuario/nueva-reserva.php' => 'usuario/nueva-reserva.php',
        'usuario/mis-reservas.php' => 'usuario/mis-reservas.php',
        'usuario/productos.php' => 'usuario/productos.php',
    ];
    if (!isset($alias[$file])) {
        return 'usuario/nueva-reserva.php';
    }
    $file = $alias[$file];
    return $file === 'index.php' ? $file : $file . $qs;
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
    $auto = $f['tipo'] === 'ok' ? ' data-autohide="2000"' : '';
    echo '<div class="alert ' . $clase . '"' . $auto . '>' . h($f['mensaje']) . '</div>';
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
    if (empty($_SESSION['usuario_id'])) {
        $cache = null;
        return null;
    }
    try {
        $st = db()->prepare('SELECT id, nombre, email, rol FROM usuarios WHERE id = ?');
        $st->execute([$_SESSION['usuario_id']]);
        $cache = $st->fetch() ?: null;
    } catch (Throwable $e) {
        $cache = null;
    }
    if (!$cache) {
        unset($_SESSION['usuario_id']);
    }
    return $cache;
}

function rol_de(?array $u): string
{
    return (string) ($u['rol'] ?? '');
}

function es_admin(?array $u = null): bool
{
    $u = $u ?? usuario_actual();
    return $u !== null && rol_de($u) === 'admin';
}

function es_peluquero(?array $u = null): bool
{
    $u = $u ?? usuario_actual();
    return $u !== null && rol_de($u) === 'peluquero';
}

function es_staff(?array $u = null): bool
{
    return es_admin($u) || es_peluquero($u);
}

function url_inicio_interno(?array $u = null): string
{
    $u = $u ?? usuario_actual();
    if (es_admin($u)) {
        return url('admin/servicios.php');
    }
    if (es_peluquero($u)) {
        return url('admin/dashboard.php');
    }
    return url('index.php');
}

function require_cliente(): array
{
    $u = usuario_actual();
    if ($u && es_staff($u)) {
        ir(url_inicio_interno($u));
    }
    if (!$u) {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'usuario/nueva-reserva.php';
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        $dest = normalizar_next(ltrim($uri, '/') . ($qs !== '' ? ('?' . $qs) : ''));
        ir(url('auth/login.php') . '?next=' . urlencode($dest));
    }
    return $u;
}

function destino_post_login(?array $user = null): string
{
    if ($user && es_staff($user)) {
        return url_inicio_interno($user);
    }
    $next = (string) ($_POST['next'] ?? $_GET['next'] ?? 'usuario/nueva-reserva.php');
    return url(normalizar_next($next));
}

function url_reservar(?int $serv = null): string
{
    $dest = 'usuario/nueva-reserva.php' . ($serv ? ('?serv=' . $serv) : '');
    $u = usuario_actual();
    if ($u && es_staff($u)) {
        return url_inicio_interno($u);
    }
    if (!$u) {
        return url('auth/login.php') . '?next=' . urlencode($dest);
    }
    return url($dest);
}

function nombre_automatico(string $origen = 'email'): string
{
    $n = random_int(1000, 9999);
    return 'Cliente ' . $n;
}

function login_identificador(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '') {
        return '';
    }
    $bajo = strtolower($valor);
    if ($bajo === 'admin') {
        return 'admin@admin.cl';
    }
    if ($bajo === 'ricardo') {
        return 'ricardo@me.com';
    }
    if (strpos($valor, '@') === false) {
        return $valor . '@haircut.cl';
    }
    return $valor;
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

function lugar_reserva(?string $lugar): string
{
    return $lugar === 'domicilio' ? 'domicilio' : 'salon';
}

function lugar_label(?string $lugar): string
{
    return lugar_reserva($lugar) === 'domicilio' ? 'A domicilio' : 'En el salón';
}

function texto_ficha_servicio(array $serv): string
{
    $nombre = trim((string) ($serv['nombre'] ?? ''));
    $desc = trim((string) ($serv['descripcion'] ?? ''));
    if ($nombre === '') {
        return $desc;
    }
    $nomBajo = mb_strtolower($nombre, 'UTF-8');
    $bajo = mb_strtolower($desc, 'UTF-8');
    if ($desc !== '' && str_starts_with($bajo, $nomBajo)) {
        $desc = ltrim(substr($desc, strlen($nombre)));
        $desc = ltrim($desc, " \t:-–—");
    }
    return $nombre . ($desc !== '' ? ': ' . $desc : '');
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

/** Catálogo alineado a la entrevista (tiempos reales en la descripción; duracion_min = bloque de agenda). */
function servicios_desde_entrevista(): array
{
    return [
        [1, 'Corte de damas', '15 a 30 minutos. En la agenda se reserva 1 hora.', 60, 15000],
        [1, 'Corte infantil', 'En la agenda se reserva 1 hora.', 60, 12000],
        [1, 'Lavado y brushing', '25 a 40 minutos. En la agenda se reserva 1 hora.', 60, 16000],
        [1, 'Peinado', '20 a 40 minutos. En la agenda se reserva 1 hora.', 60, 15000],
        [1, 'Corte + lavado', 'A veces también incluye peinado. En la agenda se reserva 1 hora.', 60, 18000],
        [2, 'Cobertura de canas', '1 a 1,5 horas.', 120, 28000],
        [2, 'Retoque de crecimiento', '1 a 1,5 horas.', 120, 22000],
        [2, 'Visos', '2 a 3 horas.', 180, 35000],
        [2, 'Mechas', '2 a 3 horas.', 180, 35000],
        [2, 'Balayage', '3 a 5 horas, a veces hasta 6. El tiempo varía con el cabello.', 300, 45000],
        [2, 'Baby lights', '4 a 5 horas, a veces hasta 6.', 300, 50000],
        [2, 'Color fantasía', 'En la agenda se reservan 4 horas.', 240, 35000],
        [3, 'Masaje capilar', 'Aproximadamente 1 hora.', 60, 18000],
        [3, 'Botox capilar', '1 a 2 horas.', 120, 30000],
        [3, 'Liso permanente', '2 a 3,5 horas. A veces se combina con retoque de color.', 240, 40000],
        [3, 'Maquillaje social', 'Para eventos, de forma ocasional, 30 minutos a 1 hora.', 60, 18000],
        [3, 'Olaplex', 'Reparación de la fibra capilar. Aprox. 1 hora.', 60, 25000],
    ];
}

function hora_cierre_sabado(): string
{
    return '14:30';
}

function hora_cierre_para_fecha(?string $fecha): string
{
    $cfg = configuracion();
    $fin = substr((string) ($cfg['hora_fin'] ?? '19:30:00'), 0, 5);
    if (!preg_match('/^\d{2}:\d{2}$/', $fin)) {
        $fin = '19:30';
    }
    if ($fecha && (int) date('N', strtotime($fecha)) === 6) {
        return hora_cierre_sabado();
    }
    return $fin;
}

const HHS_SCHEMA_VER = 19;

function ruta_schema_ok(): string
{
    return __DIR__ . '/../config/.schema_ok';
}

function esquema_ya_listo(): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }
    $f = ruta_schema_ok();
    $listo = is_file($f) && trim((string) @file_get_contents($f)) === (string) HHS_SCHEMA_VER;
    return $listo;
}

function marcar_esquema_listo(): void
{
    @file_put_contents(ruta_schema_ok(), (string) HHS_SCHEMA_VER);
}

function asegurar_esquema(): void
{
    static $ok = false;
    if ($ok) {
        return;
    }
    if (esquema_ya_listo()) {
        $ok = true;
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
        $scols[] = 'precio';
    }
    if (!in_array('imagen', $scols, true)) {
        $pdo->exec('ALTER TABLE servicios ADD imagen VARCHAR(255) DEFAULT NULL');
    }

    $rcols = $pdo->query('SHOW COLUMNS FROM reservas')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('lugar', $rcols, true)) {
        $pdo->exec("ALTER TABLE reservas ADD lugar ENUM('salon','domicilio') NOT NULL DEFAULT 'salon' AFTER foto");
    }
    $estCol = $pdo->query("SHOW COLUMNS FROM reservas LIKE 'estado'")->fetch();
    $estTipo = (string) ($estCol['Type'] ?? '');
    if (!str_contains($estTipo, 'no_asistio')) {
        $pdo->exec(
            "ALTER TABLE reservas MODIFY estado ENUM('pendiente','confirmada','rechazada','cancelada','realizada','no_asistio') NOT NULL DEFAULT 'pendiente'"
        );
    }

    $ccols = $pdo->query('SHOW COLUMNS FROM categorias')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('imagen', $ccols, true)) {
        $pdo->exec('ALTER TABLE categorias ADD imagen VARCHAR(255) DEFAULT NULL');
    }
    if (!in_array('slug', $ccols, true)) {
        $pdo->exec("ALTER TABLE categorias ADD slug VARCHAR(80) NOT NULL DEFAULT '' AFTER nombre");
        $ccols[] = 'slug';
    }
    $upSlug = $pdo->prepare('UPDATE categorias SET slug = ? WHERE id = ?');
    foreach ($pdo->query('SELECT id, nombre, slug FROM categorias')->fetchAll() as $fila) {
        $slug = trim((string) ($fila['slug'] ?? ''));
        if ($slug !== '') {
            continue;
        }
        $nom = strtolower((string) $fila['nombre']);
        if (str_contains($nom, 'color')) {
            $slug = 'color';
        } elseif (str_contains($nom, 'trat')) {
            $slug = 'tratamientos';
        } elseif (str_contains($nom, 'corte')) {
            $slug = 'corte';
        } else {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $fila['nombre']));
            $slug = trim($slug, '-') ?: ('cat-' . (int) $fila['id']);
        }
        $upSlug->execute([$slug, (int) $fila['id']]);
    }

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

    $pdo->exec("CREATE TABLE IF NOT EXISTS meses_visibles (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      anio SMALLINT UNSIGNED NOT NULL,
      mes TINYINT UNSIGNED NOT NULL,
      UNIQUE KEY uq_mes (anio, mes)
    ) ENGINE=InnoDB");

    asegurar_tabla_productos();
    asegurar_usuario_demo($pdo);
    asegurar_cuentas_staff($pdo);
    asegurar_catalogo_base($pdo);
    try {
        $pdo->exec("UPDATE configuracion SET hora_inicio = '10:30:00', hora_fin = '19:30:00', dias_atencion = '2,3,4,5,6'");
    } catch (Throwable $e) {
        // Sin tabla de configuración aún.
    }
    asegurar_mes_actual_visible();

    try {
        $cfgRow = $pdo->query('SELECT id, dias_atencion FROM configuracion LIMIT 1')->fetch();
        if ($cfgRow) {
            $csv = sanitizar_dias_atencion_csv($cfgRow['dias_atencion'] ?? '');
            if ($csv !== (string) $cfgRow['dias_atencion']) {
                $pdo->prepare('UPDATE configuracion SET dias_atencion = ? WHERE id = ?')->execute([$csv, $cfgRow['id']]);
            }
        }
    } catch (Throwable $e) {
        // La tabla puede no existir en una instalación a medias.
    }

    relacionar_tablas($pdo);
    asegurar_iconos_catalogo($pdo);
    marcar_esquema_listo();
    $ok = true;
}

function tabla_tiene_columna(PDO $pdo, string $tabla, string $columna): bool
{
    $st = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $tabla) . '` LIKE ?');
    $st->execute([$columna]);
    return (bool) $st->fetch();
}

function tabla_tiene_fk(PDO $pdo, string $tabla, string $nombre): bool
{
    $st = $pdo->prepare(
        'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?
           AND CONSTRAINT_TYPE = \'FOREIGN KEY\'
         LIMIT 1'
    );
    $st->execute([$tabla, $nombre]);
    return (bool) $st->fetch();
}

function relacionar_tablas(PDO $pdo): void
{
    $cfgId = (int) ($pdo->query('SELECT id FROM configuracion LIMIT 1')->fetchColumn() ?: 0);
    if ($cfgId < 1) {
        $pdo->exec("INSERT INTO configuracion (hora_inicio, hora_fin, dias_atencion) VALUES ('10:30:00', '19:30:00', '2,3,4,5,6')");
        $cfgId = (int) $pdo->lastInsertId();
    }

    $cats = [];
    foreach ($pdo->query('SELECT id, slug FROM categorias')->fetchAll() as $c) {
        $cats[(string) $c['slug']] = (int) $c['id'];
    }
    $catDefault = (int) ($cats['corte'] ?? $cats['color'] ?? reset($cats) ?: 0);

    if ($catDefault > 0 && tabla_tiene_columna($pdo, 'productos', 'id')) {
        try {
            if (!tabla_tiene_columna($pdo, 'productos', 'categoria_id')) {
                $pdo->exec('ALTER TABLE productos ADD categoria_id INT UNSIGNED NULL AFTER id');
            }
            $pdo->prepare('UPDATE productos SET categoria_id = ? WHERE categoria_id IS NULL OR categoria_id = 0')->execute([$catDefault]);
            $idColor = $cats['color'] ?? $catDefault;
            $idTrat = $cats['tratamientos'] ?? $catDefault;
            $pdo->prepare("UPDATE productos SET categoria_id = ? WHERE nombre LIKE '%color%' OR nombre LIKE '%tinte%'")->execute([$idColor]);
            $pdo->prepare("UPDATE productos SET categoria_id = ? WHERE nombre LIKE '%Olaplex%' OR nombre LIKE '%tratamiento%' OR nombre LIKE '%ampolleta%'")->execute([$idTrat]);
            $pdo->exec('ALTER TABLE productos MODIFY categoria_id INT UNSIGNED NOT NULL');
            if (!tabla_tiene_fk($pdo, 'productos', 'fk_productos_categoria')) {
                $pdo->exec(
                    'ALTER TABLE productos
                     ADD CONSTRAINT fk_productos_categoria
                     FOREIGN KEY (categoria_id) REFERENCES categorias(id)
                     ON DELETE RESTRICT ON UPDATE CASCADE'
                );
            }
        } catch (Throwable $e) {
            // Relación ya aplicada o motor sin permiso para ALTER.
        }
    }

    foreach (['dias_off' => 'fk_dias_off_config', 'meses_visibles' => 'fk_meses_visibles_config'] as $tabla => $fk) {
        try {
            $existe = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($tabla))->fetchColumn();
            if (!$existe) {
                continue;
            }
            if (!tabla_tiene_columna($pdo, $tabla, 'configuracion_id')) {
                $pdo->exec('ALTER TABLE `' . $tabla . '` ADD configuracion_id INT UNSIGNED NULL AFTER id');
            }
            $pdo->prepare('UPDATE `' . $tabla . '` SET configuracion_id = ? WHERE configuracion_id IS NULL OR configuracion_id = 0')->execute([$cfgId]);
            $pdo->exec('ALTER TABLE `' . $tabla . '` MODIFY configuracion_id INT UNSIGNED NOT NULL');
            if (!tabla_tiene_fk($pdo, $tabla, $fk)) {
                $pdo->exec(
                    'ALTER TABLE `' . $tabla . '`
                     ADD CONSTRAINT `' . $fk . '`
                     FOREIGN KEY (configuracion_id) REFERENCES configuracion(id)
                     ON DELETE CASCADE ON UPDATE CASCADE'
                );
            }
        } catch (Throwable $e) {
            // Si la FK ya existía con otro nombre, se deja la relación actual.
        }
    }
}

function asegurar_cuentas_staff(PDO $pdo): void
{
    $col = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch();
    $tipo = strtolower((string) ($col['Type'] ?? ''));
    if (!str_contains($tipo, 'peluquero')) {
        $pdo->exec("ALTER TABLE usuarios MODIFY rol ENUM('cliente','admin','peluquero') NOT NULL DEFAULT 'cliente'");
    }

    $hashAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    $hashPelu = password_hash('kako123', PASSWORD_DEFAULT);

    $porEmail = static function (PDO $pdo, string $email): ?array {
        $st = $pdo->prepare('SELECT id, email, rol, password FROM usuarios WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $row = $st->fetch();
        return $row ?: null;
    };

    $guardar = static function (PDO $pdo, string $email, string $nombre, string $rol, string $hash, string $plain) use ($porEmail): void {
        $row = $porEmail($pdo, $email);
        if ($row) {
            $pass = $row['password'];
            if (!password_verify($plain, (string) $pass)) {
                $pass = $hash;
            }
            $pdo->prepare('UPDATE usuarios SET nombre = ?, rol = ?, password = ? WHERE id = ?')
                ->execute([$nombre, $rol, $pass, (int) $row['id']]);
            return;
        }
        $pdo->prepare(
            'INSERT INTO usuarios (nombre, email, password, rol, es_invitado, origen)
             VALUES (?, ?, ?, ?, 0, "email")'
        )->execute([$nombre, $email, $hash, $rol]);
    };

    $viejo = $porEmail($pdo, 'admin@haircut.cl');
    $ricardo = $porEmail($pdo, 'ricardo@me.com');
    if ($viejo && !$ricardo) {
        $pdo->prepare('UPDATE usuarios SET email = ?, nombre = ?, rol = ?, password = ? WHERE id = ?')
            ->execute(['ricardo@me.com', 'Ricardo', 'peluquero', $hashPelu, (int) $viejo['id']]);
    } else {
        $guardar($pdo, 'ricardo@me.com', 'Ricardo', 'peluquero', $hashPelu, 'kako123');
        $ricardoId = $porEmail($pdo, 'ricardo@me.com');
        if ($viejo && $ricardoId && (int) $viejo['id'] !== (int) $ricardoId['id']) {
            $pdo->prepare('UPDATE usuarios SET rol = "cliente" WHERE id = ? AND email = ?')
                ->execute([(int) $viejo['id'], 'admin@haircut.cl']);
        }
    }

    $guardar($pdo, 'admin@admin.cl', 'Administrador', 'admin', $hashAdmin, 'admin123');
}

function asegurar_usuario_demo(PDO $pdo): void
{
    $st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $st->execute(['ana@haircut.cl']);
    if ($st->fetch()) {
        return;
    }
    $hash = password_hash('ana123', PASSWORD_DEFAULT);
    $ins = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password, rol, es_invitado, origen)
         VALUES (?, ?, ?, "cliente", 0, "email")'
    );
    $ins->execute(['Ana', 'ana@haircut.cl', $hash]);
}

function asegurar_catalogo_base(PDO $pdo): void
{
    $cats = [
        [1, 'Corte', 'corte'],
        [2, 'Color', 'color'],
        [3, 'Tratamientos', 'tratamientos'],
    ];
    $insCat = $pdo->prepare('INSERT INTO categorias (id, nombre, slug) VALUES (?, ?, ?)');
    $upCat = $pdo->prepare('UPDATE categorias SET nombre = ?, slug = ? WHERE id = ?');
    foreach ($cats as $c) {
        $st = $pdo->prepare('SELECT id, nombre, slug FROM categorias WHERE id = ? OR slug = ? LIMIT 1');
        $st->execute([$c[0], $c[2]]);
        $ex = $st->fetch();
        if ($ex) {
            if ((string) $ex['nombre'] !== $c[1] || (string) $ex['slug'] !== $c[2]) {
                $upCat->execute([$c[1], $c[2], (int) $ex['id']]);
            }
        } else {
            $insCat->execute($c);
        }
    }

    $renombres = [
        'Color global' => 'Cobertura de canas',
        'Balayage / Babylight' => 'Balayage',
        'Retoque de raíz' => 'Retoque de crecimiento',
        'Alisado / lisos' => 'Liso permanente',
        'Corte + brushing' => 'Corte + lavado',
    ];
    $stNom = $pdo->prepare('SELECT id FROM servicios WHERE nombre = ? LIMIT 1');
    $upNom = $pdo->prepare('UPDATE servicios SET nombre = ? WHERE id = ?');
    foreach ($renombres as $antes => $despues) {
        $stNom->execute([$despues]);
        if ($stNom->fetch()) {
            continue;
        }
        $stNom->execute([$antes]);
        $fila = $stNom->fetch();
        if ($fila) {
            $upNom->execute([$despues, (int) $fila['id']]);
        }
    }

    $servicios = servicios_desde_entrevista();
    $insServ = $pdo->prepare(
        'INSERT INTO servicios (categoria_id, nombre, descripcion, duracion_min, precio, activo)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $upServ = $pdo->prepare(
        'UPDATE servicios SET categoria_id = ?, descripcion = ?, duracion_min = ?, activo = 1 WHERE id = ?'
    );
    foreach ($servicios as $s) {
        $st = $pdo->prepare('SELECT id FROM servicios WHERE nombre = ? LIMIT 1');
        $st->execute([$s[1]]);
        $ex = $st->fetch();
        if ($ex) {
            $upServ->execute([$s[0], $s[2], $s[3], (int) $ex['id']]);
        } else {
            $insServ->execute($s);
        }
    }

    $productos = [
        ['Shampoo de color', 'Cuidado para cabello teñido', 12990, 1],
        ['Acondicionador nutritivo', 'Hidratación y brillo', 11990, 2],
        ['Tratamiento Olaplex', 'Reparación de fibra capilar', 24990, 3],
        ['Leave-in protector', 'Protección térmica diaria', 9990, 4],
        ['Aceite capilar', 'Nutrición y anti-frizz', 14990, 5],
        ['Ampolleta de reparación', 'Dosis de tratamiento intensivo', 7990, 6],
    ];
    $insProd = $pdo->prepare(
        'INSERT INTO productos (nombre, descripcion, precio, imagen, activo, orden) VALUES (?, ?, ?, NULL, 1, ?)'
    );
    foreach ($productos as $p) {
        $st = $pdo->prepare('SELECT id FROM productos WHERE nombre = ? LIMIT 1');
        $st->execute([$p[0]]);
        if ($st->fetch()) {
            continue;
        }
        $insProd->execute($p);
    }

    asegurar_iconos_catalogo($pdo);
}

function asegurar_iconos_catalogo(PDO $pdo): void
{
    $fotos = __DIR__ . '/../uploads/fotos';
    $iconos = __DIR__ . '/../img/iconos';
    if (!is_dir($fotos)) {
        mkdir($fotos, 0777, true);
    }
    $copias = [
        'cat-corte.png' => 'ico-cat-corte.png',
        'corte.png' => 'ico-corte.png',
        'corte-lavado.png' => 'ico-corte-lavado.png',
        'infantil.png' => 'ico-infantil.png',
        'fantasia.png' => 'ico-fantasia.png',
        'color.png' => 'ico-color.png',
        'liso.png' => 'ico-liso.png',
        'brushing.png' => 'ico-brushing.png',
        'mechas.png' => 'ico-mechas.png',
        'cobertura.png' => 'ico-cobertura.png',
        'peinado.png' => 'ico-peinado.png',
        'maquillaje.png' => 'ico-maquillaje.png',
        'retoque.png' => 'ico-retoque.png',
        'visos.png' => 'ico-visos.png',
        'babylight.png' => 'ico-babylight.png',
        'olaplex.png' => 'ico-olaplex.png',
        'botox.png' => 'ico-botox.png',
        'masaje.png' => 'ico-masaje.png',
        'balayage.png' => 'ico-balayage.png',
        'tratamiento.png' => 'ico-tratamiento.png',
        'lugar-salon.png' => 'ico-lugar-salon.png',
        'lugar-domicilio.png' => 'ico-lugar-domicilio.png',
    ];
    foreach ($copias as $origen => $destino) {
        $src = $iconos . '/' . $origen;
        if (is_file($src)) {
            @copy($src, $fotos . '/' . $destino);
        }
    }

    $serv = [
        'Corte de damas' => 'ico-corte.png',
        'Corte + lavado' => 'ico-corte-lavado.png',
        'Corte infantil' => 'ico-infantil.png',
        'Lavado y brushing' => 'ico-brushing.png',
        'Peinado' => 'ico-peinado.png',
        'Cobertura de canas' => 'ico-cobertura.png',
        'Retoque de crecimiento' => 'ico-retoque.png',
        'Visos' => 'ico-visos.png',
        'Mechas' => 'ico-mechas.png',
        'Balayage' => 'ico-balayage.png',
        'Baby lights' => 'ico-babylight.png',
        'Color fantasía' => 'ico-fantasia.png',
        'Masaje capilar' => 'ico-masaje.png',
        'Botox capilar' => 'ico-botox.png',
        'Liso permanente' => 'ico-liso.png',
        'Maquillaje social' => 'ico-maquillaje.png',
        'Olaplex' => 'ico-olaplex.png',
    ];
    $up = $pdo->prepare('UPDATE servicios SET imagen = ? WHERE nombre = ?');
    foreach ($serv as $nombre => $archivo) {
        if (is_file($fotos . '/' . $archivo)) {
            $up->execute([$archivo, $nombre]);
        }
    }
    $upCat = $pdo->prepare('UPDATE categorias SET imagen = ? WHERE slug = ?');
    $cats = [
        'corte' => 'ico-cat-corte.png',
        'color' => 'ico-color.png',
        'tratamientos' => 'ico-tratamiento.png',
    ];
    foreach ($cats as $slug => $archivo) {
        if (is_file($fotos . '/' . $archivo)) {
            $upCat->execute([$archivo, $slug]);
        }
    }
}

function require_admin(): array
{
    $u = usuario_actual();
    if (!es_admin($u)) {
        flash('error', es_peluquero($u)
            ? 'El catálogo de servicios y productos lo carga el administrador.'
            : 'Acceso solo para administradores.');
        ir(es_peluquero($u) ? url_inicio_interno($u) : url('auth/login.php'));
    }
    return $u;
}

function require_peluquero(): array
{
    $u = usuario_actual();
    if (!es_peluquero($u)) {
        flash('error', es_admin($u)
            ? 'La agenda y las solicitudes las ve el peluquero.'
            : 'Acceso solo para el peluquero.');
        ir(es_admin($u) ? url_inicio_interno($u) : url('auth/login.php'));
    }
    return $u;
}

function configuracion(): array
{
    static $row = false;
    if ($row !== false) {
        return $row;
    }
    try {
        $found = db()->query('SELECT * FROM configuracion LIMIT 1')->fetch();
    } catch (Throwable $e) {
        $found = false;
    }
    $row = $found ?: [
        'hora_inicio'    => '10:30:00',
        'hora_fin'       => '19:30:00',
        'dias_atencion'  => '2,3,4,5,6',
    ];
    return $row;
}

function sanitizar_dias_atencion_csv($dias): string
{
    if (is_string($dias)) {
        $dias = explode(',', $dias);
    }
    $partes = array_values(array_unique(array_filter(array_map('intval', (array) $dias), static function ($n) {
        return $n >= 2 && $n <= 6;
    })));
    sort($partes);
    return $partes ? implode(',', $partes) : '2,3,4,5,6';
}

function dias_atencion_array(): array
{
    static $partes = null;
    if ($partes !== null) {
        return $partes;
    }
    $cfg = configuracion();
    $csv = sanitizar_dias_atencion_csv($cfg['dias_atencion'] ?? '');
    $partes = array_map('intval', explode(',', $csv));
    return $partes;
}

function es_dia_cerrado_fijo(string $fecha): bool
{
    $n = (int) date('N', strtotime($fecha));
    return $n === 1 || $n === 7;
}

function es_dia_off(string $fecha): bool
{
    static $cache = [];
    if (array_key_exists($fecha, $cache)) {
        return $cache[$fecha];
    }
    $ts = strtotime($fecha);
    $anio = (int) date('Y', $ts);
    $mes = (int) date('n', $ts);
    $inicio = sprintf('%04d-%02d-01', $anio, $mes);
    $fin = date('Y-m-t', $ts);
    $st = db()->prepare('SELECT fecha FROM dias_off WHERE fecha BETWEEN ? AND ?');
    $st->execute([$inicio, $fin]);
    $set = array_flip(array_column($st->fetchAll(), 'fecha'));
    $dias = (int) date('t', $ts);
    for ($d = 1; $d <= $dias; $d++) {
        $f = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
        $cache[$f] = isset($set[$f]);
    }
    return $cache[$fecha] ?? false;
}

function es_dia_atencion(string $fecha): bool
{
    $n = (int) date('N', strtotime($fecha));
    return $n !== 1 && $n !== 7 && in_array($n, dias_atencion_array(), true);
}

function meses_visibles(): array
{
    static $lista = null;
    if ($lista !== null) {
        return $lista;
    }
    try {
        $lista = db()->query('SELECT anio, mes FROM meses_visibles ORDER BY anio, mes')->fetchAll();
    } catch (Throwable $e) {
        $lista = [];
    }
    return $lista;
}

function mes_es_visible(int $anio, int $mes): bool
{
    foreach (meses_visibles() as $mv) {
        if ((int) $mv['anio'] === $anio && (int) $mv['mes'] === $mes) {
            return true;
        }
    }
    return false;
}

function ym_indice(int $anio, int $mes): int
{
    return ($anio * 12) + ($mes - 1);
}

function ym_desde_indice(int $idx): array
{
    return [intdiv($idx, 12), ($idx % 12) + 1];
}

/** Marca el mes en curso como visible para las clientas. */
function asegurar_mes_actual_visible(): void
{
    static $hecho = false;
    if ($hecho) {
        return;
    }
    $hecho = true;
    try {
        $st = db()->prepare('INSERT IGNORE INTO meses_visibles (anio, mes) VALUES (?, ?)');
        $st->execute([(int) date('Y'), (int) date('n')]);
    } catch (Throwable $e) {
        $hecho = false;
    }
}

/** La clienta nunca ve meses anteriores; el actual siempre; los siguientes solo si el admin los marcó. */
function cliente_puede_ver_mes(int $anio, int $mes): bool
{
    if ($mes < 1 || $mes > 12 || $anio < 2000) {
        return false;
    }
    asegurar_mes_actual_visible();
    $ahora = ym_indice((int) date('Y'), (int) date('n'));
    $req = ym_indice($anio, $mes);
    if ($req < $ahora) {
        return false;
    }
    if ($req === $ahora) {
        return true;
    }
    return mes_es_visible($anio, $mes);
}

function meses_visibles_cliente(): array
{
    asegurar_mes_actual_visible();
    $ahora = ym_indice((int) date('Y'), (int) date('n'));
    $set = [$ahora => true];
    foreach (meses_visibles() as $mv) {
        $idx = ym_indice((int) $mv['anio'], (int) $mv['mes']);
        if ($idx >= $ahora) {
            $set[$idx] = true;
        }
    }
    ksort($set);
    $out = [];
    foreach (array_keys($set) as $idx) {
        [$y, $m] = ym_desde_indice((int) $idx);
        $out[] = ['anio' => $y, 'mes' => $m];
    }
    return $out;
}

function mes_cliente_vecino(int $anio, int $mes, int $delta): ?array
{
    $lista = meses_visibles_cliente();
    $idx = ym_indice($anio, $mes);
    $pos = null;
    foreach ($lista as $i => $mv) {
        if (ym_indice((int) $mv['anio'], (int) $mv['mes']) === $idx) {
            $pos = $i;
            break;
        }
    }
    if ($pos === null) {
        return $lista[0] ?? null;
    }
    return $lista[$pos + $delta] ?? null;
}

function categorias_con_servicios(): array
{
    $cats = db()->query('SELECT * FROM categorias ORDER BY id')->fetchAll();
    $servs = db()->query('SELECT * FROM servicios WHERE activo = 1 ORDER BY categoria_id, id')->fetchAll();
    $porCat = [];
    foreach ($servs as $s) {
        $porCat[(int) $s['categoria_id']][] = $s;
    }
    foreach ($cats as &$c) {
        $c['servicios'] = $porCat[(int) $c['id']] ?? [];
    }
    unset($c);
    return $cats;
}

function servicio_por_id(int $id): ?array
{
    static $cache = [];
    if (array_key_exists($id, $cache)) {
        return $cache[$id];
    }
    $st = db()->prepare(
        'SELECT s.*, c.nombre AS categoria, c.slug AS categoria_slug
         FROM servicios s
         JOIN categorias c ON c.id = s.categoria_id
         WHERE s.id = ?'
    );
    $st->execute([$id]);
    $cache[$id] = $st->fetch() ?: null;
    return $cache[$id];
}

function clave_icono_categoria(array $c): string
{
    $t = strtolower(($c['slug'] ?? '') . ' ' . ($c['nombre'] ?? ''));
    if (str_contains($t, 'color')) {
        return 'color';
    }
    if (str_contains($t, 'trat')) {
        return 'tratamiento';
    }
    if (str_contains($t, 'corte')) {
        return 'corte_cat';
    }
    return 'servicio';
}

function clave_icono_servicio(array $s): string
{
    $n = strtolower((string) ($s['nombre'] ?? ''));
    if (str_contains($n, 'infantil') || str_contains($n, 'niñ')) {
        return 'infantil';
    }
    if (str_contains($n, 'corte') && str_contains($n, 'lavado')) {
        return 'corte_lavado';
    }
    if (str_contains($n, 'brush') || (str_contains($n, 'lavado') && !str_contains($n, 'corte'))) {
        return 'brushing';
    }
    if (str_contains($n, 'peinado')) {
        return 'peinado';
    }
    if (str_contains($n, 'maquill')) {
        return 'maquillaje';
    }
    if (str_contains($n, 'liso')) {
        return 'liso';
    }
    if (str_contains($n, 'fantas')) {
        return 'fantasia';
    }
    if (str_contains($n, 'canas') || str_contains($n, 'cobertura')) {
        return 'cobertura';
    }
    if (str_contains($n, 'retoque') || str_contains($n, 'crecimiento') || str_contains($n, 'raíz') || str_contains($n, 'raiz')) {
        return 'raiz';
    }
    if (str_contains($n, 'visos')) {
        return 'visos';
    }
    if (str_contains($n, 'mechas')) {
        return 'mechas';
    }
    if (str_contains($n, 'baby')) {
        return 'babylight';
    }
    if (str_contains($n, 'balayage')) {
        return 'balayage';
    }
    if (str_contains($n, 'olaplex')) {
        return 'olaplex';
    }
    if (str_contains($n, 'botox')) {
        return 'botox';
    }
    if (str_contains($n, 'masaje')) {
        return 'masaje';
    }
    if (str_contains($n, 'damas') || str_contains($n, 'corte')) {
        return 'corte';
    }
    return clave_icono_categoria([
        'slug' => (string) ($s['categoria_slug'] ?? ''),
        'nombre' => (string) ($s['categoria'] ?? ''),
    ]);
}

function icono_svg(string $clave): string
{
    $icons = [
        'corte' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.2 3.5a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4zm0 4.6a1.4 1.4 0 1 1 0-2.8 1.4 1.4 0 0 1 0 2.8zm7.6 8a3.2 3.2 0 1 0 .1 6.4 3.2 3.2 0 0 0-.1-6.4zm.1 4.6a1.4 1.4 0 1 1 0-2.8 1.4 1.4 0 0 1 0 2.8zM9.6 8.7l1.4 1.4 8.6-8.6 1.3 1.3-8.6 8.6 1.3 1.3 8.7-8.6 1.3 1.3-10 9.9-3.3-3.3 1.3-1.3-1.4-1.4z"/></svg>',
        'color' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 14.5c0-2.6 2.2-5.8 5-9.2 2.8 3.4 5 6.6 5 9.2a5 5 0 0 1-10 0zm5-11C8.2 8 5.2 12 5.2 14.7a6.8 6.8 0 1 0 13.6 0C18.8 12 15.8 8 12 3.5z"/><circle cx="10.2" cy="15.2" r="1.1" fill="#FABB05"/><circle cx="13.5" cy="16.4" r="1" fill="#fff"/></svg>',
        'tratamiento' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 2h4v3h3v3h-1.2v14H8.2V8H7V5h3V2zm1.6 1.6v1.8H8.6v1h6.8v-1h-3.2V3.6h-1.2zM9.8 9.6v10.8h4.4V9.6H9.8z"/></svg>',
        'brushing' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 8.5h7.2c.6-2 2.4-3.4 4.6-3.4A4.8 4.8 0 0 1 21 10.2c0 2.4-1.8 4.4-4.2 4.7V20H14v-5.1c-.7-.2-1.3-.5-1.8-1H4V8.5zm11.8.2a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4z"/></svg>',
        'infantil' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3.2 13.6 8h4.8L14.8 11l1.6 4.8L12 13.2 7.6 15.8 9.2 11 5.6 8h4.8L12 3.2z"/></svg>',
        'balayage' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3c3.8 0 7 4.4 7 9.2S15.8 21 12 21s-7-4-7-8.8S8.2 3 12 3zm0 1.8c-2.6 0-5.2 3.4-5.2 7.4S9.4 19.2 12 19.2s5.2-3 5.2-7S14.6 4.8 12 4.8z"/><path fill="#FABB05" d="M12 6.2c1.7 0 3.4 2.3 3.4 5.2 0 1.4-.4 2.6-1 3.6-.6-2-1.6-3.8-2.4-5.2-.8 1.4-1.8 3.2-2.4 5.2-.6-1-1-2.2-1-3.6 0-2.9 1.7-5.2 3.4-5.2z"/></svg>',
        'raiz' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3c2.8 2.6 4.6 6 4.6 9.4 0 2.6-1 4.8-2.6 6.2l-1.2-1.4c1.2-1 1.8-2.6 1.8-4.8 0-2.6-1.3-5.4-2.6-7.6-1.3 2.2-2.6 5-2.6 7.6 0 2.2.6 3.8 1.8 4.8l-1.2 1.4C8.4 17.2 7.4 15 7.4 12.4 7.4 9 9.2 5.6 12 3z"/></svg>',
        'fantasia' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.8 13.4 8H19l-4.4 3.2L16.2 17 12 13.8 7.8 17l1.6-5.8L5 8h5.6L12 2.8z"/></svg>',
        'olaplex' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3.2c3.6 3.8 6 7.4 6 10.4A6 6 0 0 1 6 13.6c0-3 2.4-6.6 6-10.4zm0 3.1C9.6 8.8 8 11.2 8 13.6a4 4 0 0 0 8 0c0-2.4-1.6-4.8-4-7.3z"/></svg>',
        'botox' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10.2 2h3.6v2.2h2V6H8.2V4.2h2V2zm-2 6h7.6v14H8.2V8zm1.8 2.2v9.6h4V10.2h-4z"/></svg>',
        'masaje' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9.4 4.2c1.3 0 2.4 1 2.4 2.3v.7h.4c1.3 0 2.4 1 2.4 2.3v.8h.3c1.2 0 2.2 1 2.2 2.2v6.8c0 2-1.6 3.5-3.6 3.5H9.2C6.4 22.8 4 20.4 4 17.2V12c0-1.4 1.1-2.6 2.5-2.6h.5V8.8c0-1.3 1-2.3 2.4-2.3v-2.3z"/></svg>',
        'liso' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 4h10v2.2H7V4zm0 4.6h10V11H7V8.6zm0 4.6h10v2.2H7v-2.2zm0 4.6h10V20H7v-2.2z"/></svg>',
        'servicio' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3.5 13.2 8h4.8L14.8 11l1.3 4.6L12 13.2 7.9 15.6 9.2 11 6 8h4.8L12 3.5z"/></svg>',
    ];
    return $icons[$clave] ?? $icons['servicio'];
}

function html_icono_item(?string $imagen, string $clave): string
{
    $ver = 'iconos-11';
    if ($imagen) {
        return '<img class="icon-pack" src="' . h(foto_url($imagen)) . '?v=' . $ver . '" alt="">';
    }
    static $pack = [
        'corte' => 'img/iconos/corte.png',
        'corte_cat' => 'img/iconos/cat-corte.png',
        'corte_lavado' => 'img/iconos/corte-lavado.png',
        'infantil' => 'img/iconos/infantil.png',
        'fantasia' => 'img/iconos/fantasia.png',
        'color' => 'img/iconos/color.png',
        'liso' => 'img/iconos/liso.png',
        'brushing' => 'img/iconos/brushing.png',
        'mechas' => 'img/iconos/mechas.png',
        'cobertura' => 'img/iconos/cobertura.png',
        'peinado' => 'img/iconos/peinado.png',
        'maquillaje' => 'img/iconos/maquillaje.png',
        'tratamiento' => 'img/iconos/tratamiento.png',
        'raiz' => 'img/iconos/retoque.png',
        'visos' => 'img/iconos/visos.png',
        'babylight' => 'img/iconos/babylight.png',
        'olaplex' => 'img/iconos/olaplex.png',
        'botox' => 'img/iconos/botox.png',
        'masaje' => 'img/iconos/masaje.png',
        'balayage' => 'img/iconos/balayage.png',
    ];
    return isset($pack[$clave])
        ? '<img class="icon-pack" src="' . h(url($pack[$clave])) . '?v=' . $ver . '" alt="">'
        : icono_svg($clave);
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

function hora_fin_bloque(string $hora_inicio, int $duracion_min): string
{
    $start = strtotime(substr($hora_inicio, 0, 5));
    if ($start === false) {
        return substr($hora_inicio, 0, 5);
    }
    return date('H:i', $start + duracion_horas($duracion_min) * 3600);
}

function horas_desde_reserva(array $r): array
{
    $start = strtotime(substr((string) $r['hora'], 0, 5));
    $bloques = duracion_horas((int) $r['duracion_min']);
    $horas = [];
    for ($i = 0; $i < $bloques; $i++) {
        $horas[] = date('H:i', $start + ($i * 3600));
    }
    return $horas;
}

function slug_categoria_item(array $item): string
{
    return strtolower(trim((string) ($item['categoria_slug'] ?? $item['slug'] ?? '')));
}

function es_servicio_color(array $item): bool
{
    return str_contains(slug_categoria_item($item), 'color');
}

/** Corte (o lavado/peinado) de 1 h: cabe en el pose de un color. */
function es_corto_en_pose(array $item): bool
{
    return str_contains(slug_categoria_item($item), 'corte')
        && duracion_horas((int) ($item['duracion_min'] ?? 60)) === 1;
}

/** Última hora de un color de 3 a 5 h (el tinte queda en pose). Los de 2 h no se superponen. */
function es_hora_pose_color(array $reserva, string $hora): bool
{
    if (!es_servicio_color($reserva)) {
        return false;
    }
    $bloques = duracion_horas((int) ($reserva['duracion_min'] ?? 0));
    if ($bloques < 3 || $bloques > 5) {
        return false;
    }
    $horas = horas_desde_reserva($reserva);
    if (!$horas) {
        return false;
    }
    return end($horas) === substr($hora, 0, 5);
}

function hora_es_manana(string $hora): bool
{
    return substr($hora, 0, 5) < '13:00';
}

function es_trabajo_largo(int $duracion_min): bool
{
    return duracion_horas($duracion_min) >= 3;
}

function ocupantes_en_hora(array $reservas, string $hora): array
{
    $hora = substr($hora, 0, 5);
    $out = [];
    foreach ($reservas as $r) {
        if (in_array($hora, horas_desde_reserva($r), true)) {
            $out[] = $r;
        }
    }
    return $out;
}

/**
 * Solo se superpone un corte de 1 h con la última hora de un color.
 * No dos cortes, no dos colores, ni corte sobre la aplicación del color.
 */
function solape_hora_permitido(array $ocupantes, array $nuevo, string $hora): bool
{
    if (count($ocupantes) >= 2) {
        return false;
    }
    if (!$ocupantes) {
        return true;
    }
    $exist = $ocupantes[0];
    $nuevoCorto = es_corto_en_pose($nuevo);
    $existCorto = es_corto_en_pose($exist);
    $nuevoPose = es_hora_pose_color($nuevo, $hora);
    $existPose = es_hora_pose_color($exist, $hora);
    if ($nuevoCorto && $existPose) {
        return true;
    }
    if ($existCorto && $nuevoPose) {
        return true;
    }
    return false;
}

function reservas_del_dia(string $fecha, ?int $excepto_id = null): array
{
    $sql = "SELECT r.fecha, r.hora, s.duracion_min, c.slug AS categoria_slug
            FROM reservas r
            JOIN servicios s ON s.id = r.servicio_id
            JOIN categorias c ON c.id = s.categoria_id
            WHERE r.estado IN ('pendiente', 'confirmada')";
    if ($excepto_id) {
        $st = db()->prepare($sql . ' AND r.fecha = ? AND r.id <> ?');
        $st->execute([$fecha, $excepto_id]);
        return $st->fetchAll();
    }

    static $mesCache = [];
    $ts = strtotime($fecha);
    $anio = (int) date('Y', $ts);
    $mes = (int) date('n', $ts);
    $key = sprintf('%04d-%02d', $anio, $mes);
    if (!isset($mesCache[$key])) {
        $inicio = $key . '-01';
        $fin = date('Y-m-t', $ts);
        $st = db()->prepare($sql . ' AND r.fecha BETWEEN ? AND ?');
        $st->execute([$inicio, $fin]);
        $map = [];
        $dias = (int) date('t', $ts);
        for ($d = 1; $d <= $dias; $d++) {
            $map[sprintf('%04d-%02d-%02d', $anio, $mes, $d)] = [];
        }
        foreach ($st->fetchAll() as $r) {
            $f = (string) $r['fecha'];
            if (!isset($map[$f])) {
                $map[$f] = [];
            }
            $map[$f][] = $r;
        }
        $mesCache[$key] = $map;
    }
    return $mesCache[$key][$fecha] ?? [];
}

function bloque_disponible(string $fecha, array $serv, string $hora_inicio, ?int $excepto_id = null): bool
{
    $reservas = reservas_del_dia($fecha, $excepto_id);
    $nuevo = [
        'hora' => substr($hora_inicio, 0, 5),
        'duracion_min' => (int) ($serv['duracion_min'] ?? 60),
        'categoria_slug' => slug_categoria_item($serv),
    ];
    $bloques = duracion_horas((int) $nuevo['duracion_min']);
    $start = strtotime($nuevo['hora']);
    if ($start === false) {
        return false;
    }
    for ($i = 0; $i < $bloques; $i++) {
        $h = date('H:i', $start + ($i * 3600));
        if (!solape_hora_permitido(ocupantes_en_hora($reservas, $h), $nuevo, $h)) {
            return false;
        }
    }
    return true;
}

/** libre | pose | ocupada, para pintar las pastillas según el servicio que se pide. */
function ocupacion_vista_dia(string $fecha, array $serv): array
{
    $reservas = reservas_del_dia($fecha);
    $vista = [];
    foreach (generar_slots(60, $fecha) as $h) {
        $ocup = ocupantes_en_hora($reservas, $h);
        $n = count($ocup);
        $estado = 'libre';
        if ($n >= 2) {
            $estado = 'ocupada';
        } elseif ($n === 1) {
            $estado = (es_hora_pose_color($ocup[0], $h) && es_corto_en_pose($serv))
                ? 'pose'
                : 'ocupada';
        }
        $vista[$h] = ['n' => $n, 'estado' => $estado];
    }
    return $vista;
}

function generar_slots(int $duracion_min = 60, ?string $fecha = null): array
{
    static $cache = [];
    $sab = $fecha && ((int) date('N', strtotime($fecha)) === 6);
    $key = $duracion_min . ':' . ($sab ? 's' : 'n');
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $cfg = configuracion();
    $ini = substr((string) $cfg['hora_inicio'], 0, 5);
    $fin = hora_cierre_para_fecha($fecha);
    if (!preg_match('/^\d{2}:\d{2}$/', $ini)) {
        $ini = '10:30';
    }
    $inicio = strtotime('1970-01-01 ' . $ini . ':00');
    $finTs  = strtotime('1970-01-01 ' . $fin . ':00');
    $slots  = [];
    $paso   = 3600;
    $bloque = max(60, $duracion_min) * 60;
    while ($inicio + $bloque <= $finTs) {
        $slots[] = date('H:i', $inicio);
        $inicio += $paso;
    }
    $cache[$key] = $slots;
    return $slots;
}

function slots_disponibles(string $fecha, int $servicio_id): array
{
    static $cache = [];
    $ck = $fecha . ':' . $servicio_id;
    if (isset($cache[$ck])) {
        return $cache[$ck];
    }
    $hoy = date('Y-m-d');
    if ($fecha < $hoy || !es_dia_atencion($fecha) || es_dia_off($fecha)) {
        return $cache[$ck] = [];
    }
    $anio = (int) substr($fecha, 0, 4);
    $mes  = (int) substr($fecha, 5, 2);
    if (!cliente_puede_ver_mes($anio, $mes)) {
        return $cache[$ck] = [];
    }

    $serv = servicio_por_id($servicio_id);
    if (!$serv) {
        return $cache[$ck] = [];
    }
    $dur  = (int) $serv['duracion_min'];
    $todos = generar_slots($dur, $fecha);
    $ahora = $fecha === $hoy ? date('H:i') : null;

    $libres = [];
    foreach ($todos as $h) {
        if ($ahora !== null && $h <= $ahora) {
            continue;
        }
        if (bloque_disponible($fecha, $serv, $h)) {
            $libres[] = $h;
        }
    }
    return $cache[$ck] = $libres;
}

function reserva_inicio_ts(array $r): int
{
    $ts = strtotime(($r['fecha'] ?? '') . ' ' . substr((string) ($r['hora'] ?? '00:00'), 0, 5) . ':00');
    return $ts === false ? 0 : $ts;
}

function cliente_puede_cancelar(array $r): bool
{
    if (!in_array((string) ($r['estado'] ?? ''), ['pendiente', 'confirmada'], true)) {
        return false;
    }
    $inicio = reserva_inicio_ts($r);
    return $inicio > 0 && time() <= ($inicio - 10 * 60);
}

function fecha_agenda_texto(string $fecha): string
{
    global $DIAS_ES;
    $hoy = date('Y-m-d');
    if ($fecha === $hoy) {
        return 'hoy';
    }
    if ($fecha === date('Y-m-d', strtotime('+1 day'))) {
        return 'mañana';
    }
    $ts = strtotime($fecha);
    if ($ts === false) {
        return $fecha;
    }
    $dia = $DIAS_ES[(int) date('N', $ts)] ?? '';
    return trim($dia . ' ' . (int) date('j', $ts));
}

/**
 * Hora más cercana del día (mañana si el color es largo) y la siguiente fecha libre.
 */
function sugerencias_hora(int $servicio_id, string $fecha): array
{
    $serv = servicio_por_id($servicio_id);
    $largo = $serv && es_trabajo_largo((int) ($serv['duracion_min'] ?? 0));
    $elegir = static function (array $slots) use ($largo): ?string {
        if (!$slots) {
            return null;
        }
        if ($largo) {
            foreach ($slots as $h) {
                if (hora_es_manana($h)) {
                    return $h;
                }
            }
        }
        return $slots[0];
    };

    $misma = $elegir(slots_disponibles($fecha, $servicio_id));
    $otra = null;
    $base = strtotime($fecha);
    if ($base !== false) {
        for ($i = 1; $i <= 31 && $otra === null; $i++) {
            $f = date('Y-m-d', $base + ($i * 86400));
            $h = $elegir(slots_disponibles($f, $servicio_id));
            if ($h) {
                $otra = ['fecha' => $f, 'hora' => $h];
            }
        }
    }

    return ['misma' => $misma, 'otra' => $otra, 'largo' => $largo];
}

function estado_dia_reserva(string $fecha, int $servicio_id): string
{
    static $cache = [];
    $ck = $fecha . ':' . $servicio_id;
    if (isset($cache[$ck])) {
        return $cache[$ck];
    }
    $hoy = date('Y-m-d');
    if ($fecha < $hoy) {
        return $cache[$ck] = 'pasado';
    }
    $anio = (int) substr($fecha, 0, 4);
    $mes  = (int) substr($fecha, 5, 2);
    if (!cliente_puede_ver_mes($anio, $mes)) {
        return $cache[$ck] = 'libre';
    }
    if (es_dia_cerrado_fijo($fecha) || !es_dia_atencion($fecha) || es_dia_off($fecha) || !slots_disponibles($fecha, $servicio_id)) {
        return $cache[$ck] = 'ocupado';
    }
    return $cache[$ck] = 'disponible';
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
        'no_asistio' => 'status-cancelada',
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
        'realizando' => 'Realizando',
        'finalizado' => 'Finalizado',
        'cancelada'  => 'Cancelada',
        'no_asistio' => 'No llegó',
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
    $orig = strtolower((string) ($archivo['name'] ?? ''));
    if (str_ends_with($orig, '.pdf') || $mime === 'application/pdf') {
        throw new RuntimeException('Solo se permiten imágenes. No se aceptan PDF.');
    }
    $exts = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($exts[$mime])) {
        throw new RuntimeException('Solo se permiten imágenes (JPG, PNG o WEBP). No PDF.');
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

function hora_ampm(string $hhmm): string
{
    if (!preg_match('/^(\d{1,2}):(\d{2})/', $hhmm, $m)) {
        return $hhmm;
    }
    $h = (int) $m[1];
    $min = $m[2];
    $suf = $h >= 12 ? 'p.m.' : 'a.m.';
    $h12 = $h % 12;
    if ($h12 === 0) {
        $h12 = 12;
    }
    return $h12 . ':' . $min . "\u{00A0}" . $suf;
}

/** Crea la tabla de productos si aún no existe (instalaciones ya hechas). */
function asegurar_tabla_productos(): void
{
    static $ok = false;
    if ($ok) {
        return;
    }
    if (esquema_ya_listo()) {
        $ok = true;
        return;
    }

    $intentar = static function (PDO $pdo): bool {
        $hay = $pdo->query("SHOW TABLES LIKE 'productos'")->fetchColumn();
        if ($hay) {
            return true;
        }
        $pdo->exec(
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
        $n = (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
        if ($n > 0) {
            return true;
        }
        $ins = $pdo->prepare(
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
        return true;
    };

    try {
        $ok = $intentar(db());
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '2006') || str_contains($msg, 'gone away')) {
            $ok = $intentar(db(true));
            return;
        }
        throw $e;
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
