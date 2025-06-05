<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: index.php");
    exit();
}

require 'conexion.php';
$conn = conectar();

$sql = "
    SELECT u.ID, u.NOMBRE, u.CORREO, u.ROL, p.T_PLAN,
           (SELECT GROUP_CONCAT(NOMBRE_BD) FROM base_datos bd WHERE bd.ID = u.ID) AS BASES
    FROM usuarios u
    LEFT JOIN plan p ON u.ID = p.ID
    ORDER BY FIELD(u.ROL, 'superadmin', 'admin', 'usuario')
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Superadmin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-gray-100">

<header class="bg-gray-800 text-white shadow-md">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <svg class="w-8 h-8 text-yellow-400" viewBox="0 0 24 24" fill="none">
        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"/>
        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2"/>
        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2"/>
      </svg>
      <h1 class="text-2xl font-bold">SQLCloud - Superadmin</h1>
    </div>
    <nav class="hidden md:flex space-x-6">
      <a href="admin.php" class="hover:text-blue-300 transition">Volver</a>
    </nav>
    <form method="POST" action="logout.php">
      <button type="submit" class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded-md transition">
        Cerrar sesión
      </button>
    </form>
  </div>
</header>

<section class="max-w-7xl mx-auto px-4 py-10">
  <h2 class="text-2xl font-bold mb-6">Gestor Global de Usuarios</h2>
  <div class="space-y-6">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="bg-gray-800 p-6 rounded-lg shadow-md">
        <p><strong>ID:</strong> <?= htmlspecialchars($row['ID']) ?></p>
        <p><strong>Nombre:</strong> <?= htmlspecialchars($row['NOMBRE']) ?></p>
        <p><strong>Correo:</strong> <?= htmlspecialchars($row['CORREO']) ?></p>
        <p><strong>Rol:</strong> <?= htmlspecialchars($row['ROL']) ?></p>
        <p><strong>Plan:</strong> <?= htmlspecialchars($row['T_PLAN'] ?? 'gratuito') ?></p>
        <p><strong>Bases de Datos:</strong> <?= htmlspecialchars($row['BASES'] ?? 'Ninguna') ?></p>

        <div class="mt-4 flex gap-3">
          <!-- Cambiar rol -->
          <form method="POST" action="acciones_superadmin.php">
            <input type="hidden" name="accion" value="cambiar_rol">
            <input type="hidden" name="id_usuario" value="<?= $row['ID'] ?>">
            <select name="nuevo_rol" class="text-black px-2 py-1 rounded">
              <option value="usuario" <?= $row['ROL'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
              <option value="admin" <?= $row['ROL'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              <option value="superadmin" <?= $row['ROL'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
            </select>
            <button class="ml-2 bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">Cambiar rol</button>
          </form>

          <!-- Eliminar usuario y sus bases -->
          <?php if ($_SESSION['user'] !== $row['CORREO']): ?>
            <form method="POST" action="acciones_superadmin.php" onsubmit="return confirm('Eliminar usuario y sus bases de datos?');">
              <input type="hidden" name="accion" value="eliminar_todo">
              <input type="hidden" name="id_usuario" value="<?= $row['ID'] ?>">
              <button class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Eliminar todo</button>
            </form>
          <?php else: ?>
            <p class="text-sm italic text-gray-400">No puedes eliminarte a ti mismo.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<footer class="bg-gray-800 mt-16 py-6 px-4 text-center text-gray-400 text-sm">
  © <?= date('Y') ?> SQLCloud. Todos los derechos reservados.
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>

