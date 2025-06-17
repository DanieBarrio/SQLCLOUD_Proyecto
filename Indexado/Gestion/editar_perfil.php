<?php
header('Content-Type: application/json');

session_start();
require 'conexion.php';

$response = ['exito' => false, 'mensaje' => 'Error desconocido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_actual'])) {
    $nombre = trim($_POST['nombreCompleto']);
    $password = $_POST['password_actual'];
    $userId = $_SESSION['ID'] ?? null;

    if (!$userId) {
        $response['mensaje'] = 'No has iniciado sesión.';
        echo json_encode($response);
        exit;
    }

    $conn = conectar();

    // Obtener contraseña del usuario
    $stmt = $conn->prepare("SELECT CONTRASENA FROM usuarios WHERE ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows !== 1) {
        $response['mensaje'] = 'Usuario no encontrado.';
        echo json_encode($response);
        exit;
    }

    $fila = $resultado->fetch_assoc();
    if (!password_verify($password, $fila['CONTRASENA'])) {
        $response['mensaje'] = 'Contraseña incorrecta.';
        echo json_encode($response);
        exit;
    }

    // Actualizar solo el nombre
    $stmt = $conn->prepare("UPDATE usuarios SET NOMBRE = ? WHERE ID = ?");
    $stmt->bind_param("si", $nombre, $userId);
    if ($stmt->execute()) {
        $_SESSION['NOMBRE_COMPLETO'] = $nombre;

        $response = ['exito' => true, 'mensaje' => 'Nombre actualizado correctamente.'];
    } else {
        $response['mensaje'] = 'Error al actualizar el nombre.';
    }

    echo json_encode($response);
    exit;
}

$response['mensaje'] = 'Solicitud inválida.';
echo json_encode($response);
?>
