<?php
// admin.php - Panel de administración con vista de logs
session_start();
require 'conexion.php';

if (!isset($_SESSION['user'])) {
    header('Location: logister.php');
    exit;
}

$conn = conectar();
$correo = $_SESSION['user'];

$stmt = $conn->prepare("SELECT ROL, ID FROM usuarios WHERE CORREO = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    die("<p>No autorizado</p>");
}

$row = $res->fetch_assoc();
$rol = $row['ROL'];
$idUsuario = $row['ID'];

if ($rol !== 'admin' && $rol !== 'superadmin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_logs'])) {
    if (in_array($rol, ['admin', 'superadmin'])) {
        file_put_contents('/var/www/sqlcloud.site/logs/logs.txt', '');
        header("Location: logs.php?borrado=ok");
        exit;
    } else {
        http_response_code(403);
        echo "No autorizado.";
        exit;
    }
}

// Leer logs desde el archivo
$logs = [];
$logFile = '/var/www/sqlcloud.site/logs/logs.txt';
if (file_exists($logFile)) {
    $lineas = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach (array_reverse($lineas) as $index => $linea) {
        $logs[] = [
            'ID'     => $index + 1,
            'RAW'    => $linea
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SQLCloud - Admin</title>
      <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">

  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
  <header class="bg-gray-800 text-white px-6 py-4 flex justify-between">
    <h1 class="text-xl font-bold">SQLCloud - Panel Admin</h1>
    <nav class="space-x-4">
      <a href="index.php" class="hover:underline">Inicio</a>
      <a href="superadmin.php" class="hover:underline">Administrar</a>
      <?php if ($rol === 'admin' || $rol === 'superadmin'): ?>
        <a href="#" class="text-yellow-400 font-semibold">Logs</a>
      <?php endif; ?>
      <form action="logout.php" method="POST" class="inline">
        <button class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded">Cerrar sesión</button>
      </form>
    </nav>
  </header>

  <main class="p-6">
    <h2 class="text-2xl font-bold mb-4">Últimos eventos registrados</h2>
    <div class="overflow-x-auto">
<?php if ($rol === 'superadmin'): ?>
  <?php if (isset($_GET['borrado']) && $_GET['borrado'] === 'ok'): ?>
    <div class="bg-green-600 text-white px-4 py-2 rounded mb-4">
      ✅ Todos los logs han sido eliminados correctamente.
    </div>
  <?php endif; ?>

  <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar TODOS los logs? Esta acción es irreversible');" class="mb-6">
    <button type="submit" name="eliminar_logs"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded shadow">
      Eliminar todos los logs
    </button>
  </form>
<?php else: ?>
  <p class="text-gray-600 mb-6">Solo los usuarios con rol de superadministrador pueden eliminar los logs.</p>
<?php endif; ?>

      <table class="min-w-full bg-white shadow rounded">
        <thead class="bg-gray-200 text-gray-700">
          <tr>
            <th class="px-4 py-2 text-left">#</th>
            <th class="px-4 py-2 text-left">Usuario</th>
            <th class="px-4 py-2 text-left">IP</th>
            <th class="px-4 py-2 text-left">Evento</th>
            <th class="px-4 py-2 text-left">Fecha</th>
          </tr>
        </thead>
        <tbody>
  <?php foreach ($logs as $log): ?>
    <?php
      preg_match('/\[(.*?)\] \[(.*?)\] \[(.*?)\] (.*)/', $log['RAW'], $matches);
      $fecha  = $matches[1] ?? '';
      $ip     = $matches[2] ?? '';
      $correo = $matches[3] ?? '';
      $evento = $matches[4] ?? '';
    ?>
    <tr class="border-b">
      <td class="px-4 py-2 text-sm text-gray-700">#<?= htmlspecialchars($log['ID']) ?></td>
      <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($correo) ?></td>
      <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($ip) ?></td>
      <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($evento) ?></td>
      <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($fecha) ?></td>
    </tr>
  <?php endforeach; ?>
</tbody>
      </table>
    </div>
  </main>
</body>
</html>
