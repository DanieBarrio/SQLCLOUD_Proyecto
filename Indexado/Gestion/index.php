<?php
// -------------------------------------
// index.php (versión corregida 27 de mayo de 2025)
// -------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Incluir la conexión y las funciones
require 'conexion.php';
require 'funciones.php';

// 1) Si no hay usuario en sesión, redirige a login
if (!isset($_SESSION['user'])) {
    header('Location: logister.php');
    exit;
}

// 2) Recargar datos del usuario desde la base de datos para mostrarlos en el modal
$connUser = conectar();
$stmt = $connUser->prepare("SELECT NOMBRE, CORREO, PLAN, FECHA_EXPIRACION FROM usuarios WHERE CORREO = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    $_SESSION['NOMBRE_COMPLETO']   = $fila['NOMBRE'];
    $_SESSION['CORREO']            = $fila['CORREO'];
    $_SESSION['PLAN']              = $fila['PLAN'];
    $_SESSION['FECHA_EXPIRACION']  = $fila['FECHA_EXPIRACION'];
}

// Mostrar fecha de expiración (si aplica)
if (!empty($_SESSION['FECHA_EXPIRACION'])) {
    $fecha_exp = DateTime::createFromFormat('Y-m-d', $_SESSION['FECHA_EXPIRACION']);
    $_SESSION['FECHA_EXPIRACION_FORMATO'] = $fecha_exp ? $fecha_exp->format('d/m/Y') : 'No disponible';
}

// 3) Generar token CSRF si aún no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Guardamos si hay alerta para el modal
$tieneAlerta = isset($_SESSION['error']) || isset($_SESSION['exito']);

// -------------------------------------
// Procesamiento de actualización de perfil
// -------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_perfil'])) {
    $conn2 = conectar();

    // 1. Verificar CSRF y regenerar token
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    if (!VerificarToken($tokenRecibido, $_SESSION['user'], $conn2)) {
        session_unset();
        session_destroy();
        header('Location: logister.php');
        exit;
    }
    unset($_SESSION['csrf_token']);

    // 2. Obtener y sanitizar datos del formulario
    $correo_original  = $_SESSION['user'];
    $nuevo_nombre     = trim(filter_input(INPUT_POST, 'nombreCompleto', FILTER_SANITIZE_SPECIAL_CHARS));
    $nuevo_correo     = trim(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
    $password_actual  = trim(filter_input(INPUT_POST, 'password_actual', FILTER_SANITIZE_SPECIAL_CHARS));

    // 3. Verificar contraseña actual
    $stmt = $conn2->prepare("SELECT CONTRASENA FROM usuarios WHERE CORREO = ?");
    $stmt->bind_param("s", $correo_original);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $filaUsuario = $resultado->fetch_assoc();
        if (!password_verify($password_actual, $filaUsuario['CONTRASENA'])) {
            $_SESSION['error'] = "Contraseña actual incorrecta";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Usuario no encontrado";
        header("Location: index.php");
        exit;
    }

    // 4. Verificar si el nuevo correo ya existe (si cambió)
    if ($nuevo_correo !== $correo_original) {
        $stmt2 = $conn2->prepare("SELECT CORREO FROM usuarios WHERE CORREO = ?");
        $stmt2->bind_param("s", $nuevo_correo);
        $stmt2->execute();
        if ($stmt2->get_result()->num_rows > 0) {
            $_SESSION['error'] = "El correo ya está en uso";
            header("Location: index.php");
            exit;
        }
    }

    // 5. Actualizar datos en la BD
    $stmt3 = $conn2->prepare("UPDATE usuarios SET NOMBRE = ?, CORREO = ? WHERE CORREO = ?");
    $stmt3->bind_param("sss", $nuevo_nombre, $nuevo_correo, $correo_original);

    if ($stmt3->execute()) {
        // Refrescar correo en sesión
        $_SESSION['user'] = $nuevo_correo;

        // Recargar datos desde la base de datos
        $stmt = $conn2->prepare("SELECT NOMBRE, CORREO, PLAN, FECHA_EXPIRACION FROM usuarios WHERE CORREO = ?");
        $stmt->bind_param("s", $_SESSION['user']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows === 1) {
            $fila = $resultado->fetch_assoc();
            $_SESSION['NOMBRE_COMPLETO']   = $fila['NOMBRE'];
            $_SESSION['CORREO']            = $fila['CORREO'];
            $_SESSION['PLAN']              = $fila['PLAN'];
            $_SESSION['FECHA_EXPIRACION']  = $fila['FECHA_EXPIRACION'];
            $fecha_exp = DateTime::createFromFormat('Y-m-d', $_SESSION['FECHA_EXPIRACION']);
            $_SESSION['FECHA_EXPIRACION_FORMATO'] = $fecha_exp ? $fecha_exp->format('d/m/Y') : 'No disponible';
        }

        $_SESSION['exito'] = "Datos actualizados correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar los datos";
    }

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es" class="<?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark' : ''; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SQLCloud - Panel</title>

  <!-- Tailwind CSS (para dark-mode) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Bootstrap y FontAwesome (modal y iconos) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <style>
    /* =======================
       ESTILOS LIGHT y DARK
       ======================= */
    /* MODO CLARO (predeterminado) */
    body {
      background-color: #ffffff;
      color: #000000;
      transition: background-color 0.3s, color 0.3s;
    }
    /* MODO OSCURO */
    html.dark body {
      background-color: #111827;
      color: #ffffff;
    }

    /* Ajustes específicos al modo oscuro */
    html.dark .bg-gray-800 {
      background-color: #1f2937 !important;
    }
    html.dark .text-white {
      color: #ffffff !important;
    }
    html.dark .btn-dark-toggle {
      background-color: #374151;
      color: white;
    }
    html.dark .card {
      background-color: #1f2937;
      color: #ffffff;
    }
    html.dark .modal-content {
      background-color: #1f2937;
      color: #ffffff;
    }

    /* Campos inactivos vs activos en el modal */
    .campo-inactivo {
      background-color: #e9ecef;
      cursor: not-allowed;
    }
    .campo-activo {
      background-color: #ffffff;
    }
  </style>
</head>
<body>
  <!-- ============================
       Navbar principal
       =========================== -->
  <header class="bg-gray-800 text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <!-- Logo sencillo -->
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
        <!-- Botón Dark/Light Mode -->
        <button id="toggleTheme" class="p-2 rounded-full hover:bg-gray-700 btn-dark-toggle">
          <i id="iconTheme" class="fas fa-moon text-lg"></i>
        </button>

        <!-- Icono de perfil (abre modal) -->
        <button id="btnPerfil" class="relative">
          <i class="fas fa-user-circle text-2xl hover:text-blue-400 transition"></i>
        </button>

        <!-- Botón Cerrar Sesión -->
        <form method="POST" action="logout.php">
          <button type="submit" class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded-md transition">
            Cerrar sesión
          </button>
        </form>
      </div>
    </div>
  </header>

  <!-- ============================
       Sección: Dashboard principal
       =========================== -->
  <section id="dashboard" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <?php
      // Simulación de datos de sistema
      $cpu = rand(20, 85);
      $ram = rand(30, 75);
      $disk = rand(10, 60);
      $plan = "Gratuito";
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
        <a href="tarjeta.php" title="Mejorar a Premium"
           class="text-blue-400 hover:text-blue-300 text-2xl transition duration-200">
          <i class="fas fa-gem"></i>
        </a>
      <?php else: ?>
        <span class="text-green-400 text-lg" title="Plan Premium Activo">
          <i class="fas fa-check-circle"></i>
        </span>
      <?php endif; ?>
    </div>

    <p><strong>Plan:</strong> <?= $plan === 'gratuito' ? 'Gratuito' : 'Premium' ?></p>
    <p><strong>Bases de datos:</strong> 2 activas, 0 pausadas</p>
    <p><strong>Último backup:</strong> 2025-04-03</p>
    <button class="mt-4 bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition">
      Generar Backup
    </button>
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

  <!-- ============================
       Sección: Terminal SQL
       =========================== -->
  <section id="terminal" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-black text-white p-6 rounded-lg shadow-lg">
      <h2 class="text-xl font-semibold mb-4">Terminal SQL</h2>
      <form id="terminalForm" class="mb-4">
        <input type="text" id="terminalInput"
               placeholder="Escribe tu consulta SQL..."
               class="w-full bg-gray-900 border border-gray-700 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
      </form>
      <pre id="terminalOutput"
           class="bg-gray-900 p-4 rounded text-sm overflow-x-auto whitespace-pre-wrap h-64">$ </pre>
    </div>
  </section>

  <!-- ============================
       Modal: Perfil de Usuario (minimalista)
       =========================== -->
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
    <!-- Token CSRF -->
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

  <!-- ============================
       Footer general
       =========================== -->
  <footer class="bg-gray-800 mt-16 py-6 px-4 text-center text-gray-400 text-sm">
    © <?= date('Y') ?> SQLCloud. Todos los derechos reservados.
  </footer>

  <!-- ============================
       Scripts JS
       =========================== -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // 1) Aplicar tema guardado en localStorage, afectando al <html> y <body>
    document.addEventListener("DOMContentLoaded", function () {
      const root     = document.documentElement;
      const body     = document.body;
      const iconTheme = document.getElementById('iconTheme');
      const storedTheme = localStorage.getItem('theme') || 'light';

      if (storedTheme === 'dark') {
        root.classList.add('dark');
        // Aseguramos que el body también reciba clase para estilos adicionales si hace falta
        // (nuestro CSS ya chequea html.dark body { … })
        iconTheme.classList.replace('fa-moon', 'fa-sun');
      } else {
        root.classList.remove('dark');
        iconTheme.classList.replace('fa-sun', 'fa-moon');
      }

      // 2) Si había alerta (error ó éxito), abrimos el modal de perfil automáticamente
      <?php if ($tieneAlerta): ?>
        new bootstrap.Modal(document.getElementById('modalPerfil')).show();
      <?php endif; ?>
    });

    // 3) Toggle modo claro/oscuro
    document.getElementById('toggleTheme').addEventListener('click', () => {
      const root     = document.documentElement;
      const iconTheme = document.getElementById('iconTheme');

      if (root.classList.contains('dark')) {
        // Pasamos a modo claro
        root.classList.remove('dark');
        iconTheme.classList.replace('fa-sun', 'fa-moon');
        localStorage.setItem('theme', 'light');
      } else {
        // Pasamos a modo oscuro
        root.classList.add('dark');
        iconTheme.classList.replace('fa-moon', 'fa-sun');
        localStorage.setItem('theme', 'dark');
      }
    });
  </script>

  <script>
    // --------------------------------
    // Lógica de Terminal SQL (mock)
    // --------------------------------
    const terminalForm = document.getElementById('terminalForm');
    const terminalInput = document.getElementById('terminalInput');
    const terminalOutput = document.getElementById('terminalOutput');

    terminalForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const query = terminalInput.value.trim();
      if (!query) return;
      const mockResult = { id: 1, nombre: "Juan Pérez", correo: "juan@example.com" };
      const outputLine = `sqlcloud@db-terminal:~$ ${query}\nResultado:\n${JSON.stringify(mockResult, null, 2)}\n`;
      terminalOutput.textContent += outputLine;
      terminalInput.value = '';
    });

    // --------------------------------
    // Modal y lógica edición de perfil
    // --------------------------------
    const modalPerfil = new bootstrap.Modal(document.getElementById('modalPerfil'));
    const btnPerfil  = document.getElementById('btnPerfil');

    btnPerfil.addEventListener('click', () => {
      modalPerfil.show();
    });

    // Botones dentro del modal
    const editarBtnModal       = document.getElementById('editarBtnModal');
    const cancelarBtnModal     = document.getElementById('cancelarBtnModal');
    const accionesEdicionModal = document.getElementById('accionesEdicionModal');
    const inputsModal          = document.querySelectorAll('#perfilFormModal input:not([type="hidden"])');
    let valoresOriginalesModal = {};

    // Guardar valores originales y marcar como inactivos
    inputsModal.forEach(inputElem => {
      valoresOriginalesModal[inputElem.id] = inputElem.value;
      inputElem.classList.add('campo-inactivo');
      inputElem.readOnly = true;
    });

    editarBtnModal.addEventListener('click', () => {
      inputsModal.forEach(inputElem => {
        inputElem.readOnly = false;
        inputElem.classList.replace('campo-inactivo', 'campo-activo');
      });
      editarBtnModal.classList.add('d-none');
      accionesEdicionModal.classList.remove('d-none');
    });

    cancelarBtnModal.addEventListener('click', () => {
      inputsModal.forEach(inputElem => {
        inputElem.value = valoresOriginalesModal[inputElem.id];
        inputElem.readOnly = true;
        inputElem.classList.replace('campo-activo', 'campo-inactivo');
      });
      accionesEdicionModal.classList.add('d-none');
      editarBtnModal.classList.remove('d-none');
      // Eliminar posible campo oculto de contraseña
      const existingHidden = document.querySelector('input[name="password_actual"]');
      if (existingHidden) existingHidden.remove();
      modalPerfil.hide();
    });

    document.getElementById('perfilFormModal').addEventListener('submit', (e) => {
      e.preventDefault();
      // Verificar si hay cambios
      const cambios = Array.from(inputsModal).some(inputElem =>
        inputElem.value.trim() !== valoresOriginalesModal[inputElem.id].trim()
      );
      if (!cambios) {
        alert('No se detectaron cambios para guardar.');
        return;
      }
      // Mostrar modal de confirmación de contraseña
      const modalHtml = `
        <div class="modal fade" id="modalVerificacionPass" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Verificación de seguridad</h5>
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
        // Inyectar contraseña en el form de perfil
        const hiddenPass = document.createElement('input');
        hiddenPass.type = 'hidden';
        hiddenPass.name = 'password_actual';
        hiddenPass.value = passwordValue;
        document.getElementById('perfilFormModal').appendChild(hiddenPass);

        modalPassElem.hide();
        modalPerfil.hide();
        document.getElementById('perfilFormModal').submit();
      });

      // Al ocultar el modal de verificación, elimino su HTML
      document.getElementById('modalVerificacionPass').addEventListener('hidden.bs.modal', () => {
        document.getElementById('modalVerificacionPass').remove();
      });
    });
  </script>
</body>
</html>
