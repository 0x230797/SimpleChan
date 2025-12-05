<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'simplechan_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Configuraciones generales
define('ADMIN_PASSWORD', getenv('SIMPLECHAN_ADMIN_PASSWORD') ?: 'admin123'); // Solo para retrocompatibilidad
define('ADMIN_PASSWORD_HASH', getenv('SIMPLECHAN_ADMIN_HASH') ?: ''); // Configurar con password_hash
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_DIR', 'uploads/');
define('UPLOAD_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// Crear directorio de uploads si no existe
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Función para limpiar entrada
function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función para obtener IP del usuario
function get_user_ip() {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $ip_list = explode(',', $_SERVER[$key]);
        foreach ($ip_list as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}
?>