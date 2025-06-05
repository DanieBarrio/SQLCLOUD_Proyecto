<?php 
//destruye todas las sesiones activas para que no vuelva a entrar
session_start();
session_unset();
session_destroy();
//redirige al login ya que si no se quedaria en esta pagina para siempre
header("Location: logister.php");
?>
