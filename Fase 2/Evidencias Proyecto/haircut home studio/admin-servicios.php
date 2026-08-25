<?php
require_once __DIR__ . '/includes/funciones.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $volver_cat = (int) ($_POST['volver_cat'] ?? 0);

    if ($accion === 'categoria') {
        $nombre = trim($_POST['nombre'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombre));
        $slug = trim($slug, '-');
        if ($nombre === '' || $slug === '') {
            flash('error', 'El nombre de la categoría no puede estar vacío.');
        } else {
            try {
                db()->prepare('INSERT INTO categorias (nombre, slug) VALUES (?, ?)')->execute([$nombre, $slug]);
                flash('ok', 'Categoría creada.');
            } catch (PDOException $e) {
                flash('error', 'No se pudo crear la categoría (¿nombre repetido?).');
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
        } else {
            db()->prepare(
                'INSERT INTO servicios (categoria_id, nombre, descripcion, duracion_min, precio) VALUES (?, ?, ?, ?, ?)'
            )->execute([$cat, $nombre, $desc, max(60, $dur), max(0, $precio)]);
            flash('ok', 'Servicio agregado.');
        }
    }

    if ($accion === 'editar') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        $dur = (int) ($_POST['duracion_min'] ?? 60);
        $precio = (int) ($_POST['precio'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        if ($id && $nombre !== '') {
            db()->prepare(
                'UPDATE servicios SET nombre = ?, descripcion = ?, duracion_min = ?, precio = ?, activo = ? WHERE id = ?'
            )->execute([$nombre, $desc, max(60, $dur), max(0, $precio), $activo, $id]);
            flash('ok', 'Servicio actualizado.');
        }
    }

    if ($accion === 'borrar_categoria') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash('error', 'Elige una categoría para eliminar.');
        } else {
            $totalCats = (int) db()->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
            if ($totalCats <= 1) {
                flash('error', 'Debe quedar al menos una categoría.');
            } else {
                $st = db()->prepare(
                    'SELECT COUNT(*) FROM reservas r
                     JOIN servicios s ON s.id = r.servicio_id
                     WHERE s.categoria_id = ?'
                );
                $st->execute([$id]);
                if ((int) $st->fetchColumn() > 0) {
                    flash('error', 'No se puede eliminar: hay reservas en servicios de esa categoría.');
                } else {
                    try {
                        db()->prepare('DELETE FROM servicios WHERE categoria_id = ?')->execute([$id]);
                        db()->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
                        flash('ok', 'Categoría eliminada.');
                    } catch (PDOException $e) {
                        flash('error', 'No se pudo eliminar la categoría.');
                    }
                }
            }
        }
    }

    if ($accion === 'borrar') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            db()->prepare('DELETE FROM servicios WHERE id = ?')->execute([$id]);
            flash('ok', 'Servicio eliminado.');
        } catch (PDOException $e) {
            flash('error', 'No se puede borrar: hay reservas asociadas. Desactívalo en su lugar.');
        }
    }

    header('Location: admin-servicios.php' . (!empty($volver_cat) ? ('?cat=' . (int) $volver_cat) : ''));
    exit;
}

$filtro_cat = (int) ($_GET['cat'] ?? 0);
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

$titulo = 'Haircut Studio - Servicios';
$seccion = 'admin';
$pagina = 'servicios';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-split">
<section class="card admin-split-card cat-panel">
<h2>Categorías</h2>
<div class="split-stack">
<form method="post" class="cat-add">
<input type="hidden" name="accion" value="categoria">
<div class="field">
<label for="cat-nombre">Nombre</label>
<input type="text" id="cat-nombre" name="nombre" required placeholder="Ej: Tratamientos">
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Agregar categoría</button>
</div>
</form>
<form method="post" class="cat-del" onsubmit="return confirm('¿Eliminar esta categoría y sus tipos de servicio?');">
<input type="hidden" name="accion" value="borrar_categoria">
<div class="field">
<label for="cat-del">Eliminar</label>
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
</section>

<section class="card admin-split-card">
<h2>Nuevo servicio</h2>
<form method="post" class="split-stack">
<input type="hidden" name="accion" value="servicio">
<div class="field">
<label for="serv-cat">Categoría</label>
<select id="serv-cat" name="categoria_id" required>
<?php foreach ($cats as $c): ?>
<option value="<?php echo (int) $c['id']; ?>"><?php echo h($c['nombre']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="field">
<label for="serv-nombre">Nombre</label>
<input type="text" id="serv-nombre" name="nombre" required>
</div>
<div class="field">
<label for="serv-desc">Descripción</label>
<input type="text" id="serv-desc" name="descripcion">
</div>
<div class="field">
<label for="serv-dur">Duración (minutos)</label>
<input type="text" id="serv-dur" name="duracion_min" value="60">
</div>
<div class="field">
<label for="serv-precio">Precio (CLP)</label>
<input type="text" id="serv-precio" name="precio" value="15000">
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Agregar servicio</button>
</div>
</form>
</section>
</div>

<section class="card">
<h2>Catálogo actual</h2>
<div class="cat-filters" role="tablist" aria-label="Filtrar por categoría">
<a class="book-chip <?php echo $filtro_cat < 1 ? 'is-on' : ''; ?>" href="admin-servicios.php">Todas</a>
<?php foreach ($cats as $c): ?>
<a class="book-chip <?php echo $filtro_cat === (int) $c['id'] ? 'is-on' : ''; ?>"
 href="admin-servicios.php?cat=<?php echo (int) $c['id']; ?>"><?php echo h($c['nombre']); ?></a>
<?php endforeach; ?>
</div>

<?php
$mostradas = 0;
foreach ($cats as $c):
    if ($filtro_cat > 0 && $filtro_cat !== (int) $c['id']) {
        continue;
    }
    $lista = [];
    foreach ($servicios as $s) {
        if ((int) $s['categoria_id'] === (int) $c['id']) {
            $lista[] = $s;
        }
    }
    $mostradas++;
?>
<div class="catalog-cat">
<div class="catalog-cat-head">
<div>
<p class="cat-card-kicker">Tipos</p>
<h3><?php echo h($c['nombre']); ?></h3>
<p class="hint"><?php echo count($lista); ?> <?php echo count($lista) === 1 ? 'tipo' : 'tipos'; ?></p>
</div>
</div>

<?php if (!$lista): ?>
<p class="hint">No hay tipos en esta categoría.</p>
<?php else: ?>
<div class="carousel carousel-cover carousel-tipos" id="carousel-tipos-<?php echo (int) $c['id']; ?>">
<button type="button" class="carousel-btn carousel-btn-prev" data-dir="-1" aria-label="Anterior">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
</button>
<div class="carousel-viewport">
<div class="carousel-track">
<?php foreach ($lista as $s): ?>
<article class="tipo-card">
<form method="post" class="servicio-edit">
<input type="hidden" name="accion" value="editar">
<input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
<input type="hidden" name="volver_cat" value="<?php echo (int) $filtro_cat; ?>">
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
<label><input type="checkbox" name="activo" <?php echo (int) $s['activo'] ? 'checked' : ''; ?>> Activo en el catálogo</label>
</div>
<div class="actions">
<button type="submit" class="btn btn-primary">Guardar</button>
</div>
</form>
<form method="post" onsubmit="return confirm('¿Eliminar este servicio?');">
<input type="hidden" name="accion" value="borrar">
<input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
<input type="hidden" name="volver_cat" value="<?php echo (int) $filtro_cat; ?>">
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
</div>
<?php endforeach; ?>

<?php if ($mostradas < 1): ?>
<p class="hint">No hay categorías en el catálogo.</p>
<?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
