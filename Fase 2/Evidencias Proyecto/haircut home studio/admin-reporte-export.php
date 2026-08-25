<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();

$anio = (int) ($_GET['anio'] ?? date('Y'));
$mes  = (int) ($_GET['mes'] ?? date('n'));
$formato = $_GET['formato'] ?? 'csv';
if ($mes < 1 || $mes > 12) {
    $mes = (int) date('n');
}

$st = db()->prepare(
    "SELECT r.fecha, r.hora, r.estado, u.nombre AS cliente, u.email, s.nombre AS servicio, c.nombre AS categoria
     FROM reservas r
     JOIN usuarios u ON u.id = r.usuario_id
     JOIN servicios s ON s.id = r.servicio_id
     JOIN categorias c ON c.id = s.categoria_id
     WHERE YEAR(r.fecha) = ? AND MONTH(r.fecha) = ?
     ORDER BY r.fecha, r.hora"
);
$st->execute([$anio, $mes]);
$filas = $st->fetchAll();
$titulo_mes = $MESES_ES[$mes] . ' ' . $anio;

if ($formato === 'csv') {
    $nombre = 'reporte-haircut-' . $anio . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT) . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Fecha', 'Hora', 'Cliente', 'Email', 'Categoría', 'Servicio', 'Estado'], ';');
    foreach ($filas as $f) {
        fputcsv($out, [
            $f['fecha'],
            substr($f['hora'], 0, 5),
            $f['cliente'],
            $f['email'],
            $f['categoria'],
            $f['servicio'],
            estado_label($f['estado']),
        ], ';');
    }
    fclose($out);
    exit;
}

$titulo = 'Reporte ' . $titulo_mes;
$seccion = 'admin';
$pagina = 'reportes';
require __DIR__ . '/includes/header.php';
?>

<section class="card no-print">
<h2>Reporte <?php echo h($titulo_mes); ?></h2>
<p class="hint">Usa Ctrl+P (o Cmd+P) y elige "Guardar como PDF".</p>
<div class="actions">
<button type="button" class="btn-primary" onclick="window.print()">Imprimir / PDF</button>
<a class="btn btn-secondary" href="admin-reportes.php">Volver</a>
</div>
</section>

<section class="card">
<table>
<tr>
<th>Fecha</th>
<th>Hora</th>
<th>Cliente</th>
<th>Servicio</th>
<th>Estado</th>
</tr>
<?php if (!$filas): ?>
<tr><td colspan="5">Sin reservas en este mes.</td></tr>
<?php else: ?>
<?php foreach ($filas as $f): ?>
<tr>
<td><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></td>
<td><?php echo substr($f['hora'], 0, 5); ?></td>
<td><?php echo h($f['cliente']); ?></td>
<td><?php echo h($f['categoria'] . ' — ' . $f['servicio']); ?></td>
<td><?php echo h(estado_label($f['estado'])); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</table>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
