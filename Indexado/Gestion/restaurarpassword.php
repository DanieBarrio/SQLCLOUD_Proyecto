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
    $email = $_GET['email'];
    $token = $_GET['token'];
    $conn = conectar();
    $stmt = $conn->prepare("SELECT TOKEN.TOKEN, usuarios.CORREO FROM TOKEN, usuarios WHERE TOKEN.ID = usuarios.ID and usuarios.CORREO = ? and TOKEN.TOKEN = ? ");
     // El interrogante es una forma de evitar la inyeccion sql 
    $stmt->bind_param("ss", $email ,$token); // vinculamos el interrogante con el usuario
    $stmt->execute();
    $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado
    
    if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario
    
    } else {
        
        
    }
   
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'conexion.php'; 

    $emailcom = $_POST['emailcom'];
    $tokencom = $_POST['tokencom'];
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
                !preg_match('/[!@#$_.,+*&%\-]/', $password)
            ) {
                $mensaje = "La contraseña debe tener al menos 8 caracteres, incluir una letra, un número y un símbolo(!@#$_.,+*&%\-).";
            }
          else {
            // Hasheamos la contraseña para seguridad
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "UPDATE usuarios  SET  PASSWORD = ? WHERE ID = (SELECT ID from usuarios where CORREO = ?) AND ID = (select ID FROM TOKEN WHERE TOKEN = ?)  ";
            if ($stmt) {
                $stmt->bind_param("sss", $hash, $emailcom, $tokencom);

                if ($stmt->execute()) {
                    session_unset();
                    session_destroy();
                    header("Location: login.php"); // Redirección segura
                    exit;
                } else {
                    $mensaje = "Error al cambiar la contraseña ";
                }
                $stmt->close();
            }
        }
    } else {
        echo $emailcom;
        echo $tokencom;

        
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
    
</head>
<body>
    <?php if (isset($mensaje)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <input type="hidden" name="emailcom" value="<?php echo htmlspecialchars($_GET['email']); ?>">
        <input type="hidden" name="tokencom" value="<?php echo htmlspecialchars($_GET['token']); ?>">
        <input type="text" placeholder="Ingrese su nueva contraseña" name="password" required/>
        <input type="text" placeholder="Ingrese su nueva contraseña" name="password2" required/>
        <input type="submit" value="Cambiar contraseña" name="codigo"/>
    </form>
</body>
</html>