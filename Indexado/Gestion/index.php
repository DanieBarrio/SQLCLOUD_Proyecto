<?php
session_start();

if (!isset($_SESSION['user'])) {
    session_unset();
    session_destroy();
    header('Location: logister.php');
    exit;
}

require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    if ($tokenRecibido == "" || $tokenRecibido == $_SESSION['csrf_token']){
	session_unset();
        session_destroy();
        header('Location: logister.php');
   	exit;
    }
}
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));



$connUser = conectar();

// 2. Recargar datos del usuario
$stmt = $connUser->prepare("SELECT u.ID, u.NOMBRE, u.CORREO, u.ROL, p.T_PLAN AS PLAN FROM usuarios u JOIN plan p ON u.ID = p.ID WHERE u.CORREO = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    $_SESSION['ID'] = $fila['ID'];
    $userId = $_SESSION['ID'];

    $_SESSION['NOMBRE_COMPLETO'] = $fila['NOMBRE'];
    $_SESSION['CORREO'] = $fila['CORREO'];
    $_SESSION['PLAN'] = $fila['PLAN'];
    $_SESSION['ROL'] = $fila['ROL'];
    $userRol = $_SESSION['rol'];

} else {
    session_unset();
    session_destroy();
    header('Location: logister.php');
    exit;
}



$databases = [];

$stmtDb = $connUser->prepare("SELECT b.NOMBRE_BD FROM usuario_base_datos ub JOIN base_datos b ON ub.ID_BD = b.ID_BD WHERE ub.ID_USUARIO = ?");
$stmtDb->bind_param("i", $userId);
$stmtDb->execute();
$resultDb = $stmtDb->get_result();


while ($row = $resultDb->fetch_assoc()) {
    $databases[] = $row['NOMBRE_BD'];
}

// 7. Validar cuota de bases de datos según el plan
$numBd = count($databases);
$plan = $_SESSION['PLAN'];
$puedeCrear = false;

if ($plan === 'gratuito' && $numBd < 1) {
    $puedeCrear = true;
} elseif ($plan === 'premium' && $numBd < 3) {
    $puedeCrear = true;
}



// Función para obtener tamaño de base de datos
function getDatabaseSize($conn, $dbName) {
    $query = "SELECT TABLE_SCHEMA AS 'Base de Datos', 
              ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'Tamaño (MB)'
              FROM information_schema.tables
              WHERE TABLE_SCHEMA = '$dbName'
              GROUP BY TABLE_SCHEMA";
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return floatval($row['Tamaño (MB)']);
    }
    return 0;
}

// Limites por plan
$limitesPorPlan = [
    'gratuito' => 10,    // 10 MB
    'premium' => 100    // 100 MB
];

// Obtener tamaño de cada base de datos
$databasesConTamaño = [];
foreach ($databases as $db) {
    $tamaño = getDatabaseSize($connUser, $db);
    $databasesConTamaño[] = [
        'nombre' => $db,
        'tamaño' => $tamaño,
        'limite' => $limitesPorPlan[$plan],
        'excedido' => $tamaño >= $limitesPorPlan[$plan]
    ];
}
?>
<!DOCTYPE html>
<html lang="es" class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SQLCloud - Panel</title>
  <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>  
  <!-- Bootstrap y FontAwesome -->
  <link rel="stylesheet" href="Estilos/index.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"  rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"  rel="stylesheet">
  <style>
    /* Estilos personalizados */
    .campo-inactivo {
      background-color: #2d3748;
      color: #cbd5e0;
      border: 1px solid #4a5568;
    }
    .campo-activo {
      background-color: #1a202c;
      color: white;
      border: 1px solid #4fd1c5;
    }
    .btn-dark-toggle:hover {
      background-color: #4a5568;
    }
    /* Animación para el banner de cookies */
    @keyframes slideDown {
      from { transform: translateY(100%); }
      to { transform: translateY(0); }
    }
    #cookieConsent {
      animation: slideDown 0.5s ease-out forwards;
      transition: transform 0.3s ease-in-out;
    }
    #cookieConsent.hidden {
      transform: translateY(100%);
    }
    html, body {
      height: 100%;
      margin: 0;
      display: flex;
      flex-direction: column;
    }
    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
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
        <p class="hover:text-blue-300 transition">¡Bienvenido a tu Panel!</p>
      </nav>
      <div class="flex items-center space-x-4">

	<!-- Nuevo botón de ayuda -->
	<a href="contacto.php" title="Ayuda y Soporte" class="p-2 text-gray-300 hover:text-blue-400 transition-colors duration-200">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m0 8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h14a2 2 0 012 2v8z" />
  </svg>
	</a>

        <!-- Botón de tema claro/oscuro -->
        <button id="toggleTheme" class="p-2 rounded-full hover:bg-gray-700 btn-dark-toggle">
          <i id="iconTheme" class="fas fa-moon text-lg"></i>
        </button>
        <!-- Botón de perfil -->
        <button class="relative" data-bs-toggle="modal" data-bs-target="#modalPerfil">
          <i class="fas fa-user-circle text-2xl hover:text-blue-400 transition"></i>
        </button>
        <!-- Botón de administrador -->
        <?php if (isset($_SESSION['ROL']) && in_array($_SESSION['ROL'], ['admin', 'superadmin'])): ?>
          <a href="superadmin.php" title="Panel Admin" class="text-yellow-400 hover:text-yellow-300 transition text-2xl" aria-label="Ir al panel de administración">
            <i class="fas fa-shield-alt"></i>
          </a>
        <?php endif; ?>
        <!-- Botón de cierre de sesión -->
        <form method="POST" action="logout.php">
          <button type="submit" class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded-md transition">Cerrar sesión</button>
        </form>
      </div>
    </div>
  </header>

  <main>
    <!-- Sección: Dashboard principal -->
    <section id="dashboard" class="w-full max-w-[1440px] mx-auto px-10 py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg relative">
          <div class="flex justify-between items-start">
            <h2 class="text-xl font-semibold mb-4">Estado del Servicio</h2>
            <!-- Botón de mejora a Premium -->
            <?php
              $plan = $_SESSION['PLAN'];
              if ($plan !== 'premium'):
            ?>
              <a href="tarjeta.php" title="Mejorar a Premium" class="text-blue-400 hover:text-blue-300 transition text-2xl">
                <i class="fas fa-gem"></i>
              </a>
            <?php else: ?>
              <span class="text-green-400 text-lg" title="Plan Premium Activo">
                <i class="fas fa-check-circle"></i>
              </span>
            <?php endif; ?>
          </div>
          <p><strong>Plan:</strong> <?=  $plan === 'gratuito' ? 'Gratuito' : 'Premium' ?></p>
          <p><strong>Bases de datos:</strong> <?= count($databases) ?> activas</p>
        </div>

        <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg">
          <h2 class="text-xl font-semibold mb-4">Uso de Almacenamiento</h2>
          <?php foreach ($databasesConTamaño as $db): ?>
            <div class="mb-6">
              <div class="flex justify-between mb-2">
                <span><?= htmlspecialchars($db['nombre']) ?></span>
                <span class="<?= $db['excedido'] ? 'text-red-500' : 'text-white' ?>">
                  <?= $db['tamaño'] ?> MB / <?= $db['limite'] ?> MB
                </span>
              </div>
              <div class="w-full h-2 bg-gray-700 rounded">
                <div class="h-2 rounded <?= $db['excedido'] ? 'bg-red-500' : 'bg-green-500' ?>"
                     style="width:<?= min(($db['tamaño'] / $db['limite']) * 100, 100) ?>%">
                </div>
              </div>
              <?php if ($db['excedido']): ?>
                <p class="text-red-500 mt-2 text-sm">
                  ⚠️ Has excedido el límite de <?= $db['limite'] ?> MB para esta base de datos
                </p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (empty($databasesConTamaño)): ?>
            <p class="text-gray-400">No tienes bases de datos creadas</p>
          <?php endif; ?>
        </div>

        <!-- Gestión de Bases de Datos -->
        <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg mt-6">
          <h2 class="text-xl font-semibold mb-4">Gestión de Bases de Datos</h2>
          <?php if ($puedeCrear): ?>
            <button class="mt-2 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition" onclick="window.location.href='crear_bd.php'">
              Crear Nueva Base de Datos
            </button>
          <?php endif; ?>
          <!-- Dropdown de bases de datos -->
          <?php if (!empty($databases)): ?>
            <div class="dropdown mt-2">
              <button class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                Entrar en BD
              </button>
              <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <?php foreach ($databases as $db): ?>
                  <li>
                    <a class="dropdown-item" href="dashboard.php?bd=<?= urlencode($db) ?>">
                      <?= htmlspecialchars($db) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>

        <!-- Mis Backups -->

        <div class="bg-gray-800 text-white p-6 rounded-lg shadow-lg mt-6">

          <h2 class="text-xl font-semibold mb-4">Mis Backups</h2>
          <a href="lista_backups.php" class="inline-block bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition mb-4 flex items-center">
            <i class="fas fa-download me-2"></i> Ver todos mis backups
          </a>
<?php
            // Obtener el nombre del usuario logueado
            $user = $_SESSION['user'];
                          
            // Definir la ruta donde se almacenan los backups del usuario
            $dir = "/var/backups/sqlcloud/" . $user;
                          
            // Verificar si la carpeta existe
            if (is_dir($dir)) {
                // Listar todos los archivos en la carpeta
                $allFiles = scandir($dir);
            
                // Filtrar solo los archivos que terminan en ".sql.gz"
                $filteredFiles = [];
                foreach ($allFiles as $file) {
                    if (preg_match('/\.sql\.gz$/', $file)) {
                        $filteredFiles[] = $file;
                    }
                }
              
                // Reordenar para tener los más recientes primero
                $reversedFiles = array_reverse($filteredFiles);
              
                // Tomar solo los 3 últimos backups
                $latestBackups = array_slice($reversedFiles, 0, 3);
            } else {
                // Si no existe la carpeta, no hay backups
                $latestBackups = [];
            }
            ?>
            
            <!-- Mostrar contenido según si hay backups o no -->
            <?php if (empty($latestBackups)): ?>
              <p>No tienes backups disponibles.</p>
            <?php else: ?>
              <ul class="space-y-2">
                <?php foreach ($latestBackups as $file): ?>
                  <li class="flex justify-between items-center border-b border-gray-700 pb-2">
                    <!-- Mostrar nombre del archivo -->
                    <span><?= htmlspecialchars($file) ?></span>
                
                    <!-- Enlace para descargar -->
                    <a href="descargar_backup.php?file=<?= urlencode($file) ?>" class="text-blue-400 hover:text-blue-300 transition">
                      Descargar
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <!-- Modal: Perfil de Usuario -->
  <div class="modal fade" id="modalPerfil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Perfil de Usuario</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="alertasModal"></div>
          <form method="POST" id="perfilFormModal" action="editar_perfil.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="editar_perfil" value="1">
            <div class="mb-3">
              <label for="nombreModal" class="form-label">Nombre completo</label>
              <input type="text" class="form-control campo-inactivo" name="nombreCompleto" id="nombreModal" value="<?= htmlspecialchars($_SESSION['NOMBRE_COMPLETO'] ?? '') ?>" readonly>
            </div>
            <div class="mb-3">
              <label for="correoModal" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control campo-inactivo" name="correo" id="correoModal" value="<?= htmlspecialchars($_SESSION['CORREO'] ?? '') ?>" readonly>
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
              <a href="recuperar.php" class="btn btn-warning px-4">
                <i class="fas fa-lock me-2"></i>Cambiar Contraseña
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Banner de Consentimiento de Cookies -->
  <div id="cookieConsent" class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white p-4 shadow-lg z-50 hidden">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center">
      <p class="mb-4 md:mb-0">Utilizamos cookies para mejorar tu experiencia. ¿Aceptas nuestra política de cookies?</p>
      <div class="flex gap-2">
        <button onclick="acceptCookies()" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
          <i class="fas fa-check mr-1"></i> Aceptar
        </button>
        <button onclick="declineCookies()" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
          <i class="fas fa-times mr-1"></i> Rechazar
        </button>
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
    // Aplicar tema guardado
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
      <?php if (isset($_SESSION['error']) || isset($_SESSION['exito'])): ?>
        new bootstrap.Modal(document.getElementById('modalPerfil')).show();
      <?php endif; ?>
    });

    // Toggle de modo claro/oscuro
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

    // Lógica edición de perfil
    let originales = {};
    let esEdicion = false;

    function setupEditarBtn() {
      const editarBtn = document.getElementById('editarBtnModal');
      const cancelarBtn = document.getElementById('cancelarBtnModal');
      const acciones = document.getElementById('accionesEdicionModal');
      const inputs = document.querySelectorAll('#perfilFormModal input:not([type="hidden"])');

      if (!editarBtn || !acciones || !inputs.length) return;

      const nuevoBtn = editarBtn.cloneNode(true);
      editarBtn.replaceWith(nuevoBtn);

      nuevoBtn.addEventListener('click', () => {
        esEdicion = true;
        inputs.forEach(input => {
          input.readOnly = false;
          input.classList.replace('campo-inactivo', 'campo-activo');
        });
        nuevoBtn.classList.add('d-none');
        acciones.classList.remove('d-none');
      });

      if (cancelarBtn) {
        cancelarBtn.onclick = null;
        cancelarBtn.addEventListener('click', () => {
          inputs.forEach(input => {
            input.value = originales[input.id] || '';
            input.readOnly = true;
            input.classList.replace('campo-activo', 'campo-inactivo');
          });
          acciones.classList.add('d-none');
          nuevoBtn.classList.remove('d-none');
          esEdicion = false;
        });
      }
    }

    document.addEventListener("DOMContentLoaded", () => {
      const modalPerfilElem = document.getElementById('modalPerfil');
      const form = document.getElementById('perfilFormModal');
      const alertDiv = document.getElementById('alertasModal');

      if (modalPerfilElem) {
        const modalPerfil = new bootstrap.Modal(modalPerfilElem);

        modalPerfilElem.addEventListener('shown.bs.modal', () => {
          const inputs = document.querySelectorAll('#perfilFormModal input:not([type="hidden"])');
          originales = {};
          inputs.forEach(input => {
            originales[input.id] = input.value.trim();
          });
          setupEditarBtn();
        });

        modalPerfilElem.addEventListener('hidden.bs.modal', () => {
          const inputs = document.querySelectorAll('#perfilFormModal input:not([type="hidden"])');
          inputs.forEach(input => {
            input.readOnly = true;
            input.classList.replace('campo-activo', 'campo-inactivo');
          });
          esEdicion = false;
        });
      }

      if (form && alertDiv) {
        form.addEventListener('submit', e => {
          e.preventDefault();

          if (!esEdicion) {
            alertDiv.innerHTML = `<div class="alert alert-warning">Primero debes habilitar la edición.</div>`;
            return;
          }

          const nombreInput = document.getElementById('nombreModal');
          const correoInput = document.getElementById('correoModal');

          const hayCambios = (
            nombreInput.value.trim() !== originales['nombreModal']?.trim() ||
            correoInput.value.trim() !== originales['correoModal']?.trim()
          );

          if (!hayCambios) {
            alertDiv.innerHTML = `<div class="alert alert-warning">No se detectaron cambios.</div>`;
            return;
          }

          const modalHtml = `
            <div class="modal fade" id="modalVerificacionPass" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Verificación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" class="form-control" id="password_actual_modal" required>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" id="confirmarVerificacionPass">Verificar</button>
                  </div>
                </div>
              </div>
            </div>
          `;
          document.body.insertAdjacentHTML('beforeend', modalHtml);
          const modalPass = new bootstrap.Modal(document.getElementById('modalVerificacionPass'));
          modalPass.show();

          document.getElementById('confirmarVerificacionPass').addEventListener('click', () => {
            const pass = document.getElementById('password_actual_modal').value.trim();
            if (pass.length < 8) {
              alert('Contraseña inválida.');
              return;
            }

            const formData = new FormData(form);
            formData.append('password_actual', pass);

            fetch('editar_perfil.php', {
              method: 'POST',
              body: formData
            })
            .then(r => r.json())
            .then(data => {
              const mensaje = data.mensaje || '';
              const esExito = data.exito === true || data.exito === "true";
              alertDiv.innerHTML = `
                <div class="alert alert-${esExito ? 'success' : 'danger'}" style="background-color: ${esExito ? '#28a745' : '#dc3545'} !important; color: #fff !important;">
                  ${mensaje}
                </div>
              `;
              if (esExito) {
                [nombreInput, correoInput].forEach(input => {
                  input.readOnly = true;
                  input.classList.replace('campo-activo', 'campo-inactivo');
                });
                originales['nombreModal'] = nombreInput.value.trim();
                originales['correoModal'] = correoInput.value.trim();

                const correoAnterior = originales['correoModal'];
                const correoNuevo = correoInput.value.trim();
                if (correoAnterior !== correoNuevo) {
                  const alerta = document.createElement('div');
                  alerta.className = 'alert alert-info mt-3';
                  alerta.style.cssText = 'background-color: #17a2b8 !important; color: #fff !important;';
                  alerta.textContent = 'Has cambiado tu correo electrónico. Serás redirigido al inicio de sesión...';
                  alertDiv.appendChild(alerta);
                  setTimeout(() => window.location.href = 'logister.php', 4000);
                }

                const editarBtn = document.getElementById('editarBtnModal');
                const acciones = document.getElementById('accionesEdicionModal');
                if (editarBtn && acciones) {
                  acciones.classList.add('d-none');
                  editarBtn.classList.remove('d-none');
                }
              }
            })
            .catch(err => {
              console.error('Error:', err);
              alertDiv.innerHTML = `<div class="alert alert-danger">Error: Otro usuario tiene este correo.</div>`;
            });

            modalPass.hide();
            document.getElementById('modalVerificacionPass').addEventListener('hidden.bs.modal', () => {
              document.getElementById('modalVerificacionPass').remove();
            });
          });
        });
      }
    });
  </script>
</body>
</html>
