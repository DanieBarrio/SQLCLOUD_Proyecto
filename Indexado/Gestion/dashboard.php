<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['user'])) {
    header('Location: logister.php');
    exit;
}

$nombreBd = $_GET['bd'] ?? '';
if (empty($nombreBd)) {
    die("<p class='text-danger'>❌ Debes seleccionar una base de datos.</p>");
}

$conn = conectar();

$stmtUserId = $conn->prepare("SELECT ID FROM usuarios WHERE CORREO = ?");
$stmtUserId->bind_param("s", $_SESSION['user']);
$stmtUserId->execute();
$resultUserId = $stmtUserId->get_result();

if ($resultUserId->num_rows !== 1) {
    die("<p class='text-danger'>❌ Usuario no encontrado.</p>");
}
$usuario = $resultUserId->fetch_assoc();
$userId = $usuario['ID'];

$stmtDb = $conn->prepare("SELECT b.NOMBRE_BD FROM usuario_base_datos ub JOIN base_datos b ON ub.ID_BD = b.ID_BD WHERE ub.ID_USUARIO = ? AND b.NOMBRE_BD = ?");
$stmtDb->bind_param("is", $userId, $nombreBd);
$stmtDb->execute();
$stmtDb->store_result();

if ($stmtDb->num_rows !== 1) {
    die("<p class='text-danger'>❌ No tienes acceso a esta base de datos.</p>");
}

$stmtCred = $conn->prepare("SELECT ub.CORREO_USU AS CORREO, ub.CONTRASENA_USU AS CONTRASENA FROM usuario_base_datos ub  WHERE ub.ID_USUARIO = ? ");
$stmtCred->bind_param("i", $userId);
$stmtCred->execute();
$resultUserId = $stmtCred->get_result();
$usuario = $resultUserId->fetch_assoc();

if ($usuario) {
    $dbUser = $usuario['CORREO'];
    $dbPassword = $usuario['CONTRASENA'];
} else {
    echo "No se encontraron credenciales para el usuario con ID: $UserId";
}
$stmtCred->close();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
$containerName = "$nombreBd";
$dbHost = "172.17.0.1";
$dbName = "$nombreBd";
$dbPort = "3306";

$output = "";
$command = "";

$exists = false;
$isRunning = false;

exec("docker inspect --format='{{.State.Running}}' $containerName 2>/dev/null", $existsOutput, $returnVar);
if ($returnVar === 0) {
    $exists = true;
    $isRunning = true;
} else {
    $exists = true;
    $isRunning = false;
}


if ($exists && !$isRunning) {
    $execCmd = "docker start $containerName 2>&1";
    exec($execCmd, $outputStart, $returnVar);

    if ($returnVar === 0) {
        $isRunning = true;
    } else {
       $exists = false;
       $isRunning = false;

    }
}



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


if ($isRunning && isset($_POST['cmd'])) {
    $command = $_POST['sql_command'];
    $command = str_replace("exit", "sexi", $command);
    $comprobar = str_replace(" ", "", $command);
    if($comprobar != ""){
    $execCmd = "docker exec $containerName mariadb " .
        "-h $dbHost " .
        "-u $dbUser " .
        "-p'$dbPassword' " .
        "-D $dbName " .
        "--ssl=0 " .
	"--default-auth=mysql_native_password " .
        "-e \"$command\" 2>&1";
    exec($execCmd, $output, $returnVar);
    if ($returnVar === 0) {

    if (empty($output)) {
            $output = ["Comando ejecutado correctamente"];
        }
    }
    $output = implode("\n", $output);
    }
}


}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - <?= htmlspecialchars($nombreBd) ?></title>
    <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"  rel="stylesheet">
  <!-- FontAwesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"  rel="stylesheet">
<style>
  :root {
    --bg-main: #f8f9fa;
    --text-main: #212529;
    --card-bg: #ffffff;
    --card-border: #dee2e6;
    --btn-primary: #007bff;
    --btn-hover: #0069d9;
    --terminal-bg: #f1f3f5;      /* Fondo claro para modo claro */
    --terminal-color: #2c3e50;   /* Texto azul oscuro en modo claro */
    --terminal-border: #ced4da;
  }

  html.dark {
    --bg-main: #111827;
    --text-main: #f9fafb;
    --card-bg: #1f2937;
    --card-border: #374151;
    --btn-primary: #3b82f6;
    --btn-hover: #2563eb;
    --terminal-bg: #1e293b;      /* Fondo azul oscuro en modo oscuro */
    --terminal-color: #e2e8f0;   /* Texto gris claro en modo oscuro */
    --terminal-border: #334155;
  }

  body {
    background-color: var(--bg-main);
    color: var(--text-main);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    transition: all 0.3s ease;
  }

  .card {
    background-color: var(--card-bg);
    border-color: var(--card-border);
    transition: background-color 0.3s;
  }

  .form-control {
    border: 1px solid var(--card-border);
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    transition: all 0.2s;
  }

  .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
    border-color: var(--btn-primary);
  }

  .btn-primary {
    background-color: var(--btn-primary);
    border: none;
    transition: background-color 0.2s;
  }

  .btn-primary:hover {
    background-color: var(--btn-hover);
  }

  /* Estilo de terminal moderno */
  .terminal-input {
    background-color: var(--terminal-bg);
    color: var(--terminal-color);
    font-family: 'Courier New', monospace;
    border: 1px solid var(--terminal-border);
    box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
  }

  pre {
    background-color: var(--terminal-bg);
    color: var(--terminal-color);
    font-family: 'Courier New', monospace;
    border: 1px solid var(--terminal-border);
    border-radius: 0.5rem;
    padding: 1rem;
    overflow-x: auto;
    font-size: 0.9rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
  }

  .footer {
    margin-top: 3rem;
    padding: 1rem 0;
    text-align: center;
    color: #6c757d;
  }
</style></head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'dark' : '' ?>">
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
      <a href="index.php" class="btn btn-primary me-2">
        <i class="fas fa-arrow-left"></i> Volver a Inicio
      </a>
      <button id="themeToggle" class="btn btn-outline-secondary">
        <i class="fas fa-moon"></i> Cambiar Tema
      </button>
    </div>
  </nav>

  <div class="container mt-3">
    <h2 class="mb-4">⌨️ Terminal SQL - <?= htmlspecialchars($nombreBd) ?></h2>

    <!-- Formulario de comandos SQL -->
    <form method="POST" class="mb-4">
      <div class="mb-3">
        <label for="sql_command" class="form-label">Escribe un comando SQL</label>
	<textarea name="sql_command" id="sql_command" class="form-control terminal-input" rows="12" placeholder="Ej: SELECT * FROM usuarios&#10;INSERT INTO tabla (col) VALUES ('valor');&#10;La palabra exit no es ejecutada en la base de datos"></textarea>
      </div>
      <button type="submit" name="cmd" class="btn btn-primary">
        <i class="fas fa-terminal"></i> Ejecutar
      </button>
    </form>

    <!-- Resultado del comando -->
    <?php if (!empty($output)): ?>
      <h4 class="mt-4">📤 Salida del Comando:</h4>
      <pre><?= htmlspecialchars($output) ?></pre>
    <?php endif; ?>
    <!-- Botones de plantillas SQL -->
<div class="mb-4">
  <h5>🧩 Plantillas SQL</h5>
  <div class="btn-group" role="group" aria-label="Plantillas SQL">
    <button type="button" class="btn btn-outline-secondary" onclick="appendCommandToTextarea('CREATE TABLE nombre_tabla (id INT PRIMARY KEY AUTO_INCREMENT, campo1 VARCHAR(255), campo2 TEXT);\n-- Escribe aquí tu código personalizado')">CREATE TABLE</button>
    <button type="button" class="btn btn-outline-secondary" onclick="appendCommandToTextarea('INSERT INTO nombre_tabla (campo1, campo2) VALUES (\'valor1\', \'valor2\');')">INSERT INTO</button>
    <button type="button" class="btn btn-outline-secondary" onclick="appendCommandToTextarea('SELECT * FROM nombre_tabla;')">SELECT</button>
    <button type="button" class="btn btn-outline-secondary" onclick="appendCommandToTextarea('UPDATE nombre_tabla SET campo1 = \'nuevo_valor\' WHERE id = 1;')">UPDATE</button>
    <button type="button" class="btn btn-outline-secondary" onclick="appendCommandToTextarea('DELETE FROM nombre_tabla WHERE id = 1;')">DELETE</button>
    <button type="button" class="btn btn-outline-secondary" onclick="appendCommandToTextarea('ALTER TABLE nombre_tabla ADD nueva_columna VARCHAR(255);')">ALTER TABLE</button>
  </div>
</div>
</div>

  </div>
  <!-- Footer -->
  <footer class="footer">
    © <?= date('Y') ?> SQLCloud. Todos los derechos reservados.
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 
  <script>
    // Toggle de tema oscuro
    document.getElementById('themeToggle').addEventListener('click', () => {
      document.documentElement.classList.toggle('dark');
      const isDark = document.documentElement.classList.contains('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');

      // Actualizar icono
      document.querySelector('#themeToggle i').classList.toggle('fa-moon');
      document.querySelector('#themeToggle i').classList.toggle('fa-sun');
    });

    // Cargar tema guardado
    window.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme') || 'light';
      if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
        document.querySelector('#themeToggle i').classList.replace('fa-moon', 'fa-sun');
      }
    });
     function appendCommandToTextarea(sql) {
    const textarea = document.getElementById('sql_command');
    const currentContent = textarea.value;
    
    // Agregar salto de línea si el textarea no está vacío
    if (currentContent.trim() !== '') {
      sql = '\n\n' + sql;
    }
    
    textarea.value += sql;
  }
  </script>
</body>
</html>
