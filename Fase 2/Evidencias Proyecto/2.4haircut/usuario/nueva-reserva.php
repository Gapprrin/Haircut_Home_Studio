<?php
require_once __DIR__ . '/../includes/funciones.php';
$usuario = require_cliente();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serv_id = (int) ($_POST['servicio_id'] ?? 0);
    $fecha = (string) ($_POST['fecha'] ?? '');
    $hora = (string) ($_POST['hora'] ?? '');
    $serv = servicio_por_id($serv_id);
    if (!$serv || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !in_array($hora, slots_disponibles($fecha, $serv_id), true)) {
        flash('error', $serv ? 'Esa hora ya no está disponible. Elige otra.' : 'Selecciona un servicio.');
        ir(url('usuario/nueva-reserva.php?serv=' . $serv_id . '&fecha=' . rawurlencode($fecha) . '&lugar=' . rawurlencode(lugar_reserva((string) ($_POST['lugar'] ?? '')))));
    }
    $lugar = lugar_reserva((string) ($_POST['lugar'] ?? 'salon'));
    $foto = null;
    try {
        $foto = guardar_foto($_FILES['foto'] ?? [], 'res_');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        ir(url('usuario/nueva-reserva.php?serv=' . $serv_id . '&fecha=' . rawurlencode($fecha) . '&hora=' . rawurlencode($hora) . '&lugar=' . rawurlencode($lugar)));
    }
    db()->prepare(
        'INSERT INTO reservas (usuario_id, servicio_id, fecha, hora, foto, lugar, estado) VALUES (?, ?, ?, ?, ?, ?, "pendiente")'
    )->execute([(int) $usuario['id'], $serv_id, $fecha, $hora . ':00', $foto, $lugar]);
    flash('ok', 'Reserva enviada. El pago es presencial.');
    ir(url('usuario/mis-reservas.php'));
}

$catalogo = [];
foreach (categorias_con_servicios() as $c) {
    if (!empty($c['servicios'])) {
        $catalogo[] = $c;
    }
}

$serv_id = (int) ($_GET['serv'] ?? 0);
$cat_slug = (string) ($_GET['cat'] ?? '');
$cat_actual = null;
$serv_en_cat = [];
foreach ($catalogo as $c) {
    if ($cat_actual === null && ($c['slug'] === $cat_slug || (string) $c['id'] === $cat_slug)) {
        $cat_actual = $c;
    }
    foreach ($c['servicios'] as $s) {
        $serv_en_cat[(int) $s['id']] = $c;
    }
}

if ($serv_id > 0 && isset($serv_en_cat[$serv_id])) {
    $cat_serv = $serv_en_cat[$serv_id];
    $cat_actual = ($cat_actual && (int) $cat_actual['id'] !== (int) $cat_serv['id'])
        ? $cat_actual
        : $cat_serv;
    if ($cat_actual && (int) $cat_actual['id'] !== (int) $cat_serv['id']) {
        $serv_id = (int) $cat_actual['servicios'][0]['id'];
    }
}
$cat_actual = $cat_actual ?: ($catalogo[0] ?? null);
if ($cat_actual && $serv_id < 1) {
    $serv_id = (int) $cat_actual['servicios'][0]['id'];
}

$serv = servicio_por_id($serv_id);
if (!$serv || !$cat_actual) {
    flash('error', 'Elige un servicio para continuar.');
    ir(url('index.php'));
}

$anio = (int) ($_GET['anio'] ?? date('Y'));
$mes  = (int) ($_GET['mes'] ?? date('n'));
if ($mes < 1 || $mes > 12) {
    $anio += $mes < 1 ? -1 : 1;
    $mes = $mes < 1 ? 12 : 1;
}

asegurar_mes_actual_visible();
if (!cliente_puede_ver_mes($anio, $mes)) {
    $anio = (int) date('Y');
    $mes  = (int) date('n');
}

$lugar = lugar_reserva((string) ($_GET['lugar'] ?? 'salon'));
$fecha = (string) ($_GET['fecha'] ?? '');
$hora = (string) ($_GET['hora'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = '';
    $hora = '';
} else {
    $fy = (int) substr($fecha, 0, 4);
    $fm = (int) substr($fecha, 5, 2);
    if (!cliente_puede_ver_mes($fy, $fm)) {
        $fecha = '';
        $hora = '';
    } else {
        $anio = $fy;
        $mes = $fm;
    }
}

$slots = $fecha !== '' ? slots_disponibles($fecha, $serv_id) : [];
$slotSet = array_flip($slots);
if ($hora !== '' && !isset($slotSet[$hora])) {
    $hora = '';
}
$dur_min = (int) $serv['duracion_min'];
$rangoSel = ($hora !== '')
    ? array_flip(horas_desde_reserva(['hora' => $hora, 'duracion_min' => $dur_min]))
    : [];
$hora_fin_sel = $hora !== '' ? hora_fin_bloque($hora, $dur_min) : '';
$todos_slots = $fecha !== '' ? generar_slots(60, $fecha) : [];
$pref_manana = es_trabajo_largo($dur_min);
$sug_hora = $fecha !== '' ? sugerencias_hora($serv_id, $fecha) : ['misma' => null, 'otra' => null, 'largo' => $pref_manana];
$grupos_hora = ['' => $todos_slots];
if ($pref_manana && $todos_slots) {
    $am = [];
    $pm = [];
    foreach ($todos_slots as $hs) {
        if (hora_es_manana($hs)) {
            $am[] = $hs;
        } else {
            $pm[] = $hs;
        }
    }
    $grupos_hora = [];
    if ($am) {
        $grupos_hora['Mañana'] = $am;
    }
    if ($pm) {
        $grupos_hora['Tarde'] = $pm;
    }
}
$semanas = calendario_mes($anio, $mes);
$estados = [];
foreach ($semanas as $fila) {
    foreach ($fila as $dia) {
        if ($dia) {
            $estados[$dia] = estado_dia_reserva(sprintf('%04d-%02d-%02d', $anio, $mes, $dia), $serv_id);
        }
    }
}

$mesesCli = meses_visibles_cliente();
$idxMes = ym_indice($anio, $mes);
$mes_prev = $mes_next = null;
foreach ($mesesCli as $i => $mv) {
    if (ym_indice((int) $mv['anio'], (int) $mv['mes']) === $idxMes) {
        $mes_prev = $mesesCli[$i - 1] ?? null;
        $mes_next = $mesesCli[$i + 1] ?? null;
        break;
    }
}

$qsBase = [
    'cat' => $cat_actual['slug'] ?? '',
    'serv' => $serv_id,
    'lugar' => $lugar,
    'fecha' => $fecha,
    'hora' => $hora,
    'anio' => $anio,
    'mes' => $mes,
];
$qs = static function (array $extra) use ($qsBase): string {
    $q = $extra + $qsBase;
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    return url('usuario/nueva-reserva.php') . '?' . http_build_query($q);
};

$titulo = 'Reserva aquí';
$seccion = 'cliente';
$pagina = 'nueva';
require __DIR__ . '/../includes/header.php';

$icoCat = clave_icono_categoria($cat_actual);
?>

<section class="book">
<div class="book-head">
<h2>Reserva aquí</h2>
</div>

<div class="book-block">
<h3 class="book-section-title">Seleccionar servicio</h3>
<div class="book-icon-grid">
<?php foreach ($catalogo as $c):
    $on = (int) $c['id'] === (int) $cat_actual['id'];
    $href = $on ? '' : $qs([
        'cat' => $c['slug'],
        'serv' => (int) $c['servicios'][0]['id'],
        'hora' => '',
    ]);
    $cls = 'book-svc' . ($on ? ' is-on' : '');
?>
<?php if ($href): ?>
<a class="<?php echo $cls; ?>" href="<?php echo h($href); ?>" data-no-loader>
<?php else: ?>
<span class="<?php echo $cls; ?>">
<?php endif; ?>
<span class="book-svc-ico"><?php echo html_icono_item($c['imagen'] ?? null, clave_icono_categoria($c)); ?></span>
<span class="book-svc-name"><?php echo h($c['nombre']); ?></span>
<?php echo $href ? '</a>' : '</span>'; ?>
<?php endforeach; ?>
</div>
</div>

<div class="book-block">
<h3 class="book-section-title">Tipo de servicio</h3>
<div class="book-icon-grid">
<?php foreach ($cat_actual['servicios'] as $s):
    $on = (int) $s['id'] === $serv_id;
    $href = $on ? '' : $qs(['serv' => (int) $s['id'], 'hora' => '']);
    $cls = 'book-svc' . ($on ? ' is-on' : '');
?>
<?php if ($href): ?>
<a class="<?php echo $cls; ?>" href="<?php echo h($href); ?>" data-no-loader>
<?php else: ?>
<span class="<?php echo $cls; ?>">
<?php endif; ?>
<span class="book-svc-ico"><?php echo html_icono_item($s['imagen'] ?: null, clave_icono_servicio($s + [
    'categoria_slug' => (string) ($cat_actual['slug'] ?? ''),
    'categoria' => (string) ($cat_actual['nombre'] ?? ''),
])); ?></span>
<span class="book-svc-name"><?php echo h($s['nombre']); ?></span>
<?php echo $href ? '</a>' : '</span>'; ?>
<?php endforeach; ?>
</div>
<?php $ficha = texto_ficha_servicio($serv); if ($ficha !== ''): ?>
<p class="book-duration"><?php echo h($ficha); ?></p>
<?php endif; ?>
</div>

<div class="book-block">
<h3 class="book-section-title">Seleccionar fecha</h3>
<div class="book-month">
<?php if ($mes_prev): ?>
<a class="book-nav" href="<?php echo h($qs(['anio' => (int) $mes_prev['anio'], 'mes' => (int) $mes_prev['mes'], 'fecha' => '', 'hora' => ''])); ?>" aria-label="Mes anterior">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
</a>
<?php else: ?>
<span class="book-nav is-off" aria-hidden="true"></span>
<?php endif; ?>
<div class="book-month-pill"><?php echo h($MESES_ES[$mes]); ?></div>
<?php if ($mes_next): ?>
<a class="book-nav" href="<?php echo h($qs(['anio' => (int) $mes_next['anio'], 'mes' => (int) $mes_next['mes'], 'fecha' => '', 'hora' => ''])); ?>" aria-label="Mes siguiente">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M9.5 5.5 8.1 6.9 13.2 12l-5.1 5.1 1.4 1.4L16 12z"/></svg>
</a>
<?php else: ?>
<span class="book-nav is-off" aria-hidden="true"></span>
<?php endif; ?>
</div>

<table class="book-cal">
<tr>
<th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th>
</tr>
<?php foreach ($semanas as $fila): ?>
<tr>
<?php foreach ($fila as $dia): ?>
<td>
<?php if ($dia):
    $f = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    $estado = $estados[$dia];
    $sel = $f === $fecha;
    $clase = 'd st-' . $estado;
    if ($sel) {
        $clase .= ' is-sel';
    }
?>
<?php if (in_array($estado, ['disponible', 'ocupado'], true) && !$sel): ?>
<a class="<?php echo $clase; ?>" href="<?php echo h($qs(['fecha' => $f, 'hora' => '', 'anio' => $anio, 'mes' => $mes])); ?>"><?php echo $dia; ?></a>
<?php else: ?>
<span class="<?php echo $clase; ?>"><?php echo $dia; ?></span>
<?php endif; ?>
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>

<ul class="book-legend">
<li><span class="book-dot is-free"></span> Disponible</li>
<li><span class="book-dot is-busy"></span> No disponible</li>
</ul>
</div>

<?php if ($fecha !== ''): ?>
<div class="book-block">
<h3 class="book-section-title">Seleccionar hora</h3>
<?php if ($pref_manana): ?>
<p class="hint">En colores largos se recomienda la mañana. El bloque se reserva desde la hora que elijas hacia adelante.</p>
<?php else: ?>
<p class="hint">Se reserva desde la hora que elijas hacia adelante, según la duración del servicio.</p>
<?php endif; ?>
<?php if ($sug_hora['misma'] || $sug_hora['otra']): ?>
<p class="book-suggest">
Hora más cercana:
<?php if ($sug_hora['misma']): ?>
<a href="<?php echo h($qs(['hora' => $sug_hora['misma']])); ?>" data-no-loader><?php echo ($sug_hora['largo'] && hora_es_manana($sug_hora['misma'])) ? 'Mañana · ' : ''; ?><?php echo h(hora_ampm($sug_hora['misma'])); ?></a>
<?php else: ?>
<span>no hay en este día</span>
<?php endif; ?>
<?php if ($sug_hora['otra']): ?>
 · o
<a href="<?php echo h($qs(['fecha' => $sug_hora['otra']['fecha'], 'hora' => $sug_hora['otra']['hora'], 'anio' => (int) substr($sug_hora['otra']['fecha'], 0, 4), 'mes' => (int) substr($sug_hora['otra']['fecha'], 5, 2)])); ?>" data-no-loader><?php echo h(fecha_agenda_texto($sug_hora['otra']['fecha'])); ?> · <?php echo h(hora_ampm($sug_hora['otra']['hora'])); ?></a>
<?php endif; ?>
</p>
<?php endif; ?>
<div class="book-hours">
<?php if (!$todos_slots): ?>
<p class="hint">No hay horario configurado para este servicio.</p>
<?php else: ?>
<?php foreach ($grupos_hora as $grupo => $horas_g): ?>
<?php if ($grupo !== ''): ?>
<p class="book-hours-kicker"><?php echo $grupo === 'Mañana' ? 'Mañana · recomendada' : h($grupo); ?></p>
<?php endif; ?>
<?php foreach ($horas_g as $hslot):
    $libre = isset($slotSet[$hslot]);
    $en_rango = isset($rangoSel[$hslot]);
    $es_inicio = $hora !== '' && $hslot === $hora;
    $etiqueta = hora_ampm($hslot);
    $cls = 'hour-pill';
    if ($es_inicio) {
        $cls .= ' is-on';
    } elseif ($en_rango) {
        $cls .= ' is-range';
    } elseif ($libre) {
        $cls .= ' is-free';
        if ($pref_manana && hora_es_manana($hslot)) {
            $cls .= ' is-am';
        }
        if (($sug_hora['misma'] ?? '') === $hslot) {
            $cls .= ' is-near';
        }
    } else {
        $cls .= ' is-busy';
    }
    if ($libre && !$es_inicio): ?>
<a class="<?php echo $cls; ?>" href="<?php echo h($qs(['hora' => $hslot])); ?>"><?php echo h($etiqueta); ?></a>
<?php else: ?>
<span class="<?php echo $cls; ?>"><?php echo h($etiqueta); ?></span>
<?php endif; endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
</div>
<ul class="book-legend">
<li><span class="book-dot is-free"></span> Disponible</li>
<li><span class="book-dot is-range"></span> Tu bloque</li>
<li><span class="book-dot is-busy"></span> No disponible</li>
</ul>
</div>
<?php endif; ?>

<form method="post" class="book-reserve" enctype="multipart/form-data">
<div class="book-card">
<div class="book-photo" id="book-photo">
<input class="book-photo-input" type="file" id="book-foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
<label class="book-photo-drop" for="book-foto" id="book-photo-drop">
<span class="book-photo-ico" aria-hidden="true">
<svg viewBox="0 0 24 24" width="32" height="32"><path fill="currentColor" d="M4.5 5.5A2.5 2.5 0 0 1 7 3h10a2.5 2.5 0 0 1 2.5 2.5V7h1.2A1.8 1.8 0 0 1 22.5 8.8v10.4a1.8 1.8 0 0 1-1.8 1.8H3.3a1.8 1.8 0 0 1-1.8-1.8V8.8A1.8 1.8 0 0 1 3.3 7H4.5V5.5zM7 5a.5.5 0 0 0-.5.5V7h11V5.5a.5.5 0 0 0-.5-.5H7zm5 13.2A4.7 4.7 0 1 0 7.3 13.5 4.7 4.7 0 0 0 12 18.2zm0-2a2.7 2.7 0 1 1 2.7-2.7A2.7 2.7 0 0 1 12 16.2z"/></svg>
</span>
<span class="book-photo-title">Agregar imagen</span>
<span class="book-photo-sub">Arrástrala aquí o tócala para elegir · JPG, PNG o WEBP</span>
</label>
<div class="book-photo-preview" id="book-photo-preview" hidden>
<img alt="Vista previa de la imagen">
<button type="button" class="book-photo-clear" id="book-photo-clear" aria-label="Quitar imagen">&times;</button>
</div>
<p class="book-photo-msg" id="book-photo-msg" hidden></p>
</div>
</div>

<div class="book-block">
<h3 class="book-section-title">Lugar de atención</h3>
<div class="book-icon-grid book-lugar-grid">
<?php
$lugares = [
    'salon' => ['nombre' => 'En el salón', 'img' => 'img/iconos/lugar-salon.png'],
    'domicilio' => ['nombre' => 'A domicilio', 'img' => 'img/iconos/lugar-domicilio.png'],
];
foreach ($lugares as $clave => $info):
    $on = $lugar === $clave;
    $href = $on ? '' : $qs(['lugar' => $clave]);
    $cls = 'book-svc book-lugar' . ($on ? ' is-on' : '');
?>
<?php if ($href): ?>
<a class="<?php echo $cls; ?>" href="<?php echo h($href); ?>" data-no-loader>
<?php else: ?>
<span class="<?php echo $cls; ?>">
<?php endif; ?>
<span class="book-svc-ico"><img class="icon-pack" src="<?php echo h(url($info['img'])); ?>?v=lugar-2" alt=""></span>
<span class="book-svc-name"><?php echo h($info['nombre']); ?></span>
<?php echo $href ? '</a>' : '</span>'; ?>
<?php endforeach; ?>
</div>
</div>

<div class="book-foot">
<input type="hidden" name="servicio_id" value="<?php echo $serv_id; ?>">
<input type="hidden" name="fecha" value="<?php echo h($fecha); ?>">
<input type="hidden" name="hora" value="<?php echo h($hora); ?>">
<input type="hidden" name="lugar" value="<?php echo h($lugar); ?>">
<p class="book-foot-summary">
<span class="book-foot-kicker">Resumen:</span>
1 servicio · <?php echo h(lugar_label($lugar)); ?> · <?php echo h(duracion_texto($dur_min)); ?><?php echo $hora !== '' ? ' · ' . h(hora_ampm($hora)) . ' – ' . h(hora_ampm($hora_fin_sel)) : ''; ?>
<strong class="book-foot-price"><?php echo precio_clp($serv['precio']); ?></strong>
</p>
<div class="book-foot-actions">
<button type="submit" class="btn book-btn-now" <?php echo ($fecha !== '' && $hora !== '') ? '' : 'disabled'; ?>>Reservar</button>
<a href="<?php echo h(url('index.php')); ?>" class="btn book-btn-cancel" data-no-loader>Cancelar</a>
</div>
</div>
</form>
</section>

<script>
(function () {
  var wrap = document.getElementById('book-photo');
  var input = document.getElementById('book-foto');
  var drop = document.getElementById('book-photo-drop');
  var preview = document.getElementById('book-photo-preview');
  var img = preview ? preview.querySelector('img') : null;
  var clearBtn = document.getElementById('book-photo-clear');
  var msg = document.getElementById('book-photo-msg');
  if (!wrap || !input || !drop || !preview || !img) return;

  var okTypes = {
    'image/jpeg': true,
    'image/png': true,
    'image/webp': true,
    'image/gif': true
  };
  var url = null;

  function showMsg(text) {
    if (!msg) return;
    msg.hidden = !text;
    msg.textContent = text || '';
  }

  function resetPreview() {
    if (url) {
      URL.revokeObjectURL(url);
      url = null;
    }
    preview.hidden = true;
    drop.hidden = false;
    img.removeAttribute('src');
    wrap.classList.remove('has-file');
  }

  function setFile(file) {
    if (!file) return;
    var name = (file.name || '').toLowerCase();
    if (name.slice(-4) === '.pdf' || file.type === 'application/pdf' || !okTypes[file.type]) {
      input.value = '';
      resetPreview();
      showMsg('Solo se permiten imágenes (JPG, PNG o WEBP). No PDF.');
      return;
    }
    if (file.size > 3 * 1024 * 1024) {
      input.value = '';
      resetPreview();
      showMsg('La imagen no puede superar 3 MB.');
      return;
    }
    showMsg('');
    if (url) URL.revokeObjectURL(url);
    url = URL.createObjectURL(file);
    img.src = url;
    preview.hidden = false;
    drop.hidden = true;
    wrap.classList.add('has-file');
  }

  input.addEventListener('change', function () {
    setFile(input.files && input.files[0]);
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      input.value = '';
      resetPreview();
      showMsg('');
    });
  }

  ['dragenter', 'dragover'].forEach(function (ev) {
    drop.addEventListener(ev, function (e) {
      e.preventDefault();
      drop.classList.add('is-drag');
    });
  });
  ['dragleave', 'drop'].forEach(function (ev) {
    drop.addEventListener(ev, function (e) {
      e.preventDefault();
      drop.classList.remove('is-drag');
    });
  });
  drop.addEventListener('drop', function (e) {
    var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
    if (!file) return;
    try {
      var dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
    } catch (err) {}
    setFile(file);
  });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
