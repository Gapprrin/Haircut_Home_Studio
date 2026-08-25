<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();

$anio = (int) ($_GET['anio'] ?? date('Y'));
$mes  = (int) ($_GET['mes'] ?? date('n'));
if ($mes < 1 || $mes > 12) {
    $mes = (int) date('n');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hora_inicio = substr($_POST['hora_inicio'] ?? '09:00', 0, 5) . ':00';
    $hora_fin    = substr($_POST['hora_fin'] ?? '18:00', 0, 5) . ':00';
    $dias        = $_POST['dias'] ?? [];
    $dias_csv    = implode(',', array_map('intval', $dias));
    if ($dias_csv === '') {
        $dias_csv = '1,2,3,4,5,6';
    }

    $cfg = db()->query('SELECT id FROM configuracion LIMIT 1')->fetch();
    if ($cfg) {
        $up = db()->prepare('UPDATE configuracion SET hora_inicio = ?, hora_fin = ?, dias_atencion = ? WHERE id = ?');
        $up->execute([$hora_inicio, $hora_fin, $dias_csv, $cfg['id']]);
    } else {
        $ins = db()->prepare('INSERT INTO configuracion (hora_inicio, hora_fin, dias_atencion) VALUES (?, ?, ?)');
        $ins->execute([$hora_inicio, $hora_fin, $dias_csv]);
    }

    $anio = (int) ($_POST['anio'] ?? $anio);
    $mes  = (int) ($_POST['mes'] ?? $mes);
    $inicio_mes = sprintf('%04d-%02d-01', $anio, $mes);
    $fin_mes = date('Y-m-t', strtotime($inicio_mes));
    $del = db()->prepare('DELETE FROM dias_off WHERE fecha BETWEEN ? AND ?');
    $del->execute([$inicio_mes, $fin_mes]);

    $offs = $_POST['off'] ?? [];
    $insOff = db()->prepare('INSERT IGNORE INTO dias_off (fecha) VALUES (?)');
    foreach ($offs as $d) {
        $dia = (int) $d;
        if ($dia >= 1 && $dia <= 31) {
            $insOff->execute([sprintf('%04d-%02d-%02d', $anio, $mes, $dia)]);
        }
    }

    db()->exec('DELETE FROM meses_visibles');
    $insMes = db()->prepare('INSERT INTO meses_visibles (anio, mes) VALUES (?, ?)');
    foreach ($_POST['meses_visibles'] ?? [] as $ym) {
        if (preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
            $insMes->execute([(int) $m[1], (int) $m[2]]);
        }
    }

    flash('ok', 'Disponibilidad guardada.');
    header('Location: admin-disponibilidad.php?anio=' . $anio . '&mes=' . $mes);
    exit;
}

$cfg = configuracion();
$dias_sel = dias_atencion_array();
$hora_ini = substr($cfg['hora_inicio'], 0, 5);
$hora_fin = substr($cfg['hora_fin'], 0, 5);

$stOff = db()->prepare('SELECT DAY(fecha) AS d FROM dias_off WHERE YEAR(fecha) = ? AND MONTH(fecha) = ?');
$stOff->execute([$anio, $mes]);
$offs = array_map('intval', array_column($stOff->fetchAll(), 'd'));

$semanas = calendario_mes($anio, $mes);
$visibles_map = [];
foreach (meses_visibles() as $mv) {
    $visibles_map[$mv['anio'] . '-' . str_pad($mv['mes'], 2, '0', STR_PAD_LEFT)] = true;
}

$titulo = 'Haircut Studio - Disponibilidad';
$seccion = 'admin';
$pagina = 'disponibilidad';
require __DIR__ . '/includes/header.php';
?>

<?php
$prev_a = $mes === 1 ? $anio - 1 : $anio;
$prev_m = $mes === 1 ? 12 : $mes - 1;
$next_a = $mes === 12 ? $anio + 1 : $anio;
$next_m = $mes === 12 ? 1 : $mes + 1;
$anio_piso = 2026;
$anio_techo = max(2026 + 9, $anio, (int) date('Y') + 9);
foreach (array_keys($visibles_map) as $k) {
    $y = (int) substr($k, 0, 4);
    if ($y > $anio_techo) {
        $anio_techo = $y;
    }
    if ($y >= 2026 && $y < $anio_piso) {
        $anio_piso = $y;
    }
}
$anios_vis = range($anio_techo, $anio_piso);
$anio_vis_sel = max($anio_piso, min((int) date('Y'), $anio_techo));
$meses_nombres = array_values($MESES_ES);
?>

<section class="avail">
<header class="avail-intro">
<div>
<h2>Disponibilidad</h2>
<p class="avail-sub">Marca días libres, define el horario y elige qué meses pueden ver las clientas.</p>
</div>
</header>

<form method="post" class="avail-form">
<input type="hidden" name="anio" value="<?php echo $anio; ?>">
<input type="hidden" name="mes" value="<?php echo $mes; ?>">

<article class="avail-card">
<h3>Días libres</h3>
<p class="hint">Este calendario no es para agendar horas. Sirve para marcar los días en que el studio no atiende (feriado, vacaciones u otro). Los días naranja quedan bloqueados: la clienta no puede reservar.</p>

<div class="avail-monthbar">
<a class="avail-nav" href="?anio=<?php echo $prev_a; ?>&amp;mes=<?php echo $prev_m; ?>" aria-label="Mes anterior">&lsaquo;</a>
<div class="avail-jump">
<select id="jump-mes" aria-label="Mes">
<?php for ($i = 1; $i <= 12; $i++): ?>
<option value="<?php echo $i; ?>" <?php echo $i === $mes ? 'selected' : ''; ?>><?php echo h($MESES_ES[$i]); ?></option>
<?php endfor; ?>
</select>
<select id="jump-anio" hidden aria-hidden="true">
<?php foreach ($anios_vis as $av): ?>
<option value="<?php echo $av; ?>" <?php echo $av === $anio ? 'selected' : ''; ?>><?php echo $av; ?></option>
<?php endforeach; ?>
</select>
<div class="year-menu" data-year-menu data-min="2026" data-max="<?php echo (int) $anio_techo; ?>" data-value="<?php echo (int) $anio; ?>" data-nav="1">
<button type="button" class="year-menu-btn" aria-expanded="false" aria-label="Elegir año">
<span data-year-label><?php echo (int) $anio; ?></span>
<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
</button>
<div class="year-menu-pop" hidden>
<button type="button" class="year-menu-more" data-more>Años siguientes</button>
<ul class="year-menu-list" data-year-list></ul>
</div>
</div>
</div>
<a class="avail-nav" href="?anio=<?php echo $next_a; ?>&amp;mes=<?php echo $next_m; ?>" aria-label="Mes siguiente">&rsaquo;</a>
</div>

<div class="avail-cal-head" aria-hidden="true">
<span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
</div>
<div class="avail-cal" role="grid" aria-label="Días no disponibles">
<?php foreach ($semanas as $fila): ?>
<?php foreach ($fila as $dia): ?>
<?php if ($dia === null): ?>
<span class="avail-empty"></span>
<?php else:
    $checked = in_array($dia, $offs, true);
?>
<label class="avail-day">
<input type="checkbox" name="off[]" value="<?php echo $dia; ?>" <?php echo $checked ? 'checked' : ''; ?>>
<span><?php echo $dia; ?></span>
</label>
<?php endif; ?>
<?php endforeach; ?>
<?php endforeach; ?>
</div>
<p class="hint">Toca un día para marcarlo como no disponible. Solo se guarda este mes.</p>
</article>

<article class="avail-card">
<h3>Horario de atención</h3>
<div class="avail-times">
<div class="field">
<label for="h-ini">Desde</label>
<input type="time" id="h-ini" name="hora_inicio" value="<?php echo h($hora_ini); ?>">
</div>
<div class="field">
<label for="h-fin">Hasta</label>
<input type="time" id="h-fin" name="hora_fin" value="<?php echo h($hora_fin); ?>">
</div>
</div>
<p class="avail-label">Días de atención</p>
<div class="chip-grid">
<?php foreach ($DIAS_ES as $n => $nombre): ?>
<label class="chip">
<input type="checkbox" name="dias[]" value="<?php echo $n; ?>" <?php echo in_array($n, $dias_sel, true) ? 'checked' : ''; ?>>
<span><?php echo h($nombre); ?></span>
</label>
<?php endforeach; ?>
</div>
</article>

<article class="avail-card">
<h3>Meses visibles</h3>
<p class="hint">Elige año y marca los meses que la clienta puede ver al reservar. Puedes activar cualquier mes de cualquier año.</p>

<div class="vis-picker" id="vis-picker">
<div class="vis-picker-head">
<div class="year-menu" data-year-menu data-min="2026" data-max="<?php echo (int) $anio_techo; ?>" data-value="<?php echo (int) $anio_vis_sel; ?>" data-target="vis-year">
<span class="year-menu-label">Año</span>
<button type="button" class="year-menu-btn" aria-expanded="false" aria-haspopup="listbox">
<span data-year-label><?php echo (int) $anio_vis_sel; ?></span>
<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
</button>
<div class="year-menu-pop" hidden>
<button type="button" class="year-menu-more" data-more>Años siguientes</button>
<ul class="year-menu-list" data-year-list></ul>
</div>
</div>
<input type="hidden" id="vis-year" value="<?php echo (int) $anio_vis_sel; ?>">
</div>
<div id="vis-panels">
<?php foreach ($anios_vis as $av): ?>
<div class="vis-picker-list" data-year="<?php echo $av; ?>" <?php echo $av === $anio_vis_sel ? '' : 'hidden'; ?>>
<?php for ($i = 1; $i <= 12; $i++):
    $key = sprintf('%04d-%02d', $av, $i);
    $chk = isset($visibles_map[$key]);
?>
<label class="vis-month">
<input type="checkbox" name="meses_visibles[]" value="<?php echo h($key); ?>" <?php echo $chk ? 'checked' : ''; ?>>
<span class="vis-check" aria-hidden="true"></span>
<span class="vis-name"><?php echo h($MESES_ES[$i]); ?></span>
</label>
<?php endfor; ?>
</div>
<?php endforeach; ?>
</div>
</div>
</article>

<div class="avail-save">
<button type="submit" class="btn btn-primary">Guardar</button>
</div>
</form>
</section>

<script>
window.HHS_MESES = <?php echo json_encode(array_values($MESES_ES), JSON_UNESCAPED_UNICODE); ?>;
(function () {
  var mesSel = document.getElementById('jump-mes');
  var visYear = document.getElementById('vis-year');
  var panels = document.getElementById('vis-panels');

  function panelDeAnio(year) {
    if (!panels) return null;
    var found = panels.querySelector('.vis-picker-list[data-year="' + year + '"]');
    if (found) return found;
    var wrap = document.createElement('div');
    wrap.className = 'vis-picker-list';
    wrap.setAttribute('data-year', String(year));
    wrap.hidden = true;
    (window.HHS_MESES || []).forEach(function (nombre, i) {
      var mm = ('0' + (i + 1)).slice(-2);
      var lab = document.createElement('label');
      lab.className = 'vis-month';
      lab.innerHTML = '<input type="checkbox" name="meses_visibles[]" value="' + year + '-' + mm + '">' +
        '<span class="vis-check" aria-hidden="true"></span>' +
        '<span class="vis-name"></span>';
      lab.querySelector('.vis-name').textContent = nombre;
      wrap.appendChild(lab);
    });
    panels.appendChild(wrap);
    return wrap;
  }

  function mostrarAnio(year) {
    if (!panels) return;
    panels.querySelectorAll('.vis-picker-list').forEach(function (el) {
      el.hidden = el.getAttribute('data-year') !== String(year);
    });
    panelDeAnio(year).hidden = false;
    if (visYear) visYear.value = String(year);
  }

  function pintarLista(menu) {
    var min = parseInt(menu.getAttribute('data-min'), 10) || 2026;
    var max = parseInt(menu.getAttribute('data-max'), 10) || 2026;
    var val = parseInt(menu.getAttribute('data-value'), 10) || min;
    if (val < min) min = val;
    if (val > max) max = val;
    var ul = menu.querySelector('[data-year-list]');
    if (!ul) return;
    ul.innerHTML = '';
    for (var y = max; y >= min; y--) {
      var li = document.createElement('li');
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = String(y);
      b.setAttribute('data-year', String(y));
      if (y === val) {
        li.className = 'is-on';
        b.setAttribute('aria-current', 'true');
      }
      li.appendChild(b);
      ul.appendChild(li);
    }
  }

  function elegir(menu, year) {
    year = parseInt(year, 10);
    menu.setAttribute('data-value', String(year));
    var lab = menu.querySelector('[data-year-label]');
    if (lab) lab.textContent = String(year);
    pintarLista(menu);
    cerrar(menu);
    if (menu.getAttribute('data-nav') === '1') {
      var m = mesSel ? mesSel.value : '1';
      window.location = 'admin-disponibilidad.php?anio=' + encodeURIComponent(year) + '&mes=' + encodeURIComponent(m);
      return;
    }
    mostrarAnio(year);
  }

  function abrir(menu) {
    document.querySelectorAll('[data-year-menu]').forEach(function (otro) {
      if (otro !== menu) cerrar(otro);
    });
    var pop = menu.querySelector('.year-menu-pop');
    var btn = menu.querySelector('.year-menu-btn');
    if (pop) pop.hidden = false;
    if (btn) btn.setAttribute('aria-expanded', 'true');
    menu.classList.add('is-open');
  }

  function cerrar(menu) {
    var pop = menu.querySelector('.year-menu-pop');
    var btn = menu.querySelector('.year-menu-btn');
    if (pop) pop.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
    menu.classList.remove('is-open');
  }

  document.querySelectorAll('[data-year-menu]').forEach(function (menu) {
    pintarLista(menu);
    var btn = menu.querySelector('.year-menu-btn');
    var more = menu.querySelector('[data-more]');
    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (menu.classList.contains('is-open')) cerrar(menu);
        else abrir(menu);
      });
    }
    if (more) {
      more.addEventListener('click', function (e) {
        e.preventDefault();
        var max = parseInt(menu.getAttribute('data-max'), 10) || 2026;
        menu.setAttribute('data-max', String(max + 8));
        pintarLista(menu);
      });
    }
    menu.addEventListener('click', function (e) {
      var t = e.target.closest('[data-year]');
      if (!t || !menu.contains(t) || t.closest('[data-year-menu]') !== menu) return;
      if (t.getAttribute('data-year-menu') != null) return;
      var y = t.getAttribute('data-year');
      if (y) elegir(menu, y);
    });
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-year-menu]')) return;
    document.querySelectorAll('[data-year-menu]').forEach(cerrar);
  });

  if (mesSel) {
    mesSel.addEventListener('change', function () {
      var menu = document.querySelector('[data-year-menu][data-nav="1"]');
      var y = menu ? menu.getAttribute('data-value') : '';
      if (!y) return;
      window.location = 'admin-disponibilidad.php?anio=' + encodeURIComponent(y) + '&mes=' + encodeURIComponent(mesSel.value);
    });
  }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
