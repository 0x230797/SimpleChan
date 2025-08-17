<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'includes/ReplyController.php';
require_once 'includes/ReplyView.php';

// Inicializar controlador
$controller = new ReplyController();

// Verificar si el usuario está baneado
$ban_info = is_user_banned();
if ($ban_info) {
    header('Location: ban.php');
    exit;
}

// Validar y obtener el ID del post
$post_id = $controller->validatePostId();
if (!$post_id) {
    $controller->redirectToHome();
}

// Obtener y validar el post principal
$post = $controller->validateMainPost($post_id);
if (!$post) {
    $controller->redirectToHome();
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