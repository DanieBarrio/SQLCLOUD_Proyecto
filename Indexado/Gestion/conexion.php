<?php
function conectar() {
$servername = "sql100.thsite.top";
$username = "thsi_38723287";
$password = "gTc!!RmQ";
$db = "thsi_38723287_sqlcloud";

$conn = new mysqli($servername, $username, $password, $db);


mysqli_set_charset($conn, "utf8mb4");
return $conn;
}
?>