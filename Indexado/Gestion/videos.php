<?php
session_start();

// Protección: solo usuarios autenticados pueden acceder
if (!isset($_SESSION['user'])) {
    header("Location: logister.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vídeos sobre SQL - SQLCloud</title>
    <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"  rel="stylesheet">
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"  rel="stylesheet">
    <style>
        :root {
            --bg-main: #f8f9fa;
            --text-main: #212529;
            --card-bg: #ffffff;
            --card-border: #dee2e6;
            --btn-primary: #007bff;
            --btn-hover: #0069d9;
        }

        html.dark {
            --bg-main: #111827;
            --text-main: #f9fafb;
            --card-bg: #1f2937;
            --card-border: #374151;
            --btn-primary: #3b82f6;
            --btn-hover: #2563eb;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
            padding: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 2rem;
        }

        .videos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .video {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: calc(33% - 14px);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .video:hover {
            transform: translateY(-5px);
        }

        .video iframe {
            width: 100%;
            height: 200px;
            border: none;
        }

        .video h3 {
            font-size: 1rem;
            padding: 10px;
        }

        .btn-load-more {
            display: block;
            margin: 2rem auto;
            background-color: var(--btn-primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 0.375rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-load-more:hover {
            background-color: var(--btn-hover);
        }

        .btn-load-more[disabled] {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .footer {
            margin-top: 3rem;
            text-align: center;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .video {
                width: 100%;
            }
        }
   .btn-learn {
      display: inline-block;
      padding: 10px 15px;
      background-color: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      margin-top: 5px;
   }
 	 .btn-learn:hover {
    	   background-color: #0056b3;
 		}
    </style>
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'dark' : '' ?>">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 rounded">
    <div class="container-fluid d-flex justify-content-between align-items-center w-100">
        <!-- Botón izquierdo -->
        <a href="index.php" class="btn btn-primary me-2">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>

        <!-- Botones centrales -->
        <div class="d-flex gap-3">
            <a href="ver-manual.php" target="_blank" rel="noopener noreferrer" class="btn-learn">
                <i class="fas fa-book"></i> Quiero aprender
            </a>
        </div>

        <!-- Botón derecho -->
        <button id="themeToggle" class="btn btn-outline-secondary">
            <i class="fas fa-moon"></i> Cambiar tema
        </button>
    </div>
</nav>

<h2>🎥 Vídeos educativos sobre SQL y Bases de Datos</h2>

<div class="videos-container" id="videosContainer">
    <!-- Los vídeos se cargarán aquí dinámicamente -->
</div>

<button id="loadMoreButton" class="btn-load-more">Cargar vídeos iniciales</button>

<script>
    // Palabras clave para búsqueda en YouTube
    const searchTerms = [
        'SQL básico',
        'consultas avanzadas SQL',
        'JOINs explicados',
        'optimización de bases de datos',
        'funciones SQL',
        'normalización de bases de datos',
        'transacciones SQL',
        'índices en SQL'
    ];

    const maxRequests = 5; // Máximo de llamadas a la API
    let currentRequestCount = 0;

    const apiKey = 'AIzaSyCtXYA365Yymj2CcXpRYM4OG82cp75EHR8'; // Reemplaza esto con tu clave de API
    const container = document.getElementById('videosContainer');
    const loadMoreBtn = document.getElementById('loadMoreButton');

    async function fetchVideos() {
        if (currentRequestCount >= maxRequests) {
            loadMoreBtn.disabled = true;
            loadMoreBtn.textContent = 'Límite alcanzado';
            return;
        }

        const randomTerm = searchTerms[Math.floor(Math.random() * searchTerms.length)];
        const apiUrl = `https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(randomTerm)}&type=video&key=${apiKey}&maxResults=6`;

        try {
            const response = await fetch(apiUrl);
            const data = await response.json();

            if (data.items && data.items.length > 0) {
                displayVideos(data.items);
                currentRequestCount++;
            } else {
                alert('No se encontraron vídeos. Inténtalo de nuevo.');
            }

            if (currentRequestCount >= maxRequests) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Límite alcanzado';
            }
        } catch (error) {
            console.error('Error al obtener los vídeos:', error);
            alert('Hubo un problema al cargar los vídeos.');
        }
    }

    function displayVideos(videos) {
        videos.forEach(video => {
            const videoId = video.id.videoId;
            const title = video.snippet.title;

            const cardHTML = `
                <div class="video">
                    <h3>${title}</h3>
                    <iframe src="https://www.youtube.com/embed/${videoId}" allowfullscreen></iframe>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', cardHTML);
        });
    }

    // Cargar primer lote de vídeos al cargar la página
    window.addEventListener('DOMContentLoaded', () => {
        loadMoreBtn.textContent = 'Mostrar más';
        loadMoreBtn.disabled = false;
    });

    // Manejador del botón
    loadMoreBtn.addEventListener('click', fetchVideos);

    // Alternar entre modo claro y oscuro
    document.getElementById('themeToggle').addEventListener('click', () => {
        document.body.classList.toggle('dark');
    });
</script>

<div class="footer mt-5">
    <p>&copy; <?= date("Y") ?> SQLCloud - Aprendiendo juntos</p>
</div>

  <script>
    // Toggle de tema oscuro
    document.getElementById('themeToggle').addEventListener('click', () => {
      document.documentElement.classList.toggle('dark');
      const isDark = document.documentElement.classList.contains('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');

      // Actualizar icono
      document.querySelector('#themeToggle i').classList.toggle('fa-moon');
      document.querySelector('#themeToggle i').classList.toggle('fa-sun');
    });

    // Cargar tema guardado
    window.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme') || 'light';
      if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
        document.querySelector('#themeToggle i').classList.replace('fa-moon', 'fa-sun');
      }
    });
     function appendCommandToTextarea(sql) {
    const textarea = document.getElementById('sql_command');
    const currentContent = textarea.value;
    
    // Agregar salto de línea si el textarea no está vacío
    if (currentContent.trim() !== '') {
      sql = '\n\n' + sql;
    }
    
    textarea.value += sql;
  }
  </script>


</body>
</html>
