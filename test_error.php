<?php
/**
 * Archivo de prueba para demostrar el sistema de manejo de errores
 * NOTA: Este archivo debe eliminarse en producción
 */

session_start();
require_once 'config.php';
require_once 'functions.php';

echo "<h1>Pruebas del Sistema de Manejo de Errores</h1>";
echo "<p>Selecciona el tipo de error que quieres probar:</p>";

if (isset($_GET['test'])) {
    switch ($_GET['test']) {
        case 'fatal':
            // Error fatal: función inexistente
            call_to_undefined_function();
            break;
            
        case 'exception':
            // Excepción no capturada
            throw new Exception("Esta es una excepción de prueba");
            break;
            
        case 'db_error':
            // Error de base de datos simulado
            global $pdo;
            $pdo->query("SELECT * FROM tabla_inexistente");
            break;
            
        case 'custom_redirect':
            // Redirección personalizada con mensaje
            redirect_to_error_page("Este es un mensaje de error personalizado");
            break;
            
        case 'file_not_found':
            // Archivo no encontrado
            safe_require('archivo_inexistente.php');
            break;
            
        case 'invalid_param':
            // Parámetro inválido
            $invalid_id = safe_get_parameter('invalid_id', 'int');
            echo "ID: " . $invalid_id;
            break;
    }
} else {
    ?>
    <ul>
        <li><a href="?test=fatal">Probar Error Fatal</a></li>
        <li><a href="?test=exception">Probar Excepción No Capturada</a></li>
        <li><a href="?test=db_error">Probar Error de Base de Datos</a></li>
        <li><a href="?test=custom_redirect">Probar Redirección Personalizada</a></li>
        <li><a href="?test=file_not_found">Probar Archivo No Encontrado</a></li>
        <li><a href="?test=invalid_param&invalid_id=abc">Probar Parámetro Inválido</a></li>
    </ul>
    
    <p><strong>Nota:</strong> Todos estos tests deberían redirigir a la página 404.php con manejo adecuado del error.</p>
    
    <hr>
    <p><a href="index.php">Volver al inicio</a></p>
    <?php
}
?>
