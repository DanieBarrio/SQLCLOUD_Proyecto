<?php
// funciones.php

function Existeusuario($correo, $password, $conn) {
    $stmt = $conn->prepare("SELECT NOMBRE, CORREO, CONTRASENA, ROL, PLAN, FECHA_EXPIRACION FROM usuarios WHERE CORREO = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $respuesta = false;

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();

        if (password_verify($password, $user['CONTRASENA'])) {
            $_SESSION['user'] = $user['CORREO'];
            $_SESSION['NOMBRE_COMPLETO'] = $user['NOMBRE'];
            $_SESSION['CORREO'] = $user['CORREO'];
            $_SESSION['ROL'] = $user['ROL'];
            $_SESSION['PLAN'] = $user['PLAN'];
            $_SESSION['FECHA_EXPIRACION'] = $user['FECHA_EXPIRACION'];
            $respuesta = true;
        }
    }
    return $respuesta;
}

function verificador($password, $nombre) {
    $mensaje = null;
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Za-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[!@#$_.,+*&%\-]/', $password)
    ) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres, incluir una letra, un número y un símbolo(!@#$._,+*&%\-).";
    } elseif ($password === $nombre) {
        $mensaje = "La contraseña no puede ser el nombre del usuario.";
    }
    return $mensaje;
}

function GuardarToken($conn, $token, $correo) {
    $stmt = $conn->prepare("SELECT ID FROM usuarios WHERE CORREO = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();
        $idusuario = $user['ID'];

        $stmt = $conn->prepare("SELECT ID FROM TOKEN WHERE ID = ?");
        $stmt->bind_param("s", $idusuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $sql = "UPDATE TOKEN SET TOKEN = ? WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $token, $idusuario);
                $stmt->execute();
            }
        } else {
            $sql = "INSERT INTO TOKEN (ID, TOKEN) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $idusuario, $token);
                $stmt->execute();
            }
        }
    }
}

function sinlogin() {
    if (!isset($_SESSION['user'], $_SESSION['token'], $_SESSION['ROL'])) {
        header("Location: logister.php");
        exit;
    }
    require_once 'conexion.php';
    $conn = conectar();
    $stmt = $conn->prepare("SELECT TOKEN FROM TOKEN WHERE TOKEN = ?");
    $stmt->bind_param("s", $_SESSION['token']);
    if (!$stmt->execute()) {
        die("Error al verificar el token: " . $conn->error);
    }
    $resultado = $stmt->get_result();
    if ($resultado->num_rows !== 1) {
        header("Location: logister.php");
        exit;
    }
}

function csrfcomprovacion($tokennew, $tokenold) {
    if ($tokennew !== $tokenold) {
        exit;
    } else {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function VerificarToken($tokenOld, $correo, $conn) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $tokenOld) {
        return false;
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}
?>
