<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "No autorizado";
    exit;
}

if (isset($_SESSION['PLAN']) && strtolower($_SESSION['PLAN']) === 'premium') {
    http_response_code(403);
    echo "Ya tienes el plan premium activo.";
    exit;
}


$conn = conectar();
$correo = $_SESSION['user'];

// Obtener el ID del usuario
$stmt = $conn->prepare("SELECT ID FROM usuarios WHERE CORREO = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(404);
    echo "Usuario no encontrado";
    exit;
}

$row = $result->fetch_assoc();
$usuario_id = $row['ID'];

// Actualizar el plan
$stmt = $conn->prepare("UPDATE plan SET T_PLAN = 'premium' WHERE ID = ?");
$stmt->bind_param("i", $usuario_id);

if ($stmt->execute()) {
    $_SESSION['PLAN'] = 'premium';
    echo "ok";
} else {
    http_response_code(500);
    echo "Error al actualizar el plan";
}
?>
