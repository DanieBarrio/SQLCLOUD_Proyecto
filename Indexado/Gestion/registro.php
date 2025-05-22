<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    
    session_start();
    session_unset();
    session_destroy();
    session_start();
    require 'conexion.php';

    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $usuario = trim($_POST['nombre_usuario']);
    $correo = htmlspecialchars(trim($_POST['correo']));
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $conn = conectar();     // Conectamos usando la función definida

    // Validación de datos
    if (empty($nombre) || empty($correo) || empty($password) || empty($password2)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo electrónico inválido.";
    } elseif ($password !== $password2) {
        $mensaje = "Las contraseñas no coinciden.";
    } elseif (
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[!@#$_.,+*&%\-]/', $password)
        ) {
            $mensaje = "La contraseña debe tener al menos 8 caracteres, incluir una letra, un número y un símbolo(!@#$_.,+*&%\-).";
        }
      else {
        // Hasheamos la contraseña para seguridad
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Consulta preparada para evitar inyección SQL
        $sql = "INSERT INTO usuarios (USUARIO, NOMBRE, CONTRASENA, CORREO) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssss", $usuario, $nombre, $hash, $correo);

            if ($stmt->execute()) {
                header("Location: login.php"); // Redirección segura
                exit;
            } else {
                $mensaje = "Error al registrar, usuario ya registrado: ";
            }
            $stmt->close();
        }
    }
    
    // En caso de que alguno de los dos anteriores if de error saldra esto
    $conn->close();
   
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
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

        .white-link {
            color: #fff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .white-link:hover {
            color: #ccc;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
                <h2 class="text-center text-white mb-4 fw-bold">Registro de Usuario</h2>

                <?php if (isset($mensaje)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($mensaje) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="nombre" class="form-label text-white">Nombre y Apellidos</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user text-white-50"></i>
                            </span>
                            <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: Juan Pérez" value="<?= $_POST['nombre'] ?? '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label text-white">Nombre de Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user-tag text-white-50"></i>
                            </span>
                            <input type="text" name="nombre_usuario" id="username" class="form-control" placeholder="Ej: cloudmaster123" value="<?= $_POST['nombre_usuario'] ?? '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label text-white">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope text-white-50"></i>
                            </span>
                            <input type="email" name="correo" id="correo" class="form-control" placeholder="Ej: usuario@correo.com" value="<?= $_POST['correo'] ?? '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label text-white">Contraseña</label>
                        <div class="input-group">
                            
                            <span class="input-group-text">
                                <i class="fas fa-lock text-white-50"></i>
                            </span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Ingrese su contraseña" required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" onclick="toggleAllPasswords()">
                                <i class="fas fa-eye" id="toggle-password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password2" class="form-label text-white">Repetir Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock text-white-50"></i>
                            </span>
                            <input type="password" name="password2" id="password2" class="form-control" placeholder="Repita su contraseña" required>
                        </div>
                    </div>


                    <div class="mb-3">
                        <button type="submit" class="btn btn-success w-100 mt-4">
                            <i class="fas fa-user-plus me-2"></i> Registrarse
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="login.php" class="white-link">
                            ¿Ya tienes cuenta? Inicia sesión aquí
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

   
    <script>
    function toggleAllPasswords() {
        const password1 = document.getElementById('password');
        const password2 = document.getElementById('password2');
        const icon = document.getElementById('toggle-password-icon');
        
        const show = password1.type === 'password';
        
        password1.type = show ? 'text' : 'password';
        password2.type = show ? 'text' : 'password';
        
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
