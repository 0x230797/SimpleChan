<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

/**
 * Clase AdminController - Maneja toda la lógica del panel de administración
 */
class AdminController {
    private $messages = [];
    
    public function __construct() {
        $this->processRequests();
    }
    
    /**
     * Procesa todas las peticiones POST y GET
     */
    private function processRequests() {
        // Procesar logout
        if (isset($_GET['logout'])) {
            $this->logout();
        }
        
        // Procesar peticiones POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequests();
        }
    }
    
    /**
     * Maneja las peticiones POST
     */
    private function handlePostRequests() {
        $redirect = false;
        
        // Login de administrador
        if (isset($_POST['admin_login'])) {
            $this->processAdminLogin();
            return;
        }
        
        // Acciones de administrador (requiere autenticación)
        if (!is_admin()) {
            $this->addError('Acceso denegado.');
            return;
        }
        
        // Eliminar post
        if (isset($_POST['delete_post'])) {
            $redirect = $this->deletePost();
        }
        
        // Banear IP
        if (isset($_POST['ban_ip'])) {
            $redirect = $this->banIp();
        }
        
        // Desbanear IP
        if (isset($_POST['unban_ip'])) {
            $redirect = $this->unbanIp();
        }
        
        // Eliminar imagen
        if (isset($_POST['delete_image'])) {
            $redirect = $this->deleteImage();
        }
        
        // Desbloquear post
        if (isset($_POST['unlock_post'])) {
            $redirect = $this->unlockPost();
        }
        
        // Fijar post
        if (isset($_POST['pin_post'])) {
            $redirect = $this->pinPost();
        }
        
        // Desfijar post
        if (isset($_POST['unpin_post'])) {
            $redirect = $this->unpinPost();
        }
        
        // Crear tablón
        if (isset($_POST['create_board'])) {
            $redirect = $this->createBoard();
        }
        
        // Editar tablón
        if (isset($_POST['edit_board'])) {
            $redirect = $this->editBoard();
        }
        
        // Eliminar tablón
        if (isset($_POST['delete_board'])) {
            $redirect = $this->deleteBoard();
        }
        
        // Crear usuario del staff
        if (isset($_POST['create_staff_user'])) {
            $redirect = $this->createStaffUser();
        }
        
        // Editar usuario del staff
        if (isset($_POST['edit_staff_user'])) {
            $redirect = $this->editStaffUser();
        }
        
        // Eliminar usuario del staff
        if (isset($_POST['delete_staff_user'])) {
            $redirect = $this->deleteStaffUser();
        }
        
        // Cambiar contraseña de usuario
        if (isset($_POST['change_staff_password'])) {
            $redirect = $this->changeStaffPassword();
        }
        
        // Actualizar configuraciones
        if (isset($_POST['update_config'])) {
            $redirect = $this->updateSiteConfig();
        }
        
        // Redirigir si es necesario
        if ($redirect) {
            $this->redirect('admin.php');
        }
    }
    
    /**
     * Procesa el login de administrador
     */
    private function processAdminLogin() {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username)) {
            $this->addError('El nombre de usuario es requerido.');
            return;
        }
        
        if (empty($password)) {
            $this->addError('La contraseña es requerida.');
            return;
        }
        
        // Primero intentar autenticación con el sistema de staff users
        $staff_user = authenticate_staff_user($username, $password);
        if ($staff_user) {
            // Crear sesión de administrador para el nuevo sistema
            $_SESSION['admin_token'] = bin2hex(random_bytes(32));
            $_SESSION['staff_user_id'] = $staff_user['id'];
            $_SESSION['staff_username'] = $staff_user['username'];
            $_SESSION['staff_rank'] = $staff_user['rank'];
            
            $this->addSuccess('Sesión iniciada correctamente como ' . $staff_user['username']);
            $this->redirect('admin.php');
            return;
        }
        
        // Fallback al sistema original de admin (solo para administrador principal)
        if ($username === 'admin' && $password === ADMIN_PASSWORD) {
            if (create_admin_session()) {
                $this->addSuccess('Sesión de administrador iniciada correctamente.');
            } else {
                $this->addError('Error al crear la sesión.');
            }
        } else {
            $this->addError('Usuario o contraseña incorrectos.');
        }
    }
    
    /**
     * Cierra la sesión de administrador
     */
    private function logout() {
        unset($_SESSION['admin_token']);
        $this->redirect('admin.php');
    }
    
    /**
     * Elimina un post
     */
    private function deletePost() {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        if (delete_post($post_id)) {
            $this->addSuccess('Post eliminado correctamente.');
            return true;
        } else {
            $this->addError('Error al eliminar el post.');
            return false;
        }
    }
    
    /**
     * Banea una IP
     */
    private function banIp() {
        $ip_address = clean_input($_POST['ip_address'] ?? '');
        $reason = clean_input($_POST['reason'] ?? '');
        $duration = isset($_POST['duration']) && !empty($_POST['duration']) ? (int)$_POST['duration'] : null;
        
        if (empty($ip_address)) {
            $this->addError('La dirección IP es requerida.');
            return false;
        }
        
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            $this->addError('Dirección IP inválida.');
            return false;
        }
        
        if (ban_ip($ip_address, $reason, $duration)) {
            $this->addSuccess('IP baneada correctamente.');
            return true;
        } else {
            $this->addError('Error al banear la IP.');
            return false;
        }
    }
    
    /**
     * Desbanea una IP
     */
    private function unbanIp() {
        $ban_id = (int)($_POST['ban_id'] ?? 0);
        
        if ($ban_id <= 0) {
            $this->addError('ID de ban inválido.');
            return false;
        }
        
        if (unban_ip($ban_id)) {
            $this->addSuccess('IP desbaneada correctamente.');
            return true;
        } else {
            $this->addError('Error al desbanear la IP.');
            return false;
        }
    }
    
    /**
     * Elimina una imagen de un post
     */
    private function deleteImage() {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            $this->addError('Post no encontrado.');
            return false;
        }
        
        if (empty($post['image_filename'])) {
            $this->addError('El post no tiene imagen asociada.');
            return false;
        }
        
        $image_path = UPLOAD_DIR . $post['image_filename'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        if (update_post_image($post_id, null)) {
            $this->addSuccess('Imagen eliminada correctamente.');
            return true;
        } else {
            $this->addError('Error al eliminar la imagen del post.');
            return false;
        }
    }
    
    /**
     * Desbloquea un post
     */
    private function unlockPost() {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        if (unlock_post($post_id)) {
            $this->addSuccess('Post desbloqueado correctamente.');
            return true;
        } else {
            $this->addError('Error al desbloquear el post.');
            return false;
        }
    }
    
    /**
     * Fija un post
     */
    private function pinPost() {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        if (pin_post($post_id)) {
            $this->addSuccess('Post fijado correctamente.');
            return true;
        } else {
            $this->addError('Error al fijar el post.');
            return false;
        }
    }
    
    /**
     * Desfija un post
     */
    private function unpinPost() {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        if (unpin_post($post_id)) {
            $this->addSuccess('Post desfijado correctamente.');
            return true;
        } else {
            $this->addError('Error al desfijar el post.');
            return false;
        }
    }
    
    /**
     * Crea un nuevo tablón
     */
    private function createBoard() {
        $name = trim($_POST['board_name'] ?? '');
        $short_id = trim($_POST['board_short_id'] ?? '');
        $description = trim($_POST['board_description'] ?? '');
        
        if (empty($name)) {
            $this->addError('El nombre del tablón es requerido.');
            return false;
        }
        
        if (empty($short_id)) {
            $this->addError('El ID corto del tablón es requerido.');
            return false;
        }
        
        // Validar que el short_id solo contenga letras y números
        if (!preg_match('/^[a-zA-Z0-9]+$/', $short_id)) {
            $this->addError('El ID corto solo puede contener letras y números.');
            return false;
        }
        
        // Verificar que no exista un tablón con el mismo nombre o short_id
        if (get_board_by_name($name)) {
            $this->addError('Ya existe un tablón con ese nombre.');
            return false;
        }
        
        if (get_board_by_short_id($short_id)) {
            $this->addError('Ya existe un tablón con ese ID corto.');
            return false;
        }
        
        if (create_board($name, $short_id, $description)) {
            $this->addSuccess('Tablón creado correctamente.');
            return true;
        } else {
            $this->addError('Error al crear el tablón.');
            return false;
        }
    }
    
    /**
     * Edita un tablón existente
     */
    private function editBoard() {
        $board_id = (int)($_POST['board_id'] ?? 0);
        $name = trim($_POST['board_name'] ?? '');
        $short_id = trim($_POST['board_short_id'] ?? '');
        $description = trim($_POST['board_description'] ?? '');
        
        if ($board_id <= 0) {
            $this->addError('ID de tablón inválido.');
            return false;
        }
        
        if (empty($name)) {
            $this->addError('El nombre del tablón es requerido.');
            return false;
        }
        
        if (empty($short_id)) {
            $this->addError('El ID corto del tablón es requerido.');
            return false;
        }
        
        // Validar que el short_id solo contenga letras y números
        if (!preg_match('/^[a-zA-Z0-9]+$/', $short_id)) {
            $this->addError('El ID corto solo puede contener letras y números.');
            return false;
        }
        
        // Verificar que el tablón existe
        $existing_board = get_board_by_id($board_id);
        if (!$existing_board) {
            $this->addError('El tablón no existe.');
            return false;
        }
        
        // Verificar que no haya conflictos con otros tablones
        $name_conflict = get_board_by_name($name);
        if ($name_conflict && $name_conflict['id'] != $board_id) {
            $this->addError('Ya existe otro tablón con ese nombre.');
            return false;
        }
        
        $short_id_conflict = get_board_by_short_id($short_id);
        if ($short_id_conflict && $short_id_conflict['id'] != $board_id) {
            $this->addError('Ya existe otro tablón con ese ID corto.');
            return false;
        }
        
        if (update_board($board_id, $name, $short_id, $description)) {
            $this->addSuccess('Tablón editado correctamente.');
            return true;
        } else {
            $this->addError('Error al editar el tablón.');
            return false;
        }
    }
    
    /**
     * Elimina un tablón
     */
    private function deleteBoard() {
        $board_id = (int)($_POST['board_id'] ?? 0);
        
        if ($board_id <= 0) {
            $this->addError('ID de tablón inválido.');
            return false;
        }
        
        // Verificar que el tablón existe
        $board = get_board_by_id($board_id);
        if (!$board) {
            $this->addError('El tablón no existe.');
            return false;
        }
        
        // Contar posts en el tablón
        $post_count = count_posts_by_board($board_id);
        if ($post_count > 0) {
            $this->addError("No se puede eliminar el tablón porque contiene {$post_count} posts. Elimina todos los posts primero.");
            return false;
        }
        
        if (delete_board($board_id)) {
            $this->addSuccess('Tablón eliminado correctamente.');
            return true;
        } else {
            $this->addError('Error al eliminar el tablón.');
            return false;
        }
    }
    
    /**
     * Crea un nuevo usuario del staff
     */
    private function createStaffUser() {
        $username = trim($_POST['staff_username'] ?? '');
        $password = $_POST['staff_password'] ?? '';
        $rank = $_POST['staff_rank'] ?? '';
        $board_permissions = $_POST['board_permissions'] ?? [];
        
        if (empty($username)) {
            $this->addError('El nombre de usuario es requerido.');
            return false;
        }
        
        if (empty($password)) {
            $this->addError('La contraseña es requerida.');
            return false;
        }
        
        if (!in_array($rank, array_keys(USER_RANKS))) {
            $this->addError('Rango inválido.');
            return false;
        }
        
        // Validar que el username no exista
        $existing_user = get_staff_user_by_username($username);
        if ($existing_user) {
            $this->addError('Ya existe un usuario con ese nombre.');
            return false;
        }
        
        if (create_staff_user($username, $password, $rank, $board_permissions)) {
            $this->addSuccess('Usuario creado correctamente.');
            return true;
        } else {
            $this->addError('Error al crear el usuario.');
            return false;
        }
    }
    
    /**
     * Edita un usuario del staff
     */
    private function editStaffUser() {
        $user_id = (int)($_POST['staff_user_id'] ?? 0);
        $username = trim($_POST['staff_username'] ?? '');
        $rank = $_POST['staff_rank'] ?? '';
        $board_permissions = $_POST['board_permissions'] ?? [];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($user_id <= 0) {
            $this->addError('ID de usuario inválido.');
            return false;
        }
        
        if (empty($username)) {
            $this->addError('El nombre de usuario es requerido.');
            return false;
        }
        
        if (!in_array($rank, array_keys(USER_RANKS))) {
            $this->addError('Rango inválido.');
            return false;
        }
        
        // Verificar que el usuario existe
        $existing_user = get_staff_user($user_id);
        if (!$existing_user) {
            $this->addError('El usuario no existe.');
            return false;
        }
        
        // Verificar que no haya conflicto con el username
        $username_conflict = get_staff_user_by_username($username);
        if ($username_conflict && $username_conflict['id'] != $user_id) {
            $this->addError('Ya existe otro usuario con ese nombre.');
            return false;
        }
        
        if (update_staff_user($user_id, $username, $rank, $board_permissions, $is_active)) {
            $this->addSuccess('Usuario editado correctamente.');
            return true;
        } else {
            $this->addError('Error al editar el usuario.');
            return false;
        }
    }
    
    /**
     * Elimina un usuario del staff
     */
    private function deleteStaffUser() {
        $user_id = (int)($_POST['staff_user_id'] ?? 0);
        
        if ($user_id <= 0) {
            $this->addError('ID de usuario inválido.');
            return false;
        }
        
        // Verificar que el usuario existe
        $user = get_staff_user($user_id);
        if (!$user) {
            $this->addError('El usuario no existe.');
            return false;
        }
        
        if (delete_staff_user($user_id)) {
            $this->addSuccess('Usuario eliminado correctamente.');
            return true;
        } else {
            $this->addError('Error al eliminar el usuario.');
            return false;
        }
    }
    
    /**
     * Cambia la contraseña de un usuario del staff
     */
    private function changeStaffPassword() {
        $user_id = (int)($_POST['staff_user_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        
        if ($user_id <= 0) {
            $this->addError('ID de usuario inválido.');
            return false;
        }
        
        if (empty($new_password)) {
            $this->addError('La nueva contraseña es requerida.');
            return false;
        }
        
        if (strlen($new_password) < 6) {
            $this->addError('La contraseña debe tener al menos 6 caracteres.');
            return false;
        }
        
        // Verificar que el usuario existe
        $user = get_staff_user($user_id);
        if (!$user) {
            $this->addError('El usuario no existe.');
            return false;
        }
        
        if (change_staff_password($user_id, $new_password)) {
            $this->addSuccess('Contraseña cambiada correctamente.');
            return true;
        } else {
            $this->addError('Error al cambiar la contraseña.');
            return false;
        }
    }
    
    /**
     * Actualiza la configuración del sitio
     */
    private function updateSiteConfig() {
        $config = [
            'site_title' => trim($_POST['site_title'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'max_file_size' => (int)($_POST['max_file_size'] ?? 0),
            'max_post_length' => (int)($_POST['max_post_length'] ?? 0),
            'posts_per_page' => (int)($_POST['posts_per_page'] ?? 0),
            'enable_file_uploads' => isset($_POST['enable_file_uploads']),
            'enable_tripcode' => isset($_POST['enable_tripcode']),
            'maintenance_mode' => isset($_POST['maintenance_mode']),
            'allow_anonymous' => isset($_POST['allow_anonymous']),
        ];
        
        // Validaciones básicas
        if (empty($config['site_title'])) {
            $this->addError('El título del sitio es requerido.');
            return false;
        }
        
        if ($config['max_file_size'] < 1024 || $config['max_file_size'] > 10485760) { // 1KB - 10MB
            $this->addError('El tamaño máximo de archivo debe estar entre 1KB y 10MB.');
            return false;
        }
        
        if ($config['max_post_length'] < 10 || $config['max_post_length'] > 10000) {
            $this->addError('La longitud máxima del post debe estar entre 10 y 10000 caracteres.');
            return false;
        }
        
        if ($config['posts_per_page'] < 5 || $config['posts_per_page'] > 50) {
            $this->addError('Los posts por página deben estar entre 5 y 50.');
            return false;
        }
        
        if (update_site_config($config)) {
            $this->addSuccess('Configuración actualizada correctamente.');
            return true;
        } else {
            $this->addError('Error al actualizar la configuración.');
            return false;
        }
    }
    
    /**
     * Obtiene todos los datos necesarios para el panel
     */
    public function getData() {
        return [
            'posts' => is_admin() ? get_all_posts() : [],
            'bans' => is_admin() ? get_active_bans() : [],
            'reports' => is_admin() ? get_all_reports() : [],
            'boards' => is_admin() ? get_all_boards() : [],
            'staff_users' => is_admin() ? get_all_staff_users() : []
        ];
    }
    
    /**
     * Obtiene los mensajes (errores y éxitos)
     */
    public function getMessages() {
        return $this->messages;
    }
    
    /**
     * Añade un mensaje de error
     */
    private function addError($message) {
        $this->messages['error'] = $message;
    }
    
    /**
     * Añade un mensaje de éxito
     */
    private function addSuccess($message) {
        $this->messages['success'] = $message;
    }
    
    /**
     * Redirige a una página
     */
    private function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/**
 * Clase AdminView - Maneja la presentación del panel
 */
class AdminView {
    private $data;
    private $messages;
    
    public function __construct($data, $messages) {
        $this->data = $data;
        $this->messages = $messages;
    }
    
    /**
     * Renderiza la página completa
     */
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Panel de Administración - SimpleChan</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes.css">
            <link rel="stylesheet" href="assets/css/admin.css">
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body class="admin">
            <?php $this->renderHeader(); ?>
            
            <?php if (!is_admin()): ?>
                <main class="admin-login-container">
                    <?php $this->renderMessages(); ?>
                    <?php $this->renderLoginForm(); ?>
                </main>
            <?php else: ?>
                <div class="admin-container">
                    <?php $this->renderSidebar(); ?>
                    <main class="admin-content">
                        <?php $this->renderMessages(); ?>
                        <?php $this->renderAdminPanel(); ?>
                    </main>
                </div>
            <?php endif; ?>
            
            <?php $this->renderFooter(); ?>
            <script src="assets/js/script.js"></script>
            <script>
                // JavaScript para el menú lateral
                function showSection(sectionId) {
                    // Ocultar todas las secciones
                    const sections = document.querySelectorAll('.admin-section');
                    sections.forEach(section => {
                        section.style.display = 'none';
                    });
                    
                    // Mostrar la sección seleccionada
                    const targetSection = document.getElementById(sectionId);
                    if (targetSection) {
                        targetSection.style.display = 'block';
                    }
                    
                    // Actualizar menú activo
                    const navLinks = document.querySelectorAll('.admin-nav a');
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    const activeLink = document.querySelector(`[href="#${sectionId}"]`);
                    if (activeLink) {
                        activeLink.classList.add('active');
                    }
                    
                    return false; // Prevenir navegación
                }
                
                // Mostrar la primera sección por defecto
                document.addEventListener('DOMContentLoaded', function() {
                    showSection('ban-ip');
                });
                
                // Función para establecer IP en el formulario de ban
                function setBanIp(ip) {
                    document.getElementById('ip_address').value = ip;
                    showSection('ban-ip');
                }
                
                // Funciones para gestión de tablones
                function editBoard(id, name, shortId, description) {
                    document.getElementById('edit_board_id').value = id;
                    document.getElementById('edit_board_name').value = name;
                    document.getElementById('edit_board_short_id').value = shortId;
                    document.getElementById('edit_board_description').value = description;
                    document.getElementById('edit-board-form').style.display = 'block';
                    showSection('editar-tablon');
                }
                
                function cancelEdit() {
                    document.getElementById('edit-board-form').style.display = 'none';
                }
                
                // Funciones para gestión de usuarios
                function editStaffUser(id, username, rank, boardPerms, isActive) {
                    document.getElementById('edit_staff_user_id').value = id;
                    document.getElementById('edit_staff_username').value = username;
                    document.getElementById('edit_staff_rank').value = rank;
                    document.getElementById('edit_is_active').checked = isActive == 1;
                    
                    // Limpiar checkboxes
                    const checkboxes = document.querySelectorAll('#edit-board-permissions-group input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.checked = false);
                    
                    // Marcar tablones asignados
                    if (boardPerms && boardPerms.length > 0) {
                        boardPerms.forEach(boardId => {
                            const checkbox = document.getElementById('edit-board-' + boardId);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                    
                    toggleEditBoardPermissions();
                    document.getElementById('edit-user-form').style.display = 'block';
                    document.getElementById('change-password-form').style.display = 'none';
                    showSection('editar-usuario');
                }
                
                function cancelEditUser() {
                    document.getElementById('edit-user-form').style.display = 'none';
                }
                
                function showChangePassword(userId, username) {
                    document.getElementById('password_staff_user_id').value = userId;
                    document.getElementById('edit-user-form').style.display = 'none';
                    document.getElementById('change-password-form').style.display = 'block';
                    showSection('editar-usuario');
                }
                
                function cancelChangePassword() {
                    document.getElementById('change-password-form').style.display = 'none';
                }
                
                function toggleBoardPermissions() {
                    const rank = document.getElementById('staff_rank').value;
                    const group = document.getElementById('board-permissions-group');
                    
                    if (rank === 'board_mod' || rank === 'janitor') {
                        group.style.display = 'block';
                    } else {
                        group.style.display = 'none';
                        // Desmarcar todos los checkboxes
                        const checkboxes = group.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(cb => cb.checked = false);
                    }
                }
                
                function toggleEditBoardPermissions() {
                    const rank = document.getElementById('edit_staff_rank').value;
                    const group = document.getElementById('edit-board-permissions-group');
                    
                    if (rank === 'board_mod' || rank === 'janitor') {
                        group.style.display = 'block';
                    } else {
                        group.style.display = 'none';
                        // Desmarcar todos los checkboxes
                        const checkboxes = group.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(cb => cb.checked = false);
                    }
                }
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Renderiza el header
     */
    private function renderHeader() {
        ?>
        <header>
            <h1>Panel de Administración</h1>
            <nav>
                <a href="index.php">Volver al Tablón</a>
                <a href="admin.php">Recargar</a>
                <?php if (is_admin()): ?>
                    <a href="?logout=1">Cerrar Sesión</a>
                <?php endif; ?>
            </nav>
        </header>
        <?php
    }
    
    /**
     * Renderiza el menú lateral
     */
    private function renderSidebar() {
        ?>
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <h2>Panel de Control</h2>
                <ul>
                    <li><a href="#ban-ip" onclick="showSection('ban-ip')">Banear IP</a></li>
                    <li><a href="#bans-activos" onclick="showSection('bans-activos')">Bans Activos</a></li>
                    <li><a href="#moderar-posts" onclick="showSection('moderar-posts')">Moderar Posts</a></li>
                    <li><a href="#reportes-usuarios" onclick="showSection('reportes-usuarios')">Reportes</a></li>
                </ul>
                
                <h2>Gestión de Tablones</h2>
                <ul>
                    <li><a href="#gestionar-tablones" onclick="showSection('gestionar-tablones')">Ver Tablones</a></li>
                    <li><a href="#crear-tablon" onclick="showSection('crear-tablon')">Crear Tablón</a></li>
                    <li><a href="#editar-tablon" onclick="showSection('editar-tablon')">Editar Tablón</a></li>
                </ul>
                
                <h2>Gestión de Usuarios</h2>
                <ul>
                    <li><a href="#gestionar-usuarios" onclick="showSection('gestionar-usuarios')">Ver Usuarios</a></li>
                    <li><a href="#crear-usuario" onclick="showSection('crear-usuario')">Crear Usuario</a></li>
                    <li><a href="#editar-usuario" onclick="showSection('editar-usuario')">Editar Usuario</a></li>
                </ul>
                
                <h2>Estadísticas</h2>
                <ul>
                    <li><a href="#estadisticas" onclick="showSection('estadisticas')">Estadísticas</a></li>
                    <li><a href="#logs" onclick="showSection('logs')">Logs del Sistema</a></li>
                </ul>
                
                <h2>Configuración</h2>
                <ul>
                    <li><a href="#configuracion" onclick="showSection('configuracion')">Configuración</a></li>
                </ul>
            </nav>
        </aside>
        <?php
    }
    
    /**
     * Renderiza los mensajes de error y éxito
     */
    private function renderMessages() {
        if (isset($this->messages['error'])) {
            echo '<div class="error">' . htmlspecialchars($this->messages['error']) . '</div>';
        }
        
        if (isset($this->messages['success'])) {
            echo '<div class="success">' . htmlspecialchars($this->messages['success']) . '</div>';
        }
    }
    
    /**
     * Renderiza el formulario de login
     */
    private function renderLoginForm() {
        ?>
        <h2>Iniciar Sesión</h2>
        <section class="admin-login">
            <form method="POST">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required placeholder="Nombre de usuario">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required placeholder="Contraseña">
                </div>
                <button type="submit" name="admin_login">Iniciar Sesión</button>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza el panel de administración
     */
    private function renderAdminPanel() {
        $this->renderBanIpSection();
        $this->renderActiveBansSection();
        $this->renderModeratePostsSection();
        $this->renderReportsSection();
        $this->renderGestionarTablonesSection();
        $this->renderCrearTablonSection();
        $this->renderEditarTablonSection();
        $this->renderGestionarUsuariosSection();
        $this->renderCrearUsuarioSection();
        $this->renderEditarUsuarioSection();
        $this->renderEstadisticasSection();
        $this->renderLogsSection();
        $this->renderConfiguracionSection();
    }
    
    /**
     * Renderiza la sección de baneo de IPs
     */
    private function renderBanIpSection() {
        ?>
        <section id="ban-ip" class="admin-section">
            <h3>Banear IP</h3>
            <form method="POST" class="ban-form">
                <div class="form-group">
                    <label for="ip_address">Dirección IP:</label>
                    <input type="text" id="ip_address" name="ip_address" required placeholder="192.168.1.1">
                </div>
                <div class="form-group">
                    <label for="reason">Razón del ban:</label>
                    <input type="text" id="reason" name="reason" placeholder="Spam, contenido inapropiado, etc.">
                </div>
                <div class="form-group">
                    <label for="duration">Duración (horas, vacío = permanente):</label>
                    <input type="number" id="duration" name="duration" min="1" placeholder="24">
                </div>
                <button type="submit" name="ban_ip">Banear IP</button>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de bans activos
     */
    private function renderActiveBansSection() {
        ?>
        <section id="bans-activos" class="admin-section" style="display:none;">
            <h3>Bans Activos</h3>
            <?php if (empty($this->data['bans'])): ?>
                <p>No hay bans activos.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Razón</th>
                            <th>Fecha</th>
                            <th>Expira</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['bans'] as $ban): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ban['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($ban['reason']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($ban['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    if ($ban['expires_at']) {
                                        echo date('d/m/Y H:i', strtotime($ban['expires_at']));
                                    } else {
                                        echo 'Permanente';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="ban_id" value="<?php echo $ban['id']; ?>">
                                        <button type="submit" name="unban_ip" onclick="return confirm('¿Desbanear esta IP?')">Desbanear</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de moderación de posts
     */
    private function renderModeratePostsSection() {
        ?>
        <section id="moderar-posts" class="admin-section" style="display:none;">
            <h3>Moderar Posts</h3>
            <?php if (empty($this->data['posts'])): ?>
                <p>No hay posts.</p>
            <?php else: ?>
                <div class="posts-admin">
                    <?php foreach ($this->data['posts'] as $post): ?>
                        <?php $this->renderPostForModeration($post); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }
    
    /**
     * Renderiza un post individual para moderación
     */
    private function renderPostForModeration($post) {
        $board = isset($post['board_id']) ? get_board_by_id($post['board_id']) : null;
        ?>
        <div class="post-admin <?php echo $post['is_deleted'] ? 'deleted' : ''; ?>">
            <div class="post-header">
                <span><b>Tablón:</b> <?php echo htmlspecialchars($board['name'] ?? 'N/A'); ?> -</span>
                <span><b>ID:</b> <?php echo $post['id']; ?> -</span>
                <?php if ($post['parent_id']): ?>
                    (Respuesta a: <?php echo $post['parent_id']; ?>)
                <?php endif; ?>
                <?php if ($post['name'] === 'Administrador'): ?>
                    <span class="admin-name">Administrador</span>
                <?php else: ?>
                    <?php echo htmlspecialchars($post['name']); ?>
                <?php endif; ?>
                <span><b>Fecha/Hora:</b> <?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?> -</span>
                <span><b>IP:</b> <?php echo htmlspecialchars($post['ip_address']); ?></span>
                <?php if ($post['is_deleted']): ?>
                    <span class="deleted-label">[ELIMINADO]</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($post['subject'])): ?>
                <span><b>Asunto:</b> <?php echo htmlspecialchars($post['subject']); ?></span>
            <?php endif; ?>
            
            <?php $this->renderPostImage($post); ?>
            
            <div class="post-message">
                <?php 
                // Determinar el post padre para las referencias
                $parent_post_id = $post['parent_id'] ? $post['parent_id'] : $post['id'];
                echo parse_references($post['message'], true, $parent_post_id); 
                ?>
            </div>
            
            <?php $this->renderPostActions($post); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza la imagen de un post
     */
    private function renderPostImage($post) {
        if (!$post['image_filename']) return;
        
        ?>
        <div class="post-image">
            <?php if (file_exists(UPLOAD_DIR . $post['image_filename'])): ?>
                <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" title="<?php echo htmlspecialchars($post['image_filename']); ?>">
            <?php else: ?>
                <img src="assets/imgs/filedeleted.png" alt="Imagen no disponible">
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza las acciones disponibles para un post
     */
    private function renderPostActions($post) {
        ?>
        <div class="post-actions">
            <?php if (!$post['is_deleted']): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" name="delete_post" onclick="return confirm('¿Eliminar este post?')">Eliminar Post</button>
                </form>
            <?php endif; ?>
            
            <?php if ($post['is_locked']): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" name="unlock_post">Desbloquear</button>
                </form>
            <?php endif; ?>
            
            <?php if ($post['is_pinned']): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" name="unpin_post">Desfijar</button>
                </form>
            <?php else: ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" name="pin_post">Fijar</button>
                </form>
            <?php endif; ?>
            
            <button type="button" class="btn-ban-ip" onclick="setBanIp('<?php echo htmlspecialchars($post['ip_address']); ?>')">Banear IP</button>
            
            <?php if (!empty($post['image_filename']) && file_exists(UPLOAD_DIR . $post['image_filename'])): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" name="delete_image" onclick="return confirm('¿Eliminar la imagen de este post?')">Eliminar Imagen</button>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza la sección de reportes
     */
    private function renderReportsSection() {
        ?>
        <section id="reportes-usuarios" class="admin-section" style="display:none;">
            <h3>Reportes de Usuarios</h3>
            <?php if (empty($this->data['reports'])): ?>
                <p>No hay reportes.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Post</th>
                            <th>Motivo</th>
                            <th>Detalles</th>
                            <th>Reportado por IP</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['reports'] as $report): ?>
                            <tr>
                                <td><?php echo $report['id']; ?></td>
                                <td>
                                    <a href="index.php#post-<?php echo $report['post_id']; ?>" target="_blank">No. <?php echo $report['post_id']; ?></a>
                                    <?php if (!empty($report['name'])): ?>
                                        <br><small><?php echo htmlspecialchars($report['name']); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($report['subject'])): ?>
                                        <br><small><?php echo htmlspecialchars($report['subject']); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($report['post_id'])): ?>
                                        <br><small style="color:#888;">IP publicador: <?php echo htmlspecialchars(get_post($report['post_id'])['ip_address'] ?? ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($report['reason']); ?></td>
                                <td><?php echo htmlspecialchars($report['details']); ?></td>
                                <td><?php echo htmlspecialchars($report['reporter_ip']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($report['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="admin_actions.php">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" name="delete_report" class="btn-delete">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de gestión de tablones
     */
    private function renderGestionarTablonesSection() {
        ?>
        <section id="gestionar-tablones" class="admin-section" style="display:none;">
            <h3>Gestión de Tablones</h3>
            <?php if (empty($this->data['boards'])): ?>
                <p>No hay tablones creados.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>ID Corto</th>
                            <th>Descripción</th>
                            <th>Posts</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['boards'] as $board): ?>
                            <tr>
                                <td><?php echo $board['id']; ?></td>
                                <td><?php echo htmlspecialchars($board['name']); ?></td>
                                <td><strong>/<?php echo htmlspecialchars($board['short_id']); ?>/</strong></td>
                                <td><?php echo htmlspecialchars($board['description']); ?></td>
                                <td><?php echo number_format(count_posts_by_board($board['id'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($board['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn-edit" onclick="editBoard(<?php echo $board['id']; ?>, '<?php echo htmlspecialchars($board['name']); ?>', '<?php echo htmlspecialchars($board['short_id']); ?>', '<?php echo htmlspecialchars($board['description']); ?>')">Editar</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="board_id" value="<?php echo $board['id']; ?>">
                                        <button type="submit" name="delete_board" class="btn-delete" onclick="return confirm('¿Eliminar este tablón? Todos los posts se perderán.')">Eliminar</button>
                                    </form>
                                    <a href="catalog.php?board=<?php echo htmlspecialchars($board['short_id']); ?>" target="_blank" class="btn-view">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de crear tablón
     */
    private function renderCrearTablonSection() {
        ?>
        <section id="crear-tablon" class="admin-section" style="display:none;">
            <h3>Crear Nuevo Tablón</h3>
            <form method="POST" class="board-form">
                <div class="form-group">
                    <label for="board_name">Nombre del Tablón:</label>
                    <input type="text" id="board_name" name="board_name" required placeholder="Ej: Tecnología" maxlength="100">
                    <small>Nombre completo del tablón (máximo 100 caracteres)</small>
                </div>
                
                <div class="form-group">
                    <label for="board_short_id">ID Corto:</label>
                    <input type="text" id="board_short_id" name="board_short_id" required placeholder="Ej: tech" maxlength="10" pattern="[a-zA-Z0-9]+">
                    <small>Solo letras y números, máximo 10 caracteres (será /tech/)</small>
                </div>
                
                <div class="form-group">
                    <label for="board_description">Descripción:</label>
                    <textarea id="board_description" name="board_description" placeholder="Descripción del tablón..." maxlength="500" rows="3"></textarea>
                    <small>Descripción opcional del tablón (máximo 500 caracteres)</small>
                </div>
                
                <button type="submit" name="create_board" class="btn-primary">Crear Tablón</button>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de editar tablón
     */
    private function renderEditarTablonSection() {
        ?>
        <section id="editar-tablon" class="admin-section" style="display:none;">
            <h3>Editar Tablón</h3>
            <p>Selecciona un tablón de la lista "Ver Tablones" para editarlo aquí.</p>
            
            <form method="POST" class="board-form" id="edit-board-form" style="display:none;">
                <input type="hidden" id="edit_board_id" name="board_id">
                
                <div class="form-group">
                    <label for="edit_board_name">Nombre del Tablón:</label>
                    <input type="text" id="edit_board_name" name="board_name" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="edit_board_short_id">ID Corto:</label>
                    <input type="text" id="edit_board_short_id" name="board_short_id" required maxlength="10" pattern="[a-zA-Z0-9]+">
                    <small>⚠️ Cambiar el ID corto puede romper enlaces existentes</small>
                </div>
                
                <div class="form-group">
                    <label for="edit_board_description">Descripción:</label>
                    <textarea id="edit_board_description" name="board_description" maxlength="500" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_board" class="btn-primary">Guardar Cambios</button>
                    <button type="button" onclick="cancelEdit()" class="btn-secondary">Cancelar</button>
                </div>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de gestión de usuarios
     */
    private function renderGestionarUsuariosSection() {
        ?>
        <section id="gestionar-usuarios" class="admin-section" style="display:none;">
            <h3>Gestión de Usuarios del Staff</h3>
            
            <?php if (empty($this->data['staff_users'])): ?>
                <div class="info-message">
                    <h4>⚠️ Sistema de Usuarios no Configurado</h4>
                    <p>Para usar el sistema de gestión de usuarios, necesitas crear la tabla <code>staff_users</code> en tu base de datos.</p>
                    <p><strong>Instrucciones:</strong></p>
                    <ol>
                        <li>Accede a phpMyAdmin o tu cliente MySQL</li>
                        <li>Selecciona la base de datos <code>simplechan_db</code></li>
                        <li>Ejecuta el siguiente SQL:</li>
                    </ol>
                    <pre><code>CREATE TABLE staff_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rank ENUM('admin', 'global_mod', 'board_mod', 'janitor') NOT NULL,
    board_permissions JSON DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_rank (rank),
    INDEX idx_is_active (is_active)
);

INSERT INTO staff_users (username, password_hash, rank) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');</code></pre>
                    <p><strong>Credenciales por defecto:</strong> Usuario: <code>admin</code> | : <code>password</code></p>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rango</th>
                            <th>Tablones Asignados</th>
                            <th>Estado</th>
                            <th>Último Login</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['staff_users'] as $user): ?>
                            <tr class="<?php echo $user['is_active'] ? '' : 'inactive-user'; ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td>
                                    <span class="rank-badge rank-<?php echo $user['rank']; ?>">
                                        <?php echo USER_RANKS[$user['rank']] ?? $user['rank']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $board_perms = json_decode($user['board_permissions'], true) ?: [];
                                    if (in_array($user['rank'], ['admin', 'global_mod'])) {
                                        echo '<em>Todos los tablones</em>';
                                    } elseif (empty($board_perms)) {
                                        echo '<em>Ninguno</em>';
                                    } else {
                                        $board_names = [];
                                        foreach ($board_perms as $board_id) {
                                            $board = get_board_by_id($board_id);
                                            if ($board) {
                                                $board_names[] = '/' . $board['short_id'] . '/';
                                            }
                                        }
                                        echo implode(', ', $board_names);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn-edit" onclick="editStaffUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $user['rank']; ?>', <?php echo htmlspecialchars(json_encode($board_perms)); ?>, <?php echo $user['is_active']; ?>)">Editar</button>
                                    <button type="button" class="btn-password" onclick="showChangePassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Contraseña</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="staff_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_staff_user" class="btn-delete" onclick="return confirm('¿Eliminar este usuario?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de crear usuario
     */
    private function renderCrearUsuarioSection() {
        ?>
        <section id="crear-usuario" class="admin-section" style="display:none;">
            <h3>Crear Nuevo Usuario del Staff</h3>
            <form method="POST" class="user-form">
                <div class="form-group">
                    <label for="staff_username">Nombre de Usuario:</label>
                    <input type="text" id="staff_username" name="staff_username" required placeholder="usuario123" maxlength="50">
                    <small>Nombre único del usuario (máximo 50 caracteres)</small>
                </div>
                
                <div class="form-group">
                    <label for="staff_password">Contraseña:</label>
                    <input type="password" id="staff_password" name="staff_password" required minlength="6">
                    <small>Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="staff_rank">Rango:</label>
                    <select id="staff_rank" name="staff_rank" required onchange="toggleBoardPermissions()">
                        <option value="">Seleccionar rango...</option>
                        <?php foreach (USER_RANKS as $rank => $label): ?>
                            <option value="<?php echo $rank; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="board-permissions-group" style="display:none;">
                    <label>Tablones Asignados:</label>
                    <div class="checkbox-group">
                        <?php if (!empty($this->data['boards'])): ?>
                            <?php foreach ($this->data['boards'] as $board): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="board_permissions[]" value="<?php echo $board['id']; ?>">
                                    /<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><em>No hay tablones disponibles</em></p>
                        <?php endif; ?>
                    </div>
                    <small>Solo aplica para Moderadores de Tablón y Conserjes</small>
                </div>
                
                <button type="submit" name="create_staff_user" class="btn-primary">Crear Usuario</button>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de editar usuario
     */
    private function renderEditarUsuarioSection() {
        ?>
        <section id="editar-usuario" class="admin-section" style="display:none;">
            <h3>Editar Usuario del Staff</h3>
            <p>Selecciona un usuario de la lista "Ver Usuarios" para editarlo aquí.</p>
            
            <form method="POST" class="user-form" id="edit-user-form" style="display:none;">
                <input type="hidden" id="edit_staff_user_id" name="staff_user_id">
                
                <div class="form-group">
                    <label for="edit_staff_username">Nombre de Usuario:</label>
                    <input type="text" id="edit_staff_username" name="staff_username" required maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="edit_staff_rank">Rango:</label>
                    <select id="edit_staff_rank" name="staff_rank" required onchange="toggleEditBoardPermissions()">
                        <?php foreach (USER_RANKS as $rank => $label): ?>
                            <option value="<?php echo $rank; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="edit-board-permissions-group">
                    <label>Tablones Asignados:</label>
                    <div class="checkbox-group">
                        <?php if (!empty($this->data['boards'])): ?>
                            <?php foreach ($this->data['boards'] as $board): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="board_permissions[]" value="<?php echo $board['id']; ?>" id="edit-board-<?php echo $board['id']; ?>">
                                    /<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="edit_is_active" name="is_active" checked>
                        Usuario activo
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_staff_user" class="btn-primary">Guardar Cambios</button>
                    <button type="button" onclick="cancelEditUser()" class="btn-secondary">Cancelar</button>
                </div>
            </form>
            
            <!-- Formulario para cambiar contraseña -->
            <form method="POST" class="password-form" id="change-password-form" style="display:none;">
                <h4>Cambiar Contraseña</h4>
                <input type="hidden" id="password_staff_user_id" name="staff_user_id">
                
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <small>Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="change_staff_password" class="btn-primary">Cambiar Contraseña</button>
                    <button type="button" onclick="cancelChangePassword()" class="btn-secondary">Cancelar</button>
                </div>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de estadísticas
     */
    private function renderEstadisticasSection() {
        $stats = get_site_stats();
        ?>
        <section id="estadisticas" class="admin-section" style="display:none;">
            <h3>Estadísticas del Sitio</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Posts Totales</h4>
                    <p class="stat-number"><?php echo number_format($stats['total_posts'] ?? 0); ?></p>
                    <small>Posts activos en el sitio</small>
                </div>
                <div class="stat-card">
                    <h4>Posts Hoy</h4>
                    <p class="stat-number"><?php echo number_format($stats['posts_today'] ?? 0); ?></p>
                    <small>Actividad del día</small>
                </div>
                <div class="stat-card">
                    <h4>Posts Esta Semana</h4>
                    <p class="stat-number"><?php echo number_format($stats['posts_week'] ?? 0); ?></p>
                    <small>Últimos 7 días</small>
                </div>
                <div class="stat-card">
                    <h4>Tablones Activos</h4>
                    <p class="stat-number"><?php echo number_format($stats['active_boards'] ?? 0); ?> / <?php echo number_format($stats['total_boards'] ?? 0); ?></p>
                    <small>Con contenido / Total</small>
                </div>
                <div class="stat-card">
                    <h4>IPs Únicas</h4>
                    <p class="stat-number"><?php echo number_format($stats['unique_ips'] ?? 0); ?></p>
                    <small>Usuarios que han posteado</small>
                </div>
                <div class="stat-card">
                    <h4>Archivos Subidos</h4>
                    <p class="stat-number"><?php echo number_format($stats['total_files'] ?? 0); ?></p>
                    <small>Imágenes totales</small>
                </div>
                <div class="stat-card">
                    <h4>Espacio Usado</h4>
                    <p class="stat-number"><?php echo format_file_size($stats['total_file_size'] ?? 0); ?></p>
                    <small>Almacenamiento</small>
                </div>
                <div class="stat-card">
                    <h4>Posts Principales</h4>
                    <p class="stat-number"><?php echo number_format($stats['main_posts'] ?? 0); ?></p>
                    <small>Hilos creados</small>
                </div>
                <div class="stat-card">
                    <h4>Respuestas</h4>
                    <p class="stat-number"><?php echo number_format($stats['replies'] ?? 0); ?></p>
                    <small>Respuestas a hilos</small>
                </div>
                <div class="stat-card">
                    <h4>Posts Eliminados</h4>
                    <p class="stat-number"><?php echo number_format($stats['deleted_posts'] ?? 0); ?></p>
                    <small>Contenido moderado</small>
                </div>
            </div>
            
            <div class="stats-section">
                <h4>Estadísticas por Tablón</h4>
                <div class="board-stats">
                    <?php
                    $board_stats = get_board_statistics();
                    if (!empty($board_stats)) {
                        echo '<table class="stats-table">';
                        echo '<thead><tr><th>Tablón</th><th>Posts</th><th>Último Post</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($board_stats as $board) {
                            $last_post = $board['last_post'] ? date('d/m/Y H:i', strtotime($board['last_post'])) : 'Nunca';
                            echo '<tr>';
                            echo '<td>/' . htmlspecialchars($board['short_id']) . '/ - ' . htmlspecialchars($board['name']) . '</td>';
                            echo '<td>' . number_format($board['post_count']) . '</td>';
                            echo '<td>' . $last_post . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No hay tablones con estadísticas disponibles.</p>';
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de logs
     */
    private function renderLogsSection() {
        ?>
        <section id="logs" class="admin-section" style="display:none;">
            <h3>Logs del Sistema</h3>
            <p>Funcionalidad en desarrollo - Próximamente se mostrará el historial de acciones administrativas.</p>
        </section>
        <?php
    }
    
    /**
     * Renderiza la sección de configuración
     */
    private function renderConfiguracionSection() {
        $config = get_site_config();
        ?>
        <section id="configuracion" class="admin-section" style="display:none;">
            <h3>Configuración del Sitio</h3>
            
            <form method="post" class="config-form">
                <div class="config-section">
                    <h4>Información General</h4>
                    <div class="form-group">
                        <label for="site_title">Título del Sitio:</label>
                        <input type="text" name="site_title" id="site_title" 
                               value="<?php echo htmlspecialchars($config['site_title'] ?? 'SimpleChan'); ?>" 
                               required maxlength="100">
                        <small>Nombre que aparece en el encabezado del sitio</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Descripción del Sitio:</label>
                        <textarea name="site_description" id="site_description" rows="3" maxlength="500"><?php echo htmlspecialchars($config['site_description'] ?? 'Un imageboard simple y funcional'); ?></textarea>
                        <small>Descripción que aparece en la página principal</small>
                    </div>
                </div>
                
                <div class="config-section">
                    <h4>Límites de Contenido</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_file_size">Tamaño Máximo de Archivo (bytes):</label>
                            <input type="number" name="max_file_size" id="max_file_size" 
                                   value="<?php echo (int)($config['max_file_size'] ?? 2097152); ?>" 
                                   min="1024" max="10485760" required>
                            <small>Entre 1KB (1024) y 10MB (10485760)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_post_length">Longitud Máxima del Post:</label>
                            <input type="number" name="max_post_length" id="max_post_length" 
                                   value="<?php echo (int)($config['max_post_length'] ?? 2000); ?>" 
                                   min="10" max="10000" required>
                            <small>Entre 10 y 10000 caracteres</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="posts_per_page">Posts por Página:</label>
                            <input type="number" name="posts_per_page" id="posts_per_page" 
                                   value="<?php echo (int)($config['posts_per_page'] ?? 10); ?>" 
                                   min="5" max="50" required>
                            <small>Entre 5 y 50 posts</small>
                        </div>
                    </div>
                </div>
                
                <div class="config-section">
                    <h4>Funcionalidades</h4>
                    <div class="form-row">
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="enable_file_uploads" 
                                       <?php echo !empty($config['enable_file_uploads']) ? 'checked' : ''; ?>>
                                Permitir Subida de Archivos
                            </label>
                            <small>Los usuarios pueden subir imágenes</small>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="enable_tripcode" 
                                       <?php echo !empty($config['enable_tripcode']) ? 'checked' : ''; ?>>
                                Habilitar Tripcodes
                            </label>
                            <small>Permite usar tripcodes para identificación</small>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="allow_anonymous" 
                                       <?php echo !empty($config['allow_anonymous']) ? 'checked' : ''; ?>>
                                Permitir Posts Anónimos
                            </label>
                            <small>Los usuarios pueden postear sin nombre</small>
                        </div>
                        
                        <div class="form-group checkbox-group maintenance-warning">
                            <label>
                                <input type="checkbox" name="maintenance_mode" 
                                       <?php echo !empty($config['maintenance_mode']) ? 'checked' : ''; ?>>
                                Modo de Mantenimiento
                            </label>
                            <small>⚠️ Solo administradores pueden acceder al sitio</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_config" class="btn-primary">
                        Guardar Configuración
                    </button>
                    <button type="button" onclick="resetConfigForm()" class="btn-secondary">
                        Restablecer
                    </button>
                </div>
            </form>
            
            <div class="config-info">
                <h4>Información del Sistema</h4>
                <div class="system-info">
                    <div class="info-item">
                        <strong>Versión PHP:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="info-item">
                        <strong>Memoria Límite:</strong> <?php echo ini_get('memory_limit'); ?>
                    </div>
                    <div class="info-item">
                        <strong>Tamaño Máximo Upload:</strong> <?php echo ini_get('upload_max_filesize'); ?>
                    </div>
                    <div class="info-item">
                        <strong>Directorio de Uploads:</strong> <?php echo realpath('uploads/') ?: 'uploads/'; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <script>
        function resetConfigForm() {
            if (confirm('¿Estás seguro de que quieres restablecer todos los cambios?')) {
                document.querySelector('.config-form').reset();
            }
        }
        </script>
        <?php
    }
    
    /**
     * Renderiza el footer
     */
    private function renderFooter() {
        ?>
        <footer>
            <p>&copy; 2025 SimpleChan - Panel de Administración</p>
        </footer>
        <?php
    }
}

// Inicializar la aplicación
$controller = new AdminController();
$data = $controller->getData();
$messages = $controller->getMessages();

$view = new AdminView($data, $messages);
$view->render();
?>