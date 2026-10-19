<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requiereAdmin();

$titulo = 'Dueños · VetSalud';
$paginaAdmin = 'duenos';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear' || $accion === 'editar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = preg_replace('/\D+/', '', $_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $id = (int)($_POST['id_dueno'] ?? 0);
        if ($nombre === '' || strlen($telefono) < 7) {
            $error = 'Nombre y teléfono válidos son obligatorios.';
        } elseif ($accion === 'crear') {
            $stmt = $mysqli->prepare('INSERT INTO dueno (nombre, telefono, correo, direccion) VALUES (?,?,?,?)');
            $stmt->bind_param('ssss', $nombre, $telefono, $correo, $direccion);
            $stmt->execute();
            guardarMensaje('Dueño creado.');
            header('Location: duenos.php');
            exit;
        } else {
            $stmt = $mysqli->prepare('UPDATE dueno SET nombre=?, telefono=?, correo=?, direccion=? WHERE id_dueno=?');
            $stmt->bind_param('ssssi', $nombre, $telefono, $correo, $direccion, $id);
            $stmt->execute();
            guardarMensaje('Dueño actualizado.');
            header('Location: duenos.php');
            exit;
        }
    }
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id_dueno'] ?? 0);
        $stmt = $mysqli->prepare('DELETE FROM dueno WHERE id_dueno = ?');
        $stmt->bind_param('i', $id);
        try {
            $stmt->execute();
            guardarMensaje('Dueño eliminado.');
        } catch (Throwable $e) {
            $error = 'No se puede eliminar: tiene citas o mascotas asociadas.';
        }
        if ($error === '') {
            header('Location: duenos.php');
            exit;
        }
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $mysqli->prepare('SELECT * FROM dueno WHERE id_dueno = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

$duenos = $mysqli->query('SELECT * FROM dueno ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
require __DIR__ . '/../includes/header_admin.php';
?>
<div class="toolbar">
    <h2>Dueños</h2>
</div>
<?php if ($error): ?><p class="mensaje error"><?= e($error) ?></p><?php endif; ?>

<form method="post" class="form" style="margin-bottom:1.5rem">
    <h3><?= $editar ? 'Editar dueño' : 'Nuevo dueño' ?></h3>
    <input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'crear' ?>">
    <?php if ($editar): ?><input type="hidden" name="id_dueno" value="<?= (int)$editar['id_dueno'] ?>"><?php endif; ?>
    <label>Nombre <input name="nombre" required value="<?= e($editar['nombre'] ?? '') ?>"></label>
    <label>Teléfono <input name="telefono" required value="<?= e($editar['telefono'] ?? '') ?>"></label>
    <label>Correo <input type="email" name="correo" value="<?= e($editar['correo'] ?? '') ?>"></label>
    <label>Dirección <input name="direccion" value="<?= e($editar['direccion'] ?? '') ?>"></label>
    <button class="btn btn-primary" type="submit">Guardar</button>
    <?php if ($editar): ?><a class="btn btn-sm" href="duenos.php">Cancelar</a><?php endif; ?>
</form>

<div class="tabla-wrap">
<table>
    <thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($duenos as $d): ?>
        <tr>
            <td><?= e($d['nombre']) ?></td>
            <td><?= e($d['telefono']) ?></td>
            <td><?= e($d['correo'] ?? '') ?></td>
            <td class="acciones">
                <a class="btn btn-sm btn-primary" href="?editar=<?= (int)$d['id_dueno'] ?>">Editar</a>
                <form method="post" onsubmit="return confirm('¿Eliminar dueño?')">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_dueno" value="<?= (int)$d['id_dueno'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer_admin.php'; ?>
