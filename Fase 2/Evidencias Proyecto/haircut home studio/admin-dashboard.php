<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();
actualizar_reservas_realizadas();

$pendientes = (int) db()->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
$hoy_n = (int) db()->query(
    "SELECT COUNT(*) FROM reservas
     WHERE fecha = CURDATE() AND estado IN ('pendiente', 'confirmada', 'realizada')"
)->fetchColumn();
$confirmadas_mes = (int) db()->query(
    "SELECT COUNT(*) FROM reservas
     WHERE estado IN ('confirmada', 'realizada')
       AND YEAR(fecha) = YEAR(CURDATE())
       AND MONTH(fecha) = MONTH(CURDATE())"
)->fetchColumn();
$ingresos_mes = (float) db()->query(
    "SELECT COALESCE(SUM(s.precio), 0)
     FROM reservas r
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.estado IN ('confirmada', 'realizada')
       AND YEAR(r.fecha) = YEAR(CURDATE())
       AND MONTH(r.fecha) = MONTH(CURDATE())"
)->fetchColumn();
$bajas_mes = (int) db()->query(
    "SELECT COUNT(*) FROM reservas
     WHERE estado IN ('cancelada', 'rechazada')
       AND YEAR(fecha) = YEAR(CURDATE())
       AND MONTH(fecha) = MONTH(CURDATE())"
)->fetchColumn();

$meses_chart = [];
$cursor = new DateTime('first day of this month');
$cursor->modify('-5 months');
for ($i = 0; $i < 6; $i++) {
    $key = $cursor->format('Y-n');
    $meses_chart[$key] = [
        'label' => $MESES_ES[(int) $cursor->format('n')],
        'anio' => $cursor->format('Y'),
        'total' => 0,
        'ok' => 0,
    ];
    $cursor->modify('+1 month');
}

$st = db()->query(
    "SELECT YEAR(fecha) AS y, MONTH(fecha) AS m, estado, COUNT(*) AS n
     FROM reservas
     WHERE fecha >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
     GROUP BY y, m, estado"
);
foreach ($st as $row) {
    $key = (int) $row['y'] . '-' . (int) $row['m'];
    if (!isset($meses_chart[$key])) {
        continue;
    }
    $n = (int) $row['n'];
    $meses_chart[$key]['total'] += $n;
    if (in_array($row['estado'], ['confirmada', 'realizada'], true)) {
        $meses_chart[$key]['ok'] += $n;
    }
}
$max_mes = 1;
foreach ($meses_chart as $m) {
    $max_mes = max($max_mes, (int) $m['total']);
}

$top_servicios = db()->query(
    "SELECT s.nombre, COUNT(*) AS n
     FROM reservas r
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.estado IN ('pendiente', 'confirmada', 'realizada')
       AND r.fecha >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
     GROUP BY s.id, s.nombre
     ORDER BY n DESC
     LIMIT 5"
)->fetchAll();
$max_top = 1;
foreach ($top_servicios as $t) {
    $max_top = max($max_top, (int) $t['n']);
}

$proximas = db()->query(
    "SELECT r.fecha, r.hora, r.estado, u.nombre AS cliente, s.nombre AS servicio
     FROM reservas r
     JOIN usuarios u ON u.id = r.usuario_id
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.fecha >= CURDATE()
       AND r.estado IN ('pendiente', 'confirmada')
     ORDER BY r.fecha, r.hora
     LIMIT 8"
)->fetchAll();

$estados_mes = db()->query(
    "SELECT estado, COUNT(*) AS n
     FROM reservas
     WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())
     GROUP BY estado"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$total_estados = max(1, array_sum(array_map('intval', $estados_mes)));

$titulo = 'Haircut Studio - Panel Admin';
$seccion = 'admin';
$pagina = 'panel';
require __DIR__ . '/includes/header.php';
?>

<section class="dash-head">
<div>
<p class="dash-kicker">Panel</p>
<h2>Resumen del studio</h2>
<p class="hint"><?php echo h($MESES_ES[(int) date('n')] . ' ' . date('Y')); ?> · Haircut Home Studio</p>
</div>
</section>

<section class="dash-kpis">
<article class="dash-kpi">
<p class="dash-kpi-label">Pendientes</p>
<p class="dash-kpi-value"><?php echo $pendientes; ?></p>
<p class="hint">Solicitudes por confirmar</p>
</article>
<article class="dash-kpi">
<p class="dash-kpi-label">Hoy</p>
<p class="dash-kpi-value"><?php echo $hoy_n; ?></p>
<p class="hint">Citas de este día</p>
</article>
<article class="dash-kpi">
<p class="dash-kpi-label">Confirmadas</p>
<p class="dash-kpi-value"><?php echo $confirmadas_mes; ?></p>
<p class="hint">Este mes</p>
</article>
<article class="dash-kpi dash-kpi-accent">
<p class="dash-kpi-label">Ingresos</p>
<p class="dash-kpi-value"><?php echo precio_clp($ingresos_mes); ?></p>
<p class="hint">Estimado del mes</p>
</article>
</section>

<div class="dash-grid">
<section class="card dash-card">
<h3>Reservas últimos 6 meses</h3>
<div class="dash-bars" aria-label="Reservas por mes">
<?php foreach ($meses_chart as $m):
    $h = (int) round(((int) $m['total'] / $max_mes) * 100);
    $h_ok = (int) $m['total'] > 0 ? (int) round(((int) $m['ok'] / $max_mes) * 100) : 0;
?>
<div class="dash-bar-col">
<div class="dash-bar-track">
<span class="dash-bar dash-bar-all" style="height: <?php echo $h; ?>%"></span>
<span class="dash-bar dash-bar-ok" style="height: <?php echo $h_ok; ?>%"></span>
</div>
<strong><?php echo (int) $m['total']; ?></strong>
<small><?php echo h($m['label']); ?></small>
</div>
<?php endforeach; ?>
</div>
<ul class="dash-legend">
<li><span class="swatch swatch-all"></span> Total</li>
<li><span class="swatch swatch-ok"></span> Confirmadas / realizadas</li>
</ul>
</section>

<section class="card dash-card">
<h3>Servicios más pedidos</h3>
<?php if (!$top_servicios): ?>
<p class="hint">Aún no hay suficientes reservas para este gráfico.</p>
<?php else: ?>
<ul class="dash-ranks">
<?php foreach ($top_servicios as $t):
    $pct = (int) round(((int) $t['n'] / $max_top) * 100);
?>
<li>
<div class="dash-rank-row">
<span><?php echo h($t['nombre']); ?></span>
<strong><?php echo (int) $t['n']; ?></strong>
</div>
<span class="dash-rank-bar" style="width: <?php echo $pct; ?>%"></span>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>
</div>

<div class="dash-grid dash-grid-alt">
<section class="card dash-card">
<h3>Estado del mes</h3>
<?php
$orden_est = ['pendiente', 'confirmada', 'realizada', 'cancelada', 'rechazada'];
?>
<ul class="dash-status">
<?php foreach ($orden_est as $est):
    $n = (int) ($estados_mes[$est] ?? 0);
    $pct = (int) round(($n / $total_estados) * 100);
?>
<li>
<div class="dash-rank-row">
<span><?php echo h(estado_label($est)); ?></span>
<strong><?php echo $n; ?></strong>
</div>
<span class="dash-rank-bar st-<?php echo h($est); ?>" style="width: <?php echo $pct; ?>%"></span>
</li>
<?php endforeach; ?>
</ul>
<p class="hint">Canceladas o rechazadas este mes: <?php echo $bajas_mes; ?></p>
</section>

<section class="card dash-card">
<h3>Próximas citas</h3>
<?php if (!$proximas): ?>
<p class="hint">No hay citas próximas pendientes o confirmadas.</p>
<?php else: ?>
<ul class="dash-agenda">
<?php foreach ($proximas as $c): ?>
<li>
<div>
<strong><?php echo h($c['cliente']); ?></strong>
<span><?php echo h($c['servicio']); ?></span>
</div>
<div class="dash-agenda-meta">
<time><?php echo date('d/m', strtotime($c['fecha'])); ?> · <?php echo substr($c['hora'], 0, 5); ?></time>
<span class="dash-pill st-<?php echo h($c['estado']); ?>"><?php echo h(estado_label($c['estado'])); ?></span>
</div>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
