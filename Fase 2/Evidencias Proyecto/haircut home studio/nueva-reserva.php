<?php
require_once __DIR__ . '/includes/funciones.php';
$usuario = require_cliente();
actualizar_reservas_realizadas();

$catalogo = array_values(array_filter(categorias_con_servicios(), static function ($c) {
    return !empty($c['servicios']);
}));
$serv_id = (int) ($_POST['servicio_id'] ?? $_GET['serv'] ?? 0);
$cat_slug = (string) ($_GET['cat'] ?? '');

$cat_actual = null;
if ($cat_slug !== '') {
    foreach ($catalogo as $c) {
        if ($c['slug'] === $cat_slug || (string) $c['id'] === $cat_slug) {
            $cat_actual = $c;
            break;
        }
    }
}
if ($serv_id > 0) {
    foreach ($catalogo as $c) {
        foreach ($c['servicios'] as $s) {
            if ((int) $s['id'] === $serv_id) {
                if ($cat_actual && (int) $cat_actual['id'] !== (int) $c['id']) {
                    $serv_id = (int) $cat_actual['servicios'][0]['id'];
                } else {
                    $cat_actual = $c;
                }
                break 2;
            }
        }
    }
}
if (!$cat_actual && $catalogo) {
    $cat_actual = $catalogo[0];
}
if ($cat_actual && $serv_id < 1) {
    $serv_id = (int) $cat_actual['servicios'][0]['id'];
}

$serv = servicio_por_id($serv_id);
if (!$serv) {
    flash('error', 'Elige un servicio para continuar.');
    header('Location: index.php');
    exit;
}
if (!$cat_actual) {
    $cat_actual = [
        'id' => (int) $serv['categoria_id'],
        'slug' => (string) ($serv['categoria_slug'] ?? ''),
        'nombre' => (string) ($serv['categoria'] ?? 'Servicio'),
        'servicios' => [$serv],
    ];
}

$anio = (int) ($_GET['anio'] ?? date('Y'));
$mes  = (int) ($_GET['mes'] ?? date('n'));
if ($mes < 1) {
    $mes = 12;
    $anio--;
}
if ($mes > 12) {
    $mes = 1;
    $anio++;
}

$fecha = $_GET['fecha'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = '';
}
$hora = $_GET['hora'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serv_id = (int) ($_POST['servicio_id'] ?? 0);
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $serv = servicio_por_id($serv_id);
    try {
        if (!$serv) {
            throw new RuntimeException('Selecciona un servicio.');
        }
        $libres = slots_disponibles($fecha, $serv_id);
        if (!in_array($hora, $libres, true)) {
            throw new RuntimeException('Esa hora ya no está disponible. Elige otra.');
        }
        $ins = db()->prepare(
            'INSERT INTO reservas (usuario_id, servicio_id, fecha, hora, estado)
             VALUES (?, ?, ?, ?, "pendiente")'
        );
        $ins->execute([(int) $usuario['id'], $serv_id, $fecha, $hora . ':00']);
        flash('ok', 'Reserva enviada. El pago es presencial en el studio.');
        header('Location: mis-reservas.php');
        exit;
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        header('Location: nueva-reserva.php?serv=' . $serv_id . '&fecha=' . urlencode($fecha));
        exit;
    }
}

$slots = $fecha ? slots_disponibles($fecha, $serv_id) : [];
if ($hora !== '' && !in_array($hora, $slots, true)) {
    $hora = '';
}
$todos_slots = generar_slots((int) $serv['duracion_min']);
$semanas = calendario_mes($anio, $mes);
$hoy = date('Y-m-d');
$prev_m = $mes === 1 ? 12 : $mes - 1;
$prev_a = $mes === 1 ? $anio - 1 : $anio;
$next_m = $mes === 12 ? 1 : $mes + 1;
$next_a = $mes === 12 ? $anio + 1 : $anio;

$titulo = 'Seleccionar fecha y hora';
$seccion = 'cliente';
$pagina = 'nueva';
require __DIR__ . '/includes/header.php';

function qs_reserva(array $extra): string
{
    global $serv_id, $anio, $mes, $fecha, $hora, $cat_actual;
    $base = [
        'cat' => $cat_actual['slug'] ?? '',
        'serv' => $serv_id,
        'fecha' => $fecha,
        'hora' => $hora,
        'anio' => $anio,
        'mes' => $mes,
    ];
    foreach ($extra as $k => $v) {
        $base[$k] = $v;
    }
    return 'nueva-reserva.php?' . http_build_query(array_filter($base, static function ($v) {
        return $v !== '' && $v !== null;
    }));
}
?>

<section class="book">
<div class="book-head">
<a class="book-back" href="index.php" aria-label="Volver">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
</a>
<h2>Seleccionar fecha y hora</h2>
</div>

<p class="book-label">Categoría</p>
<div class="book-servicios">
<?php foreach ($catalogo as $c):
    $on = (int) $c['id'] === (int) $cat_actual['id'];
?>
<?php if ($on): ?>
<span class="book-chip is-on"><?php echo h($c['nombre']); ?></span>
<?php else: ?>
<a class="book-chip"
 href="<?php echo h(qs_reserva([
     'cat' => $c['slug'],
     'serv' => (int) $c['servicios'][0]['id'],
     'hora' => '',
 ])); ?>">
<?php echo h($c['nombre']); ?>
</a>
<?php endif; ?>
<?php endforeach; ?>
</div>

<p class="book-label">Tipo</p>
<div class="book-servicios">
<?php foreach ($cat_actual['servicios'] as $s):
    $on = (int) $s['id'] === $serv_id;
?>
<?php if ($on): ?>
<span class="book-chip is-on"><?php echo h($s['nombre']); ?></span>
<?php else: ?>
<a class="book-chip"
 href="<?php echo h(qs_reserva(['serv' => (int) $s['id'], 'hora' => ''])); ?>">
<?php echo h($s['nombre']); ?>
</a>
<?php endif; ?>
<?php endforeach; ?>
</div>

<p class="book-duration">Duración aproximada: <strong><?php echo h(duracion_aprox((int) $serv['duracion_min'])); ?></strong></p>

<div class="book-month">
<a class="book-nav" href="<?php echo h(qs_reserva(['anio' => $prev_a, 'mes' => $prev_m, 'fecha' => '', 'hora' => ''])); ?>" aria-label="Mes anterior">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
</a>
<div class="book-month-title"><?php echo h($MESES_ES[$mes] . ' ' . $anio); ?></div>
<a class="book-nav" href="<?php echo h(qs_reserva(['anio' => $next_a, 'mes' => $next_m, 'fecha' => '', 'hora' => ''])); ?>" aria-label="Mes siguiente">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M9.5 5.5 8.1 6.9 13.2 12l-5.1 5.1 1.4 1.4L16 12z"/></svg>
</a>
</div>

<table class="book-cal">
<tr>
<th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th>
</tr>
<?php foreach ($semanas as $fila): ?>
<tr>
<?php foreach ($fila as $dia): ?>
<td>
<?php if ($dia):
    $f = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    $estado = estado_dia_reserva($f, $serv_id);
    $sel = $f === $fecha;
    $clase = 'd st-' . $estado;
    if ($sel) {
        $clase .= ' is-sel';
    }
?>
<?php if ($estado === 'disponible' && !$sel): ?>
<a class="<?php echo $clase; ?>" href="<?php echo h(qs_reserva(['fecha' => $f, 'hora' => '', 'anio' => $anio, 'mes' => $mes])); ?>"><?php echo $dia; ?></a>
<?php else: ?>
<span class="<?php echo $clase; ?>"><?php echo $dia; ?></span>
<?php endif; ?>
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>

<ul class="book-legend">
<li class="st-disponible">disponible</li>
<li class="st-ocupado">ocupado</li>
<li class="st-libre">día libre</li>
</ul>

<?php if ($fecha): ?>
<h3 class="book-sub">Elige la hora</h3>
<div class="book-hours">
<?php if (!$todos_slots): ?>
<p class="hint">No hay horario configurado para este servicio.</p>
<?php else: ?>
<?php foreach ($todos_slots as $hslot):
    $libre = in_array($hslot, $slots, true);
    $pasada = ($fecha === $hoy && $hslot <= date('H:i'));
?>
<?php if ($libre && $hora !== $hslot): ?>
<a class="hour-pill"
 href="<?php echo h(qs_reserva(['hora' => $hslot])); ?>"><?php echo h($hslot); ?></a>
<?php elseif ($libre): ?>
<span class="hour-pill is-on"><?php echo h($hslot); ?></span>
<?php elseif ($pasada): ?>
<span class="hour-pill is-past"><?php echo h($hslot); ?></span>
<?php else: ?>
<span class="hour-pill is-busy"><?php echo h($hslot); ?></span>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="book-card">
<div class="book-card-row">
<strong><?php echo h($serv['nombre']); ?></strong>
<div class="book-price">
<?php echo precio_clp($serv['precio']); ?>
<small><?php echo h(duracion_texto((int) $serv['duracion_min'])); ?></small>
</div>
</div>
<hr>
<p class="hint">Pago presencial en el studio. No se cobra en línea.</p>
</div>
</section>

<?php if ($fecha && $hora): ?>
<form method="post" class="book-foot">
<input type="hidden" name="servicio_id" value="<?php echo $serv_id; ?>">
<input type="hidden" name="fecha" value="<?php echo h($fecha); ?>">
<input type="hidden" name="hora" value="<?php echo h($hora); ?>">
<div>
<small>1 servicio · <?php echo h(duracion_texto((int) $serv['duracion_min'])); ?> · <?php echo h($hora); ?></small>
<strong><?php echo precio_clp($serv['precio']); ?></strong>
</div>
<button type="submit" class="btn-primary">Continuar</button>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
