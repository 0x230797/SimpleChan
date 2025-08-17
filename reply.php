<?php
session_start();

// Usar manejo seguro para cargar archivos críticos
try {
    require_once 'config.php';
    require_once 'functions.php';
    safe_require('includes/ReplyController.php');
    safe_require('includes/ReplyView.php');
} catch (Exception $e) {
    error_log("Critical file loading error in reply.php: " . $e->getMessage());
    redirect_to_error_page("Error al cargar componentes necesarios");
}

// Inicializar controlador con manejo de errores
try {
    $controller = new ReplyController();
} catch (Exception $e) {
    error_log("Error creating ReplyController: " . $e->getMessage());
    redirect_to_error_page("Error interno del sistema");
}

// Verificar si el usuario está baneado usando operación segura de BD
$ban_info = safe_database_operation(function() {
    return is_user_banned();
}, "Error al verificar estado de ban");

if ($ban_info) {
    header('Location: ban.php');
    exit;
}

// Validar y obtener el ID del post
$post_id = safe_get_parameter('post_id', 'int');
if (!$post_id || $post_id <= 0) {
    redirect_to_error_page("ID de post inválido");
}

// Obtener y validar el post principal usando operación segura de BD
$post = safe_database_operation(function() use ($post_id) {
    return get_post($post_id);
}, "Error al obtener información del post");

if (!$post) {
    redirect_to_error_page("Post no encontrado");
}

// Verificar si el post está bloqueado
if ($controller->isPostLocked($post)) {
    $controller->redirectToHome();
}

// Procesar solicitudes POST
$result = $controller->handleRequest($post_id);
$error = $result['error'];
$success_message = $result['success_message'];

// Cargar datos para la vista
$view_data = $controller->loadReplyPageData($post_id);

// Inicializar vista y renderizar
$view = new ReplyView();
$view->render($post, $view_data, $error, $success_message);
?>