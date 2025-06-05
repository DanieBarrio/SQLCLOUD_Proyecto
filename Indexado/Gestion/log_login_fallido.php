<?php
if (!defined('ACCESO_INTERNO')) {
    http_response_code(403);
    exit('Acceso denegado');
}

$correo = trim($_POST['correo'] ?? 'desconocido');
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$evento = 'Login fallido';
$fecha = date('Y-m-d H:i:s');

$linea = "[$fecha] [$ip] [$correo] $evento";
file_put_contents("logs.txt", $linea . PHP_EOL, FILE_APPEND | LOCK_EX);
?>
