<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();
asegurar_tabla_productos();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        $precio = (int) ($_POST['precio'] ?? 0);
        if ($nombre === '') {
            flash('error', 'El nombre del producto no puede estar vacío.');
        } else {
            try {
                $imagen = guardar_foto($_FILES['imagen'] ?? [], 'prod_');
                $orden = siguiente_orden_producto();
                db()->prepare(
                    'INSERT INTO productos (nombre, descripcion, precio, imagen, activo, orden) VALUES (?, ?, ?, ?, 1, ?)'
                )->execute([$nombre, $desc, max(0, $precio), $imagen, $orden]);
                flash('ok', 'Producto agregado al catálogo.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
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
        } else {
            try {
                $st = db()->prepare('SELECT imagen, orden FROM productos WHERE id = ?');
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
                db()->prepare(
                    'UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, imagen = ?, activo = ?, orden = ? WHERE id = ?'
                )->execute([$nombre, $desc, max(0, $precio), $imagen, $activo, $orden, $id]);
                flash('ok', 'Producto actualizado.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
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

    header('Location: admin-catalogo.php');
    exit;
}

$productos = productos_todos();
$proximo_orden = siguiente_orden_producto();
$titulo = 'Haircut Studio - Catálogo';
$seccion = 'admin';
$pagina = 'catalogo';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
<h2>Nuevo producto</h2>
<p class="hint">Estos productos aparecen en la ventana principal. La compra es presencial. El orden se asigna solo<?php echo $proximo_orden ? ' (siguiente: ' . (int) $proximo_orden . ')' : ''; ?>.</p>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="accion" value="crear">
<div class="field">
<label for="prod-nombre">Nombre</label>
<input type="text" id="prod-nombre" name="nombre" required placeholder="Ej: Shampoo de color">
</div>
<div class="field">
<label for="prod-desc">Descripción</label>
<input type="text" id="prod-desc" name="descripcion">
</div>
<div class="field">
<label for="prod-precio">Precio (CLP)</label>
<input type="text" id="prod-precio" name="precio" value="12990">
</div>
<div class="field">
<label for="prod-img">Foto</label>
<input type="file" id="prod-img" name="imagen" accept="image/*">
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Agregar al catálogo</button>
</div>
</form>
</section>

<section class="card">
<h2>Catálogo de productos</h2>
<?php if (!$productos): ?>
<p class="hint">No hay productos en el catálogo.</p>
<?php else: ?>
<div class="carousel carousel-cover" id="carousel-catalogo">
<button type="button" class="carousel-btn carousel-btn-prev" data-dir="-1" aria-label="Anterior">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
</button>
<div class="carousel-viewport">
<div class="carousel-track">
<?php foreach ($productos as $p): ?>
<article class="catalog-card">
<p class="cat-card-kicker">Orden <?php echo (int) $p['orden']; ?></p>
<div class="product-photo">
<?php if (!empty($p['imagen'])): ?>
<img src="uploads/fotos/<?php echo h($p['imagen']); ?>" alt="<?php echo h($p['nombre']); ?>">
<?php else: ?>
<span>Espacio para imagen</span>
<?php endif; ?>
</div>
<form method="post" enctype="multipart/form-data" class="servicio-edit">
<input type="hidden" name="accion" value="editar">
<input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
<input type="hidden" name="orden" value="<?php echo (int) $p['orden']; ?>">
<div class="field">
<label>Nombre</label>
<input type="text" name="nombre" value="<?php echo h($p['nombre']); ?>" required>
</div>
<div class="field">
<label>Descripción</label>
<input type="text" name="descripcion" value="<?php echo h($p['descripcion']); ?>">
</div>
<div class="field">
<label>Precio (CLP)</label>
<input type="text" name="precio" value="<?php echo (int) $p['precio']; ?>">
</div>
<div class="field">
<label>Foto</label>
<input type="file" name="imagen" accept="image/*">
</div>
<div class="field">
<label><input type="checkbox" name="activo" <?php echo (int) $p['activo'] ? 'checked' : ''; ?>> Visible en el inicio</label>
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Guardar</button>
</div>
</form>
<form method="post" onsubmit="return confirm('¿Eliminar este producto del catálogo?');">
<input type="hidden" name="accion" value="borrar">
<input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
<button type="submit" class="btn btn-secondary">Eliminar</button>
</form>
</article>
<?php endforeach; ?>
</div>
</div>
<button type="button" class="carousel-btn carousel-btn-next" data-dir="1" aria-label="Siguiente">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M9.5 5.5 8.1 6.9 13.2 12l-5.1 5.1 1.4 1.4L16 12z"/></svg>
</button>
</div>
<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
