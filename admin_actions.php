<?php
/**
 * Admin Actions Handler
 * Maneja las acciones administrativas del sistema
 */

session_start();

// Usar manejo seguro para cargar archivos críticos
try {
    require_once 'config.php';
    require_once 'functions.php';
} catch (Exception $e) {
    error_log("Critical file loading error in admin_actions.php: " . $e->getMessage());
    redirect_to_error_page("Error al cargar componentes administrativos");
}

/**
 * Verificar si el usuario es administrador
 */
$is_admin = safe_database_operation(function() {
    return is_admin();
}, "Error al verificar permisos de administrador");

if (!$is_admin) {
    redirect_to_error_page("Acceso denegado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    processAdminActions();
}

// Redirección por defecto
redirectToPreviousPage();

/**
 * Procesa las acciones administrativas
 */
function processAdminActions() {
    $post_id = getPostId();
    $report_id = getReportId();
    
    if ($post_id > 0) {
        handlePostActions($post_id);
    }
    
    if ($report_id > 0) {
        handleReportActions($report_id);
    }
}

/**
 * Obtiene y valida el ID del post
 * @return int
 */
function getPostId() {
    return isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
}

/**
 * Obtiene y valida el ID del reporte
 * @return int
 */
function getReportId() {
    return isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
}

/**
 * Maneja las acciones relacionadas con posts
 * @param int $post_id
 */
function handlePostActions($post_id) {
    if (isset($_POST['lock_post'])) {
        lock_post($post_id);
        
    } elseif (isset($_POST['unlock_post'])) {
        unlock_post($post_id);
        
    } elseif (isset($_POST['pin_post'])) {
        pin_post($post_id);
        
    } elseif (isset($_POST['unpin_post'])) {
        unpin_post($post_id);
        
    } elseif (isset($_POST['delete_image'])) {
        deletePostImage($post_id);
    }
}

/**
 * Elimina la imagen de un post
 * @param int $post_id
 */
function deletePostImage($post_id) {
    $post = get_post($post_id);
    
    if (!$post || !$post['image_filename']) {
        return;
    }
    
    $image_path = UPLOAD_DIR . $post['image_filename'];
    
    // Eliminar archivo físico si existe
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    // Actualizar base de datos
    update_post_image($post_id, null);
}

/**
 * Maneja las acciones relacionadas con reportes
 * @param int $report_id
 */
function handleReportActions($report_id) {
    if (isset($_POST['delete_report'])) {
        delete_report($report_id);
        header('Location: admin.php');
        exit;
    }
}

/**
 * Redirecciona a la página anterior
 */
function redirectToPreviousPage() {
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'admin.php';
    header('Location: ' . $referer);
    exit;
}

?>