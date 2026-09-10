<?php
require_once __DIR__ . '/../includes/funciones.php';
$usuario = require_cliente();
actualizar_reservas_realizadas();

$st = db()->prepare(
    "SELECT r.*, s.nombre AS servicio
     FROM reservas r
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.usuario_id = ?
       AND r.estado NOT IN ('cancelada', 'rechazada', 'no_asistio')
     ORDER BY r.fecha DESC, r.hora DESC"
);
$st->execute([(int) $usuario['id']]);
$reservas = $st->fetchAll();

$titulo = 'Haircut Home Studio - Mis reservas';
$seccion = 'cliente';
$pagina = 'mis';
require __DIR__ . '/../includes/header.php';
?>

<section class="card">
<h2>Mis Reservas</h2>

<?php if (!$reservas): ?>
<p>Aún no tienes reservas. <a href="<?php echo h(url('usuario/nueva-reserva.php')); ?>">Crea la primera</a>.</p>
<?php else: ?>
<table class="stack-table">
<thead>
<tr>
<th>Fecha / Hora</th>
<th>Servicio</th>
<th>Lugar</th>
<th>Estado</th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php foreach ($reservas as $r): ?>
<tr>
<td data-label="Fecha / Hora"><?php echo date('d/m/Y', strtotime($r['fecha'])); ?> · <?php echo substr($r['hora'], 0, 5); ?></td>
<td data-label="Servicio"><?php echo h($r['servicio']); ?></td>
<td data-label="Lugar"><?php echo h(lugar_label($r['lugar'] ?? 'salon')); ?></td>
<td data-label="Estado"><span class="status <?php echo estado_clase($r['estado']); ?>"><?php echo h(estado_label($r['estado'])); ?></span></td>
<td data-label="Acción">
<?php if (cliente_puede_cancelar($r)): ?>
<form method="post" action="<?php echo h(url('usuario/cancelar-reserva.php')); ?>" onsubmit="return confirm('¿Cancelar esta reserva?');">
<input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
<button type="submit" class="btn-secondary">Cancelar</button>
</form>
<?php elseif (in_array($r['estado'], ['pendiente', 'confirmada'], true)): ?>
<span class="hint">Ya no se puede cancelar (faltan menos de 10 min)</span>
<?php else: ?>
<span class="hint">(sin acciones)</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p class="hint">El pago es presencial. Puedes cancelar hasta 10 minutos antes de la hora.</p>
<?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
