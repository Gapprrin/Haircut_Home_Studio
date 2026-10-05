<?php
require_once __DIR__ . '/../includes/funciones.php';
$usuario = require_cliente();

$id = (int) ($_POST['id'] ?? 0);
$st = db()->prepare('SELECT * FROM reservas WHERE id = ? AND usuario_id = ?');
$st->execute([$id, (int) $usuario['id']]);
$reserva = $st->fetch();

if (!$reserva) {
    flash('error', 'No se encontró la reserva.');
    ir(url('usuario/mis-reservas.php'));
}

if (!cliente_puede_cancelar($reserva)) {
    flash('error', 'Ya no se puede cancelar: solo hasta 10 minutos antes de la hora.');
    ir(url('usuario/mis-reservas.php'));
}

$up = db()->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ?");
$up->execute([$id]);
flash('ok', 'Reserva cancelada. El cupo quedó libre.');
ir(url('usuario/mis-reservas.php'));
