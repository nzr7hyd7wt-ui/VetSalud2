<?php
$mensaje = $mensaje ?? '';
if ($mensaje === '') {
    $mensaje = obtenerMensaje();
}
$pagina = $pagina ?? '';
$sinContenedor = $sinContenedor ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($titulo ?? 'VetSalud') ?></title>
    <meta name="description" content="VetSalud: baño, peluquería y cuidados para tu mascota. Agenda cita online con precios claros.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="site-header">
    <nav class="nav">
        <a href="index.php" class="brand">
            <span class="brand-mark" aria-hidden="true">◆</span>
            <span>VetSalud</span>
        </a>
        <div class="nav-links">
            <a href="index.php#servicios" class="<?= $pagina === 'inicio' ? 'activo' : '' ?>">Servicios</a>
            <a href="index.php#como">Cómo funciona</a>
            <a href="cita.php" class="nav-cta <?= $pagina === 'cita' ? 'activo' : '' ?>">Solicitar cita</a>
            <a href="admin/login.php">Admin</a>
        </div>
    </nav>
</header>
<?php if ($mensaje !== ''): ?>
    <p class="mensaje ok"><?= e($mensaje) ?></p>
<?php endif; ?>
<main class="<?= $sinContenedor ? 'contenedor-ancho' : 'contenedor' ?>">
