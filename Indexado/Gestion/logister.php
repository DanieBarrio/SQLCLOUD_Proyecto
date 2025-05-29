<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//iniciamos las sesiones 
session_start();

//vemos que no lleve mas de 30 minutos sin movimiento
if (isset($_SESSION["ultimo_acceso"])) {
    $inactividad = 1800; 
    if (time() - $_SESSION["ultimo_acceso"] > $inactividad) {
        session_destroy();
    }
}
// en caso de que no hayan pasado los 30m que te ponga de nueo un contador
$_SESSION["ultimo_acceso"] = time();

//metemos funciones.php
require 'funciones.php';

//Vemos que si entra con el metodo get que es normal ya que se le envia un enlace al usuario con el token y el correo 
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require 'conexion.php';

    //quitamos las comillas y lo igualamos a una variable para su futuro uso
    $email = str_replace('"','', $_GET['email']);
    $token = str_replace('"','', $_GET['token']);
    $conn = conectar();

    //para corroborar que el token y el correo es de el mismmo comparamos las id
    $stmt = $conn->prepare("SELECT TOKEN.TOKEN, usuarios.CORREO FROM TOKEN, usuarios WHERE TOKEN.ID = usuarios.ID and usuarios.CORREO = ? and TOKEN.TOKEN = ? ");
     // El interrogante es una forma de evitar la inyeccion sql 
    $stmt->bind_param("ss", $email ,$token); 
    $stmt->execute();
    $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado
    
    if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario
    } else {
        header("Location: login.php");
        exit;
    }
   }
//si es por el metodo post que es una vez que completa el formulario 
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'conexion.php';
    //cogemos los datos del metodo get para posterior uso y del metodo post
    $emailcom = str_replace('"','', $_GET['email']);
    $tokencom = str_replace('"','', $_GET['token']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $conn = conectar(); 

    //comprobamos de nuevo el id
    $stmt = $conn->prepare("SELECT TOKEN.TOKEN, usuarios.CORREO FROM TOKEN, usuarios WHERE TOKEN.ID = usuarios.ID and usuarios.CORREO = ? and TOKEN.TOKEN = ? "); 
    $stmt->bind_param("ss", $emailcom ,$tokencom);
    $stmt->execute();
    $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado
    if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario
        //combrobaciones de la contraseña para comprobar que es seura y igual a la segunda
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
            //ponemos la contraseña al correo 
            $sql = "UPDATE usuarios SET CONTRASENA = ? WHERE CORREO = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $hash, $emailcom);

                if ($stmt->execute()) {
                    //regeneramos el token para que no pueda volverse a usar
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
ubuntu@ip-172-31-89-188:~/Indexado/Gestion$ 
ubuntu@ip-172-31-89-188:~/Indexado/Gestion$ cat
cat     catman  
ubuntu@ip-172-31-89-188:~/Indexado/Gestion$ cat 
Recuperar.php          conexion.php           index.php              perfil.php
VersionesViejas/       dashboard.php          logister.php           restaurarpassword.php
actualizar_plan.php    funciones.php          logout.php             tarjeta.php
ubuntu@ip-172-31-89-188:~/Indexado/Gestion$ cat 
Recuperar.php          conexion.php           index.php              perfil.php
VersionesViejas/       dashboard.php          logister.php           restaurarpassword.php
actualizar_plan.php    funciones.php          logout.php             tarjeta.php
ubuntu@ip-172-31-89-188:~/Indexado/Gestion$ cat logister.php 
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
        $stmt = $conn->prepare("SELECT CORREO, CONTRASENA, NOMBRE, PLAN, FECHA_EXPIRACION FROM usuarios WHERE CORREO = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['CONTRASENA'])) {
                $_SESSION['user'] = $row['CORREO'];
                $_SESSION['nombre'] = $row['NOMBRE'];
                $_SESSION['PLAN'] = $row['PLAN'];
                $_SESSION['FECHA_EXPIRACION'] = $row['FECHA_EXPIRACION'];
                header('Location: index.php');
                exit;
            } else {
                $mensaje_login = "Contraseña incorrecta.";
            }
        } else {
            $mensaje_login = "Correo no registrado.";
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
                $fecha_exp = null;

                $stmt = $conn->prepare("INSERT INTO usuarios (NOMBRE, CONTRASENA, CORREO, PLAN, FECHA_EXPIRACION) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nombre, $hash, $correo, $plan, $fecha_exp);

                if ($stmt->execute()) {
                    $_SESSION['user'] = $correo;
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['PLAN'] = $plan;
                    $_SESSION['FECHA_EXPIRACION'] = $fecha_exp;
                    header('Location: index.php');
                    exit;
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
    <title>Animated Login Design #02 | AsmrProg</title>
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
  <a href="#">¿Olvidaste la Contraseña?</a>
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
  <a href="#">¿Olvidaste la Contraseña?</a>
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
