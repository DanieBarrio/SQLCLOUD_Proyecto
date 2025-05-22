<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['plan'] !== 'gratuito') {
    header("Location: index.php");
    exit;
}

require 'conexion.php';

$usuario = $_SESSION['user'];

$conn = conectar();
$stmt = $conn->prepare("UPDATE usuarios SET plan = 'premium' WHERE USUARIO = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();

$_SESSION['plan'] = 'premium';

echo "<h2 class='text-center mt-5'>¡Felicidades! Has mejorado a Plan Premium.</h2>";
echo "<a href='index.php' class='btn btn-primary d-block mt-3'>Volver al Panel</a>";

$stmt->close();
$conn->close();
?>
