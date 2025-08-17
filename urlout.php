<?php
// urlout.php
$url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);

if (!$url || !preg_match('/^https?:\/\//i', $url)) {
    die('Enlace no válido');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirección segura</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .warning { background: #fff3cd; border-left: 6px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Estás saliendo de nuestro sitio</h1>
    
    <div class="warning">
        <p>Estás a punto de ser redirigido a un sitio externo:</p>
        <p><strong><?php echo htmlspecialchars($url); ?></strong></p>
        <p>No nos hacemos responsables por el contenido de sitios externos.</p>
    </div>
    
    <p>Si no deseas visitar este sitio, puedes <a href="javascript:history.back()">regresar a la página anterior</a>.</p>
    
    <p>Serás redirigido automáticamente en 5 segundos...</p>
    
    <a href="<?php echo htmlspecialchars($url); ?>" class="btn">Continuar</a>
    
    <script>
        setTimeout(function() {
            window.location.href = "<?php echo htmlspecialchars($url); ?>";
        }, 5000);
    </script>
</body>
</html>