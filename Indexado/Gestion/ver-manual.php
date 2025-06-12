<?php
session_start();

// Comprobar si el usuario ha iniciado sesión y redirigir si no lo esta
if (!isset($_SESSION['user'])) {
    header("Location: logister.php");
    exit;
}

$pdfPath = '/var/www/sqlcloud.site/pdf/manual-sql.pdf';

if (file_exists($pdfPath)) {
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($pdfPath));
    readfile($pdfPath);
} else {
    http_response_code(404);
    echo "Manual no encontrado.";
}
?>
