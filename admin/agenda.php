<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requiereAdmin();

$titulo = 'Agenda · VetSalud';
$paginaAdmin = 'agenda';
$estados = ['pendiente', 'confirmada', 'cancelada', 'completada'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'estado') {
    $id = (int)($_POST['id_cita'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    if ($id > 0 && in_array($estado, $estados, true)) {
        $stmt = $mysqli->prepare('UPDATE cita SET estado = ? WHERE id_cita = ?');
        $stmt->bind_param('si', $estado, $id);
        $stmt->execute();
        guardarMensaje('Cita #' . $id . ' → ' . etiquetaEstado($estado));
    }
    $qs = http_build_query(array_filter([
        'fecha' => $_POST['filtro_fecha'] ?? '',
        'estado' => $_POST['filtro_estado'] ?? '',
        'vista' => $_POST['filtro_vista'] ?? '',
    ]));
    header('Location: agenda.php' . ($qs ? '?' . $qs : ''));
    exit;
}

$filtroFecha = trim($_GET['fecha'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');
$vista = trim($_GET['vista'] ?? 'proximas');
if (!in_array($vista, ['proximas', 'hoy', 'todas'], true)) {
    $vista = 'proximas';
}

$sql = 'SELECT c.id_cita, c.fecha, c.hora, c.estado, c.notas, c.telefono_contacto,
               d.nombre AS dueno, m.nombre AS mascota, s.nombre AS servicio, s.precio,
               e.nombre AS especie
        FROM cita c
        INNER JOIN dueno d ON d.id_dueno = c.id_dueno
        INNER JOIN mascota m ON m.id_mascota = c.id_mascota
        INNER JOIN especie e ON e.id_especie = m.id_especie
        INNER JOIN servicio s ON s.id_servicio = c.id_servicio
        WHERE 1=1';
$types = '';
$params = [];

$hoy = date('Y-m-d');
if ($filtroFecha !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroFecha)) {
    $sql .= ' AND c.fecha = ?';
    $types .= 's';
    $params[] = $filtroFecha;
} elseif ($vista === 'hoy') {
    $sql .= ' AND c.fecha = ?';
    $types .= 's';
    $params[] = $hoy;
} elseif ($vista === 'proximas') {
    $sql .= ' AND c.fecha >= ?';
    $types .= 's';
    $params[] = $hoy;
}

if ($filtroEstado !== '' && in_array($filtroEstado, $estados, true)) {
    $sql .= ' AND c.estado = ?';
    $types .= 's';
    $params[] = $filtroEstado;
}
$sql .= ' ORDER BY c.fecha ASC, c.hora ASC';

$stmt = $mysqli->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$porDia = [];
foreach ($citas as $c) {
    $porDia[$c['fecha']][] = $c;
}

$contPend = (int)$mysqli->query("SELECT COUNT(*) c FROM cita WHERE estado='pendiente' AND fecha >= CURDATE()")->fetch_assoc()['c'];
$contHoy = (int)$mysqli->query("SELECT COUNT(*) c FROM cita WHERE fecha = CURDATE() AND estado IN ('pendiente','confirmada')")->fetch_assoc()['c'];

require __DIR__ . '/../includes/header_admin.php';
?>
<div class="toolbar">
    <div>
        <h2>Agenda del salón</h2>
        <p class="meta"><?= $contHoy ?> activas hoy · <?= $contPend ?> pendientes por confirmar</p>
    </div>
    <span class="meta"><?= count($citas) ?> cita(s) en vista</span>
</div>

<div class="admin-quick" style="margin-bottom:1rem">
    <a class="btn btn-sm <?= $vista === 'hoy' && $filtroFecha === '' ? 'btn-primary' : '' ?>" href="?vista=hoy">Hoy</a>
    <a class="btn btn-sm <?= $vista === 'proximas' && $filtroFecha === '' ? 'btn-primary' : '' ?>" href="?vista=proximas">Próximas</a>
    <a class="btn btn-sm <?= $vista === 'todas' && $filtroFecha === '' ? 'btn-primary' : '' ?>" href="?vista=todas">Todas</a>
    <a class="btn btn-sm" href="?vista=proximas&estado=pendiente">Solo pendientes</a>
</div>

<form class="filtros" method="get">
    <input type="hidden" name="vista" value="<?= e($vista) ?>">
    <label>Fecha exacta
        <input type="date" name="fecha" value="<?= e($filtroFecha) ?>">
    </label>
    <label>Estado
        <select name="estado">
            <option value="">Todos</option>
            <?php foreach ($estados as $est): ?>
                <option value="<?= e($est) ?>" <?= $filtroEstado === $est ? 'selected' : '' ?>>
                    <?= e(etiquetaEstado($est)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
    <a class="btn btn-sm" href="agenda.php">Limpiar</a>
</form>

<?php if (!$citas): ?>
    <div class="tabla-wrap agenda-vacia">
        <p>No hay citas con esos filtros.</p>
    </div>
<?php else: ?>
    <?php foreach ($porDia as $fecha => $lista): ?>
        <section class="agenda-dia">
            <div class="agenda-dia__cabecera">
                <h3><?= e(formatearFechaLarga($fecha)) ?></h3>
                <span class="meta"><?= count($lista) ?> turno(s)</span>
            </div>
            <div class="tabla-wrap tabla-agenda-wrap">
                <table class="tabla-agenda">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Mascota</th>
                            <th>Servicio</th>
                            <th>Estado</th>
                            <th>Notas</th>
                            <th>Cambiar estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lista as $c): ?>
                        <tr>
                            <td data-label="Hora">
                                <span class="hora-chip"><?= e(substr($c['hora'], 0, 5)) ?></span>
                                <br><span class="meta">#<?= (int)$c['id_cita'] ?></span>
                            </td>
                            <td data-label="Cliente">
                                <strong><?= e($c['dueno']) ?></strong>
                                <br><span class="meta"><?= e($c['telefono_contacto']) ?></span>
                                <br>
                                <a class="btn btn-wa btn-sm" style="margin-top:0.35rem"
                                   href="<?= e(whatsappUrl('Hola ' . $c['dueno'] . ', te escribimos de VetSalud sobre la cita de ' . $c['mascota'] . ' el ' . $c['fecha'] . ' a las ' . substr($c['hora'], 0, 5) . '.')) ?>"
                                   target="_blank" rel="noopener">WA</a>
                            </td>
                            <td data-label="Mascota">
                                <?= e($c['mascota']) ?>
                                <br><span class="meta"><?= e($c['especie']) ?></span>
                            </td>
                            <td data-label="Servicio">
                                <?= e($c['servicio']) ?>
                                <br><span class="meta"><?= e(formatearPrecio($c['precio'])) ?></span>
                            </td>
                            <td data-label="Estado"><span class="badge badge-<?= e($c['estado']) ?>"><?= e(etiquetaEstado($c['estado'])) ?></span></td>
                            <td data-label="Notas"><?= e($c['notas'] ?: '—') ?></td>
                            <td data-label="Cambiar estado">
                                <form method="post" class="acciones">
                                    <input type="hidden" name="accion" value="estado">
                                    <input type="hidden" name="id_cita" value="<?= (int)$c['id_cita'] ?>">
                                    <input type="hidden" name="filtro_fecha" value="<?= e($filtroFecha) ?>">
                                    <input type="hidden" name="filtro_estado" value="<?= e($filtroEstado) ?>">
                                    <input type="hidden" name="filtro_vista" value="<?= e($vista) ?>">
                                    <select name="estado">
                                        <?php foreach ($estados as $est): ?>
                                            <option value="<?= e($est) ?>" <?= $c['estado'] === $est ? 'selected' : '' ?>>
                                                <?= e(etiquetaEstado($est)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">OK</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer_admin.php'; ?>
