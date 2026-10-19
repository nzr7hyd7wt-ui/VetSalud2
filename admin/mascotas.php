<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requiereAdmin();

$titulo = 'Mascotas · VetSalud';
$paginaAdmin = 'mascotas';
$error = '';

$especies = $mysqli->query('SELECT id_especie, nombre FROM especie ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
$razas = $mysqli->query('SELECT id_raza, nombre, id_especie FROM raza ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
$duenos = $mysqli->query('SELECT id_dueno, nombre, telefono FROM dueno ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear' || $accion === 'editar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $idEspecie = (int)($_POST['id_especie'] ?? 0);
        $idRaza = (int)($_POST['id_raza'] ?? 0) ?: null;
        $idDueno = (int)($_POST['id_dueno'] ?? 0);
        $edad = ($_POST['edad_anios'] ?? '') !== '' ? (int)$_POST['edad_anios'] : null;
        $notas = trim($_POST['notas'] ?? '');
        $id = (int)($_POST['id_mascota'] ?? 0);

        if ($nombre === '' || !$idEspecie || !$idDueno) {
            $error = 'Nombre, especie y dueño son obligatorios.';
        } else {
            if ($idRaza) {
                $chk = $mysqli->prepare('SELECT id_raza FROM raza WHERE id_raza = ? AND id_especie = ?');
                $chk->bind_param('ii', $idRaza, $idEspecie);
                $chk->execute();
                if (!$chk->get_result()->fetch_assoc()) {
                    $error = 'La raza no corresponde a la especie.';
                }
            }

            if ($error === '') {
                if ($accion === 'crear') {
                    if ($idRaza === null) {
                        $stmt = $mysqli->prepare(
                            'INSERT INTO mascota (nombre, id_especie, id_raza, id_dueno, edad_anios, notas)
                             VALUES (?, ?, NULL, ?, ?, ?)'
                        );
                        $stmt->bind_param('siiis', $nombre, $idEspecie, $idDueno, $edad, $notas);
                    } else {
                        $stmt = $mysqli->prepare(
                            'INSERT INTO mascota (nombre, id_especie, id_raza, id_dueno, edad_anios, notas)
                             VALUES (?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->bind_param('siiiis', $nombre, $idEspecie, $idRaza, $idDueno, $edad, $notas);
                    }
                    $stmt->execute();
                    guardarMensaje('Mascota creada.');
                } else {
                    if ($idRaza === null) {
                        $stmt = $mysqli->prepare(
                            'UPDATE mascota SET nombre=?, id_especie=?, id_raza=NULL, id_dueno=?, edad_anios=?, notas=?
                             WHERE id_mascota=?'
                        );
                        $stmt->bind_param('siiisi', $nombre, $idEspecie, $idDueno, $edad, $notas, $id);
                    } else {
                        $stmt = $mysqli->prepare(
                            'UPDATE mascota SET nombre=?, id_especie=?, id_raza=?, id_dueno=?, edad_anios=?, notas=?
                             WHERE id_mascota=?'
                        );
                        $stmt->bind_param('siiiisi', $nombre, $idEspecie, $idRaza, $idDueno, $edad, $notas, $id);
                    }
                    $stmt->execute();
                    guardarMensaje('Mascota actualizada.');
                }
                header('Location: mascotas.php');
                exit;
            }
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id_mascota'] ?? 0);
        $stmt = $mysqli->prepare('DELETE FROM mascota WHERE id_mascota = ?');
        $stmt->bind_param('i', $id);
        try {
            $stmt->execute();
            guardarMensaje('Mascota eliminada.');
            header('Location: mascotas.php');
            exit;
        } catch (Throwable $e) {
            $error = 'No se puede eliminar: tiene citas asociadas.';
        }
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $mysqli->prepare('SELECT * FROM mascota WHERE id_mascota = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

$lista = $mysqli->query(
    'SELECT m.id_mascota, m.nombre, m.edad_anios, m.notas,
            e.nombre AS especie, r.nombre AS raza,
            d.nombre AS dueno
     FROM mascota m
     INNER JOIN especie e ON e.id_especie = m.id_especie
     LEFT JOIN raza r ON r.id_raza = m.id_raza
     INNER JOIN dueno d ON d.id_dueno = m.id_dueno
     ORDER BY m.nombre'
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../includes/header_admin.php';
?>
<div class="toolbar">
    <h2>Mascotas</h2>
</div>
<?php if ($error): ?><p class="mensaje error"><?= e($error) ?></p><?php endif; ?>

<form method="post" class="form" style="margin-bottom:1.5rem;max-width:640px">
    <h3><?= $editar ? 'Editar mascota' : 'Nueva mascota' ?></h3>
    <input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'crear' ?>">
    <?php if ($editar): ?><input type="hidden" name="id_mascota" value="<?= (int)$editar['id_mascota'] ?>"><?php endif; ?>
    <label>Nombre <input name="nombre" required value="<?= e($editar['nombre'] ?? '') ?>"></label>
    <label>Dueño
        <select name="id_dueno" required>
            <option value="">Seleccionar</option>
            <?php foreach ($duenos as $d): ?>
                <option value="<?= (int)$d['id_dueno'] ?>"
                    <?= ((int)($editar['id_dueno'] ?? 0) === (int)$d['id_dueno']) ? 'selected' : '' ?>>
                    <?= e($d['nombre']) ?> (<?= e($d['telefono']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="form-row">
        <label>Especie
            <select name="id_especie" id="id_especie" required>
                <option value="">Seleccionar</option>
                <?php foreach ($especies as $esp): ?>
                    <option value="<?= (int)$esp['id_especie'] ?>"
                        <?= ((int)($editar['id_especie'] ?? 0) === (int)$esp['id_especie']) ? 'selected' : '' ?>>
                        <?= e($esp['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Raza
            <select name="id_raza" id="id_raza">
                <option value="">Opcional</option>
                <?php foreach ($razas as $r): ?>
                    <option value="<?= (int)$r['id_raza'] ?>" data-especie="<?= (int)$r['id_especie'] ?>"
                        <?= ((int)($editar['id_raza'] ?? 0) === (int)$r['id_raza']) ? 'selected' : '' ?>>
                        <?= e($r['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <label>Edad (años) <input type="number" min="0" max="40" name="edad_anios" value="<?= e($editar['edad_anios'] ?? '') ?>"></label>
    <label>Notas <textarea name="notas" rows="2"><?= e($editar['notas'] ?? '') ?></textarea></label>
    <button class="btn btn-primary" type="submit">Guardar</button>
    <?php if ($editar): ?><a class="btn btn-sm" href="mascotas.php">Cancelar</a><?php endif; ?>
</form>

<div class="tabla-wrap">
<table>
    <thead>
        <tr><th>Nombre</th><th>Especie</th><th>Raza</th><th>Dueño</th><th>Edad</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($lista as $m): ?>
        <tr>
            <td><?= e($m['nombre']) ?></td>
            <td><?= e($m['especie']) ?></td>
            <td><?= e($m['raza'] ?? '—') ?></td>
            <td><?= e($m['dueno']) ?></td>
            <td><?= $m['edad_anios'] !== null ? (int)$m['edad_anios'] : '—' ?></td>
            <td class="acciones">
                <a class="btn btn-sm btn-primary" href="?editar=<?= (int)$m['id_mascota'] ?>">Editar</a>
                <form method="post" onsubmit="return confirm('¿Eliminar mascota?')">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_mascota" value="<?= (int)$m['id_mascota'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<script>
(function () {
  const especie = document.getElementById('id_especie');
  const raza = document.getElementById('id_raza');
  if (!especie || !raza) return;
  function filtrar() {
    const id = especie.value;
    [...raza.options].forEach((opt, i) => {
      if (i === 0) return;
      const ok = !id || opt.dataset.especie === id;
      opt.hidden = !ok;
      if (!ok && opt.selected) raza.value = '';
    });
  }
  especie.addEventListener('change', filtrar);
  filtrar();
})();
</script>
<?php require __DIR__ . '/../includes/footer_admin.php'; ?>
