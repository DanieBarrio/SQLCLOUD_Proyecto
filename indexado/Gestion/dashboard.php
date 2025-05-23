<!DOCTYPE html>
<html>
<head>
    <title>Desplegar en Kubernetes</title>
</head>
<body>
    <h2>Desplegar Aplicación</h2>
    <form method="post">
        <button type="submit" name="deploy">🚀 Desplegar</button>
    </form>

    <?php
    if (isset($_POST['deploy'])) {
        // Llama al script de despliegue
        $output = shell_exec('kubectl apply -f /var/www/sqlcloud.site/kubernetes/deployment.yaml 2>&1');
        echo "<pre>$output</pre>";
    }
    ?>
</body>
</html>
