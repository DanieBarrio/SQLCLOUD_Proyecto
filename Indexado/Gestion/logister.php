<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'conexion.php';
$mensaje_login = '';
$mensaje_registro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = conectar();

    // LOGIN
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
                define('ACCESO_INTERNO', true);
		require_once 'log_login_exitoso.php';
		header('Location: index.php');
                exit;
            } else {
                $mensaje_login = "Contraseña incorrecta.";
		define('ACCESO_INTERNO', true);
		require_once 'log_login_fallido.php';
            }
        } else {
            $mensaje_login = "Correo no registrado.";
	    define('ACCESO_INTERNO', true);
	    require_once 'log_login_fallido.php';
        }
    }

    // REGISTRO
    if ($_POST['accion'] === 'registro') {
        $nombre = htmlspecialchars(trim($_POST['nombre']));
        $correo = htmlspecialchars(trim($_POST['correo']));
        $password = $_POST['password'];

        if (empty($nombre) || empty($correo) || empty($password)) {
            $mensaje_registro = "Todos los campos son obligatorios.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje_registro = "Correo inválido.";
        } elseif (
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[!@#$_.,+*&%\-]/', $password)
        ) {
            $mensaje_registro = "La contraseña debe tener al menos 8 caracteres, una letra, un número y un símbolo.";
        } else {
            $stmt_check = $conn->prepare("SELECT CORREO FROM usuarios WHERE CORREO = ?");
            $stmt_check->bind_param("s", $correo);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $mensaje_registro = "El correo ya está registrado.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $plan = 'gratuito';

                //$stmt = $conn->prepare("INSERT INTO usuarios (NOMBRE, CONTRASENA, CORREO, PLAN, FECHA_EXPIRACION) VALUES (?, ?, ?, ?, ?)");
                //$stmt->bind_param("sssss", $nombre, $hash, $correo, $plan, $fecha_exp);

                $stmt = $conn->prepare("INSERT INTO usuarios (NOMBRE, CONTRASENA, CORREO) VALUES (?, ?, ?)");
    		$stmt->bind_param("sss", $nombre, $hash, $correo);
    		if($stmt->execute()){
                $usuario_id = $conn->insert_id;

    		// Insertar plan
    		$stmt_plan = $conn->prepare("INSERT INTO plan (ID, T_PLAN) VALUES (?, ?)");
    		$stmt_plan->bind_param("is", $usuario_id, $plan);
    		    if($stmt_plan->execute())  {
                    	$_SESSION['user'] = $correo;
                    	$_SESSION['nombre'] = $nombre;
                    	$_SESSION['PLAN'] = $plan;
                    	header('Location: index.php');
                    	exit;
		    } else {
                    	$mensaje_registro = "Error al registrar. Inténtelo de nuevo.";
                    }

                } else {
                    $mensaje_registro = "Error al registrar. Inténtelo de nuevo.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Registro/Login</title>
</head>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

a{
    text-decoration: none;
}

body{
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
}

.container{
    position: relative;
    width: 460px;
    height: 640px;
    border-radius: 12px;
    padding: 20px 30px 120px;
    background: #303f9f;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.login-section{
    position: absolute;
    left: 50%;
    bottom: -88%;
    transform: translateX(-50%);
    width: calc(100% + 180px);
    padding: 20px 140px;
    background: #fff;
    border-radius: 290px;
    height: 100%;
    transition: all 0.6s ease;
}

.login-section header,
.signup-section header{
    font-size: 30px;
    text-align: center;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}

.login-section header{
    color: #333;
    opacity: 0.6;
}

.social-buttons{
    margin-top: 40px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.social-buttons button{
    width: 100%;
    padding: 10px;
    background: #fff;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
}

.login-section .social-buttons button{
    border: 1px solid #000;
}

.login-section .social-buttons button i,
.signup-section .social-buttons button i{
    font-size: 25px;
}

.separator{
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.separator .line{
    width: 100%;
    height: 1px;
    background: #ccc;
}

.separator p{
    color: #fff;
}

.login-section .separator p{
    color: #000;
}

.container form{
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 30px;
}

form input{
    outline: none;
    border: none;
    padding: 10px 15px;
    font-size: 16px;
    color: #333;
    font-weight: 400;
    border-radius: 8px;
    background: #fff;
}

.login-section input{
    border: 1px solid #aaa;
}

form a{
    color: #333;
}

.signup-section form a{
    color: #fff;
}

form .btn{
    margin-top: 15px;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 500;
    cursor: pointer;
}

.login-section .btn{
    background: #303f9f;
    color: #fff;
    border: none;
}

.container.active .login-section{
    bottom: -12%;
    border-radius: 220px;
    box-shadow: 0 -5px 10px rgba(0, 0, 0, 0.1);
}

.container.active .login-section header{
    opacity: 1;
}

.container.active .signup-section header{
    opacity: 0.6;
}
    /* NUEVOS ESTILOS */
    .password-container {
        position: relative;
        margin: 12px 0;
    }
    
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
        z-index: 2;
        display: none;
    }
    
    .login-section.active .toggle-password,
    .signup-section.active .toggle-password {
        display: block;
    }
    
    .password-container input {
        padding-right: 40px !important;
        width: 100% !important;
    }
</style>
<body>

    <div class="container">
        <div class="signup-section">
            <header>Registro</header>

            <div class="social-buttons">
                <button><i class='bx bxl-google'></i> Use Google</button>
                <button><i class='bx bxl-apple'></i> Use Apple</button>
            </div>

            <div class="separator">
                <div class="line"></div>
                <p>Or</p>
                <div class="line"></div>
            </div>

        <?php if (!empty($mensaje_registro)): ?>
  <div style="color: red; text-align: center; margin-top: 10px;">
    <?= htmlspecialchars($mensaje_registro) ?>
  </div>
        <?php endif; ?>


            <form method="POST">
  <input type="hidden" name="accion" value="registro">
  <input type="text" name="nombre" placeholder="Nombre Completo" required>
  <input type="email" name="correo" placeholder="Correo electrónico" required>
  <div class="password-container">
    <input type="password" name="password" placeholder="Contraseña" required>
    <i class='bx bx-hide toggle-password'></i>
  </div>
  <a href="recuperar.php">¿Olvidaste la Contraseña?</a>
  <button type="submit" class="btn">Registrarse</button>
</form>


        </div>

        <div class="login-section">
            <header>Acceso</header>

            <div class="social-buttons">
                <button><i class='bx bxl-google'></i> Usar Google</button>
                <button><i class='bx bxl-apple'></i> Usar Apple</button>
            </div>

            <div class="separator">
                <div class="line"></div>
                <p>Or</p>
                <div class="line"></div>
            </div>

        <?php if (!empty($mensaje_login)): ?>
  <div style="color: red; text-align: center; margin-top: 10px;">
    <?= htmlspecialchars($mensaje_login) ?>
  </div>
        <?php endif; ?>


            <form method="POST">
  <input type="hidden" name="accion" value="login">
  <input type="email" name="correo" placeholder="Correo electrónico" required>
  <div class="password-container">
    <input type="password" name="password" placeholder="Contraseña" required>
    <i class='bx bx-hide toggle-password'></i>
  </div>
  <a href="recuperar.php">¿Olvidaste la Contraseña?</a>
  <button type="submit" class="btn">Acceder</button>
</form>

        </div>

    </div>


    <script>
    const container = document.querySelector('.container');
    const signupButton = document.querySelector('.signup-section header');
    const loginButton = document.querySelector('.login-section header');

    // Inicializar estado al cargar
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('.signup-section').classList.add('active');
    });

    // Modificar los event listeners
    loginButton.addEventListener('click', () => {
        container.classList.add('active');
        document.querySelector('.login-section').classList.add('active');
        document.querySelector('.signup-section').classList.remove('active');
    });

    signupButton.addEventListener('click', () => {
        container.classList.remove('active');
        document.querySelector('.signup-section').classList.add('active');
        document.querySelector('.login-section').classList.remove('active');
    });

    // Toggle contraseña
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const isVisible = input.type === 'password';
            
            input.type = isVisible ? 'text' : 'password';
            this.classList.toggle('bx-hide', !isVisible);
            this.classList.toggle('bx-show', isVisible);
        });
    });
    </script>
</body>

</html>
