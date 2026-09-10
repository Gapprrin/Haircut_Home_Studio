<?php
require_once __DIR__ . '/../includes/funciones.php';
require_peluquero();
actualizar_reservas_realizadas();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['accion'] ?? '', ['borrar', 'noshow'], true)) {
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $nuevo = ($_POST['accion'] === 'noshow') ? 'no_asistio' : 'cancelada';
        $st = db()->prepare(
            "UPDATE reservas SET estado = ?
             WHERE id IN ($ph) AND estado IN ('confirmada', 'realizada')"
        );
        $st->execute(array_merge([$nuevo], $ids));
        flash('ok', $nuevo === 'no_asistio' ? 'Marcada como no llegó. Queda en el informe.' : 'Hora quitada de la agenda.');
    }
    ir(url('admin/horas.php'));
}

$hoy = date('Y-m-d');
$st = db()->query(
    "SELECT r.*, u.nombre AS cliente, s.nombre AS servicio, s.duracion_min
     FROM reservas r
     JOIN usuarios u ON u.id = r.usuario_id
     JOIN servicios s ON s.id = r.servicio_id
     WHERE r.fecha >= CURDATE()
       AND r.estado IN ('confirmada', 'realizada')
     ORDER BY r.fecha, r.hora, r.id"
);
$citas = $st->fetchAll();

function horas_bloque_fin(string $fecha, string $hora, int $duracion_min): array
{
    $inicio_ts = strtotime($fecha . ' ' . substr($hora, 0, 5) . ':00');
    $min = max(60, $duracion_min);
    $fin_ts = $inicio_ts + ($min * 60);
    return [$inicio_ts, $fin_ts];
}

function horas_estado_ui(int $inicio_ts, int $fin_ts, string $estado_db): string
{
    $now = time();
    if ($now >= $fin_ts + 300) {
        return 'oculto';
    }
    if ($now >= $fin_ts) {
        return 'finalizado';
    }
    if ($now >= $inicio_ts) {
        return 'realizando';
    }
    return $estado_db;
}

function horas_agrupar(array $lista): array
{
    $out = [];
    foreach ($lista as $c) {
        [$inicio_ts, $fin_ts] = horas_bloque_fin($c['fecha'], (string) $c['hora'], (int) $c['duracion_min']);
        $item = [
            'ids' => [(int) $c['id']],
            'usuario_id' => (int) $c['usuario_id'],
            'servicio_id' => (int) $c['servicio_id'],
            'cliente' => $c['cliente'],
            'servicio' => $c['servicio'],
            'lugar' => $c['lugar'] ?? 'salon',
            'inicio_ts' => $inicio_ts,
            'fin_ts' => $fin_ts,
            'estado' => $c['estado'],
        ];
        $n = count($out);
        if ($n > 0) {
            $last = &$out[$n - 1];
            if (
                $last['usuario_id'] === $item['usuario_id']
                && $last['servicio_id'] === $item['servicio_id']
                && $last['fin_ts'] === $item['inicio_ts']
            ) {
                $last['ids'][] = $item['ids'][0];
                $last['fin_ts'] = $item['fin_ts'];
                unset($last);
                continue;
            }
            unset($last);
        }
        $out[] = $item;
    }
    return array_values(array_filter($out, static function ($item) {
        return horas_estado_ui($item['inicio_ts'], $item['fin_ts'], $item['estado']) !== 'oculto';
    }));
}

$por_dia = [];
foreach ($citas as $c) {
    if ($c['fecha'] > $hoy && $c['estado'] === 'realizada') {
        continue;
    }
    $por_dia[$c['fecha']][] = $c;
}
foreach ($por_dia as $f => $lista) {
    $por_dia[$f] = horas_agrupar($lista);
    if (!$por_dia[$f]) {
        unset($por_dia[$f]);
    }
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
        $ui = horas_estado_ui($c['inicio_ts'], $c['fin_ts'], $c['estado']);
        $rango = date('H:i', $c['inicio_ts']) . ' – ' . date('H:i', $c['fin_ts']);
        $mins = max(1, (int) round(($c['fin_ts'] - $c['inicio_ts']) / 60));
        echo '<li class="hours-item" data-start="' . h(date('Y-m-d\TH:i:s', $c['inicio_ts'])) . '" data-end="' . h(date('Y-m-d\TH:i:s', $c['fin_ts'])) . '" data-base="' . h($c['estado']) . '">';
        echo '<div class="hours-when">';
        echo '<time>' . h($rango) . '</time>';
        $ya_empezo = time() >= (int) $c['inicio_ts'];
        $accion_del = $ya_empezo ? 'noshow' : 'borrar';
        $txt_del = $ya_empezo ? 'No llegó' : 'Quitar de la agenda';
        echo '<form method="post" class="hours-del" onsubmit="return confirm(\'' . ($ya_empezo ? '¿Marcar que no llegó? Queda en el informe.' : '¿Quitar esta hora de la agenda?') . '\');">';
        echo '<input type="hidden" name="accion" value="' . $accion_del . '">';
        foreach ($c['ids'] as $id) {
            echo '<input type="hidden" name="ids[]" value="' . (int) $id . '">';
        }
        echo '<button type="submit" class="hours-del-btn" title="' . h($txt_del) . '" aria-label="' . h($txt_del) . '">';
        echo '<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path fill="currentColor" d="M6 7h12v2H6V7zm2 3h8l-.7 10H8.7L8 10zm3-6h2l1 2h4v2H6V6h4l1-2z"/></svg>';
        echo '</button></form>';
        echo '</div>';
        echo '<div>';
        echo '<strong>' . h($c['cliente']) . '</strong>';
        echo '<span>' . h($c['servicio']) . ' · ' . h(lugar_label($c['lugar'] ?? 'salon')) . ' · ' . h(duracion_texto($mins)) . '</span>';
        echo '</div>';
        echo '<span class="dash-pill hours-status st-' . h($ui) . '">' . h(estado_label($ui)) . '</span>';
        echo '</li>';
    }
    echo '</ul>';
}

$titulo = 'Haircut Studio - Horas';
$seccion = 'admin';
$pagina = 'horas';
require __DIR__ . '/../includes/header.php';
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
<section class="card hours-featured" data-hours-day>
<p class="hours-kicker"><?php echo $es_hoy ? 'Hoy' : 'Próximo día'; ?></p>
<h2><?php echo h(horas_titulo_dia($fecha_arriba)); ?></h2>
<p class="hint hours-count"><?php echo count($arriba); ?> <?php echo count($arriba) === 1 ? 'persona' : 'personas'; ?></p>
<?php horas_lista($arriba); ?>
</section>

<?php if ($otras_fechas): ?>
<section class="card">
<h3>Otros días</h3>
<?php foreach ($otras_fechas as $f): ?>
<div class="hours-day" data-hours-day>
<h3><?php echo h(horas_titulo_dia($f)); ?></h3>
<p class="hint hours-count"><?php echo count($por_dia[$f]); ?> <?php echo count($por_dia[$f]) === 1 ? 'persona' : 'personas'; ?></p>
<?php horas_lista($por_dia[$f]); ?>
</div>
<?php endforeach; ?>
</section>
<?php endif; ?>
<?php endif; ?>

<script>
(function () {
  var labels = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    realizada: 'Realizada',
    realizando: 'Realizando',
    finalizado: 'Finalizado'
  };

  function parseLocal(value) {
    var p = (value || '').split(/[-T:]/);
    if (p.length < 5) return null;
    return new Date(+p[0], +p[1] - 1, +p[2], +p[3], +p[4], +(p[5] || 0));
  }

  function tick() {
    var now = Date.now();
    document.querySelectorAll('.hours-item').forEach(function (li) {
      var start = parseLocal(li.getAttribute('data-start'));
      var end = parseLocal(li.getAttribute('data-end'));
      if (!start || !end) return;
      var gone = end.getTime() + 5 * 60 * 1000;
      if (now >= gone) {
        li.remove();
        return;
      }
      var ui = li.getAttribute('data-base') || 'pendiente';
      if (now >= end.getTime()) ui = 'finalizado';
      else if (now >= start.getTime()) ui = 'realizando';
      var pill = li.querySelector('.hours-status');
      if (!pill) return;
      pill.className = 'dash-pill hours-status st-' + ui;
      pill.textContent = labels[ui] || ui;
    });

    document.querySelectorAll('[data-hours-day]').forEach(function (day) {
      var n = day.querySelectorAll('.hours-item').length;
      var count = day.querySelector('.hours-count');
      if (count) count.textContent = n + (n === 1 ? ' persona' : ' personas');
      if (n === 0) day.hidden = true;
    });
  }

  tick();
  setInterval(tick, 15000);
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
