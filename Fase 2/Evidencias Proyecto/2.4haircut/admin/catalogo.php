<?php
require_once __DIR__ . '/../includes/funciones.php';
require_admin();
asegurar_tabla_productos();

$redir = url('admin/catalogo.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        $precio = (int) ($_POST['precio'] ?? 0);
        if ($nombre === '') {
            flash('error', 'El nombre del producto no puede estar vacío.');
            $redir = url('admin/catalogo.php?nuevo=1');
        } else {
            try {
                $imagen = guardar_foto($_FILES['imagen'] ?? [], 'prod_');
                $orden = siguiente_orden_producto();
                $cat = (int) ($_POST['categoria_id'] ?? 0);
                if ($cat < 1) {
                    $cat = (int) db()->query('SELECT id FROM categorias ORDER BY id LIMIT 1')->fetchColumn();
                }
                db()->prepare(
                    'INSERT INTO productos (categoria_id, nombre, descripcion, precio, imagen, activo, orden) VALUES (?, ?, ?, ?, ?, 1, ?)'
                )->execute([$cat, $nombre, $desc, max(0, $precio), $imagen, $orden]);
                flash('ok', 'Producto agregado al catálogo.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                $redir = url('admin/catalogo.php?nuevo=1');
            }
        }
    }

    if ($accion === 'editar') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        $precio = (int) ($_POST['precio'] ?? 0);
        $orden = (int) ($_POST['orden'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        if ($id < 1 || $nombre === '') {
            flash('error', 'Completa el nombre del producto.');
            $redir = url('admin/catalogo.php?editar=' . max(0, $id));
        } else {
            try {
                $st = db()->prepare('SELECT imagen, orden, categoria_id FROM productos WHERE id = ?');
                $st->execute([$id]);
                $actual = $st->fetch();
                $imagen = $actual['imagen'] ?? null;
                if ($orden < 1) {
                    $orden = (int) ($actual['orden'] ?? 1);
                }
                $nueva = guardar_foto($_FILES['imagen'] ?? [], 'prod_');
                if ($nueva) {
                    borrar_foto($imagen);
                    $imagen = $nueva;
                }
                $cat = (int) ($_POST['categoria_id'] ?? 0);
                if ($cat < 1) {
                    $cat = (int) ($actual['categoria_id'] ?? 0);
                }
                db()->prepare(
                    'UPDATE productos SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?, imagen = ?, activo = ?, orden = ? WHERE id = ?'
                )->execute([$cat, $nombre, $desc, max(0, $precio), $imagen, $activo, $orden, $id]);
                flash('ok', 'Producto actualizado.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                $redir = url('admin/catalogo.php?editar=' . $id);
            }
        }
    }

    if ($accion === 'borrar') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT imagen FROM productos WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if ($row) {
            borrar_foto($row['imagen'] ?? null);
            db()->prepare('DELETE FROM productos WHERE id = ?')->execute([$id]);
            flash('ok', 'Producto eliminado. El número de orden queda libre para el siguiente.');
        }
    }

    ir($redir);
}

$productos = productos_todos();
$proximo_orden = siguiente_orden_producto();
$categorias_prod = db()->query('SELECT id, nombre FROM categorias ORDER BY id')->fetchAll();
$abrir_alta = isset($_GET['nuevo']);
$abrir_editar = (int) ($_GET['editar'] ?? 0);
$titulo = 'Haircut Studio - Catálogo';
$seccion = 'admin';
$pagina = 'catalogo';
require __DIR__ . '/../includes/header.php';
?>

<section class="card catalog-admin">
<div class="catalog-toolbar">
<h2>Productos</h2>
<button type="button" class="btn btn-primary" id="catalog-add-toggle" aria-expanded="<?php echo $abrir_alta ? 'true' : 'false'; ?>" aria-controls="catalog-add-panel">
Agregar producto +
</button>
</div>

<div class="catalog-add-panel" id="catalog-add-panel" <?php echo $abrir_alta ? '' : 'hidden'; ?>>
<p class="hint">Estos productos aparecen en la ventana principal. La compra es presencial. El orden se asigna solo<?php echo $proximo_orden ? ' (siguiente: ' . (int) $proximo_orden . ')' : ''; ?>.</p>
<form method="post" enctype="multipart/form-data" class="catalog-add-form">
<input type="hidden" name="accion" value="crear">
<div class="catalog-form-grid">
<div class="field">
<label for="prod-nombre">Nombre</label>
<input type="text" id="prod-nombre" name="nombre" required placeholder="Ej: Shampoo de color">
</div>
<div class="field">
<label for="prod-desc">Descripción</label>
<input type="text" id="prod-desc" name="descripcion" placeholder="Opcional">
</div>
<div class="field">
<label for="prod-cat">Categoría</label>
<select id="prod-cat" name="categoria_id">
<?php foreach ($categorias_prod as $c): ?>
<option value="<?php echo (int) $c['id']; ?>"><?php echo h($c['nombre']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="field">
<label for="prod-precio">Precio (CLP)</label>
<input type="text" id="prod-precio" name="precio" value="12990">
</div>
<div class="field">
<label for="prod-img">Foto</label>
<input type="file" id="prod-img" name="imagen" accept="image/*">
</div>
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Guardar producto</button>
<button type="button" class="btn btn-secondary" id="catalog-add-cancel">Cancelar</button>
</div>
</form>
</div>

<div class="catalog-search">
<label class="visually-hidden" for="catalog-search">Buscar producto</label>
<div class="catalog-search-wrap">
<svg class="catalog-search-ico" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14"/></svg>
<input type="search" id="catalog-search" placeholder="Busca por nombre" autocomplete="off">
</div>
</div>

<?php if (!$productos): ?>
<p class="hint">No hay productos en el catálogo. Usa “Agregar producto +” para crear el primero.</p>
<?php else: ?>
<div class="catalog-table" role="table" aria-label="Catálogo de productos">
<div class="catalog-table-head" role="row">
<span role="columnheader">Producto</span>
<span role="columnheader">Precio</span>
<span role="columnheader">Estado</span>
<span role="columnheader">Orden</span>
<span role="columnheader">Opciones</span>
</div>
<?php foreach ($productos as $p):
    $pid = (int) $p['id'];
    $edit_abierto = $abrir_editar === $pid;
?>
<article class="catalog-row" role="row" data-nombre="<?php echo h(strtolower($p['nombre'])); ?>">
<div class="catalog-row-main">
<div class="catalog-row-product">
<div class="catalog-thumb">
<?php if (!empty($p['imagen'])): ?>
<img src="<?php echo h(foto_url($p['imagen'])); ?>" alt="">
<?php else: ?>
<span>Sin foto</span>
<?php endif; ?>
</div>
<div>
<strong class="catalog-row-name"><?php echo h($p['nombre']); ?></strong>
<?php if (!empty($p['descripcion'])): ?>
<p class="catalog-row-desc"><?php echo h($p['descripcion']); ?></p>
<?php endif; ?>
</div>
</div>
<div class="catalog-row-price" data-label="Precio"><?php echo h(precio_clp($p['precio'])); ?></div>
<div class="catalog-row-status" data-label="Estado">
<span class="catalog-badge <?php echo (int) $p['activo'] ? 'is-on' : 'is-off'; ?>">
<?php echo (int) $p['activo'] ? 'Visible' : 'Oculto'; ?>
</span>
</div>
<div class="catalog-row-orden" data-label="Orden"><?php echo (int) $p['orden']; ?></div>
<div class="catalog-row-actions">
<button type="button" class="catalog-icon-btn is-edit" data-edit-toggle="<?php echo $pid; ?>" aria-expanded="<?php echo $edit_abierto ? 'true' : 'false'; ?>" aria-controls="catalog-edit-<?php echo $pid; ?>" title="Editar">
<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 17.3V21h3.7L17.8 9.9l-3.7-3.7L3 17.3zM20.7 7c.4-.4.4-1 0-1.4l-2.3-2.3a1 1 0 0 0-1.4 0l-1.8 1.8 3.7 3.7L20.7 7z"/></svg>
<span>Editar</span>
</button>
<form method="post" onsubmit="return confirm('¿Eliminar este producto del catálogo?');">
<input type="hidden" name="accion" value="borrar">
<input type="hidden" name="id" value="<?php echo $pid; ?>">
<button type="submit" class="catalog-icon-btn is-delete" title="Eliminar">
<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M6 7h12v2H6V7zm2 3h8l-.7 10H8.7L8 10zm3-6h2l1 2h4v2H6V6h4l1-2z"/></svg>
<span>Eliminar</span>
</button>
</form>
</div>
</div>
<div class="catalog-row-edit" id="catalog-edit-<?php echo $pid; ?>" <?php echo $edit_abierto ? '' : 'hidden'; ?>>
<form method="post" enctype="multipart/form-data" class="catalog-add-form">
<input type="hidden" name="accion" value="editar">
<input type="hidden" name="id" value="<?php echo $pid; ?>">
<div class="catalog-form-grid">
<div class="field">
<label>Nombre</label>
<input type="text" name="nombre" value="<?php echo h($p['nombre']); ?>" required>
</div>
<div class="field">
<label>Descripción</label>
<input type="text" name="descripcion" value="<?php echo h($p['descripcion']); ?>">
</div>
<div class="field">
<label>Categoría</label>
<select name="categoria_id">
<?php foreach ($categorias_prod as $c): ?>
<option value="<?php echo (int) $c['id']; ?>"<?php echo (int) ($p['categoria_id'] ?? 0) === (int) $c['id'] ? ' selected' : ''; ?>><?php echo h($c['nombre']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="field">
<label>Precio (CLP)</label>
<input type="text" name="precio" value="<?php echo (int) $p['precio']; ?>">
</div>
<div class="field">
<label>Orden</label>
<input type="text" name="orden" value="<?php echo (int) $p['orden']; ?>">
</div>
<div class="field">
<label>Foto</label>
<input type="file" name="imagen" accept="image/*">
</div>
<div class="field catalog-field-check">
<label><input type="checkbox" name="activo" <?php echo (int) $p['activo'] ? 'checked' : ''; ?>> Visible en el inicio</label>
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
  var addBtn = document.getElementById('catalog-add-toggle');
  var addPanel = document.getElementById('catalog-add-panel');
  var addCancel = document.getElementById('catalog-add-cancel');
  var search = document.getElementById('catalog-search');

  function setOpen(el, btn, open) {
    if (!el) return;
    el.hidden = !open;
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (addBtn && addPanel) {
    addBtn.addEventListener('click', function () {
      setOpen(addPanel, addBtn, addPanel.hidden);
      if (!addPanel.hidden) {
        var input = document.getElementById('prod-nombre');
        if (input) input.focus();
      }
    });
  }
  if (addCancel && addPanel && addBtn) {
    addCancel.addEventListener('click', function () {
      setOpen(addPanel, addBtn, false);
    });
  }

  document.querySelectorAll('[data-edit-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-edit-toggle');
      var panel = document.getElementById('catalog-edit-' + id);
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
