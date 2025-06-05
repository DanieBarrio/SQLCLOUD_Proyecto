<?php
if (!defined('ACCESO_INTERNO')) {
    http_response_code(403);
    exit('Acceso denegado');
}

if (isset($_SESSION['user'])) {
    $correo = $_SESSION['user'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $fecha = date('Y-m-d H:i:s');
    $evento = 'Login exitoso';

    $linea = "[$fecha] [$ip] [$correo] $evento";
    file_put_contents("logs.txt", $linea . PHP_EOL, FILE_APPEND | LOCK_EX);
}
?>

