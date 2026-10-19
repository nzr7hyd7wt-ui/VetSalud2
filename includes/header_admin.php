<?php
$mensaje = $mensaje ?? '';
if ($mensaje === '') {
    $mensaje = obtenerMensaje();
}
$paginaAdmin = $paginaAdmin ?? '';
$nombreAdmin = $_SESSION['admin']['nombre'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($titulo ?? 'Panel VetSalud') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body class="admin">
<header class="admin-header">
    <nav class="nav nav-admin" aria-label="Administración">
        <a href="index.php" class="brand brand-admin">
            <span class="brand-mark" aria-hidden="true">◆</span>
            VetSalud
        </a>
        <a href="index.php" class="<?= $paginaAdmin === 'panel' ? 'activo' : '' ?>">Panel</a>
        <a href="agenda.php" class="<?= $paginaAdmin === 'agenda' ? 'activo' : '' ?>">Agenda</a>
        <a href="duenos.php" class="<?= $paginaAdmin === 'duenos' ? 'activo' : '' ?>">Dueños</a>
        <a href="mascotas.php" class="<?= $paginaAdmin === 'mascotas' ? 'activo' : '' ?>">Mascotas</a>
        <a href="servicios.php" class="<?= $paginaAdmin === 'servicios' ? 'activo' : '' ?>">Servicios</a>
        <a href="logout.php" class="salir">Salir (<?= e($nombreAdmin) ?>)</a>
    </nav>
</header>
<?php if ($mensaje !== ''): ?>
    <p class="mensaje ok"><?= e($mensaje) ?></p>
<?php endif; ?>
<main class="contenedor">
