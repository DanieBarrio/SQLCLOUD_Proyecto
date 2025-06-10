<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'conexion.php';

$mensaje = '';
$token = $_GET['token'] ?? '';
$mostrar_formulario = true;

function contraseña_valida($pass) {
    return strlen($pass) >= 8 && preg_match('/[^a-zA-Z0-9]/', $pass);
}

// Validar token al entrar con GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (empty($token)) {
        $mensaje = "❌ Tiempo inválido o no proporcionado.";
        $mostrar_formulario = false;
    } else {
        $conn = conectar();
        $stmt = $conn->prepare("SELECT ID FROM token WHERE TOKEN = ? AND FECHA_EXPIRA > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $mensaje = "❌ Tiempo de recuperacion expirado.";
            $mostrar_formulario = false;
        }
        $stmt->close();
        $conn->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $pass1 = $_POST['pass1'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';

    if ($pass1 !== $pass2) {
        $mensaje = "❌ Las contraseñas no coinciden.";
    } elseif (!contraseña_valida($pass1)) {
        $mensaje = "❌ La contraseña debe tener al menos 8 caracteres y un carácter especial.";
    } else {
        $conn = conectar();
        $stmt = $conn->prepare("SELECT ID FROM token WHERE TOKEN = ? AND FECHA_EXPIRA > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($id_usuario);

        if ($stmt->fetch()) {
            $stmt->close();

            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET CONTRASENA = ? WHERE ID = ?");
            $stmt->bind_param("si", $hash, $id_usuario);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM token WHERE ID = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();

            $mensaje = "✅ Contraseña actualizada correctamente.";
            $mostrar_formulario = false;
        } else {
            $mensaje = "❌ Tiempo de recuperación expirado.";
            $mostrar_formulario = false;
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar contraseña</title>
    <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-700 to-indigo-600 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-2xl rounded-xl p-8 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Cambiar contraseña</h2>
        <?php if ($mensaje): ?>
            <div class="mb-4 text-center text-sm font-medium <?= str_starts_with($mensaje, '✅') ? 'text-green-600' : 'text-red-600' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mostrar_formulario): ?>
        <form method="POST" class="space-y-4" onsubmit="return validarFormulario()">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div>
                <label for="pass1" class="block text-sm text-gray-700">Nueva contraseña</label>
                <input type="password" name="pass1" id="pass1" required minlength="8"
                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    placeholder="Mínimo 8 caracteres y un carácter especial">
            </div>
            <div>
                <label for="pass2" class="block text-sm text-gray-700">Repetir contraseña</label>
                <input type="password" name="pass2" id="pass2" required minlength="8"
                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">Cambiar contraseña</button>
        </form>
        <script>
        function validarFormulario() {
            const pass1 = document.getElementById('pass1').value;
            const pass2 = document.getElementById('pass2').value;
            const especial = /[^a-zA-Z0-9]/;

            if (pass1.length < 8 || !especial.test(pass1)) {
                alert("La contraseña debe tener al menos 8 caracteres y un carácter especial.");
                return false;
            }

            if (pass1 !== pass2) {
                alert("Las contraseñas no coinciden.");
                return false;
            }
            return true;
        }
        </script>
        <?php else: ?>
            <p class="text-center text-sm text-gray-600 mt-4">Puedes volver al <a href="logister.php" class="text-indigo-600 hover:underline">inicio de sesión</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
