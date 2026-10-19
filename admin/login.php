<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

if (adminLogueado()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';
    if ($correo === '' || $clave === '') {
        $error = 'Correo y contraseña obligatorios.';
    } else {
        $stmt = $mysqli->prepare('SELECT id_admin, nombre, correo, password_hash FROM administrador WHERE correo = ? LIMIT 1');
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        if (!$admin || !password_verify($clave, $admin['password_hash'])) {
            $error = 'Credenciales incorrectas.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id' => (int)$admin['id_admin'],
                'nombre' => $admin['nombre'],
                'correo' => $admin['correo'],
            ];
            guardarMensaje('Bienvenido/a, ' . $admin['nombre']);
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login · VetSalud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<div class="login-box">
    <div style="text-align:center;margin-bottom:0.5rem">
        <span class="brand-mark" style="display:inline-grid;margin-bottom:0.65rem">◆</span>
    </div>
    <h1>VetSalud</h1>
    <p class="sub">Acceso administrador</p>
    <?php if ($error): ?><p class="mensaje error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="form" style="box-shadow:none;border:none;padding:0;max-width:none">
        <label>Correo
            <input type="email" name="correo" required value="<?= e($_POST['correo'] ?? 'admin@demo.com') ?>">
        </label>
        <label>Contraseña
            <input type="password" name="clave" required placeholder="Admin123!">
        </label>
        <button class="btn btn-aqua" type="submit">Ingresar</button>
        <p class="meta">Demo: admin@demo.com / Admin123!</p>
        <p><a href="../index.php">← Volver al sitio</a></p>
    </form>
</div>
</body>
</html>
