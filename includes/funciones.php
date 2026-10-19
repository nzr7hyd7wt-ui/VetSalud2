<?php
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

function adminLogueado() {
    return isset($_SESSION['admin']);
}

function requiereAdmin() {
    if (!adminLogueado()) {
        header('Location: login.php');
        exit;
    }
}

function guardarMensaje($texto) {
    $_SESSION['mensaje'] = $texto;
}

function obtenerMensaje() {
    $mensaje = $_SESSION['mensaje'] ?? '';
    unset($_SESSION['mensaje']);
    return $mensaje;
}

function formatearPrecio($precio) {
    return '$' . number_format((float)$precio, 0, ',', '.');
}

function etiquetaEstado($estado) {
    $map = [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
        'completada' => 'Completada',
    ];
    return $map[$estado] ?? $estado;
}

function formatearFechaLarga($fecha) {
    $ts = strtotime((string)$fecha);
    if ($ts === false) {
        return (string)$fecha;
    }
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
    $hoy = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    $ymd = date('Y-m-d', $ts);
    $etiqueta = $dias[(int)date('w', $ts)] . ' ' . (int)date('j', $ts) . ' ' . $meses[(int)date('n', $ts)];
    if ($ymd === $hoy) {
        return 'Hoy · ' . $etiqueta;
    }
    if ($ymd === $manana) {
        return 'Mañana · ' . $etiqueta;
    }
    return $etiqueta;
}

const WHATSAPP_VETSALUD = '573001112233';

function whatsappUrl($mensaje) {
    return 'https://wa.me/' . WHATSAPP_VETSALUD . '?text=' . rawurlencode($mensaje);
}
