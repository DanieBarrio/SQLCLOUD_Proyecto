<?php
  session_start();

// Si no hay usuario en sesión, redirige a login
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
require 'Gestion/conexion.php';
require 'Gestion/funciones.php';
if (!VerificarToken($_SESSION['token'], $_SESSION['user'], $conn)) {
// Cerrar sesión y forzar nuevo login
    session_unset();
    session_destroy();
     header('Location: login.php');
     exit;
 }
?>

<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <title>SQLCloud - Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com "></script>
  <style>
    body.dark { background-color: #111827; color: white; }
  </style>
</head>
<body class="bg-gray-900 text-white font-sans">

<?php
// Simular algunos datos reales
$cpu = rand(20, 85);
$ram = rand(30, 75);
$disk = rand(10, 60);
$queryCount = rand(100, 200);
$plan = "Gratuito";
?>

  <!-- Header -->
  <header class="bg-gray-800 shadow-md sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <svg class="w-8 h-8 text-blue-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          <path d="M2 17L12 22L22 17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          <path d="M2 12L12 17L22 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
        <h1 class="text-xl font-bold">SQLCloud</h1>
      </div>
      <nav class="hidden md:flex space-x-6">
        <a href="#dashboard" class="hover:text-blue-400 transition">Panel</a>
        <a href="#terminal" class="hover:text-blue-400 transition">Terminal</a>
        <a href="#monitoring" class="hover:text-blue-400 transition">Monitoreo</a>
        <a href="#billing" class="hover:text-blue-400 transition">Facturación</a>
      </nav>
      <div class="flex items-center space-x-4">
        <button id="toggleDarkMode" class="p-2 rounded-full hover:bg-gray-700 transition">
          <svg id="iconTheme" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
          </svg>
        </button>
        <button class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md transition">Iniciar sesión</button>
      </div>
    </div>
  </header>

  <!-- Dashboard -->
  <section id="dashboard" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold mb-4">Estado del Servicio</h2>
        <p><strong>Plan:</strong> <?= htmlspecialchars($plan) ?></p>
        <p><strong>Bases de datos:</strong> 2 activas, 0 pausadas</p>
        <p><strong>Último backup:</strong> 2025-04-03</p>
        <button class="mt-4 bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition">Generar Backup</button>
      </div>
      <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold mb-4">Recursos Usados</h2>
        <div class="space-y-2">
          <div>
            <p>CPU: <?= $cpu ?>%</p>
            <div class="w-full h-2 bg-gray-700 rounded">
              <div class="h-2 rounded bg-green-500" style="width:<?= $cpu ?>%"></div>
            </div>
          </div>
          <div>
            <p>RAM: <?= $ram ?>%</p>
            <div class="w-full h-2 bg-gray-700 rounded">
              <div class="h-2 rounded bg-green-500" style="width:<?= $ram ?>%"></div>
            </div>
          </div>
          <div>
            <p>Almacenamiento: <?= $disk ?>%</p>
            <div class="w-full h-2 bg-gray-700 rounded">
              <div class="h-2 rounded bg-green-500" style="width:<?= $disk ?>%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Terminal -->
  <section id="terminal" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-black p-6 rounded-lg shadow-lg">
      <h2 class="text-xl font-semibold mb-4">Terminal SQL</h2>
      <form id="terminalForm" class="mb-4">
        <input type="text" id="terminalInput" placeholder="Escribe tu consulta SQL..." class="w-full bg-gray-900 border border-gray-700 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </form>
      <pre id="terminalOutput" class="bg-gray-900 p-4 rounded text-sm overflow-x-auto whitespace-pre-wrap h-64">$ </pre>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-800 mt-16 py-8 px-4">
    <div class="max-w-7xl mx-auto text-center text-gray-400 text-sm">
      © <?= date('Y') ?> SQLCloud. Todos los derechos reservados.
    </div>
  </footer>

  <!-- Scripts -->
  <script>
    // Toggle Dark Mode
    document.getElementById("toggleDarkMode").addEventListener("click", () => {
      document.documentElement.classList.toggle("dark");
      const icon = document.getElementById("iconTheme");
      icon.innerHTML = document.documentElement.classList.contains("dark")
        ? `<path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />`
        : `<path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" />`;
    });

    // Simular salida de terminal
    const form = document.getElementById("terminalForm");
    const input = document.getElementById("terminalInput");
    const output = document.getElementById("terminalOutput");

    form.addEventListener("submit", function(e) {
      e.preventDefault();
      if (!input.value.trim()) return;

      const mockResult = {
        id: 1,
        nombre: "Juan Pérez",
        correo: "juan@example.com"
      };

      const outputLine = `sqlcloud@db-terminal:~$ ${input.value}\nResultado:\n${JSON.stringify(mockResult, null, 2)}\n`;

      output.textContent += outputLine;
      input.value = "";
    });
  </script>
</body>
</html>
