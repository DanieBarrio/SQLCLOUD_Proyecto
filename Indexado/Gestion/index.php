<?php
session_start();

if (isset($_SESSION["ultimo_acceso"])) {
    $inactividad = 3600; 
    if (time() - $_SESSION["ultimo_acceso"] > $inactividad) {
        session_destroy();
    }
}
$_SESSION["ultimo_acceso"] = time();

require 'funciones.php';
sinlogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQLCLOUD Gestión</title>
    <link rel="icon" href="../Recursos/icon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Nombre de la Página</a>
            <div class="ms-auto">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="light-mode-item" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark-mode') ? '' : 'd-none'; ?>">
                            <a class="dropdown-item" href="#" onclick="toggleTheme('light-mode')">
                                <i class="fas fa-sun me-2"></i>Modo Claro
                            </a>
                        </li>
                        <li id="dark-mode-item" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark-mode') ? 'd-none' : ''; ?>">
                            <a class="dropdown-item" href="#" onclick="toggleTheme('dark-mode')">
                                <i class="fas fa-moon me-2"></i>Modo Oscuro
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Cargar tema guardado
        document.body.className = localStorage.getItem('theme') || 'light-mode';

        function toggleTheme(theme) {
            // Alternar clases
            document.body.className = theme;
            
            // Actualizar botones
            document.getElementById('light-mode-item').classList.toggle('d-none', theme === 'light-mode');
            document.getElementById('dark-mode-item').classList.toggle('d-none', theme === 'dark-mode');
            
            // Guardar preferencia
            localStorage.setItem('theme', theme);
        }

        // Inicializar botones al cargar
        const savedTheme = localStorage.getItem('theme') || 'light-mode';
        document.getElementById('light-mode-item').classList.toggle('d-none', savedTheme === 'light-mode');
        document.getElementById('dark-mode-item').classList.toggle('d-none', savedTheme === 'dark-mode');
    </script>
</body>
</html>