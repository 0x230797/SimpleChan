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
        
        // Redirigir si es necesario
        if ($redirect) {
            $this->redirect('admin.php');
        }
    }
    
    /**
     * Procesa el login de administrador
     */
    private function processAdminLogin() {
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            $this->addError('La contraseña es requerida.');
            return;
        }
        
        if ($password === ADMIN_PASSWORD) {
            if (create_admin_session()) {
                $this->addSuccess('Sesión de administrador iniciada correctamente.');
            } else {
                $this->addError('Error al crear la sesión.');
            }
        } else {
            $this->addError('Contraseña incorrecta.');
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
     * Obtiene todos los datos necesarios para el panel
     */
    public function getData() {
        return [
            'posts' => is_admin() ? get_all_posts() : [],
            'bans' => is_admin() ? get_active_bans() : [],
            'reports' => is_admin() ? get_all_reports() : []
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
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body>
            <?php $this->renderHeader(); ?>
            <main>
                <?php $this->renderMessages(); ?>
                <?php if (!is_admin()): ?>
                    <?php $this->renderLoginForm(); ?>
                <?php else: ?>
                    <?php $this->renderAdminPanel(); ?>
                <?php endif; ?>
            </main>
            <?php $this->renderFooter(); ?>
            <script src="assets/js/script.js"></script>
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
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
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
        ?>
        <!-- Navegación entre secciones -->
        <nav class="admin-nav">
            <ul>
                <h2>Herramientas de Moderación</h2>
                <li><a href="#ban-ip" onclick="showSection('ban-ip')">Banear IP</a></li>
                <li><a href="#bans-activos" onclick="showSection('bans-activos')">Bans Activos</a></li>
                <li><a href="#moderar-posts" onclick="showSection('moderar-posts')">Moderar Posts</a></li>
                <li><a href="#reportes-usuarios" onclick="showSection('reportes-usuarios')">Reportes de Usuarios</a></li>
            </ul>
        </nav>

        <?php
        $this->renderBanIpSection();
        $this->renderActiveBansSection();
        $this->renderModeratePostsSection();
        $this->renderReportsSection();
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
                <?php echo parse_references($post['message'], true); ?>
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
                <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                     alt="<?php echo htmlspecialchars($post['image_original_name']); ?>" 
                     onclick="toggleImageSize(this)">
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