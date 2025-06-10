<?php
// procesar_login.php
session_start();
require 'conexion.php';
$response = ['exito' => false, 'mensaje' => 'Error desconocido'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = conectar();

    if ($_POST['accion'] === 'login') {
        $correo = trim($_POST['correo']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT u.CORREO, u.CONTRASENA, u.NOMBRE, p.T_PLAN AS PLAN FROM usuarios u JOIN plan p ON u.ID = p.ID WHERE u.CORREO = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['CONTRASENA'])) {
                $_SESSION['user'] = $row['CORREO'];
                $_SESSION['nombre'] = $row['NOMBRE'];
                $_SESSION['PLAN'] = $row['PLAN'];
                $response = ['exito' => true, 'mensaje' => 'Login exitoso'];
		define('ACCESO_INTERNO', true);
		require_once 'log_login_exitoso.php';
            } else {
                $response['mensaje'] = 'Contraseña incorrecta.';
			define('ACCESO_INTERNO', true);
			require_once 'log_login_fallido.php';
            }
        } else {
            $response['mensaje'] = 'Correo no registrado.';
		define('ACCESO_INTERNO', true);
		require_once 'log_login_fallido.php';
        }
    }

    if ($_POST['accion'] === 'registro') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    if (empty($nombre) || empty($correo) || empty($password)) {
        $response['mensaje'] = "Todos los campos son obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $response['mensaje'] = "Correo inválido.";
    } elseif (
        strlen($password) < 8 ||
        !preg_match('/[A-Za-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[!@#$_.,+*&%\-]/', $password)
    ) {
        $response['mensaje'] = "La contraseña debe tener al menos 8 caracteres, una letra, un número y un símbolo.";
    } else {
        $stmt_check = $conn->prepare("SELECT CORREO FROM usuarios WHERE CORREO = ?");
        $stmt_check->bind_param("s", $correo);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $response['mensaje'] = "El correo ya está registrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (NOMBRE, CONTRASENA, CORREO) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nombre, $hash, $correo);
            if ($stmt->execute()) {
                $usuario_id = $conn->insert_id;
                $plan = 'gratuito';

                $stmt_plan = $conn->prepare("INSERT INTO plan (ID, T_PLAN) VALUES (?, ?)");
                $stmt_plan->bind_param("is", $usuario_id, $plan);
                if ($stmt_plan->execute()) {
                    $_SESSION['user'] = $correo;
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['PLAN'] = $plan;
                    $response = ['exito' => true, 'mensaje' => 'Registro exitoso'];
                } else {
                    $response['mensaje'] = "Error al registrar el plan.";
                }
            } else {
                $response['mensaje'] = "Error al registrar. Inténtelo de nuevo.";
            }
        }
    }
}

}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
