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
require_once 'config.php';
require_once 'functions.php';
require_once 'includes/RedirectHandler.php';
require_once 'includes/BoardController.php';
require_once 'includes/BoardView.php';

// Manejar redirecciones tempranas para evitar salida previa
RedirectHandler::handleSuccessRedirects();

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