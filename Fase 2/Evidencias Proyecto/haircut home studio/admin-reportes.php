<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();

$anio = (int) ($_GET['anio'] ?? date('Y'));
$mes  = (int) ($_GET['mes'] ?? date('n'));
if ($mes < 1 || $mes > 12) {
    $mes = (int) date('n');
}

$titulo = 'Haircut Studio - Reportes';
$seccion = 'admin';
$pagina = 'reportes';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
<h2>Reportes de Trabajo Mensual</h2>

<form method="get" action="admin-reporte-export.php">
<div class="field">
<label for="anio">Año</label>
<select id="anio" name="anio">
<?php for ($y = $anio - 1; $y <= $anio + 1; $y++): ?>
<option value="<?php echo $y; ?>" <?php echo $y === $anio ? 'selected' : ''; ?>><?php echo $y; ?></option>
<?php endfor; ?>
</select>
</div>
<div class="field">
<label for="mes">Mes</label>
<select id="mes" name="mes">
<?php for ($i = 1; $i <= 12; $i++): ?>
<option value="<?php echo $i; ?>" <?php echo $i === $mes ? 'selected' : ''; ?>>
<?php echo h($MESES_ES[$i]); ?>
</option>
<?php endfor; ?>
</select>
</div>

<div class="actions">
<button type="submit" name="formato" value="pdf" class="btn-primary">Descargar PDF (imprimir)</button>
<button type="submit" name="formato" value="csv" class="btn-secondary">Descargar planilla (Excel)</button>
</div>
</form>

<p class="hint">El reporte incluye todas las reservas confirmadas, canceladas y rechazadas del mes seleccionado, para auditoría y organización interna.</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
