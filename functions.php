<?php
require_once 'config.php';

function ensure_session_started() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function get_csrf_token($form_key = 'default') {
    ensure_session_started();
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    $token_data = $_SESSION['csrf_tokens'][$form_key] ?? null;
    if (!$token_data || $token_data['expires'] < time()) {
        $_SESSION['csrf_tokens'][$form_key] = [
            'value' => bin2hex(random_bytes(32)),
            'expires' => time() + 1800,
        ];
    }
    return $_SESSION['csrf_tokens'][$form_key]['value'];
}

function validate_csrf_token($token, $form_key = 'default') {
    ensure_session_started();
    if (empty($token) || empty($_SESSION['csrf_tokens'][$form_key])) {
        return false;
    }
    $token_data = $_SESSION['csrf_tokens'][$form_key];
    $is_valid = hash_equals($token_data['value'], $token) && $token_data['expires'] >= time();
    if ($is_valid) {
        unset($_SESSION['csrf_tokens'][$form_key]);
    }
    return $is_valid;
}

function verify_admin_password($password) {
    $hash = defined('ADMIN_PASSWORD_HASH') ? ADMIN_PASSWORD_HASH : '';
    if (!empty($hash)) {
        if (password_verify($password, $hash)) {
            return true;
        }
    }
    return hash_equals(ADMIN_PASSWORD, $password);
}

// Función para verificar si un usuario está baneado
function is_user_banned() {
    global $pdo;
    $ip = get_user_ip();
    
    $stmt = $pdo->prepare("SELECT * FROM bans WHERE ip_address = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$ip]);
    
    $ban = $stmt->fetch(PDO::FETCH_ASSOC);
    return $ban !== false ? $ban : false;
}

// Función para subir imagen
function upload_image($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Carga de archivo inválida.'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB.'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    if ($mime_type === false || !in_array($mime_type, ALLOWED_MIME_TYPES, true)) {
        return ['success' => false, 'error' => 'El tipo de archivo no coincide con el contenido.'];
    }
    
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $filepath = UPLOAD_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'original_name' => $file['name']
        ];
    } else {
        return ['success' => false, 'error' => 'Error al subir el archivo.'];
    }
}

// Función para crear un post
function create_post($name, $subject, $message, $image_filename, $image_original_name, $parent_id = null) {
    global $pdo;
    
    // Asegurar que el nombre nunca esté vacío
    if (empty(trim($name))) {
        $name = 'Anónimo';
    }
    
    // Validar formatos reservados antes de crear un post
    if (!validate_admin_formats($message, $name === 'Administrador')) {
        return false; // Rechazar el post si contiene formatos reservados y no es administrador
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (name, subject, message, image_filename, image_original_name, ip_address, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $subject, $message, $image_filename, $image_original_name, get_user_ip(), $parent_id]);
    } catch (PDOException $e) {
        return false;
    }
}

// Función para obtener un post por su id
function get_post($post_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND is_deleted = 0 LIMIT 1");
    $stmt->execute([$post_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para obtener posts
function get_posts($limit = 50) {
    global $pdo;
    
    // Validar que el límite sea un entero positivo
    $limit = max(1, min(200, (int)$limit));
    
    $stmt = $pdo->query(
        "SELECT * FROM posts WHERE is_deleted = 0 ORDER BY is_pinned DESC, created_at DESC LIMIT " . $limit
    );
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener respuestas de un post
function get_replies($parent_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE parent_id = ? AND is_deleted = 0 ORDER BY created_at ASC");
    $stmt->execute([$parent_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para verificar sesión de admin
function is_admin() {
    if (!isset($_SESSION['admin_token'])) {
        return false;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT ip_address FROM admin_sessions WHERE session_token = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['admin_token']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($session && hash_equals($session['ip_address'], get_user_ip())) {
        return true;
    }
    return false;
}

// Función para crear sesión de admin
function create_admin_session() {
    global $pdo;
    ensure_session_started();
    
    $token = bin2hex(random_bytes(32));
    $ip = get_user_ip();
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
    $pdo->exec("DELETE FROM admin_sessions WHERE expires_at <= NOW()");
    $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE ip_address = ?");
    $stmt->execute([$ip]);
    session_regenerate_id(true);
    
    $stmt = $pdo->prepare("INSERT INTO admin_sessions (session_token, ip_address, expires_at) VALUES (?, ?, ?)");
    if ($stmt->execute([$token, $ip, $expires])) {
        $_SESSION['admin_token'] = $token;
        return true;
    }
    
    return false;
}

// Función para eliminar post
function delete_post($post_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$post_id]);
    } catch (PDOException $e) {
        return false;
    }
}

// Función para banear IP
function ban_ip($ip_address, $reason = '', $duration_hours = null) {
    global $pdo;
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    try {
        $expires_at = $duration_hours ? date('Y-m-d H:i:s', time() + ($duration_hours * 3600)) : null;
        
        $stmt = $pdo->prepare("INSERT INTO bans (ip_address, reason, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$ip_address, $reason, $expires_at]);
    } catch (PDOException $e) {
        return false;
    }
}

// Función para obtener todos los posts para admin
function get_all_posts() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function update_post_image($post_id, $filename, $original_name = null) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE posts SET image_filename = ?, image_original_name = ? WHERE id = ?");
    return $stmt->execute([$filename, $original_name, $post_id]);
}

// Función para crear un reporte
function create_report($post_id, $reason, $details, $reporter_ip) {
    global $pdo;
    try {
        $safe_reason = mb_substr($reason, 0, 100);
        $safe_details = mb_substr($details, 0, 500);
        $stmt = $pdo->prepare("INSERT INTO reports (post_id, reason, details, reporter_ip) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$post_id, $safe_reason, $safe_details, $reporter_ip]);
    } catch (PDOException $e) {
        return false;
    }
}

// Función para obtener todos los reportes
function get_all_reports() {
    global $pdo;
    $stmt = $pdo->query("SELECT r.*, p.name, p.subject FROM reports r LEFT JOIN posts p ON r.post_id = p.id ORDER BY r.created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para eliminar un reporte por su ID
function delete_report($report_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    return $stmt->execute([$report_id]);
}

// Función para obtener bans activos
function get_active_bans() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM bans WHERE is_active = 1 ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para desbanear IP
function unban_ip($ban_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE bans SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$ban_id]);
    } catch (PDOException $e) {
        return false;
    }
}

// Función para bloquear una publicación
function lock_post($post_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE posts SET is_locked = 1 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

// Función para desbloquear una publicación
function unlock_post($post_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE posts SET is_locked = 0 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

// Función para fijar una publicación
function pin_post($post_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE posts SET is_pinned = 1 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

// Función para desfijar una publicación
function unpin_post($post_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE posts SET is_pinned = 0 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

// Función para convertir >>id en enlaces HTML en los mensajes
function parse_references($text, $is_admin = false) {
    // 1. Primero procesar entidades HTML y preparar el texto
    if ($is_admin) {
        // Convertir entidades HTML a caracteres reales primero
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Luego convertir etiquetas HTML escapadas a etiquetas reales
        $text = preg_replace('/&lt;(h1|h2|span|div)([^&]*)&gt;/i', '<$1$2>', $text);
        $text = preg_replace('/&lt;\/(h1|h2|span|div)&gt;/i', '</$1>', $text);
    } else {
        // Para usuarios normales, solo desescapar >
        $text = str_replace('&gt;', '>', $text);
    }

    // 2. Separar por líneas para procesar formato
    $lines = preg_split('/\r?\n/', $text);
    
    foreach ($lines as &$line) {
        // Verificar si la línea ya contiene HTML válido
        $has_html = preg_match('/<[^>]+>/', $line);
        
        // Solo aplicar formatos de texto si no hay HTML o si es admin
        if (!$has_html || $is_admin) {
            // Aplicar formatos de texto
            $line = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $line);
            $line = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $line);
            $line = preg_replace('/~(.+?)~/s', '<s>$1</s>', $line);
            $line = preg_replace('/_(.+?)_/s', '<u>$1</u>', $line);
            $line = preg_replace('/\[spoiler\](.+?)\[\/spoiler\]/is', '<span class="spoiler">$1</span>', $line);
        }
        
        // Convertir referencias >>id en enlaces (siempre)
        $line = preg_replace('/>>([0-9]+)/', '<a href="#post-$1" class="ref-link">&gt;&gt;$1</a>', $line);
        
        // Verificar nuevamente si hay HTML después de los formatos
        $has_html_after = preg_match('/<[^>]+>/', $line);
        
        // Aplicar greentext y pinktext solo si no hay HTML
        if (!$has_html_after) {
            // Greentext: líneas que empiezan con >
            if (preg_match('/^>(.*)$/', $line, $matches)) {
                $line = '<span class="greentext">&gt;' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</span>';
            }
            // Pinktext: líneas que empiezan con <
            elseif (preg_match('/^<(.*)$/', $line, $matches)) {
                $line = '<span class="pinktext">&lt;' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</span>';
            }
            // Pinktext para &lt; ya escapado
            elseif (preg_match('/^&lt;(.*)$/', $line, $matches)) {
                $line = '<span class="pinktext">&lt;' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</span>';
            }
        }
        
        // Para administradores, cerrar etiquetas abiertas automáticamente
        if ($is_admin && $has_html_after) {
            // Array para rastrear etiquetas abiertas
            $open_tags = [];
            
            // Buscar etiquetas de apertura
            if (preg_match_all('/<(h1|h2|div|span)([^>]*)>/i', $line, $open_matches, PREG_SET_ORDER)) {
                foreach ($open_matches as $match) {
                    $open_tags[] = $match[1];
                }
            }
            
            // Buscar etiquetas de cierre
            if (preg_match_all('/<\/(h1|h2|div|span)>/i', $line, $close_matches, PREG_SET_ORDER)) {
                foreach ($close_matches as $match) {
                    $key = array_search($match[1], $open_tags);
                    if ($key !== false) {
                        unset($open_tags[$key]);
                    }
                }
            }
            
            // Cerrar etiquetas que quedaron abiertas
            foreach (array_reverse($open_tags) as $tag) {
                $line .= '</' . $tag . '>';
            }
        }
    }
    
    // 3. Unir líneas con <br>
    $text = implode('<br>', $lines);

    // 4. Para usuarios normales, eliminar etiquetas HTML avanzadas
    if (!$is_admin) {
        $text = preg_replace('/<\/?(?:h1|h2|div)(?:[^>]*)>/i', '', $text);
    }
    
    return $text;
}

// Validar que los formatos reservados solo sean usados por el administrador
function validate_admin_formats($message, $is_admin) {
    // Lista de formatos reservados para el administrador
    $reserved_formats = ['<h1>', '<h2>', '<color>', '<center>'];

    foreach ($reserved_formats as $format) {
        if (strpos($message, $format) !== false && !$is_admin) {
            return false;
        }
    }

    return true;
}
?>