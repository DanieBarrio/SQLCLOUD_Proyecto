<?php
session_start();
require 'conexion.php';

// Verificar si hay usuario en sesión
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "No autorizado";
    exit;
}

$conn = conectar();
$usuario = $_SESSION['user'];

// Actualizar el campo PLAN a 'premium'
$stmt = $conn->prepare("UPDATE usuarios SET PLAN = 'premium' WHERE USUARIO = ?");
$stmt->bind_param("s", $usuario);

if ($stmt->execute()) {
    $_SESSION['PLAN'] = 'premium'; // ← añadimos esto
    echo "ok";
}

else {
    http_response_code(500);
    echo "Error al actualizar el plan";
}
?>
