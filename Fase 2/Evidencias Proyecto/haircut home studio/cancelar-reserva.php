<?php
require_once __DIR__ . '/includes/funciones.php';
$usuario = require_cliente();

$id = (int) ($_POST['id'] ?? 0);
$st = db()->prepare('SELECT * FROM reservas WHERE id = ? AND usuario_id = ?');
$st->execute([$id, (int) $usuario['id']]);
$reserva = $st->fetch();

if (!$reserva) {
    flash('error', 'No se encontró la reserva.');
    header('Location: mis-reservas.php');
    exit;
}

if (!in_array($reserva['estado'], ['pendiente', 'confirmada'], true)) {
    flash('error', 'Esa reserva ya no se puede cancelar.');
    header('Location: mis-reservas.php');
    exit;
}

$up = db()->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ?");
$up->execute([$id]);
flash('ok', 'Reserva cancelada. El cupo quedó libre.');
header('Location: mis-reservas.php');
exit;
