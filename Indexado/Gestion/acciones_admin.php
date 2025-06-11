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

function guardarLog($evento) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $fecha = date("Y-m-d H:i:s");
    $usuario = $_SESSION['user'] ?? 'desconocido';
    $linea = "[$fecha] [$ip] [$usuario] $evento" . PHP_EOL;
    file_put_contents("/var/www/sqlcloud.site/logs/logs.txt", $linea, FILE_APPEND | LOCK_EX);
}

$conn = conectar();
if (!$conn) {
    respuesta_error('Error de conexión con la base de datos');
}

function es_superadmin($conn, $id_usuario) {
    $stmt = $conn->prepare("SELECT ROL FROM usuarios WHERE ID = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    return ($usuario['ROL'] === 'superadmin');
}

if (in_array($accion, ['eliminar_usuario', 'actualizar_plan', 'actualizar_rol'])) {
    $id_usuario_objetivo = intval($_POST['id_usuario'] ?? 0);

    if ($_SESSION['ROL'] === 'admin' && es_superadmin($conn, $id_usuario_objetivo)) {
        respuesta_error('No tienes permisos para realizar esta acción sobre un superadmin.');
    }
}

if ($accion === 'eliminar_usuario') {
    $id_usuario = $id_usuario_objetivo; // Ya definido
    $correo_usuario = $_POST['correo_usuario'] ?? '';

    if ($id_usuario <= 0 || empty($correo_usuario)) {
        respuesta_error('Datos inválidos para eliminar al usuario.');
    }

    if ($_SESSION['user'] === $correo_usuario) {
        respuesta_error('No puedes eliminarte a ti mismo.');
    }

    try {

        $stmt = $conn->prepare("SELECT CORREO, NOMBRE FROM usuarios WHERE ID = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            respuesta_error('Usuario no encontrado.');
        }

        $usuario = $result->fetch_assoc();
        $correo_usuario = $usuario['CORREO'];
        $nombre_usuario = $usuario['NOMBRE'];
        $stmt->close();

        guardarLog("Eliminó al usuario '$nombre_usuario' ($correo_usuario) con ID $id_usuario");

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
    $id_usuario = $id_usuario_objetivo;
    $plan = $_POST['plan'] ?? '';

    if ($id_usuario <= 0 || empty($plan)) {
        respuesta_error('Datos inválidos para actualizar el plan.');
    }

    try {
        $stmt = $conn->prepare("UPDATE plan SET T_PLAN = ? WHERE ID = ?");
        $stmt->bind_param("si", $plan, $id_usuario);
        $stmt->execute();
        $stmt->close();

        guardarLog("Actualizó el plan del usuario con ID $id_usuario a '$plan'");

        respuesta_ok('Plan actualizado correctamente.');
    } catch (Exception $e) {
        respuesta_error('Error al actualizar plan: ' . $e->getMessage());
    }

} elseif ($accion === 'actualizar_rol') {
    $id_usuario = $id_usuario_objetivo;
    $rol = $_POST['rol'] ?? '';

    $roles_validos = ['usuario', 'admin', 'superadmin'];
    if ($id_usuario <= 0 || !in_array($rol, $roles_validos)) {
        respuesta_error('Datos inválidos para actualizar el rol.');
    }

    if ($_SESSION['ROL'] !== 'superadmin') {
        respuesta_error('No tienes permisos suficientes para modificar roles.');
    }

    try {
        $stmt = $conn->prepare("UPDATE usuarios SET ROL = ? WHERE ID = ?");
        $stmt->bind_param("si", $rol, $id_usuario);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

         guardarLog("Cambió el rol del usuario con ID $id_usuario a '$rol'");
            respuesta_ok('Rol actualizado correctamente.');
        } else {
            respuesta_error('No se pudo actualizar el rol.');
        }
    } catch (Exception $e) {
        respuesta_error('Error al actualizar rol: ' . $e->getMessage());
    }

} elseif ($accion === 'obtener_bases') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);

    if ($id_usuario <= 0) {
        respuesta_error('ID de usuario inválido.');
    }

    try {
        $sql = "
            SELECT b.ID_BD, b.NOMBRE_BD
            FROM usuario_base_datos ubd
            JOIN base_datos b ON ubd.ID_BD = b.ID_BD
            WHERE ubd.ID_USUARIO = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        $bases = [];
        while ($row = $result->fetch_assoc()) {
            $bases[] = $row;
        }

        echo json_encode(['success' => true, 'databases' => $bases]);
        exit();
    } catch (Exception $e) {
        respuesta_error('Error al obtener bases de datos: ' . $e->getMessage());
    }

} elseif ($accion === 'eliminar_base') {
    if ($_SESSION['ROL'] !== 'superadmin') {
        respuesta_error('No tienes permisos para eliminar bases de datos.');
    }

    $id_bd = intval($_POST['id_bd'] ?? 0);

    if ($id_bd <= 0) {
        respuesta_error('ID de base de datos inválido.');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM base_datos WHERE ID_BD = ?");
        $stmt->bind_param("i", $id_bd);
        $stmt->execute();
        $stmt->close();

        guardarLog("Eliminó la base de datos con ID $id_bd");

        respuesta_ok('Base de datos eliminada correctamente.');
    } catch (Exception $e) {
        respuesta_error('Error al eliminar base de datos: ' . $e->getMessage());
    }

} elseif ($accion === 'eliminar_todas_bases') {
    if ($_SESSION['ROL'] !== 'superadmin') {
        respuesta_error('No tienes permisos para eliminar bases de datos.');
    }

    $id_usuario = intval($_POST['id_usuario'] ?? 0);

    if ($id_usuario <= 0) {
        respuesta_error('ID de usuario inválido.');
    }

    try {
        $stmt = $conn->prepare("SELECT ID_BD FROM usuario_base_datos WHERE ID_USUARIO = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['ID_BD'];
        }
        $stmt->close();

        if (!empty($ids)) {
            $ids_placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));

            $stmt = $conn->prepare("DELETE FROM base_datos WHERE ID_BD IN ($ids_placeholders)");
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $stmt->close();
        }

	// ✅ Guardar log
        guardarLog("Eliminó todas las bases de datos del usuario con ID $id_usuario");

        respuesta_ok([
            'deleted' => count($ids),
            'message' => count($ids) > 0 ? 'Todas las bases de datos eliminadas.' : 'El usuario no tenía bases de datos.'
        ]);
    } catch (Exception $e) {
        respuesta_error('Error al eliminar todas las bases de datos: ' . $e->getMessage());
    }

} else {
    respuesta_error('Acción no válida.');
}
?>
