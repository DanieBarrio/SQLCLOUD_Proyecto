<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['user']) || empty($_GET['file'])) header('Location: logister.php');
$user = $_SESSION['user'];
$file = basename($_GET['file']);
$path = "/var/backups/sqlcloud/$user/$file";

if (!file_exists($path)) {
  http_response_code(404);
  exit('No encontrado');
}

header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="'. $file .'"');
readfile($path);
exit;
?>
