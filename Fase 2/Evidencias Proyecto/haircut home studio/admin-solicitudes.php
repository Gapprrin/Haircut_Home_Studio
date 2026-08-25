<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();
actualizar_reservas_realizadas();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    $st = db()->prepare(
        "SELECT r.*, u.nombre, u.email, s.nombre AS servicio
         FROM reservas r
         JOIN usuarios u ON u.id = r.usuario_id
         JOIN servicios s ON s.id = r.servicio_id
         WHERE r.id = ? AND r.estado = 'pendiente'"
    );
    $st->execute([$id]);
    $reserva = $st->fetch();

    if (!$reserva) {
        flash('error', 'La solicitud ya no está pendiente.');
        header('Location: admin-solicitudes.php');
        exit;
    }

    if ($accion === 'confirmar') {
        db()->prepare("UPDATE reservas SET estado = 'confirmada' WHERE id = ?")->execute([$id]);
        notificar_cliente(
            $reserva,
            'Reserva confirmada — Haircut Studio',
            "Hola {$reserva['nombre']},\n\nTu reserva de {$reserva['servicio']} el {$reserva['fecha']} a las " . substr($reserva['hora'], 0, 5) . " fue CONFIRMADA.\n\nHaircut Studio"
        );
        flash('ok', 'Solicitud confirmada. Se intentó notificar al cliente por correo.');
    } elseif ($accion === 'rechazar') {
        db()->prepare("UPDATE reservas SET estado = 'rechazada' WHERE id = ?")->execute([$id]);
        notificar_cliente(
            $reserva,
            'Reserva rechazada — Haircut Studio',
            "Hola {$reserva['nombre']},\n\nTu solicitud de {$reserva['servicio']} el {$reserva['fecha']} no pudo confirmarse. El cupo quedó libre.\n\nHaircut Studio"
        );
        flash('ok', 'Solicitud rechazada. El cupo quedó libre.');
    }

    header('Location: admin-solicitudes.php');
    exit;
}

$st = db()->query(
    "SELECT r.*, u.nombre AS cliente, s.nombre AS servicio
     FROM reservas r
     JOIN usuarios u ON u.id = r.usuario_id
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.estado = 'pendiente'
     ORDER BY r.fecha, r.hora"
);
$solicitudes = $st->fetchAll();

$titulo = 'Haircut Studio - Solicitudes';
$seccion = 'admin';
$pagina = 'solicitudes';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
<h2>Solicitudes Pendientes</h2>

<?php if (!$solicitudes): ?>
<p>No hay solicitudes pendientes.</p>
<?php else: ?>
<table class="stack-table">
<thead>
<tr>
<th>Cliente</th>
<th>Servicio</th>
<th>Fecha / Hora</th>
<th>Foto</th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php foreach ($solicitudes as $s): ?>
<tr>
<td data-label="Cliente"><?php echo h($s['cliente']); ?></td>
<td data-label="Servicio"><?php echo h($s['servicio']); ?></td>
<td data-label="Fecha / Hora"><?php echo date('d/m', strtotime($s['fecha'])); ?> &middot; <?php echo substr($s['hora'], 0, 5); ?></td>
<td data-label="Foto">
<?php if ($s['foto']): ?>
<a href="uploads/fotos/<?php echo h($s['foto']); ?>" target="_blank">Ver foto</a>
<?php else: ?>
<span class="hint">(sin foto)</span>
<?php endif; ?>
</td>
<td data-label="Acción">
<div class="actions">
<form method="post">
<input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
<input type="hidden" name="accion" value="confirmar">
<button type="submit" class="btn-primary">Confirmar</button>
</form>
<form method="post">
<input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
<input type="hidden" name="accion" value="rechazar">
<button type="submit" class="btn-secondary">Rechazar</button>
</form>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p class="hint">Al confirmar, la hora queda reservada de forma definitiva. Al rechazar, el cupo se libera de inmediato.</p>
<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
