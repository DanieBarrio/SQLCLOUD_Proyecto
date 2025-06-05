<?php
session_start();

if (!isset($_SESSION['ROL']) || !in_array($_SESSION['ROL'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit();
}

include 'conexion.php';

$conn = conectar();

$sql = "
    SELECT u.ID, u.NOMBRE, u.CORREO, u.ROL, p.T_PLAN 
    FROM usuarios u
    LEFT JOIN plan p ON u.ID = p.ID
    ORDER BY FIELD(u.ROL, 'admin', 'usuario')
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
  <meta charset="UTF-8">
  <title>Panel de Administración</title>
  <script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class'
  };
</script> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> 

  <style>
    .user-card {
      @apply bg-gray-800 rounded-lg shadow-md border border-gray-700 mb-4 p-5 transition-all duration-200;
    }

    .user-card:hover {
      @apply bg-gray-750 border-gray-600;
    }

    .data-row {
      @apply flex justify-between items-center text-sm;
    }

    .label {
      @apply font-medium text-gray-400 w-1/4;
    }

    .value {
      @apply text-white truncate w-3/4;
    }

    .btn {
      @apply px-3 py-1 rounded text-sm font-medium transition-all duration-200;
    }

    .btn-danger {
      @apply bg-red-600 hover:bg-red-700 text-white;
    }

    .btn-success {
      @apply bg-green-600 hover:bg-green-700 text-white;
    }

    .action-form {
      @apply mt-4 pt-4 border-t border-gray-700 space-y-2;
    }

    .alert {
      @apply p-2 text-xs rounded hidden;
    }

    .alert-success {
      @apply bg-green-900 text-green-200;
    }

    .alert-danger {
      @apply bg-red-900 text-red-200;
    }
    html.dark body {
  	background-color: #111827;
  	color: #ffffff;
    }

    html.dark .bg-gray-800 {
  	background-color: #1f2937 !important;
    }

    html.dark .text-white {
  	color: #ffffff !important;
   }

   html.dark .user-card {
  	background-color: #1f2937;
  	border-color: #374151;
   }
  </style>
</head>
<body class="bg-white text-gray-800 dark:bg-gray-900 dark:text-gray-100">
<!-- ============================
     Navbar principal
     =========================== -->
<header class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-white shadow-md">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <div class="flex items-center space-x-2">
      <!-- Logo sencillo -->
      <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none">
        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"/>
        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2"/>
        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2"/>
      </svg>
      <h1 class="text-2xl font-bold">SQLCloud - Admin</h1>
    </div>
    <nav class="hidden md:flex space-x-6">
      <a href="logs.php" class="hover:text-blue-500 dark:hover:text-blue-300 transition">Logs</a>
      <a href="#usuarios" class="hover:text-blue-500 dark:hover:text-blue-300 transition">Usuarios</a>
      <a href="index.php" class="hover:text-blue-500 dark:hover:text-blue-300 transition">Volver</a>
    </nav>
    <div class="flex items-center space-x-4">
      <!-- Botón Dark/Light Mode -->
      <button id="toggleTheme" class="p-2 rounded-full hover:bg-gray-300 dark:hover:bg-gray-700 transition">
        <i id="iconTheme" class="fas fa-moon text-lg"></i>
      </button>
      <!-- Icono Admin -->
      <?php if ($_SESSION['ROL'] === 'superadmin'): ?>
        <a href="superadmin.php" class="text-yellow-500 hover:text-yellow-400 ml-4" title="Panel Superadmin">
          <i class="fas fa-crown text-xl"></i>
        </a>
      <?php endif; ?>
      <!-- Botón Cerrar Sesión -->
      <form method="POST" action="logout.php">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md transition">
          Cerrar sesión
        </button>
      </form>
    </div>
  </div>
</header>

<!-- ============================
     Sección: Panel de Administración
     =========================== -->
<section id="usuarios" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-6">Gestión de Usuarios</h2>

    <!-- Barra de búsqueda -->
    <div class="mb-6">
      <input type="text" id="searchInput" placeholder="Buscar por nombre o correo..."
             class="w-full p-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-900 text-gray-800 dark:text-white"
             onkeyup="filterUsers()">
    </div>

    <!-- Lista de Usuarios -->
    <div id="usersGrid" class="space-y-4">
      <?php while ($row = $result->fetch_assoc()):
        $id = htmlspecialchars($row['ID']);
        $nombre = htmlspecialchars($row['NOMBRE']);
        $correo = htmlspecialchars($row['CORREO']);
        $rol = htmlspecialchars($row['ROL']);
        $plan = htmlspecialchars($row['T_PLAN'] ?? 'gratuito');
      ?>
        <div class="user-card" data-nombre="<?= strtolower($nombre) ?>" data-correo="<?= strtolower($correo) ?>">
          <div class="data-row">
            <span class="label">ID:</span><span class="value"><?= $id ?></span>
          </div>
          <div class="data-row">
            <span class="label">Nombre:</span><span class="value"><?= $nombre ?></span>
          </div>
          <div class="data-row">
            <span class="label">Correo:</span><span class="value"><?= $correo ?></span>
          </div>
          <div class="data-row">
            <span class="label">Rol:</span><span class="value"><?= ucfirst($rol) ?></span>
          </div>
          <div class="data-row">
            <span class="label">Plan:</span><span class="value"><?= ucfirst($plan) ?></span>
          </div>

          <div class="action-form">
            <?php if ($_SESSION['user'] === $correo): ?>
              <p class="text-xs text-gray-400 italic">No puedes eliminarte.</p>
            <?php else: ?>
              <form class="delete-form" data-id="<?= $id ?>" data-email="<?= $correo ?>">
                <button type="button" class="btn btn-danger delete-btn w-full text-center">Eliminar Usuario</button>
                <div class="alert alert-danger hidden" data-msg>Error al eliminar</div>
              </form>
            <?php endif; ?>

            <form class="update-form" data-id="<?= $id ?>">
              <select name="plan" class="border rounded w-full px-2 py-1 text-xs bg-gray-900 text-white mt-2">
                <option value="gratuito" <?= $plan === 'gratuito' ? 'selected' : '' ?>>Gratuito</option>
                <option value="premium" <?= $plan === 'premium' ? 'selected' : '' ?>>Premium</option>
              </select>
              <button type="button" class="mt-2 btn btn-success update-btn w-full text-center">Actualizar Plan</button>
              <div class="alert alert-success hidden text-xs mt-1" data-msg>✅ Actualizado</div>
              <div class="alert alert-danger hidden text-xs mt-1" data-msg>Error</div>
            </form>
          </div>
	      <hr class="my-4 border-gray-700">
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>


<!-- ============================
     Footer general
     =========================== -->
<footer class="bg-gray-100 dark:bg-gray-800 mt-16 py-6 px-4 text-center text-gray-600 dark:text-gray-400 text-sm">
  © <?= date('Y') ?> SQLCloud. Todos los derechos reservados.
</footer>



<!-- Script del modo claro/oscuro -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const root = document.documentElement;
    const icon = document.getElementById("iconTheme");
    const storedTheme = localStorage.getItem("theme") || "light";

    if (storedTheme === "dark") {
      root.classList.add("dark");
      icon.classList.replace("fa-moon", "fa-sun");
    } else {
      root.classList.remove("dark");
      icon.classList.replace("fa-sun", "fa-moon");
    }
  });

  document.getElementById("toggleTheme").addEventListener("click", () => {
    const root = document.documentElement;
    const icon = document.getElementById("iconTheme");

    if (root.classList.contains("dark")) {
      root.classList.remove("dark");
      icon.classList.replace("fa-sun", "fa-moon");
      localStorage.setItem("theme", "light");
    } else {
      root.classList.add("dark");
      icon.classList.replace("fa-moon", "fa-sun");
      localStorage.setItem("theme", "dark");
    }
  });
</script>
<script>
  function filterUsers() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('#usersGrid .user-card');

    cards.forEach(card => {
      const nombre = card.getAttribute('data-nombre');
      const correo = card.getAttribute('data-correo');
      if (nombre.includes(input) || correo.includes(input)) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }

  // Eliminar usuario sin recargar
  document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const form = btn.closest('.delete-form');
    const id = form.dataset.id;
    const email = form.dataset.email;

    if (confirm("¿Estás seguro de eliminar este usuario?")) {
      fetch('acciones_admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ accion: 'eliminar_usuario', id_usuario: id, correo_usuario: email })
      }).then(res => res.json())
        .then(json => {
          if (json.success) {
            const userCard = form.closest('.user-card');
            userCard.remove();
          } else {
            throw new Error(json.error || "Error desconocido");
          }
        })
        .catch(err => {
          const msg = form.querySelector('[data-msg]');
          msg.textContent = err.message;
          msg.classList.remove('hidden');
        });
    }
  });
});

  // Actualizar plan sin recargar
  document.querySelectorAll('.update-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const form = btn.closest('.update-form');
    const id = form.dataset.id;
    const plan = form.querySelector('[name="plan"]').value;

    fetch('acciones_admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ accion: 'actualizar_plan', id_usuario: id, plan: plan })
    }).then(res => res.json())
      .then(json => {
        const success = form.querySelector('[data-msg][class*="success"]');
        const error = form.querySelector('[data-msg][class*="danger"]');

        if (json.success) {
          success.textContent = json.success;
          success.classList.remove('hidden');
          error.classList.add('hidden');
          setTimeout(() => success.classList.add('hidden'), 3000);
        } else {
          throw new Error(json.error || "Error desconocido");
        }
      })
      .catch(err => {
        const error = form.querySelector('[data-msg][class*="danger"]');
        error.textContent = err.message;
        error.classList.remove('hidden');
      });
  });
});

</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 

<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script> 

</body>
</html>
