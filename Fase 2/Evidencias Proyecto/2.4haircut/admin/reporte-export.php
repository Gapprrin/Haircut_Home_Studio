<?php
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/simple-pdf.php';
require_peluquero();

$anio = (int) ($_GET['anio'] ?? date('Y'));
$mes  = (int) ($_GET['mes'] ?? date('n'));
$formato = $_GET['formato'] ?? 'csv';
if ($mes < 1 || $mes > 12) {
    $mes = (int) date('n');
}

$st = db()->prepare(
    "SELECT r.fecha, r.hora, r.estado, u.nombre AS cliente, u.email,
            s.nombre AS servicio, s.duracion_min, s.precio, c.nombre AS categoria, r.lugar
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

$conteo = [
    'pendiente' => 0,
    'confirmada' => 0,
    'realizada' => 0,
    'cancelada' => 0,
    'no_asistio' => 0,
    'rechazada' => 0,
];
$ingresos = 0.0;
$top = [];
foreach ($filas as $f) {
    $est = (string) $f['estado'];
    if (isset($conteo[$est])) {
        $conteo[$est]++;
    }
    if (in_array($est, ['confirmada', 'realizada'], true)) {
        $ingresos += (float) $f['precio'];
    }
    if (in_array($est, ['confirmada', 'realizada'], true)) {
        $nom = (string) $f['servicio'];
        if (!isset($top[$nom])) {
            $top[$nom] = 0;
        }
        $top[$nom]++;
    }
}
arsort($top);
$top = array_slice($top, 0, 4, true);
$total = count($filas);
$ok = $conteo['confirmada'] + $conteo['realizada'];

function hora_rango_fila(array $f): string
{
    $ini = substr((string) $f['hora'], 0, 5);
    $min = max(60, (int) ($f['duracion_min'] ?? 60));
    $fin = date('H:i', strtotime($f['fecha'] . ' ' . $ini . ':00') + $min * 60);
    return $ini . ' – ' . $fin;
}

if ($formato === 'csv') {
    $nombre = 'reporte-haircut-' . $anio . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT) . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Fecha', 'Hora', 'Cliente', 'Email', 'Categoría', 'Servicio', 'Lugar', 'Estado'], ';');
    foreach ($filas as $f) {
        fputcsv($out, [
            $f['fecha'],
            hora_rango_fila($f),
            $f['cliente'],
            $f['email'],
            $f['categoria'],
            $f['servicio'],
            lugar_label($f['lugar'] ?? 'salon'),
            estado_label($f['estado']),
        ], ';');
    }
    fclose($out);
    exit;
}

$burgundy = [92, 41, 60];
$burgundyDark = [74, 32, 48];
$ink = [58, 37, 48];
$soft = [140, 107, 120];
$peach = [235, 224, 228];
$sand = [243, 231, 236];
$white = [255, 255, 255];
$cream = [250, 247, 245];
$line = [217, 205, 210];
$green = [61, 154, 100];

$pdf = new SimplePdf();
$pdf->addPage();
$pw = $pdf->width();
$m = 36;
$logo = __DIR__ . '/../img/logo-pdf.jpg';
if (!is_file($logo)) {
    $logo = __DIR__ . '/../img/logo.png';
}
$fechaTxt = date('d/m/Y H:i');
$logoSize = 64;
$logoX = $pw - $m - $logoSize;
$logoY = 10;
$headH = 112;

$pdf->rect(0, 0, $pw, $headH, $burgundy);
$pdf->text($m, 28, 22, 'Reporte mensual', true, $white);
$pdf->text($m, 56, 11, $titulo_mes, false, $peach);
$pdf->image($logoX, $logoY, $logoSize, $logoSize, $logo, null, true);
$fechaTop = $logoY + $logoSize + 12;
$fechaW = $pdf->textWidth($fechaTxt, 9);
$pdf->text($logoX + ($logoSize - $fechaW) / 2, $fechaTop, 9, $fechaTxt, false, $peach);

$y = 130;
$gap = 10;
$cardW = ($pw - $m * 2 - $gap * 3) / 4;
$kpis = [
    ['Reservas', (string) $total, $burgundy],
    ['Confirmadas', (string) $ok, $green],
    ['Pendientes', (string) $conteo['pendiente'], [224, 138, 60]],
    ['Ingresos', precio_clp($ingresos), $burgundyDark],
];
foreach ($kpis as $i => $k) {
    $x = $m + $i * ($cardW + $gap);
    $pdf->rect($x, $y, $cardW, 58, $white);
    $pdf->rect($x, $y, 5, 58, $k[2]);
    $pdf->text($x + 14, $y + 12, 8, strtoupper($k[0]), true, $soft);
    $pdf->text($x + 14, $y + 28, 14, $k[1], true, $burgundy);
}

$y = 204;
$leftW = 250;
$pdf->text($m, $y, 11, 'Resumen del mes', true, $burgundy);
$y += 16;
$estados = [
    ['Pendiente', $conteo['pendiente']],
    ['Confirmada', $conteo['confirmada']],
    ['Realizada', $conteo['realizada']],
    ['Cancelada', $conteo['cancelada']],
    ['No llegó', $conteo['no_asistio']],
    ['Rechazada', $conteo['rechazada']],
];
$maxE = 1;
foreach ($estados as $e) {
    $maxE = max($maxE, $e[1]);
}
foreach ($estados as $e) {
    $bar = $leftW * ($e[1] / $maxE);
    $pdf->text($m, $y, 8, $e[0], false, $ink);
    $pdf->rect($m + 72, $y + 1, $leftW, 8, $sand);
    if ($e[1] > 0) {
        $pdf->rect($m + 72, $y + 1, max(4, $bar), 8, $burgundy);
    }
    $pdf->text($m + 72 + $leftW + 8, $y, 8, (string) $e[1], true, $burgundy);
    $y += 14;
}

$sx = $m + 72 + $leftW + 48;
$sy = 204;
$pdf->text($sx, $sy, 11, 'Servicios más pedidos', true, $burgundy);
$sy += 16;
if (!$top) {
    $pdf->text($sx, $sy, 9, 'Sin datos este mes.', false, $soft);
} else {
    $maxT = max(1, max($top));
    $barW = $pw - $m - $sx - 36;
    foreach ($top as $nom => $n) {
        $pdf->text($sx, $sy, 8, $pdf->clip($nom, 8, $barW - 24), false, $ink);
        $sy += 11;
        $pdf->rect($sx, $sy, $barW, 7, $sand);
        $pdf->rect($sx, $sy, max(4, $barW * ($n / $maxT)), 7, $burgundy);
        $pdf->text($sx + $barW + 6, $sy - 1, 8, (string) $n, true, $burgundy);
        $sy += 14;
    }
}

$y = max($y, $sy) + 18;
$pdf->text($m, $y, 11, 'Detalle de reservas', true, $burgundy);
$y += 14;

$cols = [
    ['Fecha', 58],
    ['Hora', 78],
    ['Cliente', 92],
    ['Servicio', 210],
    ['Estado', 75],
];
$tableW = 0;
foreach ($cols as $c) {
    $tableW += $c[1];
}

function pdf_tabla_cabecera(SimplePdf $pdf, float $x, float &$y, array $cols, array $burgundy, array $white): void
{
    $pdf->rect($x, $y, array_sum(array_column($cols, 1)), 16, $burgundy);
    $cx = $x + 4;
    foreach ($cols as $c) {
        $pdf->text($cx, $y + 4, 8, $c[0], true, $white);
        $cx += $c[1];
    }
    $y += 16;
}

pdf_tabla_cabecera($pdf, $m, $y, $cols, $burgundy, $white);

if (!$filas) {
    $pdf->rect($m, $y, $tableW, 22, $cream);
    $pdf->text($m + 8, $y + 7, 9, 'Sin reservas en este mes.', false, $soft);
} else {
    foreach ($filas as $i => $f) {
        if ($y > 800) {
            $pdf->addPage();
            $y = 36;
            $pdf->rect(0, 0, $pw, 36, $burgundy);
            $pdf->text($m, 12, 11, 'Reporte mensual  ·  ' . $titulo_mes, true, $white);
            $pdf->image($pw - $m - 22, 7, 22, 22, $logo, null, true);
            $y = 44;
            pdf_tabla_cabecera($pdf, $m, $y, $cols, $burgundy, $white);
        }
        $rowH = 16;
        $pdf->rect($m, $y, $tableW, $rowH, $i % 2 === 0 ? $cream : $white);
        $vals = [
            date('d/m/Y', strtotime($f['fecha'])),
            hora_rango_fila($f),
            $f['cliente'],
            $f['categoria'] . ' — ' . $f['servicio'],
            estado_label($f['estado']),
        ];
        $cx = $m + 4;
        foreach ($cols as $ci => $c) {
            $pdf->text($cx, $y + 4, 8, $pdf->clip((string) $vals[$ci], 8, $c[1] - 8), false, $ink);
            $cx += $c[1];
        }
        $y += $rowH;
    }
}

$pdf->text($m, 822, 8, 'Haircut Home Studio · Prof. Ricardo Luengo Mardones · Melipilla', false, $soft);
$pdf->text($pw - $m - $pdf->textWidth('Página generada automáticamente', 8), 822, 8, 'Página generada automáticamente', false, $soft);

$nombre = 'reporte-haircut-' . $anio . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT) . '.pdf';
$pdf->output($nombre);
exit;
