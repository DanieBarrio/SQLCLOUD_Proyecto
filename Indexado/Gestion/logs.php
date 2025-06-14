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
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $fecha = date('Y-m-d H:i:s');
        $evento = 'Borrado de logs';

        $linea = "[$fecha] [$ip] [$correo] $evento";
        file_put_contents("/var/www/sqlcloud.site/logs/logs.txt", $linea . PHP_EOL, FILE_APPEND | LOCK_EX);

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
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SQLCloud - Logs</title>
  <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">
  <script src="https://cdn.tailwindcss.com"></script> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c4a6e',
            },
            dark: {
              900: '#0f172a',
              800: '#1e293b',
              700: '#334155',
              600: '#475569',
            }
          }
        }
      }
    }
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    body {
      font-family: 'Inter', sans-serif;
      transition: background-color 0.3s ease;
    }
    .dashboard-grid {
      display: grid;
      grid-template-columns: 250px 1fr;
      min-height: 100vh;
    }
    .sidebar {
      background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); 
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    .main-content {
      background-color: #f8fafc;
      transition: background-color 0.3s ease;
    }
    .dark .main-content {
      background-color: #0f172a;
    }
    .user-card {
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      border-radius: 0.75rem;
      overflow: hidden;
    }
    .user-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    .database-table {
      width: 100%;
      border-collapse: collapse;
    }
    .database-table th {
      background-color: #f1f5f9;
      padding: 0.75rem 1rem;
      text-align: left;
      font-size: 0.875rem;
      font-weight: 600;
      color: #64748b;
    }
    .dark .database-table th {
      background-color: #334155;
      color: #cbd5e1;
    }
    .database-table td {
      padding: 0.75rem 1rem;
      border-top: 1px solid #e2e8f0;
    }
    .dark .database-table td {
      border-color: #334155;
    }
    .database-table tr:hover td {
      background-color: #f8fafc;
    }
    .dark .database-table tr:hover td {
      background-color: #1e293b;
    }
    /* Estilos para botones de navegación */
    .nav-link {
      transition: all 0.2s ease;
      border-radius: 0.375rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
    }
    .nav-link:hover, .nav-link.active {
      background-color: rgba(30, 41, 59, 0.5);
    }
  </style>
</head>
<body class="bg-gray-50 dark:bg-slate-900">
  <div class="dashboard-grid">
    <!-- Sidebar -->
    <aside class="sidebar text-white">
      <div class="p-6 border-b border-slate-700">
        <div class="flex items-center space-x-3">
          <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-2 rounded-lg">
            <i class="fas fa-database text-xl"></i>
          </div>
          <div>
            <h1 class="font-bold text-xl">SQLCloud</h1>
            <p class="text-slate-400 text-xs">Panel de administración</p>
          </div>
        </div>
      </div>
      <nav class="p-4">
        <ul class="space-y-2">
          <li>
            <a href="superadmin.php" class="nav-link">
              <i class="fas fa-users text-slate-300 text-lg"></i>
              <span>Usuarios</span>
            </a>
          </li>
          <li>
            <a href="#" class="nav-link active">
              <i class="fas fa-file-alt text-slate-300 text-lg"></i>
              <span>Logs</span>
            </a>
          </li>
          <li>
            <a href="index.php" class="nav-link">
              <i class="fas fa-home text-slate-300 text-lg"></i>
              <span>Volver al inicio</span>
            </a>
          </li>
        </ul>
      </nav>
      <div class="p-4 mt-auto border-t border-slate-700">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center">
              <span class="font-bold"><?= substr($_SESSION['user'] ?? 'A', 0, 1) ?></span>
            </div>
            <div>
              <p class="font-medium"><?= $_SESSION['user'] ?? 'Administrador' ?></p>
              <p class="text-slate-400 text-sm"><?= ucfirst($_SESSION['ROL'] ?? 'admin') ?></p>
            </div>
          </div>
          <button id="toggleTheme" class="text-slate-400 hover:text-white">
            <i id="iconTheme" class="fas fa-moon text-lg"></i>
          </button>
        </div>
      </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
      <!-- Header -->
      <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
        <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Registro de Actividad</h2>
            <p class="text-slate-500 dark:text-slate-400">Vista detallada de los eventos del sistema</p>
          </div>
          <div class="flex gap-3">
            <button id="toggleThemeHeader" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-white rounded-lg flex items-center">
              <i id="iconThemeHeader" class="fas fa-moon mr-2"></i> Modo Oscuro
            </button>
            <form action="logout.php" method="POST" class="inline">
              <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
              </button>
            </form>
          </div>
        </div>
      </header>
      
      <!-- Logs Content -->
      <div class="p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
          <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Eventos del Sistema</h3>
            <p class="text-slate-500 dark:text-slate-400">Últimos registros de actividad</p>
          </div>
          
          <div class="p-4">
            <?php if ($rol === 'superadmin'): ?>
              <?php if (isset($_GET['borrado']) && $_GET['borrado'] === 'ok'): ?>
                <div class="bg-green-600 text-white px-4 py-2 rounded mb-4">
                  ✅ Todos los logs han sido eliminados correctamente.
                </div>
              <?php endif; ?>
              
              <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar TODOS los logs? Esta acción es irreversible');" class="mb-6">
                <button type="submit" name="eliminar_logs" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center">
                  <i class="fas fa-trash mr-2"></i> Eliminar todos los logs
                </button>
              </form>
            <?php else: ?>
              <div class="bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 p-4 rounded-lg mb-6">
                <i class="fas fa-info-circle mr-2"></i>
                Solo los usuarios con rol de superadministrador pueden eliminar los logs.
              </div>
            <?php endif; ?>
            
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
              <table class="database-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>IP</th>
                    <th>Evento</th>
                    <th>Fecha</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($logs as $log): 
                    preg_match('/\[(.*?)\] \[(.*?)\] \[(.*?)\] (.*)/', $log['RAW'], $matches);
                    $fecha  = $matches[1] ?? '';
                    $ip     = $matches[2] ?? '';
                    $correo = $matches[3] ?? '';
                    $evento = $matches[4] ?? '';
                  ?>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                      <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">#<?= htmlspecialchars($log['ID']) ?></td>
                      <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($correo) ?></td>
                      <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($ip) ?></td>
                      <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($evento) ?></td>
                      <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($fecha) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  
                  <?php if (empty($logs)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-file-alt text-3xl mb-2"></i>
                        <p>No hay registros disponibles</p>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    // Toggle dark/light mode
    document.addEventListener("DOMContentLoaded", function() {
      const root = document.documentElement;
      const themeButton = document.getElementById("toggleTheme");
      const themeIcon = document.getElementById("iconTheme");
      const themeButtonHeader = document.getElementById("toggleThemeHeader");
      const themeIconHeader = document.getElementById("iconThemeHeader");
      
      // Check for saved theme or prefer color scheme
      const savedTheme = localStorage.getItem("theme");
      const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
      
      // Set initial theme
      function setTheme(dark) {
        if (dark) {
          root.classList.add("dark");
          themeIcon.classList.replace("fa-moon", "fa-sun");
          themeIconHeader.classList.replace("fa-moon", "fa-sun");
        } else {
          root.classList.remove("dark");
          themeIcon.classList.replace("fa-sun", "fa-moon");
          themeIconHeader.classList.replace("fa-sun", "fa-moon");
        }
      }
      
      if (savedTheme === "dark" || (!savedTheme && prefersDark)) {
        setTheme(true);
      } else {
        setTheme(false);
      }
      
      // Toggle theme on button click
      [themeButton, themeButtonHeader].forEach(btn => {
        btn.addEventListener("click", () => {
          const isDark = root.classList.contains("dark");
          if (isDark) {
            root.classList.remove("dark");
            localStorage.setItem("theme", "light");
          } else {
            root.classList.add("dark");
            localStorage.setItem("theme", "dark");
          }
          setTheme(!isDark);
        });
      });
    });
  </script>
</body>
</html>
