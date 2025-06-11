<?php
header('Content-Type: application/json');

session_start();
require 'conexion.php';

$response = ['exito' => false, 'mensaje' => 'Error desconocido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_actual'])) {
    $nombre = trim($_POST['nombreCompleto']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password_actual'];
    $userId = $_SESSION['ID'] ?? null;

    if (!$userId) {
        $response['mensaje'] = 'No has iniciado sesión.';
        echo json_encode($response);
        exit;
    }

    $conn = conectar();

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

    $stmt = $conn->prepare("UPDATE usuarios SET NOMBRE = ?, CORREO = ? WHERE ID = ?");
    $stmt->bind_param("ssi", $nombre, $correo, $userId);
    if ($stmt->execute()) {
        $_SESSION['NOMBRE_COMPLETO'] = $nombre;
        $_SESSION['CORREO'] = $correo;
        $response = ['exito' => true, 'mensaje' => 'Perfil actualizado correctamente.'];
    } else {
        $response['mensaje'] = 'Error al actualizar el perfil.';
    }

    echo json_encode($response);
    exit;
}
$response['mensaje'] = 'Solicitud inválida.';
echo json_encode($response);
?>
