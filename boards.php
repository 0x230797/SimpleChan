<?php
/**
 * boards.php - Sistema de Tablones SimpleChan
 * 
 * Este archivo maneja la visualización y funcionalidad de los tablones
 * del imageboard, incluyendo posts, respuestas, búsquedas y paginación.
 * 
 * @author SimpleChan Team
 * @version 1.0
 */

session_start();

// Usar manejo seguro para cargar archivos críticos
try {
    require_once 'config.php';
    require_once 'functions.php';
    safe_require('includes/RedirectHandler.php');
    safe_require('includes/BoardController.php');
    safe_require('includes/BoardView.php');
} catch (Exception $e) {
    error_log("Critical file loading error in boards.php: " . $e->getMessage());
    redirect_to_error_page("Error al cargar componentes del tablón");
}

// Manejar redirecciones tempranas para evitar salida previa
try {
    RedirectHandler::handleSuccessRedirects();
} catch (Exception $e) {
    error_log("Error in RedirectHandler: " . $e->getMessage());
    redirect_to_error_page("Error en el sistema de redirecciones");
}

try {
    // Inicializar controlador y vista
    $controller = new BoardController();
    $view = new BoardView($controller);
    $view->render();
} catch (Exception $e) {
    // En caso de error, redirigir a la página 404
    error_log("Error en boards.php: " . $e->getMessage());
    header("Location: 404.php");
    exit;
}
?>