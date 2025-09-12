<?php
/**
 * report_handler.php - Manejador de Reportes
 * 
 * Este archivo maneja el envío de reportes desde cualquier parte del sitio
 * y redirige de vuelta a la página original con el resultado.
 */

require_once 'config.php';
initialize_session();
require_once 'functions.php';

// Verificar si el usuario está baneado
$ban_info = is_user_banned();
if ($ban_info) {
    header('Location: ban.php');
    exit;
}

// Solo procesar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verificar que es un reporte
if (!isset($_POST['submit_report'])) {
    header('Location: index.php');
    exit;
}

/**
 * Procesa el reporte
 */
function processReport() {
    $post_id = (int)($_POST['report_post_id'] ?? 0);
    $reason = clean_input($_POST['report_reason'] ?? '');
    $details = clean_input($_POST['report_details'] ?? '');
    $reporter_ip = get_user_ip();
    $return_url = $_POST['return_url'] ?? 'index.php';

    // Verificar CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        redirectWithError($return_url, 'Token CSRF inv\u00e1lido.');
        return;
    }
    
    // Validaciones
    if ($post_id <= 0) {
        redirectWithError($return_url, 'ID de post inválido.');
        return;
    }
    
    if (empty($reason)) {
        redirectWithError($return_url, 'El motivo del reporte es requerido.');
        return;
    }
    
    // Verificar que el post existe
    if (!post_exists($post_id)) {
        redirectWithError($return_url, 'El post no existe.');
        return;
    }
    
    // Crear el reporte
    if (create_report($post_id, $reason, $details, $reporter_ip)) {
        redirectWithSuccess($return_url, 'Reporte enviado correctamente.');
    } else {
        redirectWithError($return_url, 'Error al enviar el reporte.');
    }
}

/**
 * Redirige con mensaje de error
 */
function redirectWithError($url, $message) {
    $_SESSION['report_error'] = $message;
    header('Location: ' . $url);
    exit;
}

/**
 * Redirige con mensaje de éxito
 */
function redirectWithSuccess($url, $message) {
    $_SESSION['report_success'] = $message;
    header('Location: ' . $url);
    exit;
}

/**
 * Verifica si un post existe
 */
function post_exists($post_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$post_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Error al verificar post: " . $e->getMessage());
        return false;
    }
}

// Procesar el reporte
processReport();
?>
