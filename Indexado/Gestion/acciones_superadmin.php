<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    http_response_code(403);
    echo "Acceso denegado.";
    exit;
}

$conn = conectar();
$accion = $_POST['accion'] ?? '';

if ($accion === 'cambiar_rol') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);
    $nuevo_rol = $_POST['nuevo_rol'] ?? '';

    if (!in_array($nuevo_rol, ['usuario', 'admin', 'superadmin'])) {
        echo "Rol inválido.";
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuarios SET ROL = ? WHERE ID = ?");
    $stmt->bind_param("si", $nuevo_rol, $id_usuario);
    if ($stmt->execute()) {
        header("Location: superadmin.php");
        exit;
    } else {
        echo "Error al cambiar el rol.";
        exit;
    }

} elseif ($accion === 'eliminar_todo') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);

    // Evitar que se elimine a sí mismo
    $stmt = $conn->prepare("SELECT CORREO FROM usuarios WHERE ID = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    $userData = $res->fetch_assoc();

    if ($userData && $userData['CORREO'] === $_SESSION['user']) {
        echo "No puedes eliminarte a ti mismo.";
        exit;
    }

    // Eliminar bases de datos del usuario (clave foránea con ON DELETE CASCADE si existe)
    $stmtDel = $conn->prepare("DELETE FROM usuarios WHERE ID = ?");
    $stmtDel->bind_param("i", $id_usuario);

    if ($stmtDel->execute()) {
        header("Location: superadmin.php");
        exit;
    } else {
        echo "Error al eliminar usuario.";
        exit;
    }

} else {
    echo "Acción no válida.";
    exit;
}
?>
