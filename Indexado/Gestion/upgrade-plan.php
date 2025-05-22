<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['plan'] === 'premium') {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mejorar a Plan Premium - SQLCloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white p-4">

<div class="container mt-5">
    <h2 class="mb-4">Mejorar a Plan Premium</h2>

    <div class="card bg-secondary p-4 mb-4">
        <h5>Plan Gratuito</h5>
        <ul>
            <li>1 Base de datos</li>
            <li>Hasta 20MB de almacenamiento</li>
        </ul>
    </div>

    <div class="card bg-primary p-4 mb-4">
        <h5>Plan Premium</h5>
        <ul>
            <li>3 Bases de datos</li>
            <li>Hasta 100MB de almacenamiento</li>
            <li>Precio: <strong>79.99€ / mes</strong></li>
        </ul>
    </div>

    <form action="procesar-pago.php" method="POST">
        <button type="submit" class="btn btn-success">Pagar Ahora</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
