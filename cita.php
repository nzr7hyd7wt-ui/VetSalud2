<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$titulo = 'Solicitar cita · VetSalud';
$pagina = 'cita';
$error = '';
$exito = false;
$waLink = '';
$resumen = [];

$servicios = $mysqli->query(
    'SELECT id_servicio, nombre, precio, duracion_min FROM servicio WHERE activo = 1 ORDER BY nombre'
)->fetch_all(MYSQLI_ASSOC);
$especies = $mysqli->query('SELECT id_especie, nombre FROM especie ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
$razas = $mysqli->query('SELECT id_raza, nombre, id_especie FROM raza ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);

$servicioSel = (int)($_GET['servicio'] ?? $_POST['id_servicio'] ?? 0);
$horasSugeridas = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreDueno = trim($_POST['nombre_dueno'] ?? '');
    $telefono = preg_replace('/\D+/', '', $_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $nombreMascota = trim($_POST['nombre_mascota'] ?? '');
    $idEspecie = (int)($_POST['id_especie'] ?? 0);
    $idRaza = (int)($_POST['id_raza'] ?? 0) ?: null;
    $razaTexto = trim($_POST['raza_texto'] ?? '');
    $edad = ($_POST['edad_anios'] ?? '') !== '' ? (int)$_POST['edad_anios'] : null;
    $idServicio = (int)($_POST['id_servicio'] ?? 0);
    $fecha = trim($_POST['fecha'] ?? '');
    $hora = trim($_POST['hora'] ?? '');
    $notas = trim($_POST['notas'] ?? '');

    if ($nombreDueno === '' || strlen($telefono) < 7 || $nombreMascota === '' || !$idEspecie || !$idServicio || $fecha === '' || $hora === '') {
        $error = 'Completa dueño, teléfono, mascota, especie, servicio, fecha y hora.';
    } elseif ($fecha < date('Y-m-d')) {
        $error = 'La fecha no puede ser en el pasado.';
    } else {
        $chk = $mysqli->prepare('SELECT id_servicio, nombre, precio FROM servicio WHERE id_servicio = ? AND activo = 1');
        $chk->bind_param('i', $idServicio);
        $chk->execute();
        $serv = $chk->get_result()->fetch_assoc();
        if (!$serv) {
            $error = 'Servicio no válido.';
        } elseif ($idRaza !== null) {
            $chkR = $mysqli->prepare('SELECT id_raza FROM raza WHERE id_raza = ? AND id_especie = ?');
            $chkR->bind_param('ii', $idRaza, $idEspecie);
            $chkR->execute();
            if (!$chkR->get_result()->fetch_assoc()) {
                $error = 'La raza no corresponde a la especie.';
            }
        }

        if ($error === '') {
            $mysqli->begin_transaction();
            try {
                if ($idRaza === null && $razaTexto !== '') {
                    $bus = $mysqli->prepare('SELECT id_raza FROM raza WHERE nombre = ? AND id_especie = ?');
                    $bus->bind_param('si', $razaTexto, $idEspecie);
                    $bus->execute();
                    $exR = $bus->get_result()->fetch_assoc();
                    if ($exR) {
                        $idRaza = (int)$exR['id_raza'];
                    } else {
                        $insR = $mysqli->prepare('INSERT INTO raza (nombre, id_especie) VALUES (?, ?)');
                        $insR->bind_param('si', $razaTexto, $idEspecie);
                        $insR->execute();
                        $idRaza = (int)$mysqli->insert_id;
                    }
                }

                $stmt = $mysqli->prepare('SELECT id_dueno FROM dueno WHERE telefono = ? LIMIT 1');
                $stmt->bind_param('s', $telefono);
                $stmt->execute();
                $dueno = $stmt->get_result()->fetch_assoc();

                if ($dueno) {
                    $idDueno = (int)$dueno['id_dueno'];
                    $upd = $mysqli->prepare('UPDATE dueno SET nombre = ?, correo = ?, direccion = ? WHERE id_dueno = ?');
                    $upd->bind_param('sssi', $nombreDueno, $correo, $direccion, $idDueno);
                    $upd->execute();
                } else {
                    $ins = $mysqli->prepare('INSERT INTO dueno (nombre, telefono, correo, direccion) VALUES (?,?,?,?)');
                    $ins->bind_param('ssss', $nombreDueno, $telefono, $correo, $direccion);
                    $ins->execute();
                    $idDueno = (int)$mysqli->insert_id;
                }

                $notasMascota = null;
                if ($idRaza === null) {
                    $insM = $mysqli->prepare(
                        'INSERT INTO mascota (nombre, id_especie, id_raza, id_dueno, edad_anios, notas)
                         VALUES (?, ?, NULL, ?, ?, ?)'
                    );
                    $insM->bind_param('siiis', $nombreMascota, $idEspecie, $idDueno, $edad, $notasMascota);
                } else {
                    $insM = $mysqli->prepare(
                        'INSERT INTO mascota (nombre, id_especie, id_raza, id_dueno, edad_anios, notas)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $insM->bind_param('siiiis', $nombreMascota, $idEspecie, $idRaza, $idDueno, $edad, $notasMascota);
                }
                $insM->execute();
                $idMascota = (int)$mysqli->insert_id;

                $estado = 'pendiente';
                $horaSql = strlen($hora) === 5 ? $hora . ':00' : $hora;
                $insC = $mysqli->prepare(
                    'INSERT INTO cita (id_dueno, id_mascota, id_servicio, fecha, hora, estado, notas, telefono_contacto)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $insC->bind_param('iiisssss', $idDueno, $idMascota, $idServicio, $fecha, $horaSql, $estado, $notas, $telefono);
                $insC->execute();
                $idCita = (int)$mysqli->insert_id;

                $mysqli->commit();
                $exito = true;
                $resumen = [
                    'id' => $idCita,
                    'servicio' => $serv['nombre'],
                    'precio' => $serv['precio'],
                    'mascota' => $nombreMascota,
                    'dueno' => $nombreDueno,
                    'fecha' => $fecha,
                    'hora' => substr($horaSql, 0, 5),
                    'telefono' => $telefono,
                ];
                $msg = "Hola VetSalud, solicité cita #{$idCita} de {$serv['nombre']} para {$nombreMascota} el {$fecha} a las {$hora}. Dueño: {$nombreDueno}, tel: {$telefono}.";
                $waLink = whatsappUrl($msg);
            } catch (Throwable $e) {
                $mysqli->rollback();
                $error = 'No se pudo guardar la cita. Intenta de nuevo.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="seccion-titulo cita-intro">
    <span class="eyebrow">Agenda online</span>
    <h2>Solicitar cita</h2>
    <p class="meta">Sin registro. Tu solicitud queda <strong>pendiente</strong> y el equipo la confirma.</p>
</section>

<div class="pasos-cita" aria-hidden="true">
    <span class="<?= $exito ? '' : 'activo' ?>">1 · Tus datos</span>
    <span class="<?= $exito ? '' : 'activo' ?>">2 · Mascota y servicio</span>
    <span class="<?= $exito ? 'activo' : '' ?>">3 · Confirmación</span>
</div>

<?php if ($error): ?><p class="mensaje error"><?= e($error) ?></p><?php endif; ?>

<?php if ($exito): ?>
    <div class="confirmacion">
        <div class="confirmacion__icon" aria-hidden="true">✓</div>
        <h3>¡Solicitud enviada!</h3>
        <p class="meta">Cita #<?= (int)$resumen['id'] ?> en estado <span class="badge badge-pendiente">Pendiente</span></p>
        <div class="confirmacion-detalle">
            <div><strong>Servicio:</strong> <?= e($resumen['servicio']) ?> · <?= e(formatearPrecio($resumen['precio'])) ?></div>
            <div><strong>Mascota:</strong> <?= e($resumen['mascota']) ?></div>
            <div><strong>Dueño:</strong> <?= e($resumen['dueno']) ?> · <?= e($resumen['telefono']) ?></div>
            <div><strong>Fecha y hora:</strong> <?= e($resumen['fecha']) ?> a las <?= e($resumen['hora']) ?></div>
        </div>
        <p class="meta" style="margin-bottom:1rem">Confirma por WhatsApp para agilizar la respuesta del equipo.</p>
        <div class="confirmacion-acciones">
            <a class="btn btn-wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener">Confirmar por WhatsApp</a>
            <a class="btn btn-primary" href="index.php#servicios">Ver servicios</a>
            <a class="btn btn-ghost-dark btn-sm" href="cita.php">Nueva solicitud</a>
        </div>
    </div>
<?php else: ?>
<div class="cita-layout">
<form method="post" class="form">
    <h3>1. Datos del dueño</h3>
    <label>Nombre completo
        <input name="nombre_dueno" required autocomplete="name" placeholder="Ej. María López" value="<?= e($_POST['nombre_dueno'] ?? '') ?>">
    </label>
    <div class="form-row">
        <label>Teléfono / WhatsApp
            <input name="telefono" required inputmode="tel" autocomplete="tel" placeholder="3001112233" value="<?= e($_POST['telefono'] ?? '') ?>">
        </label>
        <label>Correo (opcional)
            <input type="email" name="correo" autocomplete="email" value="<?= e($_POST['correo'] ?? '') ?>">
        </label>
    </div>
    <label>Dirección (opcional)
        <input name="direccion" autocomplete="street-address" value="<?= e($_POST['direccion'] ?? '') ?>">
    </label>

    <h3>2. Tu mascota</h3>
    <label>Nombre de la mascota
        <input name="nombre_mascota" required placeholder="Ej. Max" value="<?= e($_POST['nombre_mascota'] ?? '') ?>">
    </label>
    <div class="form-row">
        <label>Especie
            <select name="id_especie" id="id_especie" required>
                <option value="">Seleccionar</option>
                <?php foreach ($especies as $esp): ?>
                    <option value="<?= (int)$esp['id_especie'] ?>" <?= ((int)($_POST['id_especie'] ?? 0) === (int)$esp['id_especie']) ? 'selected' : '' ?>>
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
                        <?= ((int)($_POST['id_raza'] ?? 0) === (int)$r['id_raza']) ? 'selected' : '' ?>>
                        <?= e($r['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="form-row">
        <label>O escribe la raza
            <input name="raza_texto" value="<?= e($_POST['raza_texto'] ?? '') ?>" placeholder="Si no está en la lista">
        </label>
        <label>Edad (años)
            <input type="number" min="0" max="40" name="edad_anios" value="<?= e($_POST['edad_anios'] ?? '') ?>">
        </label>
    </div>

    <h3>3. Servicio y horario</h3>
    <label>Servicio
        <select name="id_servicio" required>
            <option value="">Seleccionar</option>
            <?php foreach ($servicios as $s): ?>
                <option value="<?= (int)$s['id_servicio'] ?>" <?= ($servicioSel === (int)$s['id_servicio']) ? 'selected' : '' ?>>
                    <?= e($s['nombre']) ?> — <?= e(formatearPrecio($s['precio'])) ?> (<?= (int)$s['duracion_min'] ?> min)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="form-row">
        <label>Fecha preferida
            <input type="date" name="fecha" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($_POST['fecha'] ?? '') ?>">
        </label>
        <label>Hora preferida
            <input type="time" name="hora" id="hora" list="horas_sugeridas" required step="1800" value="<?= e($_POST['hora'] ?? '') ?>">
            <datalist id="horas_sugeridas">
                <?php foreach ($horasSugeridas as $h): ?>
                    <option value="<?= e($h) ?>">
                <?php endforeach; ?>
            </datalist>
        </label>
    </div>
    <label>Notas para el groomer (opcional)
        <textarea name="notas" rows="3" placeholder="Ej. corte corto, sensible a ruidos, alergia a perfume…"><?= e($_POST['notas'] ?? '') ?></textarea>
    </label>
    <button class="btn btn-aqua" type="submit">Enviar solicitud</button>
</form>

<aside class="cita-aside">
    <h3>Qué pasa después</h3>
    <ul>
        <li>Guardamos tu solicitud como pendiente</li>
        <li>El equipo revisa disponibilidad</li>
        <li>Te confirman por WhatsApp o llamada</li>
        <li>Puedes escribirnos al instante con el botón de confirmación</li>
    </ul>
    <p class="meta" style="margin-top:1rem">Horario sugerido: lun–sáb 9:00–17:00</p>
</aside>
</div>
<script>
(function () {
  const especie = document.getElementById('id_especie');
  const raza = document.getElementById('id_raza');
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
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
