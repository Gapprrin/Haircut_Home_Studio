<?php
require_once __DIR__ . '/../includes/funciones.php';
require_admin();

$volver_cat = (int) ($_POST['volver_cat'] ?? $_GET['cat'] ?? 0);

function servicios_redir(array $extra = []): string
{
    global $volver_cat;
    $q = [];
    $cat = (int) ($extra['cat'] ?? $volver_cat);
    if ($cat > 0) {
        $q['cat'] = $cat;
    }
    foreach ($extra as $k => $v) {
        if ($k === 'cat' || $v === '' || $v === null) {
            continue;
        }
        $q[$k] = $v;
    }
    return url('admin/servicios.php') . ($q ? ('?' . http_build_query($q)) : '');
}

$redir = servicios_redir();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'categoria') {
        $nombre = trim($_POST['nombre'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombre));
        $slug = trim($slug, '-');
        if ($nombre === '' || $slug === '') {
            flash('error', 'El nombre de la categoría no puede estar vacío.');
            $redir = servicios_redir(['nueva_cat' => 1]);
        } else {
            try {
                $imagen = guardar_foto($_FILES['imagen'] ?? [], 'cat_');
                db()->prepare('INSERT INTO categorias (nombre, slug, imagen) VALUES (?, ?, ?)')->execute([$nombre, $slug, $imagen]);
                flash('ok', 'Categoría creada.');
            } catch (PDOException $e) {
                flash('error', 'No se pudo crear la categoría (¿nombre repetido?).');
                $redir = servicios_redir(['nueva_cat' => 1]);
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                $redir = servicios_redir(['nueva_cat' => 1]);
            }
        }
    }

    if ($accion === 'servicio') {
        $cat = (int) ($_POST['categoria_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        $dur = (int) ($_POST['duracion_min'] ?? 60);
        $precio = (int) ($_POST['precio'] ?? 0);
        if ($cat < 1 || $nombre === '') {
            flash('error', 'Completa categoría y nombre del servicio.');
            $redir = servicios_redir(['nuevo' => 1]);
        } else {
            try {
                $imagen = guardar_foto($_FILES['imagen'] ?? [], 'serv_');
                db()->prepare(
                    'INSERT INTO servicios (categoria_id, nombre, descripcion, duracion_min, precio, imagen) VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([$cat, $nombre, $desc, max(60, $dur), max(0, $precio), $imagen]);
                flash('ok', 'Servicio agregado.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                $redir = servicios_redir(['nuevo' => 1]);
            }
        }
    }

    if ($accion === 'editar') {
        $id = (int) ($_POST['id'] ?? 0);
        $cat = (int) ($_POST['categoria_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        $dur = (int) ($_POST['duracion_min'] ?? 60);
        $precio = (int) ($_POST['precio'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        if ($id < 1 || $nombre === '' || $cat < 1) {
            flash('error', 'Completa categoría y nombre del servicio.');
            $redir = servicios_redir(['editar' => max(0, $id)]);
        } else {
            try {
                $st = db()->prepare('SELECT imagen FROM servicios WHERE id = ?');
                $st->execute([$id]);
                $imagen = $st->fetchColumn() ?: null;
                $nueva = guardar_foto($_FILES['imagen'] ?? [], 'serv_');
                if ($nueva) {
                    borrar_foto($imagen);
                    $imagen = $nueva;
                }
                db()->prepare(
                    'UPDATE servicios SET categoria_id = ?, nombre = ?, descripcion = ?, duracion_min = ?, precio = ?, activo = ?, imagen = ? WHERE id = ?'
                )->execute([$cat, $nombre, $desc, max(60, $dur), max(0, $precio), $activo, $imagen, $id]);
                flash('ok', 'Servicio actualizado.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                $redir = servicios_redir(['editar' => $id]);
            }
        }
    }

    if ($accion === 'borrar_categoria') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash('error', 'Elige una categoría para eliminar.');
            $redir = servicios_redir(['nueva_cat' => 1]);
        } else {
            $totalCats = (int) db()->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
            if ($totalCats <= 1) {
                flash('error', 'Debe quedar al menos una categoría.');
                $redir = servicios_redir(['nueva_cat' => 1]);
            } else {
                $st = db()->prepare(
                    'SELECT COUNT(*) FROM reservas r
                     JOIN servicios s ON s.id = r.servicio_id
                     WHERE s.categoria_id = ?'
                );
                $st->execute([$id]);
                if ((int) $st->fetchColumn() > 0) {
                    flash('error', 'No se puede eliminar: hay reservas en servicios de esa categoría.');
                    $redir = servicios_redir(['nueva_cat' => 1]);
                } else {
                    try {
                        $stImg = db()->prepare('SELECT imagen FROM servicios WHERE categoria_id = ?');
                        $stImg->execute([$id]);
                        foreach ($stImg->fetchAll(PDO::FETCH_COLUMN) as $img) {
                            borrar_foto($img);
                        }
                        $catImg = db()->prepare('SELECT imagen FROM categorias WHERE id = ?');
                        $catImg->execute([$id]);
                        borrar_foto($catImg->fetchColumn() ?: null);
                        db()->prepare('DELETE FROM servicios WHERE categoria_id = ?')->execute([$id]);
                        db()->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
                        flash('ok', 'Categoría eliminada.');
                        if ($volver_cat === $id) {
                            $volver_cat = 0;
                            $redir = servicios_redir(['cat' => 0]);
                        }
                    } catch (PDOException $e) {
                        flash('error', 'No se pudo eliminar la categoría.');
                        $redir = servicios_redir(['nueva_cat' => 1]);
                    }
                }
            }
        }
    }

    if ($accion === 'borrar') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $st = db()->prepare('SELECT imagen FROM servicios WHERE id = ?');
            $st->execute([$id]);
            borrar_foto($st->fetchColumn() ?: null);
            db()->prepare('DELETE FROM servicios WHERE id = ?')->execute([$id]);
            flash('ok', 'Servicio eliminado.');
        } catch (PDOException $e) {
            flash('error', 'No se puede borrar: hay reservas asociadas. Desactívalo en su lugar.');
        }
    }

    header('Location: ' . $redir);
    exit;
}

$filtro_cat = (int) ($_GET['cat'] ?? 0);
$abrir_alta = isset($_GET['nuevo']);
$abrir_cat = isset($_GET['nueva_cat']);
$abrir_editar = (int) ($_GET['editar'] ?? 0);
$cats = db()->query(
    'SELECT c.id, c.nombre, c.slug, COUNT(s.id) AS total_servicios
     FROM categorias c
     LEFT JOIN servicios s ON s.categoria_id = c.id
     GROUP BY c.id, c.nombre, c.slug
     ORDER BY c.id'
)->fetchAll();
$servicios = db()->query(
    'SELECT s.*, c.nombre AS categoria
     FROM servicios s
     JOIN categorias c ON c.id = s.categoria_id
     ORDER BY c.id, s.id'
)->fetchAll();
if ($filtro_cat > 0) {
    $servicios = array_values(array_filter($servicios, static function ($s) use ($filtro_cat) {
        return (int) $s['categoria_id'] === $filtro_cat;
    }));
}

$titulo = 'Haircut Studio - Servicios';
$seccion = 'admin';
$pagina = 'servicios';
require __DIR__ . '/../includes/header.php';
?>

<section class="card catalog-admin">
<div class="catalog-toolbar">
<h2>Servicios</h2>
<div class="catalog-toolbar-actions">
<button type="button" class="btn btn-secondary" id="serv-cat-toggle" aria-expanded="<?php echo $abrir_cat ? 'true' : 'false'; ?>" aria-controls="serv-cat-panel">Categorías</button>
<button type="button" class="btn btn-primary" id="serv-add-toggle" aria-expanded="<?php echo $abrir_alta ? 'true' : 'false'; ?>" aria-controls="serv-add-panel">Agregar servicio +</button>
</div>
</div>

<div class="catalog-add-panel" id="serv-cat-panel" <?php echo $abrir_cat ? '' : 'hidden'; ?>>
<p class="hint">Las categorías agrupan los tipos de servicio que ve la clienta al reservar.</p>
<div class="catalog-form-grid">
<form method="post" class="catalog-add-form" enctype="multipart/form-data">
<input type="hidden" name="accion" value="categoria">
<input type="hidden" name="volver_cat" value="<?php echo (int) $filtro_cat; ?>">
<div class="field">
<label for="cat-nombre">Nueva categoría</label>
<input type="text" id="cat-nombre" name="nombre" required placeholder="Ej: Tratamientos">
</div>
<div class="field">
<label for="cat-img">Imagen / icono</label>
<input type="file" id="cat-img" name="imagen" accept="image/*">
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Agregar categoría</button>
<button type="button" class="btn btn-secondary" id="serv-cat-cancel">Cancelar</button>
</div>
</form>
<form method="post" class="catalog-add-form" onsubmit="return confirm('¿Eliminar esta categoría y sus tipos de servicio?');">
<input type="hidden" name="accion" value="borrar_categoria">
<input type="hidden" name="volver_cat" value="<?php echo (int) $filtro_cat; ?>">
<div class="field">
<label for="cat-del">Eliminar categoría</label>
<select id="cat-del" name="id" required>
<?php foreach ($cats as $c): ?>
<option value="<?php echo (int) $c['id']; ?>"><?php echo h($c['nombre']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="actions">
<button type="submit" class="btn btn-secondary">Eliminar</button>
</div>
</form>
</div>
</div>

<div class="catalog-add-panel" id="serv-add-panel" <?php echo $abrir_alta ? '' : 'hidden'; ?>>
<p class="hint">Estos servicios aparecen al reservar hora.</p>
<form method="post" class="catalog-add-form" enctype="multipart/form-data">
<input type="hidden" name="accion" value="servicio">
<input type="hidden" name="volver_cat" value="<?php echo (int) $filtro_cat; ?>">
<div class="catalog-form-grid">
<div class="field">
<label for="serv-cat">Categoría</label>
<select id="serv-cat" name="categoria_id" required>
<?php foreach ($cats as $c): ?>
<option value="<?php echo (int) $c['id']; ?>" <?php echo $filtro_cat === (int) $c['id'] ? 'selected' : ''; ?>><?php echo h($c['nombre']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="field">
<label for="serv-nombre">Nombre</label>
<input type="text" id="serv-nombre" name="nombre" required placeholder="Ej: Color global">
</div>
<div class="field">
<label for="serv-desc">Descripción</label>
<input type="text" id="serv-desc" name="descripcion" placeholder="Opcional">
</div>
<div class="field">
<label for="serv-dur">Duración (minutos)</label>
<input type="text" id="serv-dur" name="duracion_min" value="60">
</div>
<div class="field">
<label for="serv-precio">Precio (CLP)</label>
<input type="text" id="serv-precio" name="precio" value="15000">
</div>
<div class="field">
<label for="serv-img">Imagen / icono</label>
<input type="file" id="serv-img" name="imagen" accept="image/*">
</div>
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Guardar servicio</button>
<button type="button" class="btn btn-secondary" id="serv-add-cancel">Cancelar</button>
</div>
</form>
</div>

<div class="catalog-search">
<label class="visually-hidden" for="serv-search">Buscar servicio</label>
<div class="catalog-search-wrap">
<svg class="catalog-search-ico" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14"/></svg>
<input type="search" id="serv-search" placeholder="Busca por nombre o categoría" autocomplete="off">
</div>
</div>

<div class="cat-filters" role="tablist" aria-label="Filtrar por categoría">
<a class="book-chip <?php echo $filtro_cat < 1 ? 'is-on' : ''; ?>" href="<?php echo h(url('admin/servicios.php')); ?>">Todas</a>
<?php foreach ($cats as $c): ?>
<a class="book-chip <?php echo $filtro_cat === (int) $c['id'] ? 'is-on' : ''; ?>"
 href="<?php echo h(url('admin/servicios.php?cat=' . (int) $c['id'])); ?>"><?php echo h($c['nombre']); ?></a>
<?php endforeach; ?>
</div>

<?php if (!$servicios): ?>
<p class="hint">No hay servicios<?php echo $filtro_cat ? ' en esta categoría' : ''; ?>. Usa “Agregar servicio +” para crear uno.</p>
<?php else: ?>
<div class="catalog-table servicios-table" role="table" aria-label="Servicios">
<div class="catalog-table-head" role="row">
<span role="columnheader">Servicio</span>
<span role="columnheader">Categoría</span>
<span role="columnheader">Duración</span>
<span role="columnheader">Precio</span>
<span role="columnheader">Estado</span>
<span role="columnheader">Opciones</span>
</div>
<?php foreach ($servicios as $s):
    $sid = (int) $s['id'];
    $edit_abierto = $abrir_editar === $sid;
    $busca = strtolower($s['nombre'] . ' ' . $s['categoria']);
?>
<article class="catalog-row" role="row" data-nombre="<?php echo h($busca); ?>">
<div class="catalog-row-main">
<div class="catalog-row-product">
<div class="catalog-thumb">
<?php echo html_icono_item($s['imagen'] ?? null, clave_icono_servicio($s)); ?>
</div>
<div>
<strong class="catalog-row-name"><?php echo h($s['nombre']); ?></strong>
<?php if (!empty($s['descripcion'])): ?>
<p class="catalog-row-desc"><?php echo h($s['descripcion']); ?></p>
<?php endif; ?>
</div>
</div>
<div class="catalog-row-cat" data-label="Categoría"><?php echo h($s['categoria']); ?></div>
<div class="catalog-row-dur" data-label="Duración"><?php echo h(duracion_aprox((int) $s['duracion_min'])); ?></div>
<div class="catalog-row-price" data-label="Precio"><?php echo h(precio_clp($s['precio'] ?? 0)); ?></div>
<div class="catalog-row-status" data-label="Estado">
<span class="catalog-badge <?php echo (int) $s['activo'] ? 'is-on' : 'is-off'; ?>">
<?php echo (int) $s['activo'] ? 'Visible' : 'Oculto'; ?>
</span>
</div>
<div class="catalog-row-actions">
<button type="button" class="catalog-icon-btn is-edit" data-edit-toggle="<?php echo $sid; ?>" aria-expanded="<?php echo $edit_abierto ? 'true' : 'false'; ?>" aria-controls="serv-edit-<?php echo $sid; ?>" title="Editar">
<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 17.3V21h3.7L17.8 9.9l-3.7-3.7L3 17.3zM20.7 7c.4-.4.4-1 0-1.4l-2.3-2.3a1 1 0 0 0-1.4 0l-1.8 1.8 3.7 3.7L20.7 7z"/></svg>
<span>Editar</span>
</button>
<form method="post" onsubmit="return confirm('¿Eliminar este servicio?');">
<input type="hidden" name="accion" value="borrar">
<input type="hidden" name="id" value="<?php echo $sid; ?>">
<input type="hidden" name="volver_cat" value="<?php echo (int) $filtro_cat; ?>">
<button type="submit" class="catalog-icon-btn is-delete" title="Eliminar">
<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M6 7h12v2H6V7zm2 3h8l-.7 10H8.7L8 10zm3-6h2l1 2h4v2H6V6h4l1-2z"/></svg>
<span>Eliminar</span>
</button>
</form>
</div>
</div>
<div class="catalog-row-edit" id="serv-edit-<?php echo $sid; ?>" <?php echo $edit_abierto ? '' : 'hidden'; ?>>
<form method="post" class="catalog-add-form" enctype="multipart/form-data">
<input type="hidden" name="accion" value="editar">
<input type="hidden" name="id" value="<?php echo $sid; ?>">
<input type="hidden" name="volver_cat" value="<?php echo (int) $filtro_cat; ?>">
<div class="catalog-form-grid">
<div class="field">
<label>Categoría</label>
<select name="categoria_id" required>
<?php foreach ($cats as $c): ?>
<option value="<?php echo (int) $c['id']; ?>" <?php echo (int) $s['categoria_id'] === (int) $c['id'] ? 'selected' : ''; ?>><?php echo h($c['nombre']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="field">
<label>Nombre</label>
<input type="text" name="nombre" value="<?php echo h($s['nombre']); ?>" required>
</div>
<div class="field">
<label>Descripción</label>
<input type="text" name="descripcion" value="<?php echo h($s['descripcion']); ?>">
</div>
<div class="field">
<label>Duración (min)</label>
<input type="text" name="duracion_min" value="<?php echo (int) $s['duracion_min']; ?>">
</div>
<div class="field">
<label>Precio (CLP)</label>
<input type="text" name="precio" value="<?php echo (int) ($s['precio'] ?? 0); ?>">
</div>
<div class="field">
<label>Imagen / icono</label>
<input type="file" name="imagen" accept="image/*">
<?php if (!empty($s['imagen'])): ?>
<p class="hint">Actual: <?php echo h($s['imagen']); ?>. Sube otra para reemplazarla.</p>
<?php endif; ?>
</div>
<div class="field catalog-field-check">
<label><input type="checkbox" name="activo" <?php echo (int) $s['activo'] ? 'checked' : ''; ?>> Visible al reservar</label>
</div>
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Guardar cambios</button>
<button type="button" class="btn btn-secondary" data-edit-cancel>Cancelar</button>
</div>
</form>
</div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<script>
(function () {
  function setOpen(el, btn, open) {
    if (!el) return;
    el.hidden = !open;
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  var addBtn = document.getElementById('serv-add-toggle');
  var addPanel = document.getElementById('serv-add-panel');
  var addCancel = document.getElementById('serv-add-cancel');
  var catBtn = document.getElementById('serv-cat-toggle');
  var catPanel = document.getElementById('serv-cat-panel');
  var search = document.getElementById('serv-search');

  if (addBtn && addPanel) {
    addBtn.addEventListener('click', function () {
      var open = addPanel.hidden;
      setOpen(addPanel, addBtn, open);
      if (open) {
        setOpen(catPanel, catBtn, false);
        var input = document.getElementById('serv-nombre');
        if (input) input.focus();
      }
    });
  }
  if (addCancel && addPanel && addBtn) {
    addCancel.addEventListener('click', function () {
      setOpen(addPanel, addBtn, false);
    });
  }
  if (catBtn && catPanel) {
    catBtn.addEventListener('click', function () {
      var open = catPanel.hidden;
      setOpen(catPanel, catBtn, open);
      if (open) setOpen(addPanel, addBtn, false);
    });
  }
  var catCancel = document.getElementById('serv-cat-cancel');
  if (catCancel && catPanel && catBtn) {
    catCancel.addEventListener('click', function () {
      setOpen(catPanel, catBtn, false);
    });
  }

  document.querySelectorAll('[data-edit-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-edit-toggle');
      var panel = document.getElementById('serv-edit-' + id);
      if (!panel) return;
      setOpen(panel, btn, panel.hidden);
    });
  });

  document.querySelectorAll('[data-edit-cancel]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = btn.closest('.catalog-row-edit');
      if (!panel) return;
      var toggle = document.querySelector('[data-edit-toggle][aria-controls="' + panel.id + '"]');
      setOpen(panel, toggle, false);
    });
  });

  if (search) {
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      document.querySelectorAll('.catalog-row').forEach(function (row) {
        var nombre = row.getAttribute('data-nombre') || '';
        row.hidden = q !== '' && nombre.indexOf(q) === -1;
      });
    });
  }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
