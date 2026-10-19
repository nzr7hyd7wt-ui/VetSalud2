<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requiereAdmin();

$titulo = 'Panel · VetSalud';
$paginaAdmin = 'panel';

$totales = [
    'duenos' => (int)$mysqli->query('SELECT COUNT(*) c FROM dueno')->fetch_assoc()['c'],
    'mascotas' => (int)$mysqli->query('SELECT COUNT(*) c FROM mascota')->fetch_assoc()['c'],
    'servicios' => (int)$mysqli->query('SELECT COUNT(*) c FROM servicio WHERE activo = 1')->fetch_assoc()['c'],
    'pendientes' => (int)$mysqli->query("SELECT COUNT(*) c FROM cita WHERE estado = 'pendiente'")->fetch_assoc()['c'],
    'hoy' => (int)$mysqli->query("SELECT COUNT(*) c FROM cita WHERE fecha = CURDATE()")->fetch_assoc()['c'],
];

$proximas = $mysqli->query(
    "SELECT c.id_cita, c.fecha, c.hora, c.estado, d.nombre AS dueno, m.nombre AS mascota, s.nombre AS servicio
     FROM cita c
     INNER JOIN dueno d ON d.id_dueno = c.id_dueno
     INNER JOIN mascota m ON m.id_mascota = c.id_mascota
     INNER JOIN servicio s ON s.id_servicio = c.id_servicio
     WHERE c.fecha >= CURDATE() AND c.estado IN ('pendiente','confirmada')
     ORDER BY c.fecha ASC, c.hora ASC
     LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../includes/header_admin.php';
?>
<section class="seccion-titulo">
    <span class="eyebrow">Operación</span>
    <h2>Panel principal</h2>
    <p class="meta">Resumen del día y accesos rápidos</p>
</section>
<div class="metricas">
    <div class="metrica"><div class="label">Citas hoy</div><div class="valor"><?= $totales['hoy'] ?></div></div>
    <div class="metrica"><div class="label">Pendientes</div><div class="valor"><?= $totales['pendientes'] ?></div></div>
    <div class="metrica"><div class="label">Dueños</div><div class="valor"><?= $totales['duenos'] ?></div></div>
    <div class="metrica"><div class="label">Mascotas</div><div class="valor"><?= $totales['mascotas'] ?></div></div>
    <div class="metrica"><div class="label">Servicios activos</div><div class="valor"><?= $totales['servicios'] ?></div></div>
</div>

<div class="admin-quick">
    <a class="btn btn-aqua" href="agenda.php?vista=hoy">Agenda de hoy</a>
    <a class="btn btn-primary" href="agenda.php?vista=proximas&estado=pendiente">Confirmar pendientes</a>
    <a class="btn btn-ghost-dark" href="servicios.php">Gestionar servicios</a>
</div>

<section style="margin-top:2rem">
    <div class="toolbar">
        <h2 style="font-size:1.25rem;color:var(--aqua-deep)">Próximas citas</h2>
        <a class="btn btn-sm" href="agenda.php">Ver agenda completa</a>
    </div>
    <div class="tabla-wrap">
        <?php if (!$proximas): ?>
            <p class="meta" style="padding:1.5rem">No hay citas próximas pendientes o confirmadas.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Cuándo</th><th>Cliente</th><th>Mascota</th><th>Servicio</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($proximas as $c): ?>
                <tr>
                    <td>
                        <strong><?= e(formatearFechaLarga($c['fecha'])) ?></strong>
                        <br><span class="hora-chip"><?= e(substr($c['hora'], 0, 5)) ?></span>
                    </td>
                    <td><?= e($c['dueno']) ?></td>
                    <td><?= e($c['mascota']) ?></td>
                    <td><?= e($c['servicio']) ?></td>
                    <td><span class="badge badge-<?= e($c['estado']) ?>"><?= e(etiquetaEstado($c['estado'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer_admin.php'; ?>
