<?php
session_start();

// Validación de sesión
if (!isset($_SESSION['user'])) {
    die('<div class="alert alert-danger">❌ Usuario no autenticado</div>');
}

// Verificar base de datos seleccionada
if (!isset($_POST['backup_bd']) || empty($_POST['backup_bd'])) {
    die('<div class="alert alert-danger">❌ Debe seleccionar una base de datos</div>');
}
$nombreBd = $_POST['backup_bd'];

// Conexión a la base de datos principal
require 'conexion.php';
$conn = conectar();

$userId = $_SESSION['ID'];

// Verificar permisos del usuario
$stmt = $conn->prepare("SELECT b.NOMBRE_BD FROM usuario_base_datos ub JOIN base_datos b ON ub.ID_BD = b.ID_BD WHERE ub.ID_USUARIO = ? AND b.NOMBRE_BD = ?");
$stmt->bind_param("is", $userId, $nombreBd);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    die('<div class="alert alert-danger">❌ No tiene acceso a esta base de datos</div>');
}

// Generar nombre de archivo
$fecha = date('Y-m-d_H-i-s');
$nombreArchivo = "backup_{$nombreBd}_{$fecha}.sql";
$rutaTemporal = "/tmp/" . $nombreArchivo;

// Comando final usando socket Unix (sin -h ni -P)
$comando = "mariadb-dump " . escapeshellarg($nombreBd) . " > " . escapeshellarg($rutaTemporal) . " 2>&1";

// Ejecutar el comando
$output = [];
$returnVar = 0;
exec($comando, $output, $returnVar);

// Manejo de errores
if ($returnVar !== 0) {
    die("❌ Error al generar el backup. Código de error: $returnVar<br>📤 Salida del sistema:<br><pre>" . implode("<br>", $output) . "</pre>");
}

// Verificar que el archivo exista
if (!file_exists($rutaTemporal)) {
    die("❌ No se pudo crear el archivo de backup en '$rutaTemporal'");
}

// Configurar headers para la descarga
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($nombreArchivo));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($rutaTemporal));

readfile($rutaTemporal);

// Limpiar
unlink($rutaTemporal);
exit;
