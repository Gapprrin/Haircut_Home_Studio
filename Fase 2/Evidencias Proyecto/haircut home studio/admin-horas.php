<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();
actualizar_reservas_realizadas();

$hoy = date('Y-m-d');
$st = db()->query(
    "SELECT r.*, u.nombre AS cliente, s.nombre AS servicio, s.duracion_min
     FROM reservas r
     JOIN usuarios u ON u.id = r.usuario_id
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.fecha >= CURDATE()
       AND r.estado IN ('pendiente', 'confirmada', 'realizada')
     ORDER BY r.fecha, r.hora"
);
$citas = $st->fetchAll();

$por_dia = [];
foreach ($citas as $c) {
    if ($c['fecha'] > $hoy && $c['estado'] === 'realizada') {
        continue;
    }
    $por_dia[$c['fecha']][] = $c;
}

$fechas = array_keys($por_dia);
$fecha_arriba = isset($por_dia[$hoy]) ? $hoy : ($fechas[0] ?? null);
$otras_fechas = array_values(array_filter($fechas, static function ($f) use ($fecha_arriba) {
    return $f !== $fecha_arriba;
}));

function horas_titulo_dia(string $fecha): string
{
    global $MESES_ES, $DIAS_ES;
    $ts = strtotime($fecha);
    $dow = (int) date('N', $ts);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $anio = (int) date('Y', $ts);
    $base = $DIAS_ES[$dow] . ' ' . $d . ' de ' . $MESES_ES[$m];
    if ($anio !== (int) date('Y')) {
        $base .= ' ' . $anio;
    }
    if ($fecha === date('Y-m-d')) {
        return 'Hoy · ' . $base;
    }
    if ($fecha === date('Y-m-d', strtotime('+1 day'))) {
        return 'Mañana · ' . $base;
    }
    return $base;
}

function horas_lista(array $lista): void
{
    echo '<ul class="hours-list">';
    foreach ($lista as $c) {
        $hora = substr((string) $c['hora'], 0, 5);
        echo '<li>';
        echo '<time>' . h($hora) . '</time>';
        echo '<div>';
        echo '<strong>' . h($c['cliente']) . '</strong>';
        echo '<span>' . h($c['servicio']) . ' · ' . h(duracion_texto((int) $c['duracion_min'])) . '</span>';
        echo '</div>';
        echo '<span class="dash-pill st-' . h($c['estado']) . '">' . h(estado_label($c['estado'])) . '</span>';
        echo '</li>';
    }
    echo '</ul>';
}

$titulo = 'Haircut Studio - Horas';
$seccion = 'admin';
$pagina = 'horas';
require __DIR__ . '/includes/header.php';
?>

<?php if (!$fecha_arriba): ?>
<section class="card">
<h2>Horas</h2>
<p>No hay horas agendadas hacia adelante. Las de días que ya terminaron se ocultan solas.</p>
</section>
<?php else:
    $arriba = $por_dia[$fecha_arriba];
    $es_hoy = $fecha_arriba === $hoy;
?>
<section class="card hours-featured">
<p class="hours-kicker"><?php echo $es_hoy ? 'Hoy' : 'Próximo día'; ?></p>
<h2><?php echo h(horas_titulo_dia($fecha_arriba)); ?></h2>
<p class="hint"><?php echo count($arriba); ?> <?php echo count($arriba) === 1 ? 'persona' : 'personas'; ?></p>
<?php horas_lista($arriba); ?>
</section>

<?php if ($otras_fechas): ?>
<section class="card">
<h2>Otros días</h2>
<?php foreach ($otras_fechas as $f): ?>
<div class="hours-day">
<h3><?php echo h(horas_titulo_dia($f)); ?></h3>
<p class="hint"><?php echo count($por_dia[$f]); ?> <?php echo count($por_dia[$f]) === 1 ? 'persona' : 'personas'; ?></p>
<?php horas_lista($por_dia[$f]); ?>
</div>
<?php endforeach; ?>
</section>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
