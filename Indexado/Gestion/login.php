<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
$error = null;
if (!isset($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $tokenold = $_SESSION['csrf_token'];
} 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'conexion.php'; 
    require 'funciones.php';

    $error = null;
    $tokenold = $_SESSION['csrf_token'];

    $tokennew = $_POST['csrf_token'];
    csrfcomprovacion($tokennew, $tokenold);

    $usuario = trim($_POST['usuario'] ?? '');

    $password = $_POST['password'] ?? '';

    if (!$usuario || !$password) {
        $error = "Por favor, ingrese usuario y contraseña.";
    } else {
        $conn = conectar();
        if (Existeusuario($usuario, $usuario, $password, $conn)) { 
            session_regenerate_id(true); // Regenerar ID de sesión por seguridad

            $token = bin2hex(random_bytes(32));
            
            // Establecer todas las variables necesarias
            $_SESSION['user'] = $_SESSION['USUARIO']; // Coincide con sinlogin()
            $_SESSION['token'] = $token;
            $_SESSION["ultimo_acceso"] = time();

            GuardarToken($conn, $token, $usuario);

            header("Location: index.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }

        $conn->close();
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
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.25);
        }
        .text-primary {
            color: #4CAF50 !important;
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
            display: block;
            margin-top: 1rem;
            text-align: center;
            font-size: 0.9rem;
            color: #fff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .white-link:hover {
            color: #ccc;
        }
        .navbar-custom {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
        <div class="container-fluid">
         
            <div class="mx-auto">
                <p class="navbar-brand text-primary fw-bold fs-3 m-0">
                    SQLCLOUD
                </p>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="flex-grow-1 d-flex justify-content-center align-items-center">

        <div class="card login-card shadow-lg" style="width: 28rem;">
            <div class="card-body p-5">
                <h2 class="text-center text-white mb-4 fw-bold">Iniciar Sesión</h2>

                <?php if($error): ?> 
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error ?> 
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="mb-3">
                        <label for="usuario" class="form-label text-white">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="fas fa-user text-white-50"></i>
                            </span>
                            <input type="text" name="usuario" id="usuario" 
                                   class="form-control border-start-0" 
                                   placeholder="Ingrese su usuario o correo electronico" 
                                   style="color: white" value="<?php if( $_SERVER['REQUEST_METHOD'] === 'POST') { 
                                    echo $usuario; };?>" required>  
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label text-white">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="fas fa-lock text-white-50"></i>
                            </span>
                            <input type="password" name="password" id="password" 
                                   class="form-control border-start-0" 
                                   placeholder="Ingrese su contraseña" 
                                   style="color: white" required>
                            <button type="button" class="btn btn-outline-secondary border-start-0" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-success w-100 mt-4">
                            <i class="fas fa-sign-in-alt me-2"></i> Entrar
                        </button>
                    </div>
                    

                    <div class="mt-3 text-center">
                    <a href="./RecuperarContrasena.php" class="white-link">
                        ¿Has olvidado tu contraseña?
                    </a>
                    </div>

                    <!-- Segundo enlace: Regístrate aquí -->
                    <div class="mt-3 text-center">
                        <a href="./registro.php" class="white-link">
                            ¿No tienes cuenta? Regístrate aquí
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>