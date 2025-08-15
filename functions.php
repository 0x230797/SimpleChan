<?php
require_once 'config.php';

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
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB.'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido.'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;
    
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
    
    $stmt = $pdo->query("SELECT * FROM posts WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT " . $limit);
    
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
    $stmt = $pdo->prepare("SELECT * FROM admin_sessions WHERE session_token = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['admin_token']]);
    
    return $stmt->fetch() !== false;
}

// Función para crear sesión de admin
function create_admin_session() {
    global $pdo;
    
    $token = bin2hex(random_bytes(32));
    $ip = get_user_ip();
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
    
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

// Función para crear un reporte
function create_report($post_id, $reason, $details, $reporter_ip) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO reports (post_id, reason, details, reporter_ip) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$post_id, $reason, $details, $reporter_ip]);
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

// Función para convertir >>id en enlaces HTML en los mensajes
function parse_references($text) {
    // Primero, desescapar los > para que funcione el regex
    $text = str_replace('&gt;', '>', $text);
    // Negrita: **texto**
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $text);
    // Cursiva: *texto*
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    // Tachado: ~~texto~~
    $text = preg_replace('/~~(.+?)~~/s', '<s>$1</s>', $text);
    // Spoiler: [spoiler]texto[/spoiler]
    $text = preg_replace('/\[spoiler\](.+?)\[\/spoiler\]/is', '<span class="spoiler">$1</span>', $text);
    // Referencias >>id
    $text = preg_replace('/>>([0-9]+)/', '<a href="#post-$1" class="ref-link">&gt;&gt;$1</a><br>', $text);

    // Greentext SOLO en líneas que no contienen etiquetas HTML
    $text = preg_replace_callback('/^([^<\n]*)(>[^\s][^<]*?)(<br>|$)/m', function($m) {
        if (strpos($m[1], '<') !== false) return $m[0];
        return $m[1] . '<span class="greentext">' . htmlspecialchars($m[2]) . '</span>' . ($m[3] === '<br>' ? '<br>' : '');
    }, $text);
    
    // Pinktext: soporta < y &lt; al inicio de línea
    $text = preg_replace_callback('/^([^<\n]*)(<[^\s][^<]*?)(<br>|$)/m', function($m) {
        if (strpos($m[1], '<') !== false) return $m[0];
        return $m[1] . '<span class="pinktext">' . htmlspecialchars($m[2]) . '</span>' . ($m[3] === '<br>' ? '<br>' : '');
    }, $text);
    
    $text = preg_replace_callback('/^([^<\n]*)&lt;([^\s][^<]*?)(<br>|$)/m', function($m) {
        if (strpos($m[1], '<') !== false) return $m[0];
        return $m[1] . '<span class="pinktext">&lt;' . htmlspecialchars($m[2]) . '</span>' . ($m[3] === '<br>' ? '<br>' : '');
    }, $text);
    
    // Convertir saltos de línea restantes en <br>
    return str_replace("\n", "<br>", $text);
}
?>
