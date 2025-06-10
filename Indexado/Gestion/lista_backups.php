<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['user'])) {
  header('Location: logister.php');
  exit;
}

$user = $_SESSION['user'];
$dir = "/var/backups/sqlcloud/" . $user;
if (!is_dir($dir)) $files = [];
else {
  $files = array_filter(scandir($dir), fn($f) => preg_match('/\.sql\.gz$/', $f));
}
?>
<!DOCTYPE html>
<html class="dark"> <!-- Puedes quitar "class='dark'" si deseas modo claro por defecto -->
<head>
  <meta charset="UTF-8">
  <title>Mis Backups</title>
  <style>
    /* Estilos generales */
    body {
      font-family: sans-serif;
      background-color: #ffffff;
      color: #111827;
      margin: 0;
      padding: 2rem;
    }

    html.dark body {
      background-color: #111827 !important;
      color: #ffffff !important;
    }

    h2 {
      margin-bottom: 1rem;
    }

    .container {
      max-width: 600px;
      margin: auto;
    }

    .card {
      background-color: #ffffff;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
      margin-top: 1rem;
    }

    html.dark .card {
      background-color: #1f2937 !important;
      color: #ffffff !important;
    }

    ul {
      list-style: none;
      padding-left: 0;
    }

    li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem 0;
      border-bottom: 1px solid #e5e7eb;
    }

    html.dark li {
      border-bottom: 1px solid #374151;
    }

    a {
      text-decoration: none;
      color: #3b82f6;
      font-weight: bold;
    }

    a:hover {
      text-decoration: underline;
    }

    .btn-primary {
      background-color: #3b82f6;
      color: white;
      padding: 0.375rem 0.75rem;
      border-radius: 0.375rem;
      transition: filter 0.2s ease;
    }

    .btn-primary:hover {
      background-color: #2563eb;
    }

    html.dark .btn-primary {
      filter: brightness(90%);
    }

    html.dark .btn-primary:hover {
      filter: brightness(75%);
    }

    p {
      font-size: 1rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2>Descargar mis backups</h2>
      <?php if (empty($files)): ?>
        <p>No tienes backups disponibles.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($files as $file): ?>
            <li>
              <span><?= htmlspecialchars($file) ?></span>
              <a href="descargar_backup.php?file=<?= urlencode($file) ?>" class="btn-primary">Descargar</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
