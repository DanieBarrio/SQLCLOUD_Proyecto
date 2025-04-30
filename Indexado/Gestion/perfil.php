<?php

session_start();
require 'funciones.php';
require 'conexion.php';

// Verificar inactividad y CSRF
if (isset($_SESSION["ultimo_acceso"])) {
    $inactividad = 3600; 
    if (time() - $_SESSION["ultimo_acceso"] > $inactividad) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}
$_SESSION["ultimo_acceso"] = time();

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_perfil'])) {
    $error = null;
    $conn = conectar();
    
    // Obtener datos del formulario
    $usuario_original = $_SESSION['USUARIO'];
    $nuevo_usuario = trim($_POST['usuario']);
    $nuevo_nombre = trim($_POST['nombreCompleto']);
    $nuevo_correo = trim($_POST['correo']);
    $password_actual = $_POST['password_actual'];

    // 1. Verificar contraseña actual
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

    // 2. Verificar si el nuevo usuario ya existe
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

    // 3. Actualizar datos en la base de datos
    $stmt = $conn->prepare("UPDATE usuarios SET USUARIO = ?, NOMBRE = ?, CORREO = ? WHERE USUARIO = ?");
    $stmt->bind_param("ssss", $nuevo_usuario, $nuevo_nombre, $nuevo_correo, $usuario_original);
    
    if ($stmt->execute()) {
        // Actualizar datos en sesión
        $_SESSION['USUARIO'] = $nuevo_usuario;
        $_SESSION['NOMBRE_COMPLETO'] = $nuevo_nombre;
        $_SESSION['CORREO'] = $nuevo_correo;
        $_SESSION['exito'] = "Datos actualizados correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar los datos";
    }
    
    header("Location: perfil.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Estilos para modo oscuro -->
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
    </style>
</head>

<body class="<?php echo isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light-mode'; ?>">
    <!-- Script temprano para aplicar el tema antes de renderizar contenido -->
    <script>
        // Aplicar tema desde localStorage si existe
        const savedTheme = localStorage.getItem('theme') || 'light-mode';
        document.body.classList.add(savedTheme);
        if (savedTheme === 'dark-mode') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>

    <!-- Modal Verificación Contraseña -->
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
            <a class="navbar-brand" href="#">SQLCloud</a>
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

            <form method="POST" id="perfilForm" action="perfil.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="editar_perfil" value="1">
                
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" name="usuario" id="usuario" 
                           value="<?= htmlspecialchars($_SESSION['USUARIO'] ?? '') ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label for="nombreCompleto" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" name="nombreCompleto" id="nombreCompleto" 
                           value="<?= htmlspecialchars($_SESSION['NOMBRE_COMPLETO'] ?? '') ?>" readonly>
                </div>
                
                <div class="mb-4">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" name="correo" id="correo" 
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
                        
                        <a href="cambiar_contrasena.php" class="btn btn-warning px-4">
                            <i class="fas fa-lock me-2"></i>Cambiar Contraseña
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para alternar entre temas
        function toggleTheme(theme) {
            document.body.className = theme;
            localStorage.setItem('theme', theme);

            const lightItem = document.getElementById('light-mode-item');
            const darkItem = document.getElementById('dark-mode-item');

            lightItem.style.display = (theme === 'light-mode') ? 'none' : '';
            darkItem.style.display = (theme === 'dark-mode') ? 'none' : '';
        }

        // Inicializa estado del menú según tema guardado
        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light-mode';

            const lightItem = document.getElementById('light-mode-item');
            const darkItem = document.getElementById('dark-mode-item');

            if (savedTheme === 'light-mode') {
                lightItem.style.display = 'none';
                darkItem.style.display = '';
            } else {
                darkItem.style.display = 'none';
                lightItem.style.display = '';
            }
        });

        // Resto del código existente (manejo de formulario y edición)
        const editarBtn = document.getElementById('editarBtn');
        const enviarBtn = document.getElementById('enviarBtn');
        const cancelarBtn = document.getElementById('cancelarBtn');
        const accionesEdicion = document.getElementById('accionesEdicion');
        const inputs = document.querySelectorAll('#perfilForm input:not([type="hidden"])');
        const modalVerificacion = new bootstrap.Modal('#modalVerificacion');
        let submitting = false;

        const valoresOriginales = {};
        inputs.forEach(input => {
            valoresOriginales[input.id] = input.value;
        });

        editarBtn.addEventListener('click', () => {
            inputs.forEach(input => {
                input.readOnly = false;
                input.classList.remove('bg-light');
            });
            editarBtn.classList.add('d-none');
            accionesEdicion.classList.remove('d-none');
        });

        document.getElementById('perfilForm').addEventListener('submit', (e) => {
            if (!submitting) {
                e.preventDefault();
                modalVerificacion.show();
            }
        });

        document.getElementById('confirmarVerificacion').addEventListener('click', () => {
            const password = document.getElementById('password_actual').value;
            const hiddenPassword = document.createElement('input');
            hiddenPassword.type = 'hidden';
            hiddenPassword.name = 'password_actual';
            hiddenPassword.value = password;
            document.getElementById('perfilForm').appendChild(hiddenPassword);
            
            submitting = true;
            document.getElementById('perfilForm').submit();
        });

        cancelarBtn.addEventListener('click', () => {
            inputs.forEach(input => {
                input.value = valoresOriginales[input.id];
                input.readOnly = true;
                input.classList.add('bg-light');
            });
            accionesEdicion.classList.add('d-none');
            editarBtn.classList.remove('d-none');
            modalVerificacion.hide();
        });
    </script>
</body>
</html>