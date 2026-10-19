<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requiereAdmin();

$titulo = 'Servicios · VetSalud';
$paginaAdmin = 'servicios';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear' || $accion === 'editar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = str_replace(',', '.', trim($_POST['precio'] ?? ''));
        $duracion = (int)($_POST['duracion_min'] ?? 0);
        $imagen = basename(trim($_POST['imagen'] ?? ''));
        $activo = isset($_POST['activo']) ? 1 : 0;
        $id = (int)($_POST['id_servicio'] ?? 0);

        if ($nombre === '' || !is_numeric($precio) || (float)$precio < 0 || $duracion <= 0) {
            $error = 'Nombre, precio y duración válidos son obligatorios.';
        } elseif ($accion === 'crear') {
            $precioF = (float)$precio;
            $stmt = $mysqli->prepare(
                'INSERT INTO servicio (nombre, descripcion, precio, duracion_min, imagen, activo) VALUES (?,?,?,?,?,?)'
            );
            $stmt->bind_param('ssdisi', $nombre, $descripcion, $precioF, $duracion, $imagen, $activo);
            $stmt->execute();
            guardarMensaje('Servicio creado.');
            header('Location: servicios.php');
            exit;
        } else {
            $precioF = (float)$precio;
            $stmt = $mysqli->prepare(
                'UPDATE servicio SET nombre=?, descripcion=?, precio=?, duracion_min=?, imagen=?, activo=? WHERE id_servicio=?'
            );
            $stmt->bind_param('ssdisii', $nombre, $descripcion, $precioF, $duracion, $imagen, $activo, $id);
            $stmt->execute();
            guardarMensaje('Servicio actualizado.');
            header('Location: servicios.php');
            exit;
        }
    }

    if ($accion === 'toggle') {
        $id = (int)($_POST['id_servicio'] ?? 0);
        $stmt = $mysqli->prepare('UPDATE servicio SET activo = IF(activo=1,0,1) WHERE id_servicio = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        guardarMensaje('Estado del servicio actualizado.');
        header('Location: servicios.php');
        exit;
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id_servicio'] ?? 0);
        $chk = $mysqli->prepare('SELECT COUNT(*) c FROM cita WHERE id_servicio = ?');
        $chk->bind_param('i', $id);
        $chk->execute();
        $n = (int)$chk->get_result()->fetch_assoc()['c'];
        if ($n > 0) {
            $stmt = $mysqli->prepare('UPDATE servicio SET activo = 0 WHERE id_servicio = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            guardarMensaje('Tiene citas asociadas: se desactivó el servicio.');
        } else {
            $stmt = $mysqli->prepare('DELETE FROM servicio WHERE id_servicio = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            guardarMensaje('Servicio eliminado.');
        }
        header('Location: servicios.php');
        exit;
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $mysqli->prepare('SELECT * FROM servicio WHERE id_servicio = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

$servicios = $mysqli->query('SELECT * FROM servicio ORDER BY activo DESC, nombre')->fetch_all(MYSQLI_ASSOC);
require __DIR__ . '/../includes/header_admin.php';
?>
<div class="toolbar">
    <h2>Servicios</h2>
</div>
<?php if ($error): ?><p class="mensaje error"><?= e($error) ?></p><?php endif; ?>

<form method="post" class="form" style="margin-bottom:1.5rem">
    <h3><?= $editar ? 'Editar servicio' : 'Nuevo servicio' ?></h3>
    <input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'crear' ?>">
    <?php if ($editar): ?><input type="hidden" name="id_servicio" value="<?= (int)$editar['id_servicio'] ?>"><?php endif; ?>
    <label>Nombre <input name="nombre" required value="<?= e($editar['nombre'] ?? '') ?>"></label>
    <label>Descripción <textarea name="descripcion" rows="2"><?= e($editar['descripcion'] ?? '') ?></textarea></label>
    <div class="form-row">
        <label>Precio <input type="number" name="precio" min="0" step="100" required value="<?= e($editar['precio'] ?? '') ?>"></label>
        <label>Duración (min) <input type="number" name="duracion_min" min="5" step="5" required value="<?= e($editar['duracion_min'] ?? '60') ?>"></label>
    </div>
    <label>Imagen (archivo en /img/)
        <input name="imagen" placeholder="servicio-bano.jpg" value="<?= e($editar['imagen'] ?? '') ?>">
    </label>
    <label><input type="checkbox" name="activo" value="1" <?= !isset($editar) || (int)($editar['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Activo (visible en el sitio)</label>
    <button class="btn btn-primary" type="submit">Guardar</button>
    <?php if ($editar): ?><a class="btn btn-sm" href="servicios.php">Cancelar</a><?php endif; ?>
</form>

<div class="tabla-wrap">
<table>
    <thead><tr><th></th><th>Nombre</th><th>Precio</th><th>Duración</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($servicios as $s): ?>
        <tr>
            <td style="width:64px">
                <?php if (!empty($s['imagen'])): ?>
                    <img src="../img/<?= e($s['imagen']) ?>" alt="" width="56" height="42" style="border-radius:8px;object-fit:cover;width:56px;height:42px">
                <?php endif; ?>
            </td>
            <td>
                <strong><?= e($s['nombre']) ?></strong>
                <?php if ($s['descripcion']): ?>
                    <br><span class="meta"><?= e($s['descripcion']) ?></span>
                <?php endif; ?>
            </td>
            <td><?= e(formatearPrecio($s['precio'])) ?></td>
            <td><?= (int)$s['duracion_min'] ?> min</td>
            <td>
                <span class="badge <?= (int)$s['activo'] ? 'badge-confirmada' : 'badge-cancelada' ?>">
                    <?= (int)$s['activo'] ? 'Activo' : 'Inactivo' ?>
                </span>
            </td>
            <td class="acciones">
                <a class="btn btn-sm btn-primary" href="?editar=<?= (int)$s['id_servicio'] ?>">Editar</a>
                <form method="post">
                    <input type="hidden" name="accion" value="toggle">
                    <input type="hidden" name="id_servicio" value="<?= (int)$s['id_servicio'] ?>">
                    <button class="btn btn-sm" type="submit"><?= (int)$s['activo'] ? 'Desactivar' : 'Activar' ?></button>
                </form>
                <form method="post" onsubmit="return confirm('¿Eliminar o desactivar?')">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_servicio" value="<?= (int)$s['id_servicio'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer_admin.php'; ?>
