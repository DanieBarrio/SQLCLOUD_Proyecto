<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include 'conexion.php';

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo inválido.";
    } else {
        $conn = conectar();
        $stmt = $conn->prepare("SELECT ID FROM usuarios WHERE correo = ?");
	$stmt->bind_param("s", $correo);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($row = $result->fetch_assoc()) {
    	$id_usuario = $row['ID'];

            $stmt->close();
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $conn->prepare("REPLACE INTO token (ID, TOKEN, FECHA_EXPIRA) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_usuario, $token, $expira);
            $stmt->execute();
            $stmt->close();
	// === REGISTRO EN LOG ===
	$fecha = date("Y-m-d H:i:s");
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'IP desconocida';
	$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'User-Agent desconocido';
	$linea = "[$fecha] [$ip] [$correo] Recuperación de contraseña solicitada | UA: $userAgent";
	file_put_contents("logs.txt", $linea . PHP_EOL, FILE_APPEND | LOCK_EX);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'mail.sqlcloud.site'; // IP de tu servidor SMTP
                $mail->Port = 25;
                $mail->SMTPAuth = false;

                $mail->setFrom('no-reply@sqlcloud.site', 'SQLCloud');
                $mail->addAddress($correo);
                $mail->isHTML(true);
                $mail->Subject = 'Recuperación de contraseña';
                $mail->Body = "Haz clic aquí para cambiar tu contraseña: 
                <a href='http://sqlcloud.site/Gestion/cambiar_contrasena.php?token=$token'>Cambiar contraseña</a>";

                $mail->send();
                $mensaje = "✅ Correo enviado. Revisa tu bandeja de entrada.";
            } catch (Exception $e) {
                $mensaje = "❌ Error al enviar correo: " . $mail->ErrorInfo;
            }
        } else {
            $mensaje = "❌ Este correo no está registrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-600 to-purple-700 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-xl rounded-xl p-8 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">¿Olvidaste tu contraseña?</h2>
        <?php if ($mensaje): ?>
            <div class="mb-4 text-center text-sm font-medium <?= str_starts_with($mensaje, '✅') ? 'text-green-600' : 'text-red-600' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="correo" class="block text-sm text-gray-700">Correo electrónico</label>
                <input type="email" name="correo" id="correo" required
                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">Enviar enlace de recuperación</button>
        </form>
        <p class="text-xs text-center mt-6 text-gray-500">Recibirás un enlace para restablecer tu contraseña si el correo está registrado.</p>
    </div>
</body>
</html>
