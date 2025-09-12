<?php
/**
 * SimpleBoard Configuration File
 * Configuraciones principales del sistema
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// ============================================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================================================

// Configuración de conexión
define('DB_HOST', 'localhost');
define('DB_NAME', 'simplechan_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// CONFIGURACIONES DE SEGURIDAD
// ============================================================================

// Contraseña de administrador (CAMBIAR EN PRODUCCIÓN)
// define('ADMIN_PASSWORD', 'SimpleChanAdmin230797');

// Modo debug (DESACTIVAR EN PRODUCCIÓN)
define('DEBUG_MODE', false);

// ============================================================================
// CONFIGURACIONES DE TIEMPO
// ============================================================================

// Zona horaria (cambiar según tu ubicación)
// Ejemplos: 'America/Mexico_City', 'America/Bogota', 'America/Argentina/Buenos_Aires'
date_default_timezone_set('America/Guayaquil');

// ============================================================================
// CONFIGURACIONES DE ARCHIVOS
// ============================================================================

// Límites de archivos
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Extensiones permitidas
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Directorios
define('UPLOAD_DIR', 'uploads/');

// ============================================================================
// INICIALIZACIÓN DEL SISTEMA
// ============================================================================

// Conexión a base de datos
$pdo = initializeDatabase();

// Crear directorios necesarios
createRequiredDirectories();

// Inicializar manejo global de errores (después de que functions.php esté cargado)
if (function_exists('initialize_global_error_handling')) {
    initialize_global_error_handling();
}

// ============================================================================
// FUNCIONES DE INICIALIZACIÓN
// ============================================================================

/**
 * Inicializa la conexión a la base de datos
 * @return PDO
 * @throws Exception
 */
function initializeDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        // Solo agregar MYSQL_ATTR_INIT_COMMAND si está disponible
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . DB_CHARSET;
        }
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Si no pudimos usar MYSQL_ATTR_INIT_COMMAND, ejecutar SET NAMES manualmente
        if (!defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $pdo->exec("SET NAMES " . DB_CHARSET);
        }
        
        // Configurar zona horaria de MySQL para que coincida con PHP
        $timezone = date('P'); // Obtener offset de PHP (+02:00, -05:00, etc.)
        $pdo->exec("SET time_zone = '$timezone'");
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Error de conexión a BD: " . $e->getMessage());
        die("Error de conexión a la base de datos. Contacte al administrador.");
    }
}

/**
 * Inicializa sesión con opciones seguras y añade cabeceras de seguridad HTTP
 */
function initialize_session() {
    // Evitar re-inicializar
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Configurar cookies de sesión seguras
    $cookieParams = session_get_cookie_params();
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    ini_set('session.use_strict_mode', '1');
    session_start();

    // Cabeceras de seguridad basicas
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // HSTS solo si HTTPS
    if ($secure) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    // CSP muy basica — ajustar según recursos usados
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
}

/**
 * Crea los directorios necesarios para el funcionamiento
 */
function createRequiredDirectories() {
    $directories = [
        UPLOAD_DIR
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("No se pudo crear el directorio: $dir");
            }
        }
    }
}

// ============================================================================
// FUNCIONES UTILITARIAS GLOBALES
// ============================================================================

/**
 * Genera un hash seguro para contraseñas
 * @param string $password
 * @return string
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verifica una contraseña contra su hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genera un token CSRF
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

?>