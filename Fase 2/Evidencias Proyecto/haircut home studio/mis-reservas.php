<?php
require_once __DIR__ . '/includes/funciones.php';
$usuario = require_cliente();
actualizar_reservas_realizadas();

$st = db()->prepare(
    "SELECT r.*, s.nombre AS servicio
     FROM reservas r
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.usuario_id = ?
     ORDER BY r.fecha DESC, r.hora DESC"
);
$st->execute([(int) $usuario['id']]);
$reservas = $st->fetchAll();

$titulo = 'Haircut Home Studio - Mis reservas';
$seccion = 'cliente';
$pagina = 'mis';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
<h2>Mis Reservas</h2>
<?php if (!empty($usuario['es_invitado'])): ?>
<p class="hint">Estás como invitada. Tus horas quedan guardadas en este dispositivo.</p>
<?php endif; ?>

<?php if (!$reservas): ?>
<p>Aún no tienes reservas. <a href="nueva-reserva.php">Crea la primera</a>.</p>
<?php else: ?>
<table class="stack-table">
<thead>
<tr>
<th>Fecha / Hora</th>
<th>Servicio</th>
<th>Estado</th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php foreach ($reservas as $r): ?>
<tr>
<td data-label="Fecha / Hora"><?php echo date('d/m/Y', strtotime($r['fecha'])); ?> · <?php echo substr($r['hora'], 0, 5); ?></td>
<td data-label="Servicio"><?php echo h($r['servicio']); ?></td>
<td data-label="Estado"><span class="status <?php echo estado_clase($r['estado']); ?>"><?php echo h(estado_label($r['estado'])); ?></span></td>
<td data-label="Acción">
<?php if (in_array($r['estado'], ['pendiente', 'confirmada'], true)): ?>
<form method="post" action="cancelar-reserva.php" onsubmit="return confirm('¿Cancelar esta reserva?');">
<input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
<button type="submit" class="btn-secondary">Cancelar</button>
</form>
<?php else: ?>
<span class="hint">(sin acciones)</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p class="hint">El pago es presencial. Solo se pueden cancelar reservas Confirmadas o Pendientes.</p>
<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
