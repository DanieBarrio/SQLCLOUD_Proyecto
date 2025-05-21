<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (isset($_SESSION["ultimo_acceso"])) {
    $inactividad = 1800; 
    if (time() - $_SESSION["ultimo_acceso"] > $inactividad) {
        session_destroy();
    }
}
$_SESSION["ultimo_acceso"] = time();

require 'funciones.php';


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require 'conexion.php';
    $email = str_replace('"','', $_GET['email']);
    $token = str_replace('"','', $_GET['token']);
    $conn = conectar();
    $stmt = $conn->prepare("SELECT TOKEN.TOKEN, usuarios.CORREO FROM TOKEN, usuarios WHERE TOKEN.ID = usuarios.ID and usuarios.CORREO = ? and TOKEN.TOKEN = ? ");
     // El interrogante es una forma de evitar la inyeccion sql 
    $stmt->bind_param("ss", $email ,$token); // vinculamos el interrogante con el usuario
    $stmt->execute();
    $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado
    
    if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario
    } else {
        header("Location: login.php");
        exit;
    }
   
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'conexion.php'; 

    $emailcom = str_replace('"','', $_GET['email']);
    $tokencom = str_replace('"','', $_GET['token']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $conn = conectar(); 
    $stmt = $conn->prepare("SELECT TOKEN.TOKEN, usuarios.CORREO FROM TOKEN, usuarios WHERE TOKEN.ID = usuarios.ID and usuarios.CORREO = ? and TOKEN.TOKEN = ? ");
     // El interrogante es una forma de evitar la inyeccion sql 
    $stmt->bind_param("ss", $emailcom ,$tokencom); // vinculamos el interrogante con el usuario
    $stmt->execute();
    $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado
    if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario

        if ($password !== $password2) {
            $mensaje = "Las contraseñas no coinciden.";
        } elseif (
                strlen($password) < 8 ||
                !preg_match('/[A-Za-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[!@#$.,+*&%\-_]/', $password)
            ) {
                $mensaje = "La contraseña debe tener al menos 8 caracteres, incluir una letra, un número y un símbolo(!@#_.,+*&%\-$).";
            }
          else {
            // Hasheamos la contraseña para seguridad
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "UPDATE usuarios SET CONTRASENA = ? WHERE CORREO = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $hash, $emailcom);

                if ($stmt->execute()) {
                   
                    $token = bin2hex(random_bytes(32));
                    $usuario = $emailcom;
                    GuardarToken($conn, $token, $usuario);

                    session_unset();
                    session_destroy();
                    header("Location: login.php");
                    exit;
                 
                  
                } else {
                    $mensaje = "Error al cambiar la contraseña ";
                }
                $stmt->close();
            }
        }
    } 
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../Recursos/icon.png" type="image/png">
    <style>
        body {
            background: #1a1a1a;
            background-image: linear-gradient(rgba(255,255,255,0.05), rgba(255,255,255,0.05)),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path fill="%23333" fill-opacity="0.3" d="M0 0h40v40H0V0zm10 10h20v20H10V10zm10 10h20v20H20V20z"/></svg>');
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.25);
        }

        .btn-success {
            background: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-success:hover {
            background: #45a049;
            border-color: #45a049;
        }

        .alert-danger {
            background: #ff4444;
            border-color: #ff4444;
            color: white;
        }

        .input-group-text {
            background: transparent !important;
            border: none !important;
        }

        .password-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
        <div class="container-fluid">
            <div class="mx-auto">
                <p class="navbar-brand text-success fw-bold fs-3 m-0">
                    SQLCLOUD
                </p>
            </div>
        </div>
    </nav>

    <div class="flex-grow-1 d-flex justify-content-center align-items-center">
        <div class="card login-card shadow-lg" style="width: 30rem;">
            <div class="card-body p-5">
                <h2 class="text-center text-white mb-4 fw-bold">Restablecer Contraseña</h2>

                <?php if (isset($mensaje)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label text-white">Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock text-white-50"></i>
                            </span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Ingrese su nueva contraseña" required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggle-password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password2" class="form-label text-white">Confirmar Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock text-white-50"></i>
                            </span>
                            <input type="password" name="password2" id="password2" class="form-control" placeholder="Repita su contraseña" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <input type="submit" value="Cambiar contraseña" name="codigo" class="btn btn-success">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggle-password-icon');
            
            const show = password.type === 'password';
            
            password.type = show ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>