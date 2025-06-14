<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['user'])) {
    session_unset();
    session_destroy();
    header('Location: logister.php');
    exit;
}

$conn = conectar();

$stmtUserId = $conn->prepare("SELECT ID, CORREO, CONTRASENA FROM usuarios WHERE CORREO = ?");
$stmtUserId->bind_param("s", $_SESSION['user']);
$stmtUserId->execute();
$resultUserId = $stmtUserId->get_result();

if ($resultUserId->num_rows !== 1) {
    session_unset();
    session_destroy();
    header('Location: logister.php');
    exit;
}

$usuario = $resultUserId->fetch_assoc();
$userId = $usuario['ID'];
$nombreUsuario = $usuario['CORREO'];
$contrasenaUsuario = $usuario['CONTRASENA'];
$stmtUserId->close();

$stmtDbCount = $conn->prepare("SELECT COUNT(*) AS total FROM usuario_base_datos WHERE ID_USUARIO = ?");
$stmtDbCount->bind_param("i", $userId);
$stmtDbCount->execute();
$stmtDbCount->bind_result($numBd);
$stmtDbCount->fetch();
$stmtDbCount->close();

$plan = $_SESSION['PLAN'] ?? 'gratuito';
$puedeCrear = false;

if ($plan === 'gratuito' && $numBd < 1) {
    $puedeCrear = true;
} elseif ($plan === 'premium' && $numBd < 3) {
    $puedeCrear = true;
}

$error = "";
$exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    if ($tokenRecibido == "" || $tokenRecibido != $_SESSION['csrf_token']){
        header('Location: index.php');
        exit;
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $nombreBd = trim($_POST['nombre_bd'] ?? '');
    $nombreBd = str_replace( " ", "", $nombreBd);
    if(strlen($nombreBd) > 30){
      exit;
    }
    if (empty($nombreBd)) {
        $error = "El nombre de la base de datos no puede estar vacío.";
    } elseif ($plan === 'gratuito' && $numBd >= 1) {
        $error = "Usuarios gratuitos solo pueden crear 1 base de datos.";
    } elseif ($plan === 'premium' && $numBd >= 3) {
        $error = "Has alcanzado el límite de bases de datos.";
    } else {

	$invalidChars = array( " ", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "+", "=", "{", "}", "[", "]", "|", "\\", ":", ";", "\"", "'", "<", ">", ",", ".", "?", "/", "~", "`" );
        $nombreBd = str_replace($invalidChars, "B", $nombreBd);
	$aleatorio = rand(1000,100000);
	$nombreBd = $aleatorio . "_" . $nombreBd;
	$stmtCheck = $conn->prepare("SELECT NOMBRE_BD FROM base_datos WHERE NOMBRE_BD = ?");
        $stmtCheck->bind_param("s", $nombreBd);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $error = "El nombre de la base de datos ya está en uso.";
        } else {
            $createDbSql = "CREATE DATABASE IF NOT EXISTS `$nombreBd`";
            if (!$conn->query($createDbSql)) {
                $error = "Error al crear la base de datos";
            } else {
                $invalidChars = array( " ", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "+", "=", "{", "}", "[", "]", "|", "\\", ":", ";", "\"", "'", "<", ">", ",", ".", "?", "/", "~", "`" );
		$nombreUsuario = str_replace($invalidChars, "C", $nombreUsuario);
		$nombreUsuario = $nombreUsuario . $userId;
		$createUser = "CREATE USER IF NOT EXISTS '$nombreUsuario'@'172.17.0.%' IDENTIFIED WITH mysql_native_password BY '$contrasenaUsuario'";
		if (!$conn->query($createUser)){
		    $error = "Error al crear usuario";
		}
                $grantSql = "GRANT ALL PRIVILEGES ON `$nombreBd`.* TO '$nombreUsuario'@'172.17.0.%'";
                if (!$conn->query($grantSql)) {
                    $error = "Error al asignar permisos";
                } else {
                      if (!$conn->query("FLUSH PRIVILEGES")) {
                           $error = "Error al actualizar privilegios";
                      } else {

                        $stmtInsertBd = $conn->prepare("INSERT INTO base_datos (NOMBRE_BD) VALUES (?)");
                        $stmtInsertBd->bind_param("s", $nombreBd);
                        $stmtInsertBd->execute();
                        $idBd = $conn->insert_id;
                        $stmtInsertBd->close();

                        $idUsuarioBd = $userId . '_' . $idBd;
                        $stmtInsertRel = $conn->prepare("INSERT INTO usuario_base_datos (ID_USUARIO_BD, ID_USUARIO, ID_BD, CONTRASENA_USU, CORREO_USU) VALUES (?, ?, ?, ?,?)");
                        $stmtInsertRel->bind_param("siiss", $idUsuarioBd, $userId, $idBd, $contrasenaUsuario, $nombreUsuario);

                        if ($stmtInsertRel->execute()) {
                            $stmtInsertRel->close();
                            header("Location: index.php");
                            exit;
                        } else {
                            $error = "Error al asignar acceso a la base de datos.";
                        }
	 	   }
                }
            }
        }
        $stmtCheck->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear Base de Datos</title>
      <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"  rel="stylesheet">
</head>
<body class="bg-dark text-white">
  <div class="container mt-5">
    <h2 class="mb-4">Crear Nueva Base de Datos</h2>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <label for="nombre_bd" class="form-label">Nombre de la Base de Datos</label>
        <input type="text" class="form-control" id="nombre_bd" name="nombre_bd" required maxlength="30">
        <div class="form-text">Máximo 100 caracteres.</div>
      </div>
      <button type="submit" class="btn btn-success">Crear Base de Datos</button>
    </form>
  </div>
</body>
</html>
