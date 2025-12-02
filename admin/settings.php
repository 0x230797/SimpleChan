<?php
/**
 * Configuración Global - Panel de Administración SimpleChan
 */

require_once 'includes/AdminController.php';
require_once 'includes/AdminTemplate.php';

class SettingsController extends AdminController {
    
    public function __construct() {
        parent::__construct();
        
        // Solo administradores pueden acceder
        $this->requireAdmin();
        
        $this->processRequests();
    }
    
    private function processRequests() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequests();
        }
    }
    
    private function handlePostRequests() {
        $redirect = false;
        
        // Guardar configuración general
        if (isset($_POST['save_general_settings'])) {
            $redirect = $this->saveGeneralSettings();
        }
        
        // Guardar configuración de archivos
        if (isset($_POST['save_file_settings'])) {
            $redirect = $this->saveFileSettings();
        }
        
        // Guardar configuración de seguridad
        if (isset($_POST['save_security_settings'])) {
            $redirect = $this->saveSecuritySettings();
        }
        
        // Limpiar archivos temporales
        if (isset($_POST['cleanup_temp_files'])) {
            $redirect = $this->cleanupTempFiles();
        }
        
        // Optimizar base de datos
        if (isset($_POST['optimize_database'])) {
            $redirect = $this->optimizeDatabase();
        }
        
        if ($redirect) {
            $this->redirect('settings.php');
        }
    }
    
    private function saveGeneralSettings() {
        $site_name = $this->cleanInput($_POST['site_name'] ?? '');
        $site_description = $this->cleanInput($_POST['site_description'] ?? '');
        $admin_email = $this->cleanInput($_POST['admin_email'] ?? '');
        $default_theme = $this->cleanInput($_POST['default_theme'] ?? '');
        $posts_per_page = max(5, min(100, (int)($_POST['posts_per_page'] ?? 20)));
        $enable_captcha = isset($_POST['enable_captcha']);
        $maintenance_mode = isset($_POST['maintenance_mode']);
        
        if (empty($site_name)) {
            $this->addError('El nombre del sitio es obligatorio.');
            return false;
        }
        
        if (!empty($admin_email) && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('El email del administrador no es válido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Actualizar configuraciones
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'admin_email' => $admin_email,
                'default_theme' => $default_theme,
                'posts_per_page' => $posts_per_page,
                'enable_captcha' => $enable_captcha ? 1 : 0,
                'maintenance_mode' => $maintenance_mode ? 1 : 0
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)
                ");
                $stmt->execute([$key, $value]);
            }
            
            $this->addSuccess('Configuración general guardada correctamente.');
            $this->logActivity('update_general_settings', json_encode($settings));
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error saving general settings: " . $e->getMessage());
            $this->addError('Error al guardar la configuración general.');
            return false;
        }
    }
    
    private function saveFileSettings() {
        $max_file_size = max(1, min(50, (int)($_POST['max_file_size'] ?? 8))); // MB
        $allowed_extensions = $this->cleanInput($_POST['allowed_extensions'] ?? '');
        $image_quality = max(10, min(100, (int)($_POST['image_quality'] ?? 85)));
        $max_image_width = max(100, min(5000, (int)($_POST['max_image_width'] ?? 2000)));
        $max_image_height = max(100, min(5000, (int)($_POST['max_image_height'] ?? 2000)));
        $create_thumbnails = isset($_POST['create_thumbnails']);
        $thumbnail_size = max(50, min(500, (int)($_POST['thumbnail_size'] ?? 200)));
        
        // Validar extensiones
        $extensions = array_map('trim', explode(',', $allowed_extensions));
        $valid_extensions = [];
        foreach ($extensions as $ext) {
            $ext = strtolower(ltrim($ext, '.'));
            if (preg_match('/^[a-z0-9]+$/', $ext)) {
                $valid_extensions[] = $ext;
            }
        }
        
        if (empty($valid_extensions)) {
            $this->addError('Debe especificar al menos una extensión de archivo válida.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            $settings = [
                'max_file_size' => $max_file_size * 1024 * 1024, // Convertir a bytes
                'allowed_extensions' => implode(',', $valid_extensions),
                'image_quality' => $image_quality,
                'max_image_width' => $max_image_width,
                'max_image_height' => $max_image_height,
                'create_thumbnails' => $create_thumbnails ? 1 : 0,
                'thumbnail_size' => $thumbnail_size
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)
                ");
                $stmt->execute([$key, $value]);
            }
            
            $this->addSuccess('Configuración de archivos guardada correctamente.');
            $this->logActivity('update_file_settings', json_encode($settings));
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error saving file settings: " . $e->getMessage());
            $this->addError('Error al guardar la configuración de archivos.');
            return false;
        }
    }
    
    private function saveSecuritySettings() {
        $max_posts_per_hour = max(1, min(100, (int)($_POST['max_posts_per_hour'] ?? 10)));
        $ban_duration_default = max(1, min(8760, (int)($_POST['ban_duration_default'] ?? 24))); // horas
        $auto_ban_reports = max(3, min(50, (int)($_POST['auto_ban_reports'] ?? 10)));
        $enable_ip_logging = isset($_POST['enable_ip_logging']);
        $enable_user_agents = isset($_POST['enable_user_agents']);
        $blocked_words = $this->cleanInput($_POST['blocked_words'] ?? '');
        $require_subject = isset($_POST['require_subject']);
        $min_message_length = max(1, min(1000, (int)($_POST['min_message_length'] ?? 10)));
        
        $pdo = $this->auth->pdo;
        
        try {
            $settings = [
                'max_posts_per_hour' => $max_posts_per_hour,
                'ban_duration_default' => $ban_duration_default,
                'auto_ban_reports' => $auto_ban_reports,
                'enable_ip_logging' => $enable_ip_logging ? 1 : 0,
                'enable_user_agents' => $enable_user_agents ? 1 : 0,
                'blocked_words' => $blocked_words,
                'require_subject' => $require_subject ? 1 : 0,
                'min_message_length' => $min_message_length
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)
                ");
                $stmt->execute([$key, $value]);
            }
            
            $this->addSuccess('Configuración de seguridad guardada correctamente.');
            $this->logActivity('update_security_settings', json_encode($settings));
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error saving security settings: " . $e->getMessage());
            $this->addError('Error al guardar la configuración de seguridad.');
            return false;
        }
    }
    
    private function cleanupTempFiles() {
        try {
            $upload_dir = ADMIN_UPLOAD_PATH;
            $temp_dir = $upload_dir . 'temp/';
            $deleted_files = 0;
            
            if (is_dir($temp_dir)) {
                $files = glob($temp_dir . '*');
                $cutoff_time = time() - (24 * 3600); // 24 horas
                
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $cutoff_time) {
                        if (unlink($file)) {
                            $deleted_files++;
                        }
                    }
                }
            }
            
            // Limpiar imágenes huérfanas (sin post asociado)
            $stmt = $this->auth->pdo->prepare("
                SELECT image_filename FROM posts 
                WHERE image_filename IS NOT NULL AND image_filename != ''
            ");
            $stmt->execute();
            $used_images = array_column($stmt->fetchAll(), 'image_filename');
            
            $image_files = glob($upload_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            foreach ($image_files as $image_file) {
                $filename = basename($image_file);
                if (!in_array($filename, $used_images)) {
                    if (unlink($image_file)) {
                        $deleted_files++;
                    }
                }
            }
            
            $this->addSuccess("Limpieza completada. Se eliminaron $deleted_files archivos.");
            $this->logActivity('cleanup_temp_files', "Deleted files: $deleted_files");
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error cleaning up temp files: " . $e->getMessage());
            $this->addError('Error al limpiar archivos temporales.');
            return false;
        }
    }
    
    private function optimizeDatabase() {
        try {
            $pdo = $this->auth->pdo;
            $optimized_tables = 0;
            
            // Obtener todas las tablas
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $stmt = $pdo->prepare("OPTIMIZE TABLE `$table`");
                if ($stmt->execute()) {
                    $optimized_tables++;
                }
            }
            
            // Limpiar sesiones expiradas
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $stmt->execute();
            $expired_sessions = $stmt->rowCount();
            
            // Limpiar logs antiguos (más de 30 días)
            $stmt = $pdo->prepare("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            $old_logs = $stmt->rowCount();
            
            $this->addSuccess("Optimización completada. $optimized_tables tablas optimizadas, $expired_sessions sesiones expiradas eliminadas, $old_logs logs antiguos eliminados.");
            $this->logActivity('optimize_database', "Tables: $optimized_tables, Sessions: $expired_sessions, Logs: $old_logs");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error optimizing database: " . $e->getMessage());
            $this->addError('Error al optimizar la base de datos.');
            return false;
        }
    }
    
    public function getSetting($key, $default = '') {
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            
            return $result !== false ? $result : $default;
            
        } catch (PDOException $e) {
            error_log("Error getting setting: " . $e->getMessage());
            return $default;
        }
    }
    
    public function getAllSettings() {
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Valores por defecto
            $defaults = [
                'site_name' => 'SimpleChan',
                'site_description' => 'Un imageboard simple y funcional',
                'admin_email' => '',
                'default_theme' => 'default',
                'posts_per_page' => '20',
                'enable_captcha' => '0',
                'maintenance_mode' => '0',
                'max_file_size' => '8388608', // 8MB en bytes
                'allowed_extensions' => 'jpg,jpeg,png,gif,webp',
                'image_quality' => '85',
                'max_image_width' => '2000',
                'max_image_height' => '2000',
                'create_thumbnails' => '1',
                'thumbnail_size' => '200',
                'max_posts_per_hour' => '10',
                'ban_duration_default' => '24',
                'auto_ban_reports' => '10',
                'enable_ip_logging' => '1',
                'enable_user_agents' => '0',
                'blocked_words' => '',
                'require_subject' => '0',
                'min_message_length' => '10'
            ];
            
            return array_merge($defaults, $settings);
            
        } catch (PDOException $e) {
            error_log("Error getting all settings: " . $e->getMessage());
            return [];
        }
    }
    
    public function getSystemInfo() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'disk_free_space' => $this->formatBytes(disk_free_space('.')),
            'disk_total_space' => $this->formatBytes(disk_total_space('.')),
        ];
    }
    
    private function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($size, 1024);
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }
}

class SettingsView {
    private $controller;
    private $settings;
    private $system_info;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->settings = $controller->getAllSettings();
        $this->system_info = $controller->getSystemInfo();
    }
    
    public function render() {
        $user = $this->controller->getCurrentUser();
        
        AdminTemplate::renderHeader('Configuración del Sitio', $user);
        ?>
        
        <div style="display: flex;margin: 20px auto;">
            <?php AdminTemplate::renderSidebar('settings', $user['role']); ?>
            
            <main style="flex: 1;">
                <?php AdminTemplate::renderMessages($this->controller->getMessages()); ?>
                <?php $this->renderSettingsManagement(); ?>
            </main>
        </div>
        
        <?php AdminTemplate::renderFooter(); ?>
        <?php
    }
    
    private function renderHeader() {
        $user = $this->controller->getCurrentUser();
        ?>
        <header class="admin-header">
            <div class="admin-header-content">
                <h1>SimpleChan Admin</h1>
                <div class="admin-user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="user-role">(<?php echo ucfirst($user['role']); ?>)</span>
                    <a href="?logout=1" class="logout-btn">Cerrar Sesión</a>
                </div>
            </div>
        </header>
        <?php
    }
    
    private function renderSidebar() {
        ?>
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php"><i>🏠</i> Dashboard</a></li>
                    <li><a href="posts.php"><i>📄</i> Moderar Posts</a></li>
                    <li><a href="bans.php"><i>🚫</i> Gestión de Bans</a></li>
                    <li><a href="reports.php"><i>🚨</i> Reportes</a></li>
                    <li class="nav-divider"></li>
                    <li><a href="users.php"><i>👥</i> Gestión de Usuarios</a></li>
                    <li><a href="boards.php"><i>📋</i> Gestión de Tablones</a></li>
                    <li><a href="settings.php" class="active"><i>⚙️</i> Configuración</a></li>
                    <li class="nav-divider"></li>
                    <li><a href="../index.php" target="_blank"><i>🌐</i> Ver Sitio</a></li>
                </ul>
            </nav>
        </aside>
        <?php
    }
    
    private function renderMessages() {
        $messages = $this->controller->getMessages();
        
        foreach (['error', 'success', 'info'] as $type) {
            if (!empty($messages[$type])) {
                foreach ($messages[$type] as $message) {
                    echo '<div class="message message-' . $type . '">' . htmlspecialchars($message) . '</div>';
                }
            }
        }
    }
    
    private function renderSettingsManagement() {
        ?>
        <div class="settings-management">
            <h2>Configuración Global del Sitio</h2>
            
            <!-- Información del Sistema -->
            <?php $this->renderSystemInfo(); ?>
            
            <div class="settings-tabs">
                <nav class="tab-nav">
                    <button class="tab-btn active" data-tab="general">General</button>
                    <button class="tab-btn" data-tab="files">Archivos</button>
                    <button class="tab-btn" data-tab="security">Seguridad</button>
                    <button class="tab-btn" data-tab="maintenance">Mantenimiento</button>
                </nav>
                
                <!-- Configuración General -->
                <div id="tab-general" class="tab-content active">
                    <?php $this->renderGeneralSettings(); ?>
                </div>
                
                <!-- Configuración de Archivos -->
                <div id="tab-files" class="tab-content">
                    <?php $this->renderFileSettings(); ?>
                </div>
                
                <!-- Configuración de Seguridad -->
                <div id="tab-security" class="tab-content">
                    <?php $this->renderSecuritySettings(); ?>
                </div>
                
                <!-- Herramientas de Mantenimiento -->
                <div id="tab-maintenance" class="tab-content">
                    <?php $this->renderMaintenanceTools(); ?>
                </div>
            </div>
        </div>
        
        <script>
        // Sistema de pestañas
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.dataset.tab;
                
                // Actualizar botones
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Actualizar contenido
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
        </script>
        <?php
    }
    
    private function renderSystemInfo() {
        ?>
        <div class="system-info">
            <h3>Información del Sistema</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>PHP Version:</strong> <?php echo $this->system_info['php_version']; ?>
                </div>
                <div class="info-item">
                    <strong>Servidor:</strong> <?php echo htmlspecialchars($this->system_info['server_software']); ?>
                </div>
                <div class="info-item">
                    <strong>Memoria límite:</strong> <?php echo $this->system_info['memory_limit']; ?>
                </div>
                <div class="info-item">
                    <strong>Tiempo ejecución:</strong> <?php echo $this->system_info['max_execution_time']; ?>s
                </div>
                <div class="info-item">
                    <strong>Upload máximo:</strong> <?php echo $this->system_info['upload_max_filesize']; ?>
                </div>
                <div class="info-item">
                    <strong>Espacio libre:</strong> <?php echo $this->system_info['disk_free_space']; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function renderGeneralSettings() {
        ?>
        <form method="POST" class="settings-form">
            <h3>Configuración General</h3>
            
            <div class="form-group">
                <label for="site_name">Nombre del Sitio:</label>
                <input type="text" id="site_name" name="site_name" 
                       value="<?php echo htmlspecialchars($this->settings['site_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="site_description">Descripción del Sitio:</label>
                <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($this->settings['site_description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="admin_email">Email del Administrador:</label>
                <input type="email" id="admin_email" name="admin_email" 
                       value="<?php echo htmlspecialchars($this->settings['admin_email']); ?>">
            </div>
            
            <div class="form-group">
                <label for="default_theme">Tema por Defecto:</label>
                <select id="default_theme" name="default_theme">
                    <option value="default" <?php echo $this->settings['default_theme'] === 'default' ? 'selected' : ''; ?>>Default</option>
                    <option value="dark" <?php echo $this->settings['default_theme'] === 'dark' ? 'selected' : ''; ?>>Oscuro</option>
                    <option value="light" <?php echo $this->settings['default_theme'] === 'light' ? 'selected' : ''; ?>>Claro</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="posts_per_page">Posts por Página:</label>
                <input type="number" id="posts_per_page" name="posts_per_page" 
                       value="<?php echo (int)$this->settings['posts_per_page']; ?>" 
                       min="5" max="100">
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="enable_captcha" 
                           <?php echo $this->settings['enable_captcha'] ? 'checked' : ''; ?>>
                    Habilitar CAPTCHA
                </label>
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="maintenance_mode" 
                           <?php echo $this->settings['maintenance_mode'] ? 'checked' : ''; ?>>
                    Modo de Mantenimiento
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_general_settings" class="btn btn-primary">Guardar Configuración</button>
            </div>
        </form>
        <?php
    }
    
    private function renderFileSettings() {
        ?>
        <form method="POST" class="settings-form">
            <h3>Configuración de Archivos</h3>
            
            <div class="form-group">
                <label for="max_file_size">Tamaño Máximo de Archivo (MB):</label>
                <input type="number" id="max_file_size" name="max_file_size" 
                       value="<?php echo (int)($this->settings['max_file_size'] / 1024 / 1024); ?>" 
                       min="1" max="50">
            </div>
            
            <div class="form-group">
                <label for="allowed_extensions">Extensiones Permitidas (separadas por coma):</label>
                <input type="text" id="allowed_extensions" name="allowed_extensions" 
                       value="<?php echo htmlspecialchars($this->settings['allowed_extensions']); ?>"
                       placeholder="jpg,png,gif,webp">
            </div>
            
            <div class="form-group">
                <label for="image_quality">Calidad de Imagen (%):</label>
                <input type="number" id="image_quality" name="image_quality" 
                       value="<?php echo (int)$this->settings['image_quality']; ?>" 
                       min="10" max="100">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="max_image_width">Ancho Máximo (px):</label>
                    <input type="number" id="max_image_width" name="max_image_width" 
                           value="<?php echo (int)$this->settings['max_image_width']; ?>" 
                           min="100" max="5000">
                </div>
                
                <div class="form-group">
                    <label for="max_image_height">Alto Máximo (px):</label>
                    <input type="number" id="max_image_height" name="max_image_height" 
                           value="<?php echo (int)$this->settings['max_image_height']; ?>" 
                           min="100" max="5000">
                </div>
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="create_thumbnails" 
                           <?php echo $this->settings['create_thumbnails'] ? 'checked' : ''; ?>>
                    Crear Miniaturas Automáticamente
                </label>
            </div>
            
            <div class="form-group">
                <label for="thumbnail_size">Tamaño de Miniatura (px):</label>
                <input type="number" id="thumbnail_size" name="thumbnail_size" 
                       value="<?php echo (int)$this->settings['thumbnail_size']; ?>" 
                       min="50" max="500">
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_file_settings" class="btn btn-primary">Guardar Configuración</button>
            </div>
        </form>
        <?php
    }
    
    private function renderSecuritySettings() {
        ?>
        <form method="POST" class="settings-form">
            <h3>Configuración de Seguridad</h3>
            
            <div class="form-group">
                <label for="max_posts_per_hour">Posts Máximos por Hora (por IP):</label>
                <input type="number" id="max_posts_per_hour" name="max_posts_per_hour" 
                       value="<?php echo (int)$this->settings['max_posts_per_hour']; ?>" 
                       min="1" max="100">
            </div>
            
            <div class="form-group">
                <label for="ban_duration_default">Duración de Ban por Defecto (horas):</label>
                <input type="number" id="ban_duration_default" name="ban_duration_default" 
                       value="<?php echo (int)$this->settings['ban_duration_default']; ?>" 
                       min="1" max="8760">
            </div>
            
            <div class="form-group">
                <label for="auto_ban_reports">Auto-Ban después de X reportes:</label>
                <input type="number" id="auto_ban_reports" name="auto_ban_reports" 
                       value="<?php echo (int)$this->settings['auto_ban_reports']; ?>" 
                       min="3" max="50">
            </div>
            
            <div class="form-group">
                <label for="min_message_length">Longitud Mínima de Mensaje:</label>
                <input type="number" id="min_message_length" name="min_message_length" 
                       value="<?php echo (int)$this->settings['min_message_length']; ?>" 
                       min="1" max="1000">
            </div>
            
            <div class="form-group">
                <label for="blocked_words">Palabras Bloqueadas (una por línea):</label>
                <textarea id="blocked_words" name="blocked_words" rows="5" 
                          placeholder="palabra1&#10;palabra2&#10;etc..."><?php echo htmlspecialchars($this->settings['blocked_words']); ?></textarea>
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="enable_ip_logging" 
                           <?php echo $this->settings['enable_ip_logging'] ? 'checked' : ''; ?>>
                    Registrar Direcciones IP
                </label>
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="enable_user_agents" 
                           <?php echo $this->settings['enable_user_agents'] ? 'checked' : ''; ?>>
                    Registrar User Agents
                </label>
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="require_subject" 
                           <?php echo $this->settings['require_subject'] ? 'checked' : ''; ?>>
                    Requerir Asunto en Nuevos Hilos
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_security_settings" class="btn btn-primary">Guardar Configuración</button>
            </div>
        </form>
        <?php
    }
    
    private function renderMaintenanceTools() {
        ?>
        <div class="maintenance-tools">
            <h3>Herramientas de Mantenimiento</h3>
            
            <div class="tool-section">
                <h4>Limpieza de Archivos</h4>
                <p>Elimina archivos temporales y imágenes huérfanas (sin post asociado).</p>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="cleanup_temp_files" class="btn btn-warning"
                            onclick="return confirm('¿Está seguro de querer limpiar archivos temporales?')">
                        Limpiar Archivos Temporales
                    </button>
                </form>
            </div>
            
            <div class="tool-section">
                <h4>Optimización de Base de Datos</h4>
                <p>Optimiza todas las tablas y elimina datos antiguos (sesiones expiradas, logs antiguos).</p>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="optimize_database" class="btn btn-primary"
                            onclick="return confirm('¿Está seguro de querer optimizar la base de datos?')">
                        Optimizar Base de Datos
                    </button>
                </form>
            </div>
            
            <div class="tool-section">
                <h4>Información de Configuración Actual</h4>
                <div class="config-info">
                    <p><strong>Configuración cargada:</strong> <?php echo count($this->settings); ?> valores</p>
                    <p><strong>Última actualización:</strong> 
                        <?php 
                        $pdo = $this->controller->getPDO();
                        $stmt = $pdo->query("SELECT MAX(updated_at) FROM settings");
                        $last_update = $stmt->fetchColumn();
                        echo $last_update ? date('d/m/Y H:i:s', strtotime($last_update)) : 'Nunca';
                        ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function renderFooter() {
        ?>
        <footer class="admin-footer">
            <p>&copy; 2025 SimpleChan - Panel de Administración</p>
        </footer>
        <?php
    }
}

// Inicializar la aplicación
try {
    $controller = new SettingsController();
    $view = new SettingsView($controller);
    $view->render();
} catch (Exception $e) {
    header('Location: login.php');
    exit;
}
?>