<?php
/**
 * AdminController - Controlador base para el panel de administración
 */

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/functions.php';
require_once 'Auth.php';

// Definir ruta de uploads desde el contexto del admin
if (!defined('ADMIN_UPLOAD_PATH')) {
    define('ADMIN_UPLOAD_PATH', dirname(__DIR__, 2) . '/' . UPLOAD_DIR);
}

class AdminController {
    protected $auth;
    protected $messages = [];
    protected $current_user = null;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->current_user = $this->auth->getCurrentUser();
        
        // Verificar si el usuario está autenticado
        if (!$this->auth->isLoggedIn()) {
            $this->redirectToLogin();
        }
        
        $this->processGlobalActions();
    }
    
    /**
     * Procesa acciones globales disponibles en todos los módulos
     */
    protected function processGlobalActions() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Logout
            if (isset($_GET['logout'])) {
                $this->logout();
            }
        }
    }
    
    /**
     * Verifica si el usuario actual es administrador
     */
    protected function isAdmin() {
        return $this->current_user && $this->current_user['role'] === 'admin';
    }
    
    /**
     * Verifica si el usuario puede moderar
     */
    protected function canModerate() {
        return $this->current_user && in_array($this->current_user['role'], ['admin', 'moderator']);
    }
    
    /**
     * Requiere permisos de administrador
     */
    protected function requireAdmin() {
        if (!$this->isAdmin()) {
            $this->addError('Acceso denegado. Se requieren permisos de administrador.');
            $this->redirect('index.php');
        }
    }
    
    /**
     * Requiere permisos de moderación
     */
    protected function requireModerator() {
        if (!$this->canModerate()) {
            $this->addError('Acceso denegado. Se requieren permisos de moderación.');
            $this->redirect('index.php');
        }
    }
    
    /**
     * Cierra la sesión
     */
    protected function logout() {
        $this->auth->logout();
        $this->redirect('login.php');
    }
    
    /**
     * Obtiene el usuario actual
     */
    public function getCurrentUser() {
        return $this->current_user;
    }
    
    /**
     * Obtiene mensajes de error y éxito
     */
    public function getMessages() {
        return $this->messages;
    }
    
    /**
     * Obtiene la instancia de PDO
     */
    public function getPDO() {
        return $this->auth->pdo;
    }
    
    /**
     * Añade mensaje de error
     */
    protected function addError($message) {
        $this->messages['error'][] = $message;
    }
    
    /**
     * Añade mensaje de éxito
     */
    protected function addSuccess($message) {
        $this->messages['success'][] = $message;
    }
    
    /**
     * Añade mensaje de información
     */
    protected function addInfo($message) {
        $this->messages['info'][] = $message;
    }
    
    /**
     * Redirige a una página
     */
    protected function redirect($url) {
        // Si la URL no tiene protocolo, asegurar que sea relativa correctamente
        if (!preg_match('/^https?:\/\//', $url)) {
            // Si empieza con /, es absoluta desde la raíz
            if (strpos($url, '/') === 0) {
                $url = $url;
            } else {
                // Si no, es relativa al directorio actual
                $url = './' . ltrim($url, './');
            }
        }
        
        header("Location: $url");
        exit;
    }
    
    /**
     * Redirige al login si no está autenticado
     */
    protected function redirectToLogin() {
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            $this->redirect('login.php');
        }
    }
    
    /**
     * Valida entrada de datos
     */
    protected function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Campo requerido
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "El campo {$field} es requerido.";
                continue;
            }
            
            // Validación solo si el valor no está vacío
            if (!empty($value)) {
                // Longitud mínima
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field] = "El campo {$field} debe tener al menos {$rule['min_length']} caracteres.";
                }
                
                // Longitud máxima
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field] = "El campo {$field} no puede tener más de {$rule['max_length']} caracteres.";
                }
                
                // Email válido
                if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "El campo {$field} debe ser un email válido.";
                }
                
                // IP válida
                if (isset($rule['ip']) && $rule['ip'] && !filter_var($value, FILTER_VALIDATE_IP)) {
                    $errors[$field] = "El campo {$field} debe ser una IP válida.";
                }
                
                // Número entero
                if (isset($rule['integer']) && $rule['integer'] && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $errors[$field] = "El campo {$field} debe ser un número entero.";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Limpia entrada de datos
     */
    protected function cleanInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'cleanInput'], $data);
        }
        return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
    }
    
    /**
     * Log de actividad administrativa
     */
    protected function logActivity($action, $details = '') {
        try {
            // Usar la conexión PDO del sistema de auth
            $pdo = $this->auth->pdo ?? null;
            if (!$pdo) {
                error_log("Admin Activity (no DB): User: {$this->current_user['id']}, Action: $action, Details: $details");
                return;
            }
            
            // Verificar si existe la tabla admin_logs, si no, solo loguear en archivo
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'admin_logs'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                error_log("Admin Activity: User: {$this->current_user['id']}, Action: $action, Details: $details, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                return;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO admin_logs (user_id, action, details, ip_address, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $this->current_user['id'],
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            // Log error pero no interrumpir la operación
            error_log("Error logging admin activity: " . $e->getMessage());
        }
    }
}