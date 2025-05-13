<?php
    
// Función de ejemplo para autenticación (debes implementarla de forma segura)
    function Existeusuario($usuario, $password, $conn) {
        
        $stmt = $conn->prepare("SELECT USUARIO, NOMBRE, CORREO, CONTRASENA, ROL FROM usuarios WHERE USUARIO = ? OR CORREO = ?"); // El interrogante es una forma de evitar la inyeccion sql 
        $stmt->bind_param("ss", $usuario, $usuario); // vinculamos el interrogante con el usuario
        $stmt->execute();
        $respuesta = false;

        $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado

        if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario
            $user = $resultado->fetch_assoc(); // Guarda los datos de resultado en un array llamado user


            if (password_verify($password, $user['CONTRASENA'])) {//Comprobacion de contraseña aun sin hashear
            $_SESSION['USUARIO'] = $user['USUARIO'];
            $_SESSION['NOMBRE_COMPLETO'] = $user['NOMBRE'];
            $_SESSION['CORREO'] = $user['CORREO'];
            $_SESSION['ROL'] = $user['ROL'];
            $_SESSION['user'] = $user['USUARIO'];
            $respuesta = true;
            } 
        }
        
        return $respuesta;
    }

    function verificador($password, $nombre){
        $mensaje = null;
        if (
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[!@#$_.,+*&%\-]/', $password) 
            
        ) {
            $mensaje = "La contraseña debe tener al menos 8 caracteres, incluir una letra, un número y un símbolo(!@#$._,+*&%\-).";
        }elseif ( $password == $nombre ){
            $mensaje = "La contraseña no puede ser el nombre de usuario.";
        }
        return $mensaje;
    }

    function GuardarToken($conn, $token, $usuario){
        $stmt = $conn->prepare("SELECT ID FROM usuarios WHERE USUARIO = ? OR CORREO = ?"); // El interrogante es una forma de evitar la inyeccion sql 
        $stmt->bind_param("ss", $usuario, $usuario); // vinculamos el interrogante con el usuario
        $stmt->execute();

        $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado

        if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario
            $user = $resultado->fetch_assoc(); // Guarda los datos de resultado en un array llamado user

            $idusuario = $user['ID'];
        }
        $stmt = $conn->prepare("SELECT ID FROM TOKEN WHERE ID = ?"); // El interrogante es una forma de evitar la inyeccion sql 
        $stmt->bind_param("s", $idusuario); // vinculamos el interrogante con el usuario
        $stmt->execute();

        $resultado = $stmt->get_result(); // El resultado del select lo guarda en resutado

        if ($resultado->num_rows === 1) { // Comprueba que solo a devuelto una linea con un solo usuario
            $user = $resultado->fetch_assoc(); // Guarda los datos de resultado en un array llamado user
            $sql = "UPDATE TOKEN  SET  TOKEN = ? WHERE ID = ? ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $token, $idusuario);

                $stmt->execute();
            } 
        } else {
        
            $sql = "INSERT INTO TOKEN (ID, TOKEN) VALUES (?, ?) ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $idusuario , $token);

                $stmt->execute();
            } 
        }
            
    }





    function sinlogin() {
    if (!isset($_SESSION['user'], $_SESSION['token'], $_SESSION['ROL'])) {
        header("Location: login.php");
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
        header("Location: login.php");
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

?>

