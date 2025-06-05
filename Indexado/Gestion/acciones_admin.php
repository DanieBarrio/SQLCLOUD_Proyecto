<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['ROL']) || !in_array($_SESSION['ROL'], ['admin', 'superadmin'])) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

include 'conexion.php';

$accion = $_POST['accion'] ?? '';

function respuesta_error($mensaje) {
    echo json_encode(['error' => $mensaje]);
    exit();
}

function respuesta_ok($mensaje) {
    echo json_encode(['success' => $mensaje]);
    exit();
}

$conn = conectar();
if (!$conn) {
    respuesta_error('Error de conexión con la base de datos');
}

if ($accion === 'eliminar_usuario') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);
    $correo_usuario = $_POST['correo_usuario'] ?? '';

    if ($id_usuario <= 0 || empty($correo_usuario)) {
        respuesta_error('Datos inválidos para eliminar al usuario.');
    }

    if ($_SESSION['user'] === $correo_usuario) {
        respuesta_error('No puedes eliminarte a ti mismo.');
    }

    try {
        // Eliminar datos relacionados antes del usuario
        $stmt = $conn->prepare("DELETE FROM token WHERE ID = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM plan WHERE ID = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM usuario_base_datos WHERE ID_USUARIO = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE ID = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        respuesta_ok('Usuario eliminado correctamente.');
    } catch (Exception $e) {
        respuesta_error('Error al eliminar usuario: ' . $e->getMessage());
    }

} elseif ($accion === 'actualizar_plan') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);
    $plan = $_POST['plan'] ?? '';

    if ($id_usuario <= 0 || empty($plan)) {
        respuesta_error('Datos inválidos para actualizar el plan.');
    }

    try {
        $stmt = $conn->prepare("UPDATE plan SET T_PLAN = ? WHERE ID = ?");
        $stmt->bind_param("si", $plan, $id_usuario);
        $stmt->execute();
        $stmt->close();

        respuesta_ok('Plan actualizado correctamente.');
    } catch (Exception $e) {
        respuesta_error('Error al actualizar plan: ' . $e->getMessage());
    }

} else {
    respuesta_error('Acción no válida.');
}
?>
