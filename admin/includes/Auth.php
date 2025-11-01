<?php
/**
 * Auth - Sistema de autenticación para el panel de administración
 */

class Auth {
    public $pdo; // Hacer público para acceso desde AdminController
    private $session_duration = 3600; // 1 hora
    
    public function __construct() {
        // Cargar configuración si no está cargada
        if (!defined('DB_HOST')) {
            require_once dirname(__DIR__, 2) . '/config.php';
        }
        
        // Crear conexión PDO directamente
        $this->pdo = $this->createConnection();
        
        // Iniciar sesión si no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->cleanExpiredSessions();
    }
    
    /**
     * Crea la conexión a la base de datos
     */
    private function createConnection() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            return new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Error de conexión Auth: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos.");
        }
    }
    
    /**
     * Autentica un usuario
     */
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, password, email, role, is_active, last_login
                FROM users 
                WHERE username = ? AND is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }
            
            // Crear sesión
            $session_token = $this->generateSessionToken();
            $expires_at = date('Y-m-d H:i:s', time() + $this->session_duration);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user['id'],
                $session_token,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $expires_at
            ]);
            
            // Actualizar último login
            $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                     ->execute([$user['id']]);
            
            // Guardar en sesión
            $_SESSION['admin_session_token'] = $session_token;
            $_SESSION['admin_user_id'] = $user['id'];
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Auth login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si hay una sesión activa
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_session_token'])) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        return $user !== null;
    }
    
    /**
     * Obtiene el usuario actual de la sesión
     */
    public function getCurrentUser() {
        if (!isset($_SESSION['admin_session_token'])) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.role, u.is_active, u.last_login, u.created_at
                FROM users u
                INNER JOIN user_sessions s ON u.id = s.user_id
                WHERE s.session_token = ? AND s.expires_at > NOW() AND s.is_active = 1 AND u.is_active = 1
            ");
            
            $stmt->execute([$_SESSION['admin_session_token']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Renovar sesión si está próxima a expirar
                $this->renewSession($_SESSION['admin_session_token']);
                return $user;
            }
            
            // Si no hay usuario válido, limpiar sesión
            $this->clearSession();
            return null;
            
        } catch (PDOException $e) {
            error_log("Auth getCurrentUser error: " . $e->getMessage());
            $this->clearSession();
            return null;
        }
    }
    
    /**
     * Cierra la sesión actual
     */
    public function logout() {
        if (isset($_SESSION['admin_session_token'])) {
            try {
                // Desactivar sesión en base de datos
                $stmt = $this->pdo->prepare("
                    UPDATE user_sessions 
                    SET is_active = 0 
                    WHERE session_token = ?
                ");
                $stmt->execute([$_SESSION['admin_session_token']]);
            } catch (PDOException $e) {
                error_log("Auth logout error: " . $e->getMessage());
            }
        }
        
        $this->clearSession();
    }
    
    /**
     * Crea un nuevo usuario
     */
    public function createUser($data, $created_by_id) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, email, role, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['email'] ?? null,
                $data['role'],
                $created_by_id
            ]);
            
        } catch (PDOException $e) {
            error_log("Auth createUser error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un usuario
     */
    public function updateUser($user_id, $data) {
        try {
            $fields = [];
            $values = [];
            
            if (isset($data['username'])) {
                $fields[] = 'username = ?';
                $values[] = $data['username'];
            }
            
            if (isset($data['email'])) {
                $fields[] = 'email = ?';
                $values[] = $data['email'];
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = 'password = ?';
                $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (isset($data['role'])) {
                $fields[] = 'role = ?';
                $values[] = $data['role'];
            }
            
            if (isset($data['is_active'])) {
                $fields[] = 'is_active = ?';
                $values[] = $data['is_active'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = 'updated_at = NOW()';
            $values[] = $user_id;
            
            $stmt = $this->pdo->prepare("
                UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?
            ");
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Auth updateUser error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene todos los usuarios
     */
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, creator.username as created_by_username
                FROM users u
                LEFT JOIN users creator ON u.created_by = creator.id
                ORDER BY u.role ASC, u.username ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Auth getAllUsers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un usuario por ID
     */
    public function getUserById($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, creator.username as created_by_username
                FROM users u
                LEFT JOIN users creator ON u.created_by = creator.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Auth getUserById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Elimina un usuario
     */
    public function deleteUser($user_id) {
        try {
            // No permitir eliminar el último administrador
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
            $stmt->execute();
            $admin_count = $stmt->fetchColumn();
            
            $user = $this->getUserById($user_id);
            if ($user['role'] === 'admin' && $admin_count <= 1) {
                return false; // No eliminar el último admin
            }
            
            // Desactivar en lugar de eliminar para mantener integridad referencial
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$user_id]);
            
        } catch (PDOException $e) {
            error_log("Auth deleteUser error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Genera un token de sesión único
     */
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Renueva la sesión si está próxima a expirar
     */
    private function renewSession($token) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_sessions 
                SET expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
                WHERE session_token = ? AND expires_at > NOW()
            ");
            $stmt->execute([$this->session_duration, $token]);
        } catch (PDOException $e) {
            error_log("Auth renewSession error: " . $e->getMessage());
        }
    }
    
    /**
     * Limpia la sesión PHP
     */
    private function clearSession() {
        unset($_SESSION['admin_session_token']);
        unset($_SESSION['admin_user_id']);
    }
    
    /**
     * Limpia sesiones expiradas de la base de datos
     */
    private function cleanExpiredSessions() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_sessions 
                WHERE expires_at < NOW() OR is_active = 0
            ");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Auth cleanExpiredSessions error: " . $e->getMessage());
        }
    }
}