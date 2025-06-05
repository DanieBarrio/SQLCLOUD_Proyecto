<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'funciones.php';
require_once 'conexion.php';

// Verificamos inactividad y CSRF
if (isset($_SESSION["ultimo_acceso"])) {
    $inactividad = 3600; 
    if (time() - $_SESSION["ultimo_acceso"] > $inactividad) {
        session_destroy();
        header("Location: logister.php");
        exit;
    }
}
$_SESSION["ultimo_acceso"] = time();
sinlogin();

// Procesamos actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_perfil'])) {
    $error = null;
    $conn = conectar();
    
    // Obtenemos y sanitizamos datos del formulario
    $usuario_original = $_SESSION['USUARIO'];
    $nuevo_usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS);
    $nuevo_nombre = filter_input(INPUT_POST, 'nombreCompleto', FILTER_SANITIZE_SPECIAL_CHARS);
    $nuevo_correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
    $password_actual = filter_input(INPUT_POST, 'password_actual', FILTER_SANITIZE_SPECIAL_CHARS);

    // Aplicar trim()
    $nuevo_usuario = trim($nuevo_usuario);
    $nuevo_nombre = trim($nuevo_nombre);
    $nuevo_correo = trim($nuevo_correo);
    $password_actual = trim($password_actual);

    // 1. Verificamos contraseña actual
    $stmt = $conn->prepare("SELECT CONTRASENA FROM usuarios WHERE USUARIO = ?");
    $stmt->bind_param("s", $usuario_original);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        if (!password_verify($password_actual, $usuario['CONTRASENA'])) {
            $_SESSION['error'] = "Contraseña actual incorrecta";
            header("Location: perfil.php");
            exit;
        }
    }

    // 2. Verificamos si el nuevo usuario ya existe
    if ($nuevo_usuario !== $usuario_original) {
        $stmt = $conn->prepare("SELECT USUARIO FROM usuarios WHERE USUARIO = ?");
        $stmt->bind_param("s", $nuevo_usuario);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = "El nombre de usuario ya está en uso";
            header("Location: perfil.php");
            exit;
        }
    }

    // 3. Actualizamos datos en la base de datos
    $stmt = $conn->prepare("UPDATE usuarios SET USUARIO = ?, NOMBRE = ?, CORREO = ? WHERE USUARIO = ?");
    $stmt->bind_param("ssss", $nuevo_usuario, $nuevo_nombre, $nuevo_correo, $usuario_original);
    
    if ($stmt->execute()) {
        // Actualizar datos en sesión (sanitizados previamente)
        $_SESSION['USUARIO'] = $nuevo_usuario;
        $_SESSION['NOMBRE_COMPLETO'] = $nuevo_nombre;
        $_SESSION['CORREO'] = $nuevo_correo;
        $_SESSION['exito'] = "Datos actualizados correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar los datos";
    }
    
    header("Location: perfil.php");
    
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .dark-mode .navbar {
            background-color: #2d2d2d !important;
        }
        .dark-mode .dropdown-menu {
            background-color: #333 !important;
        }
        .dark-mode .dropdown-item {
            color: #e0e0e0 !important;
        }
        .dark-mode .dropdown-item:hover {
            background-color: #444 !important;
        }
        .modal-content {
            background-color: var(--bs-modal-bg);
            color: var(--bs-modal-color);
        }
        
        .modal-header {
            border-bottom: var(--bs-modal-header-border-width) solid var(--bs-modal-header-border-color);
        }
        
        .modal-footer {
            border-top: var(--bs-modal-footer-border-width) solid var(--bs-modal-footer-border-color);
        }
        .dark-mode .modal-content {
            background-color: #2d2d2d;
            color: #ffffff;
        }
        
        .dark-mode .modal-header,
        .dark-mode .modal-footer {
            border-color: #404040;
        }
        .campo-inactivo {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light-mode'; ?>">
    <script>
        // Aplicar tema desde localStorage si existe
        const savedTheme = localStorage.getItem('theme') || 'light-mode';
        document.body.classList.add(savedTheme);
        if (savedTheme === 'dark-mode') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>

    <div class="modal fade" id="modalVerificacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verificación de seguridad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formVerificacion">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="password_actual" class="form-label">Contraseña actual</label>
                            <input type="password" class="form-control" id="password_actual" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="confirmarVerificacion" class="btn btn-primary">Verificar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">SQLCloud</a>
            <div class="ms-auto">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bars"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="light-mode-item">
                            <a class="dropdown-item" href="#" onclick="toggleTheme('light-mode')">
                                <i class="fas fa-sun me-2"></i>Modo Claro
                            </a>
                        </li>
                        <li id="dark-mode-item">
                            <a class="dropdown-item" href="#" onclick="toggleTheme('dark-mode')">
                                <i class="fas fa-moon me-2"></i>Modo Oscuro
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="w-50 mx-auto p-4 border rounded shadow-sm">
            <h3 class="text-center mb-4">Perfil de Usuario</h3>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mb-3"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['exito'])): ?>
                <div class="alert alert-success mb-3"><?= $_SESSION['exito']; unset($_SESSION['exito']); ?></div>
            <?php endif; ?>

            <form method="POST" id="perfilForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="editar_perfil" value="1">
                
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control campo-inactivo" name="usuario" id="usuario" 
                           value="<?= htmlspecialchars($_SESSION['USUARIO'] ?? '') ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label for="nombreCompleto" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control campo-inactivo" name="nombreCompleto" id="nombreCompleto" 
                           value="<?= htmlspecialchars($_SESSION['NOMBRE_COMPLETO'] ?? '') ?>" readonly>
                </div>
                
                <div class="mb-4">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control campo-inactivo" name="correo" id="correo" 
                           value="<?= htmlspecialchars($_SESSION['CORREO'] ?? '') ?>" readonly>
                </div>

                <div class="botones-container mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" id="editarBtn" class="btn btn-primary px-4">
                            <i class="fas fa-edit me-2"></i>Editar
                        </button>
                        
                        <div class="d-none gap-2" id="accionesEdicion">
                            <button type="submit" id="enviarBtn" class="btn btn-success px-4">
                                <i class="fas fa-save me-2"></i>Guardar
                            </button>
                            <button type="button" id="cancelarBtn" class="btn btn-secondary px-4">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                        </div>
                        
                        <a href="recuperar.php" class="btn btn-warning px-4">
                            <i class="fas fa-lock me-2"></i>Cambiar Contraseña
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Tema y estilos
    function toggleTheme(theme) {
        document.body.className = theme;
        localStorage.setItem('theme', theme);
        
        // Actualizar tema del modal
        const modal = document.querySelector('.modal-content');
        if (modal) {
            modal.classList.toggle('dark-mode', theme === 'dark-mode');
        }

        // Actualizar visibilidad de opciones
        document.getElementById('light-mode-item').style.display = 
            theme === 'light-mode' ? 'none' : '';
        document.getElementById('dark-mode-item').style.display = 
            theme === 'dark-mode' ? 'none' : '';
    }

    // Inicialización del tema
    window.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme') || 'light-mode';
        document.body.className = savedTheme;
        toggleTheme(savedTheme);
    });

    // Gestión del formulario
    const editarBtn = document.getElementById('editarBtn');
    const cancelarBtn = document.getElementById('cancelarBtn');
    const accionesEdicion = document.getElementById('accionesEdicion');
    const inputs = document.querySelectorAll('#perfilForm input:not([type="hidden"])');
    const modalVerificacion = new bootstrap.Modal('#modalVerificacion');
    let submitting = false;

    // Valores originales y estado inicial
    const valoresOriginales = {};
    inputs.forEach(input => {
        valoresOriginales[input.id] = input.value;
        input.classList.add('campo-inactivo'); // Estado inicial gris
    });

    // Eventos
    editarBtn.addEventListener('click', () => {
        inputs.forEach(input => {
            input.readOnly = false;
            input.classList.replace('campo-inactivo', 'campo-activo');
        });
        editarBtn.classList.add('d-none');
        accionesEdicion.classList.remove('d-none');
    });

    cancelarBtn.addEventListener('click', () => {
        inputs.forEach(input => {
            input.value = valoresOriginales[input.id];
            input.readOnly = true;
            input.classList.replace('campo-activo', 'campo-inactivo');
        });
        accionesEdicion.classList.add('d-none');
        editarBtn.classList.remove('d-none');
        modalVerificacion.hide();
        
        // Limpiar posibles campos ocultos previos
        const existingHidden = document.querySelector('input[name="password_actual"]');
        if (existingHidden) existingHidden.remove();
    });

    // Validación de envío
    document.getElementById('perfilForm').addEventListener('submit', (e) => {
        if (!submitting) {
            e.preventDefault();
            
            // Detección de cambios reales
            const cambios = Array.from(inputs).some(input => 
                input.value.trim() !== valoresOriginales[input.id].trim()
            );

            if (!cambios) {
                alert('No se detectaron cambios para guardar.');
                submitting = false;
                return; // Salir inmediatamente
            }
            
            modalVerificacion.show();
        }
    });

    // Verificación final
    document.getElementById('confirmarVerificacion').addEventListener('click', () => {
        const passwordInput = document.getElementById('password_actual');
        const password = passwordInput.value.trim();
        
        // Validación básica en cliente
        if (password.length < 8) {
            alert('La contraseña debe tener al menos 8 caracteres');
            passwordInput.focus();
            return;
        }

        // Sanitización avanzada
        inputs.forEach(input => {
            if (input.name === 'correo') {
                input.value = input.value.replace(/[^\w@.-]/gi, '');
            } else {
                input.value = input.value.replace(/[^\p{L}\s]/gu, '');
            }
        });

        // Inyectar contraseña
        const hiddenPassword = document.createElement('input');
        hiddenPassword.type = 'hidden';
        hiddenPassword.name = 'password_actual';
        hiddenPassword.value = password;
        document.getElementById('perfilForm').appendChild(hiddenPassword);

        submitting = true;
        document.getElementById('perfilForm').submit();
    });
</script>
</body>
</html>
