<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // ajusta la ruta si es necesario

function enviarCorreoRecuperacion($destinatario, $token) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = '172.16.2.23';  // Sustituye por la IP del otro Ubuntu
        $mail->Port = 25;
        $mail->SMTPAuth = false; // Asumimos que tu servidor no requiere autenticación

        $mail->setFrom('soporte.sqlcloud@gmail.com', 'SQLCloud');
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña - SQLCloud';
        $mail->Body    = 'Haz clic en el siguiente enlace para cambiar tu contraseña:<br><br>' .
                         '<a href="http://sqlcloud.site/Gestion/resetear.php?token=' . urlencode($token) . '">Recuperar Contraseña</a><br><br>' .
                         'Este enlace expirará en 1 hora.';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar el correo: {$mail->ErrorInfo}");
        return false;
    }
}
?>
