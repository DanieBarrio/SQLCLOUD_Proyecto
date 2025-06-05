<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'conexion.php';
require 'funciones.php';

// 1) Verificar si el usuario está autenticado
if (!isset($_SESSION['user'])) {
    header('Location: logister.php');
    exit;
}

// 2) Recargar datos del usuario desde la base de datos
$connUser = conectar();
$stmt = $connUser->prepare("SELECT u.NOMBRE, u.CORREO, u.ROL, p.T_PLAN AS PLAN, p.F_EXPIRACION AS FECHA_EXPIRACION FROM usuarios u JOIN plan p ON u.ID = p.ID WHERE u.CORREO = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    $_SESSION['NOMBRE_COMPLETO'] = $fila['NOMBRE'];
    $_SESSION['CORREO'] = $fila['CORREO'];
    $_SESSION['PLAN'] = $fila['PLAN'];
    $_SESSION['FECHA_EXPIRACION'] = $fila['FECHA_EXPIRACION'];
    $_SESSION['ROL'] = $fila['ROL'];
}

// Mostrar fecha de expiración (si aplica)
if (!empty($_SESSION['FECHA_EXPIRACION'])) {
    $fecha_exp = DateTime::createFromFormat('Y-m-d', $_SESSION['FECHA_EXPIRACION']);
    $_SESSION['FECHA_EXPIRACION_FORMATO'] = $fecha_exp ? $fecha_exp->format('d/m/Y') : 'No disponible';
}

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar si hay alerta
$tieneAlerta = isset($_SESSION['error']) || isset($_SESSION['exito']);

// Obtener ID del usuario
$stmtUserId = $connUser->prepare("SELECT ID FROM usuarios WHERE CORREO = ?");
$stmtUserId->bind_param("s", $_SESSION['user']);
$stmtUserId->execute();
$resultUserId = $stmtUserId->get_result();
if ($resultUserId->num_rows === 1) {
    $userData = $resultUserId->fetch_assoc();
    $userId = $userData['ID'];
} else {
    $userId = null;
}

// Obtener bases de datos del usuario
$databases = [];
if ($userId) {
    $stmtDb = $connUser->prepare("SELECT b.NOMBRE_BD FROM usuario_base_datos ub JOIN base_datos b ON ub.ID_BD = b.ID_BD WHERE ub.ID_USUARIO = ?");
    $stmtDb->bind_param("i", $userId);
    $stmtDb->execute();
    $resultDb = $stmtDb->get_result();
    while ($row = $resultDb->fetch_assoc()) {
        $databases[] = $row['NOMBRE_BD'];
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="<?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark' : ''; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SQLCloud - Panel</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script> 
  <!-- Bootstrap y FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"  rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"  rel="stylesheet">
  <style>
    /* Estilos para modo claro y oscuro */
    html.dark body {
      background-color: #111827 !important;
      color: #ffffff !important;
    }

    html.dark .bg-gray-800,
    html.dark .card,
    html.dark .modal-content {
      background-color: #1f2937 !important;
      color: #ffffff !important;
    }

    html.dark .text-white,
    html.dark .form-control,
    html.dark .dropdown-menu,
    html.dark .btn-dark-toggle {
      background-color: #1f2937 !important;
      color: #ffffff !important;
    }

    html.dark .btn-close-white .btn-close:not([disabled]):not(.disabled):active,
    html.dark .btn-close-white .btn-close:not([disabled]):not(.disabled):focus {
      filter: brightness(90%);
    }

    html.dark .campo-inactivo {
      background-color: #374151 !important;
      cursor: not-allowed;
    }

    html.dark .campo-activo {
      background-color: #1f2937 !important;
    }

    html.dark .dropdown-menu {
      background-color: #1f2937 !important;
      border: none !important;
    }

    html.dark .dropdown-item:hover,
    html.dark .dropdown-item:focus {
      background-color: #374151 !important;
    }

    html.dark .alert-danger,
    html.dark .alert-success {
      background-color: #374151 !important;
      color: #ffffff !important;
      border: none !important;
    }

    html.dark .btn-close-white {
      filter: brightness(90%);
    }

    html.dark .btn-close-white:hover {
      filter: brightness(75%);
    }

    html.dark .btn-close-white:focus {
      outline: none;
    }

    html.dark .btn-close-white:active {
      filter: brightness(60%);
    }

    html.dark .btn-close-white[disabled] {
      opacity: 0.5;
      pointer-events: none;
    }

    html.dark .dropdown-toggle::after {
      filter: invert(1);
    }

    html.dark .dropdown-item {
      color: #ffffff !important;
    }

    html.dark .dropdown-item:hover,
    html.dark .dropdown-item:focus {
      color: #ffffff !important;
      background-color: #374151 !important;
    }

    html.dark .btn-primary,
    html.dark .btn-success,
    html.dark .btn-secondary,
    html.dark .btn-warning,
    html.dark .btn-danger {
      filter: brightness(90%);
    }

    html.dark .btn-primary:hover,
    html.dark .btn-success:hover,
    html.dark .btn-secondary:hover,
    html.dark .btn-warning:hover,
    html.dark .btn-danger:hover {
      filter: brightness(75%);
    }

    html.dark .btn-primary:focus,
    html.dark .btn-success:focus,
    html.dark .btn-secondary:focus,
    html.dark .btn-warning:focus,
    html.dark .btn-danger:focus {
      box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5) !important;
    }

    /* Estilos generales */
    body {
      transition: background-color 0.3s, color 0.3s;
    }

    .campo-inactivo {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    .campo-activo {
      background-color: #ffffff;
    }

    .dropdown-menu {
      z-index: 1000;
    }

    .dropdown-toggle::after {
      margin-left: 0.255em;
      vertical-align: middle;
    }

    .card {
      border: none;
    }

    .modal-content {
      border-radius: 0.5rem;
    }

    .btn-dark-toggle {
      background-color: #374151;
      color: white;
    }

    .btn-dark-toggle:hover {
      background-color: #4b5563;
    }

    .alert-danger {
      background-color: #dc3545 !important;
      color: #fff !important;
    }

    .alert-success {
      background-color: #28a745 !important;
      color: #fff !important;
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);
    }

    .form-label {
      font-weight: 500;
    }

    .dropdown {
      position: relative;
      display: inline-block;
    }

    .dropdown-menu {
      position: absolute;
      top: 100%;
      left: 0;
      z-index: 1000;
      display: none;
      min-width: 160px;
      padding: 0.5rem 0;
      margin: 0;
      font-size: 1rem;
      color: #212529;
      background-color: #fff;
      border: 1px solid #ccc;
      border-radius: 0.375rem;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .dropdown:hover .dropdown-menu {
      display: block;
    }

    .dropdown-item {
      display: block;
      padding: 0.5rem 1rem;
      clear: both;
      font-weight: 400;
      color: #212529;
      text-align: inherit;
      white-space: nowrap;
      background-color: transparent;
      border: none;
      cursor: pointer;
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
      background-color: #f1f3f5;
      color: #111827;
    }

    .dropdown-menu.show {
      display: block;
    }

    .btn-close-white {
      color: #ffffff;
      opacity: 0.5;
    }

    .btn-close-white:hover {
      opacity: 0.75;
    }

    .btn-close-white:focus {
      outline: none;
      box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.5);
    }

    .btn-close-white:disabled,
    .btn-close-white.disabled {
      opacity: 0.3;
      pointer-events: none;
    }

    @media (max-width: 768px) {
      .dropdown-menu {
        position: static;
        float: none;
        display: block;
        width: 100%;
        margin-top: 0.5rem;
        border-radius: 0.375rem;
      }

      .dropdown-toggle::after {
        display: none;
      }

      .dropdown:hover .dropdown-menu {
        display: none;
      }
    }
  </style>
</head>
<body>
  <!-- Navbar principal -->
  <header class="bg-gray-800 text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <svg class="w-8 h-8 text-blue-400" viewBox="0 0 24 24" fill="none">
          <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"/>
          <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2"/>
          <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2"/>
        </svg>
        <h1 class="text-2xl font-bold">SQLCloud</h1>
      </div>
      <nav class="hidden md:flex space-x-6">
        <a href="#dashboard" class="hover:text-blue-300 transition">Panel</a>
        <a href="#terminal" class="hover:text-blue-300 transition">Terminal</a>
        <a href="#monitoring" class="hover:text-blue-300 transition">Monitoreo</a>
        <a href="#billing" class="hover:text-blue-300 transition">Facturación</a>
      </nav>
      <div class="flex items-center space-x-4">
        <button id="toggleTheme" class="p-2 rounded-full hover:bg-gray-700 btn-dark-toggle">
          <i id="iconTheme" class="fas fa-moon text-lg"></i>
        </button>
        <button class="relative" data-bs-toggle="modal" data-bs-target="#modalPerfil">
          <i class="fas fa-user-circle text-2xl hover:text-blue-400 transition"></i>
        </button>
	<?php if (isset($_SESSION['ROL']) && in_array($_SESSION['ROL'], ['admin', 'superadmin'])): ?>
  <a href="admin.php" title="Panel Admin"
     class="text-yellow-400 hover:text-yellow-300 transition text-xl mr-2"
     aria-label="Ir al panel de administración">
    <i class="fas fa-shield-alt"></i>
  </a>
<?php endif; ?>
        <form method="POST" action="logout.php">
          <button type="submit" class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded-md transition">Cerrar sesión</button>
        </form>
      </div>
    </div>
  </header>

  <!-- Sección: Dashboard principal -->
  <section id="dashboard" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <?php
    // Simulación de datos de sistema
    $cpu = rand(20, 85);
    $ram = rand(30, 75);
    $disk = rand(10, 60);
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg relative">
        <div class="flex justify-between items-start">
          <h2 class="text-xl font-semibold mb-4">Estado del Servicio</h2>
          <?php
          $plan = $_SESSION['PLAN'] ?? 'gratuito';
          if ($plan !== 'premium'):
          ?>
            <!-- Icono de mejora a Premium -->
            <a href="tarjeta.php" title="Mejorar a Premium" class="text-blue-400 hover:text-blue-300 transition text-2xl">
              <i class="fas fa-gem"></i>
            </a>
          <?php else: ?>
            <span class="text-green-400 text-lg" title="Plan Premium Activo">
              <i class="fas fa-check-circle"></i>
            </span>
          <?php endif; ?>
        </div>
        <p><strong>Plan:</strong> <?= $plan === 'gratuito' ? 'Gratuito' : 'Premium' ?></p>
        <p><strong>Bases de datos:</strong> <?= count($databases) ?> activas</p>
        <p><strong>Último backup:</strong> 2025-04-03</p>
        <button class="mt-4 bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition">Generar Backup</button>
      </div>
      <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold mb-4">Recursos Usados</h2>
        <div class="space-y-4">
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

  <!-- Gestión de Bases de Datos -->
  <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg mt-6">
    <h2 class="text-xl font-semibold mb-4">Gestión de Bases de Datos</h2>
    <?php if ($_SESSION['PLAN'] === 'premium'): ?>
      <button class="mt-2 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition"
              onclick="window.location.href='crear_bd.php'">
        Crear Nueva Base de Datos
      </button>
    <?php endif; ?>
    <?php if (!empty($databases)): ?>
      <div class="dropdown mt-2">
        <button class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition dropdown-toggle"
                type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          Entrar en BD
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
          <?php foreach ($databases as $db): ?>
            <li>
              <a class="dropdown-item" href="index.php?bd=<?= urlencode($db) ?>">
                <?= htmlspecialchars($db) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modal: Perfil de Usuario -->
  <div class="modal fade" id="modalPerfil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Perfil de Usuario</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger mb-3"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
          <?php endif; ?>
          <?php if (isset($_SESSION['exito'])): ?>
            <div class="alert alert-success mb-3"><?= $_SESSION['exito']; unset($_SESSION['exito']); ?></div>
          <?php endif; ?>
          <form method="POST" id="perfilFormModal">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="editar_perfil" value="1">
            <div class="mb-3">
              <label for="nombreModal" class="form-label">Nombre completo</label>
              <input type="text" class="form-control campo-inactivo" name="nombreCompleto" id="nombreModal"
                     value="<?= htmlspecialchars($_SESSION['NOMBRE_COMPLETO'] ?? '') ?>" readonly>
            </div>
            <div class="mb-3">
              <label for="correoModal" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control campo-inactivo" name="correo" id="correoModal"
                     value="<?= htmlspecialchars($_SESSION['CORREO'] ?? '') ?>" readonly>
            </div>
            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
              <button type="button" id="editarBtnModal" class="btn btn-primary px-4">
                <i class="fas fa-edit me-2"></i>Editar
              </button>
              <div class="d-none gap-2" id="accionesEdicionModal">
                <button type="submit" id="enviarBtnModal" class="btn btn-success px-4">
                  <i class="fas fa-save me-2"></i>Guardar
                </button>
                <button type="button" id="cancelarBtnModal" class="btn btn-secondary px-4">
                  <i class="fas fa-times me-2"></i>Cancelar
                </button>
              </div>
              <a href="cambiar_contrasena.php" class="btn btn-warning px-4">
                <i class="fas fa-lock me-2"></i>Cambiar Contraseña
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gray-800 mt-16 py-6 px-4 text-center text-gray-400 text-sm">
    © <?= date('Y') ?> SQLCloud. Todos los derechos reservados.
  </footer>

  <!-- Scripts JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const root = document.documentElement;
      const iconTheme = document.getElementById('iconTheme');
      const storedTheme = localStorage.getItem('theme') || 'light';
      if (storedTheme === 'dark') {
        root.classList.add('dark');
        iconTheme.classList.replace('fa-moon', 'fa-sun');
      } else {
        root.classList.remove('dark');
        iconTheme.classList.replace('fa-sun', 'fa-moon');
      }
      <?php if ($tieneAlerta): ?>
        new bootstrap.Modal(document.getElementById('modalPerfil')).show();
      <?php endif; ?>
    });

    document.getElementById('toggleTheme').addEventListener('click', () => {
      const root = document.documentElement;
      const iconTheme = document.getElementById('iconTheme');
      if (root.classList.contains('dark')) {
        root.classList.remove('dark');
        iconTheme.classList.replace('fa-sun', 'fa-moon');
        localStorage.setItem('theme', 'light');
      } else {
        root.classList.add('dark');
        iconTheme.classList.replace('fa-moon', 'fa-sun');
        localStorage.setItem('theme', 'dark');
      }
    });
  </script>

  <!-- Lógica de edición de perfil -->
  <script>
    const modalPerfil = new bootstrap.Modal(document.getElementById('modalPerfil'));
    const btnPerfil = document.getElementById('btnPerfil');
    btnPerfil?.addEventListener('click', () => modalPerfil.show());

    const editarBtnModal = document.getElementById('editarBtnModal');
    const cancelarBtnModal = document.getElementById('cancelarBtnModal');
    const accionesEdicionModal = document.getElementById('accionesEdicionModal');
    const inputsModal = document.querySelectorAll('#perfilFormModal input:not([type="hidden"])');
    let valoresOriginalesModal = {};

    inputsModal.forEach(inputElem => {
      valoresOriginalesModal[inputElem.id] = inputElem.value;
      inputElem.classList.add('campo-inactivo');
      inputElem.readOnly = true;
    });

    editarBtnModal?.addEventListener('click', () => {
      inputsModal.forEach(inputElem => {
        inputElem.readOnly = false;
        inputElem.classList.replace('campo-inactivo', 'campo-activo');
      });
      editarBtnModal.classList.add('d-none');
      accionesEdicionModal.classList.remove('d-none');
    });

    cancelarBtnModal?.addEventListener('click', () => {
      inputsModal.forEach(inputElem => {
        inputElem.value = valoresOriginalesModal[inputElem.id];
        inputElem.readOnly = true;
        inputElem.classList.replace('campo-activo', 'campo-inactivo');
      });
      accionesEdicionModal.classList.add('d-none');
      editarBtnModal.classList.remove('d-none');
      modalPerfil.hide();
    });

    document.getElementById('perfilFormModal')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const cambios = Array.from(inputsModal).some(inputElem =>
        inputElem.value.trim() !== valoresOriginalesModal[inputElem.id].trim()
      );
      if (!cambios) {
        alert('No se detectaron cambios para guardar.');
        return;
      }
      const modalHtml = `
        <div class="modal fade" id="modalVerificacionPass" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Verificación de Seguridad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="password_actual" class="form-label">Contraseña actual</label>
                  <input type="password" class="form-control" id="password_actual" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="confirmarVerificacionPass" class="btn btn-primary">Verificar</button>
              </div>
            </div>
          </div>
        </div>`;
      document.body.insertAdjacentHTML('beforeend', modalHtml);
      const modalPassElem = new bootstrap.Modal(document.getElementById('modalVerificacionPass'));
      modalPassElem.show();

      document.getElementById('confirmarVerificacionPass').addEventListener('click', () => {
        const passwordValue = document.getElementById('password_actual').value.trim();
        if (passwordValue.length < 8) {
          alert('La contraseña debe tener al menos 8 caracteres.');
          return;
        }
        const hiddenPass = document.createElement('input');
        hiddenPass.type = 'hidden';
        hiddenPass.name = 'password_actual';
        hiddenPass.value = passwordValue;
        document.getElementById('perfilFormModal').appendChild(hiddenPass);
        modalPassElem.hide();
        modalPerfil.hide();
        document.getElementById('perfilFormModal').submit();
      });

      document.getElementById('modalVerificacionPass').addEventListener('hidden.bs.modal', () => {
        document.getElementById('modalVerificacionPass').remove();
      });
    });
  </script>
</body>
</html>
