<?php
session_start();

if (!isset($_SESSION['ROL']) || !in_array($_SESSION['ROL'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit();
}

include 'conexion.php';

$conn = conectar();

$sql = "SELECT u.ID, u.NOMBRE, u.CORREO, u.ROL, p.T_PLAN FROM usuarios u LEFT JOIN plan p ON u.ID = p.ID ORDER BY FIELD(u.ROL, 'superadmin', 'admin', 'usuario'), u.ID DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración SQLCloud</title>
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
    
    .user-header {
      padding: 1.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .dark .user-header {
      border-color: #334155;
    }
    
    .user-actions {
      padding: 1.25rem;
      background-color: #f8fafc;
    }
    
    .dark .user-actions {
      background-color: #1e293b;
    }
    
    .action-section {
      margin-bottom: 1.5rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px dashed #cbd5e1;
    }
    
    .dark .action-section {
      border-color: #475569;
    }
    
    .action-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    
    .plan-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-weight: 600;
    }
    
    .plan-free {
      background-color: #dbeafe;
      color: #1d4ed8;
    }
    
    .dark .plan-free {
      background-color: #1e3a8a;
      color: #dbeafe;
    }
    
    .plan-premium {
      background-color: #dcfce7;
      color: #166534;
    }
    
    .dark .plan-premium {
      background-color: #14532d;
      color: #dcfce7;
    }
    
    .role-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .role-user {
      background-color: #dbeafe;
      color: #1d4ed8;
    }
    
    .dark .role-user {
      background-color: #1e3a8a;
      color: #dbeafe;
    }
    
    .role-admin {
      background-color: #ede9fe;
      color: #7c3aed;
    }
    
    .dark .role-admin {
      background-color: #4c1d95;
      color: #ddd6fe;
    }
    
    .role-superadmin {
      background-color: #fef3c7;
      color: #b45309;
    }
    
    .dark .role-superadmin {
      background-color: #78350f;
      color: #fef3c7;
    }
    
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
    
    .database-modal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 50;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }
    
    .database-modal.active {
      opacity: 1;
      pointer-events: all;
    }
    
    .database-modal-content {
      background-color: white;
      border-radius: 0.75rem;
      width: 90%;
      max-width: 800px;
      max-height: 90vh;
      overflow: hidden;
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }
    
    .dark .database-modal-content {
      background-color: #1e293b;
    }
    
    .database-modal.active .database-modal-content {
      transform: translateY(0);
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
    
    .fade-in {
      animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .slide-down {
      animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
      from { max-height: 0; opacity: 0; }
      to { max-height: 500px; opacity: 1; }
    }
    
    .action-panel {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }
    
    .action-panel.open {
      max-height: 500px;
    }
    
    .toggle-icon {
      transition: transform 0.3s ease;
    }
    
    .toggle-icon.open {
      transform: rotate(180deg);
    }
  @media (max-width: 640px) {
  .user-card { margin: 0.5rem; }
  .database-table { font-size: 0.875rem; }
}
  </style>
</head>
<body class="bg-gray-50 dark:bg-slate-900">
  <!-- Modal para bases de datos -->
  <div id="databaseModal" class="database-modal">
    <div class="database-modal-content">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-800 dark:text-white" id="modalTitle">Bases de datos del usuario</h3>
          <button id="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>
        
        <div class="mb-4 text-gray-700 dark:text-gray-300">
          <p class="mb-1"><span class="font-medium">Usuario:</span> <span id="modalUserName"></span></p>
          <p><span class="font-medium">Correo:</span> <span id="modalUserEmail"></span></p>
        </div>
        
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700 mb-6">
          <table class="database-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre de la Base</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="databasesList">
              <!-- Las bases de datos se cargarán aquí dinámicamente -->
            </tbody>
          </table>
        </div>
        
        <div class="flex flex-col sm:flex-row justify-end gap-3">
          <button id="deleteAllDbsBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center justify-center">
            <i class="fas fa-trash mr-2"></i> Eliminar todas
          </button>
          <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-white rounded-lg" id="closeModalBtn">
            <i class="fas fa-times mr-2"></i> Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

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
            <a href="#" class="nav-link active">
              <i class="fas fa-users text-slate-300"></i>
              <span>Usuarios</span>
            </a>
          </li>
          <li>
            <a href="logs.php" class="nav-link">
              <i class="fas fa-file-alt text-slate-300"></i>
              <span>Logs</span>
            </a>
          </li>
          <li>
            <a href="index.php" class="nav-link">
              <i class="fas fa-home text-slate-300"></i>
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
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Gestión de Usuarios</h2>
            <p class="text-slate-500 dark:text-slate-400">Administra usuarios, roles y bases de datos</p>
          </div>
          <div class="flex gap-3">
              <button id="toggleThemeHeader" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-white rounded-lg flex items-center">
              <i id="iconThemeHeader" class="fas fa-moon mr-2"></i> Modo Oscuro
            </button>
          </div>
        </div>
      </header>
      
      <!-- Stats -->
      <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Usuarios Totales</p>
                <h3 class="text-3xl font-bold mt-2"><?= $result->num_rows ?></h3>
              </div>
              <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-full">
                <i class="fas fa-users text-blue-500 dark:text-blue-400 text-xl"></i>
              </div>
            </div>
          </div>
          
          <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Administradores</p>
                <h3 class="text-3xl font-bold mt-2">
                  <?php 
                    $adminCount = 0;
                    while ($row = $result->fetch_assoc()) {
                      if (in_array($row['ROL'], ['admin', 'superadmin'])) $adminCount++;
                    }
                    echo $adminCount;
                    $result->data_seek(0); 
                  ?>
                </h3>
              </div>
              <div class="bg-amber-100 dark:bg-amber-900/30 p-3 rounded-full">
                <i class="fas fa-crown text-amber-500 dark:text-amber-400 text-xl"></i>
              </div>
            </div>
          </div>
          
          <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Plan Premium</p>
                <h3 class="text-3xl font-bold mt-2">
                  <?php 
                    $premiumCount = 0;
                    while ($row = $result->fetch_assoc()) {
                      if ($row['T_PLAN'] === 'premium') $premiumCount++;
                    }
                    echo $premiumCount;
                    $result->data_seek(0); 
                  ?>
                </h3>
              </div>
              <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full">
                <i class="fas fa-star text-green-500 dark:text-green-400 text-xl"></i>
              </div>
            </div>
          </div>
          
          <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Superadmins</p>
                <h3 class="text-3xl font-bold mt-2">
                  <?php 
                    $superadminCount = 0;
                    while ($row = $result->fetch_assoc()) {
                      if ($row['ROL'] === 'superadmin') $superadminCount++;
                    }
                    echo $superadminCount;
                    $result->data_seek(0); 
                  ?>
                </h3>
              </div>
              <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-full">
                <i class="fas fa-shield-alt text-purple-500 dark:text-purple-400 text-xl"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- User Management -->
      <div class="p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
          <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
              <div>
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Lista de Usuarios</h3>
                <p class="text-slate-500 dark:text-slate-400">Administra los usuarios de SQLCloud</p>
              </div>
              
              <div class="w-full md:w-64">
                <div class="relative">
                  <input type="text" id="searchInput" placeholder="Buscar usuarios..." 
                         class="w-full px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <i class="fas fa-search absolute right-3 top-3 text-slate-400"></i>
                </div>
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4" id="usersGrid">
            <?php while ($row = $result->fetch_assoc()):
              $id = htmlspecialchars($row['ID']);
              $nombre = htmlspecialchars($row['NOMBRE']);
              $correo = htmlspecialchars($row['CORREO']);
              $rol = htmlspecialchars($row['ROL']);
              $plan = htmlspecialchars($row['T_PLAN'] ?? 'gratuito');
              
              // Determinar clases CSS
              $roleClass = 'role-user';
              $roleText = 'Usuario';
              if ($rol === 'admin') {
                $roleClass = 'role-admin';
                $roleText = 'Admin';
              } 
              if ($rol === 'superadmin') {
                $roleClass = 'role-superadmin';
                $roleText = 'Superadmin';
              }
              
              $planClass = 'plan-free';
              $planText = 'Gratuito';
              if ($plan === 'premium') {
                $planClass = 'plan-premium';
                $planText = 'Premium';
              }
            ?>
              <div class="user-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 fade-in">
                <div class="user-header">
                  <div class="flex items-start justify-between">
                    <div>
                      <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold text-lg">
                          <?= substr($nombre, 0, 1) ?>
                        </div>
                        <div>
                          <h4 class="font-bold text-slate-800 dark:text-white"><?= $nombre ?></h4>
                          <p class="text-xs text-slate-500 dark:text-slate-400">ID: <?= $id ?></p>
                        </div>
                      </div>
                      <p class="text-sm text-slate-600 dark:text-slate-300 mb-1">
                        <i class="fas fa-envelope mr-2"></i><?= $correo ?>
                      </p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                      <span class="<?= $roleClass ?>"><?= $roleText ?></span>
                      <span class="<?= $planClass ?>"><?= $planText ?></span>
                    </div>
                  </div>
                </div>
                
                <div class="user-actions">
                  <!-- Acción: Ver bases de datos -->
                  <button class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-white rounded-lg mb-3 flex items-center justify-between view-databases-btn" 
                          data-id="<?= $id ?>" 
                          data-name="<?= $nombre ?>"
                          data-email="<?= $correo ?>">
                    <span><i class="fas fa-database mr-2"></i> Bases de datos</span>
                    <i class="fas fa-chevron-right"></i>
                  </button>
                  
                  <!-- Sección de acciones desplegable -->
                  <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    <button class="w-full px-3 py-2 flex items-center justify-between action-toggle">
                      <span class="font-medium text-slate-700 dark:text-slate-200">Acciones de administración</span>
                      <i class="fas fa-chevron-down toggle-icon"></i>
                    </button>
                    
                    <div class="action-panel">
                      <div class="p-3 space-y-3">
                        <!-- Actualización de Plan -->
                        <div>
                          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Plan:</label>
                          <div class="flex gap-2">
                            <select name="plan" class="flex-1 border rounded px-3 py-2 text-sm bg-white dark:bg-slate-800 text-slate-800 dark:text-white">
                              <option value="gratuito" <?= $plan === 'gratuito' ? 'selected' : '' ?>>Gratuito</option>
                              <option value="premium" <?= $plan === 'premium' ? 'selected' : '' ?>>Premium</option>
                            </select>
                            <button type="button" class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg update-btn">
                              <i class="fas fa-sync"></i>
                            </button>
                          </div>
                        </div>
                        
                        <!-- Actualización de Rol -->
                        <?php if ($_SESSION['ROL'] === 'superadmin'): ?>
                        <div>
                          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Rol:</label>
                          <div class="flex gap-2">
                            <select name="rol" class="flex-1 border rounded px-3 py-2 text-sm bg-white dark:bg-slate-800 text-slate-800 dark:text-white">
                              <option value="usuario" <?= $rol === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                              <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Admin</option>
                              <option value="superadmin" <?= $rol === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                            </select>
                            <button type="button" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg update-role-btn">
                              <i class="fas fa-sync"></i>
                            </button>
                          </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Eliminar usuario -->
                        <?php if ($_SESSION['user'] !== $correo): ?>
                        <div>
                          <button type="button" class="w-full px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg delete-btn flex items-center justify-center">
                            <i class="fas fa-trash mr-2"></i> Eliminar usuario
                          </button>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-2 text-sm text-slate-500 dark:text-slate-400">
                          No puedes eliminarte a ti mismo
                        </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
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
      // User search functionality
      const searchInput = document.getElementById("searchInput");
      searchInput.addEventListener("input", function() {
        const searchTerm = this.value.toLowerCase();
        const userCards = document.querySelectorAll("#usersGrid .user-card");
        
        userCards.forEach(card => {
          const userName = card.querySelector("h4").textContent.toLowerCase();
          const userEmail = card.querySelector("p:nth-child(2)").textContent.toLowerCase();
          
          if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
            card.style.display = "block";
          } else {
            card.style.display = "none";
          }
        });
      });
      
      // Toggle action panels
      document.querySelectorAll('.action-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
          const panel = this.closest('.bg-slate-50').querySelector('.action-panel');
          const icon = this.querySelector('.toggle-icon');
          
          panel.classList.toggle('open');
          icon.classList.toggle('open');
        });
      });
      
      // Database modal functionality
      const databaseModal = document.getElementById('databaseModal');
      const closeModal = document.getElementById('closeModal');
      const closeModalBtn = document.getElementById('closeModalBtn');
      const modalTitle = document.getElementById('modalTitle');
      const modalUserName = document.getElementById('modalUserName');
      const modalUserEmail = document.getElementById('modalUserEmail');
      const databasesList = document.getElementById('databasesList');
      const deleteAllDbsBtn = document.getElementById('deleteAllDbsBtn');
      
      let currentUserId = null;
      let currentUserName = null;
      let currentUserEmail = null;
      
      // Open database modal
      document.querySelectorAll('.view-databases-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          currentUserId = this.getAttribute('data-id');
          currentUserName = this.getAttribute('data-name');
          currentUserEmail = this.getAttribute('data-email');
          
          modalTitle.textContent = `Bases de datos de ${currentUserName}`;
          modalUserName.textContent = currentUserName;
          modalUserEmail.textContent = currentUserEmail;
          
          databaseModal.classList.add('active');
          
          loadUserDatabases(currentUserId);
        });
      });
      
      // Close modal
      const closeDatabaseModal = () => {
        databaseModal.classList.remove('active');
      };
      
      closeModal.addEventListener('click', closeDatabaseModal);
      closeModalBtn.addEventListener('click', closeDatabaseModal);
      
      // Load user databases
      function loadUserDatabases(userId) {
        databasesList.innerHTML = `
          <tr>
            <td colspan="3" class="text-center py-4">
              <i class="fas fa-spinner fa-spin mr-2"></i> Cargando bases de datos...
            </td>
          </tr>
        `;
        
        fetch('acciones_admin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ accion: 'obtener_bases', id_usuario: userId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success && data.databases.length > 0) {
            databasesList.innerHTML = '';
            
            data.databases.forEach(db => {
              const row = document.createElement('tr');
              row.innerHTML = `
                <td>${db.ID_BD}</td>
                <td class="font-medium">${db.NOMBRE_BD}</td>
                <td>
                  <button class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg delete-db-btn" data-id="${db.ID_BD}">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              `;
              
              // Add delete event
              row.querySelector('.delete-db-btn').addEventListener('click', function() {
                const dbId = this.getAttribute('data-id');
                deleteDatabase(dbId);
              });
              
              databasesList.appendChild(row);
            });
          } else {
            databasesList.innerHTML = `
              <tr>
                <td colspan="3" class="text-center py-4 text-gray-500 dark:text-gray-400">
                  <i class="fas fa-database mr-2"></i> El usuario no tiene bases de datos
                </td>
              </tr>
            `;
          }
        })
        .catch(err => {
          databasesList.innerHTML = `
            <tr>
              <td colspan="3" class="text-center py-4 text-red-500">
                <i class="fas fa-exclamation-circle mr-2"></i> Error al cargar bases de datos
              </td>
            </tr>
          `;
        });
      }
      
      // Delete database
      function deleteDatabase(dbId) {
        if (!confirm('¿Estás seguro de eliminar esta base de datos? Esta acción es irreversible.')) {
          return;
        }
        
        fetch('acciones_admin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ accion: 'eliminar_base', id_bd: dbId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            loadUserDatabases(currentUserId);
          } else {
            alert('Error al eliminar la base de datos: ' + data.error);
          }
        })
        .catch(err => {
          alert('Error en la solicitud: ' + err.message);
        });
      }
      
      // Delete all databases
      deleteAllDbsBtn.addEventListener('click', function() {
        if (!confirm(`¿Estás seguro de eliminar TODAS las bases de datos de ${currentUserName}? Esta acción es irreversible.`)) {
          return;
        }
        
        fetch('acciones_admin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ accion: 'eliminar_todas_bases', id_usuario: currentUserId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert(`Se han eliminado ${data.deleted} bases de datos`);
            loadUserDatabases(currentUserId);
          } else {
            alert('Error al eliminar las bases de datos: ' + data.error);
          }
        })
        .catch(err => {
          alert('Error en la solicitud: ' + err.message);
        });
      });
      
      // Delete user
      document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const userCard = this.closest('.user-card');
          const userId = userCard.querySelector('.view-databases-btn').getAttribute('data-id');
          const userName = userCard.querySelector('h4').textContent;
          
          if (confirm(`¿Estás seguro de eliminar al usuario ${userName}? Esta acción eliminará todos sus datos.`)) {
            fetch('acciones_admin.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({ 
                accion: 'eliminar_usuario', 
                id_usuario: userId, 
                correo_usuario: userCard.querySelector('p:nth-child(2)').textContent 
              })
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                userCard.style.opacity = '0';
                setTimeout(() => userCard.remove(), 300);
              } else {
                alert('Error al eliminar el usuario: ' + data.error);
              }
            })
            .catch(err => {
              alert('Error en la solicitud: ' + err.message);
            });
          }
        });
      });

      // Update plan
  document.querySelectorAll('.update-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const userCard = this.closest('.user-card');
    const userId = userCard.querySelector('.view-databases-btn').getAttribute('data-id');
    const planSelect = this.closest('.flex').querySelector('select[name="plan"]');
    const newPlan = planSelect.value;

    // Obtener el plan actual
    const planBadge = userCard.querySelector('.plan-badge');
    const currentPlanText = planBadge?.textContent.trim().toLowerCase();
    const newPlanText = newPlan.toLowerCase();

    if (currentPlanText === newPlanText) {
      alert('⚠️ Error: El usuario ya tiene este plan.');
      return;
    }

    fetch('acciones_admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        accion: 'actualizar_plan',
        id_usuario: userId,
        plan: newPlan
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('✅ Éxito al actualizar el plan.');

        if (planBadge) {
          planBadge.textContent = newPlan === 'premium' ? 'Premium' : 'Gratuito';
          planBadge.className = newPlan === 'premium' ? 'plan-premium' : 'plan-free';

          const originalBg = userCard.style.backgroundColor;
          userCard.style.backgroundColor = '#dcfce7';
          setTimeout(() => {
            userCard.style.backgroundColor = originalBg;
          }, 1000);
        }
      } else {
        alert('❌ Error al actualizar el plan: ' + (data.error || 'No se pudo actualizar.'));
      }
    })
    .catch(err => {
      alert('⚠️ Error en la solicitud: ' + err.message);
    });
  });
});

      // Update role
  document.querySelectorAll('.update-role-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const userCard = this.closest('.user-card');
    const userId = userCard.querySelector('.view-databases-btn').getAttribute('data-id');
    const roleSelect = this.closest('.flex').querySelector('select[name="rol"]');
    const newRole = roleSelect.value;

    fetch('acciones_admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        accion: 'actualizar_rol',
        id_usuario: userId,
        rol: newRole
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const roleBadge = userCard.querySelector('.role-badge');
 	alert('Cambio de rol exitoso');
        if (roleBadge) {
          roleBadge.textContent = 
            newRole === 'superadmin' ? 'Superadmin' :
            newRole === 'admin' ? 'Admin' : 'Usuario';
	  alert('Cambio de rol exitoso');
          roleBadge.className = 'role-badge ';
          if (newRole === 'superadmin') roleBadge.classList.add('role-superadmin');
          else if (newRole === 'admin') roleBadge.classList.add('role-admin');
          else roleBadge.classList.add('role-user');

          const originalBg = userCard.style.backgroundColor;
          userCard.style.backgroundColor = '#dbeafe';
          setTimeout(() => {
            userCard.style.backgroundColor = originalBg;
          }, 1000);
        }
      } else {
        alert('Error al actualizar el rol: Usuario ya tiene ese rol. ' );
      }
    })
    .catch(err => {
      alert('Error en la solicitud: ' + err.message);
    });
  });
});
    });

  </script>
</body>
</html>
