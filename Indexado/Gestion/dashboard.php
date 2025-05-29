<?php
// Variables predefinidas según el comando `docker run`
$containerName = "prueba3";
$dbHost = "172.17.0.1"; // Host del contenedor
$dbUser = "sqlcloud_admin"; // Usuario de MySQL
$dbPassword = "i8inJ9%88NSI"; // Contraseña
$dbName = "334287_sqlcloud_db"; // Nombre de la base de datos
$dbPort = "3306"; // Puerto de MySQL

$output = "";
$command = "";

// 1. Verificar si el contenedor existe y su estado
$exists = false;
$isRunning = false;

exec("docker inspect --format='{{.State.Running}}' $containerName 2>/dev/null", $existsOutput, $returnVar);
if ($returnVar === 0) {
    $exists = true;
    $isRunning = true;
} else {
    $exists = false;
    $isRunning = false;
}

// 2. Crear contenedor si no existe
if (!$exists) {
    $createCmd = "docker run -d " .
        "--name $containerName " .
        "-e DB_HOST=$dbHost " .
        "-e DB_PORT=$dbPort " .
        "-e DB_USER=$dbUser " .
        "-e DB_PASSWORD='$dbPassword' " .
        "-e DB_NAME=$dbName " .
        "mi-app-node 2>&1";

    exec($createCmd, $outputCreate , $returnVar);
    if ($returnVar === 0) {
        $exists = true;
        $isRunning = true;
    } else {
        die("<p>❌ Error al crear el contenedor, contacte con soporte si esto persiste");
    }
}

// 3. Iniciar contenedor si existe pero está parado
if ($exists && !$isRunning && isset($_POST['start'])) {
    $execCmd = "docker start $containerName 2>&1";
    exec($execCmd, $outputStart, $returnVar);

    if ($returnVar === 0) {
        $isRunning = true;
    } else {
        die("<p>❌ Error al iniciar el contenedor, si esto persiste contacte con soporte");
    }
}

// 4. Ejecutar comandos SQL dentro del contenedor
if ($isRunning && isset($_POST['cmd'])) {
    $command = $_POST['sql_command'];
    // Comando completo para ejecutar SQL en MySQL desde el contenedor
    $execCmd = "docker exec $containerName mariadb " .
        "-h $dbHost " .
        "-u $dbUser " .
        "-p'$dbPassword' " .
        "-D $dbName " .
        "--ssl=0 " .
        "-e \"$command\" 2>&1";
    exec($execCmd, $output, $returnVar);
    $output = implode("\n", $output);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Control de Docker y MySQL</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        input[type="text"] { padding: 8px; width: 100%; box-sizing: border-box; margin-bottom: 10px; }
        input[type="submit"] { padding: 10px 15px; background: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>🐋 Gestión de Contenedor Docker y Base de Datos</h2>

    <?php if ($isRunning): ?>
        <h3>⌨️ Ejecutar Comando SQL</h3>
        <form method="POST">
            <input type="text" name="sql_command" placeholder="Ej: SELECT * FROM usuarios" size="50" required>
            <input type="submit" name="cmd" value="Ejecutar">
        </form>

        <!-- Mostrar salida del comando -->
        <?php if (!empty($output)): ?>
            <h4>📤 Salida del comando:</h4>
            <pre><?= htmlspecialchars($output) ?></pre>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
