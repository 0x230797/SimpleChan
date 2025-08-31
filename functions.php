<?php
/**
 * SimpleChan - Functions Library
 * Biblioteca de funciones para el sistema de imageboard
 */

require_once 'config.php';

// ===================================
// UTILIDADES Y HELPERS
// ===================================

/**
 * Obtiene la IP del usuario actual
 * @return string IP del usuario
 */
/**
 * Obtiene la IP real del usuario
 * @return string
 */
function get_user_ip() {
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            
            // Validar IP
            if (filter_var($ip, FILTER_VALIDATE_IP, 
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Limpia y sanitiza la entrada del usuario
 * @param string $input Texto a limpiar
 * @return string Texto limpio
 */
function clean_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Formatea el tamaño de archivos en unidades legibles
 * @param int $bytes Tamaño en bytes
 * @return string Tamaño formateado
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $size = $bytes;
    $unit_index = 0;
    
    while ($size >= 1024 && $unit_index < count($units) - 1) {
        $size /= 1024;
        $unit_index++;
    }
    
    return number_format($size, 2) . ' ' . $units[$unit_index];
}

// ===================================
// GESTIÓN DE BANS Y SEGURIDAD
// ===================================

/**
 * Verifica si un usuario está baneado
 * @param string|null $ip IP a verificar (por defecto la IP actual)
 * @return array|false Información del ban o false si no está baneado
 */
function is_user_banned($ip = null) {
    global $pdo;
    
    if ($ip === null) {
        $ip = get_user_ip();
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM bans 
        WHERE ip_address = ? 
        AND is_active = 1 
        AND (expires_at IS NULL OR expires_at > NOW())
    ");
    $stmt->execute([$ip]);
    
    $ban = $stmt->fetch(PDO::FETCH_ASSOC);
    return $ban !== false ? $ban : false;
}

/**
 * Banea una IP
 * @param string $ip_address IP a banear
 * @param string $reason Razón del ban
 * @param int|null $duration_hours Duración en horas (null = permanente)
 * @return bool Éxito de la operación
 */
function ban_ip($ip_address, $reason = '', $duration_hours = null) {
    global $pdo;
    
    try {
        $expires_at = $duration_hours ? 
            date('Y-m-d H:i:s', time() + ($duration_hours * 3600)) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO bans (ip_address, reason, expires_at) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$ip_address, $reason, $expires_at]);
    } catch (PDOException $e) {
        error_log("Error al banear IP: " . $e->getMessage());
        return false;
    }
}

/**
 * Desbanea una IP
 * @param int $ban_id ID del ban
 * @return bool Éxito de la operación
 */
function unban_ip($ban_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE bans SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$ban_id]);
    } catch (PDOException $e) {
        error_log("Error al desbanear IP: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene todos los bans activos
 * @return array Lista de bans activos
 */
function get_active_bans() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT * FROM bans 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===================================
// GESTIÓN DE TABLONES (BOARDS)
// ===================================

/**
 * Obtiene un tablón por su nombre
 * @param string $name Nombre del tablón
 * @return array|false Datos del tablón o false si no existe
 */
function get_board_by_name($name) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM boards WHERE BINARY name = ? LIMIT 1");
    $stmt->execute([$name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtiene un tablón por su short_id
 * @param string $short_id ID corto del tablón
 * @return array|false Datos del tablón o false si no existe
 */
function get_board_by_short_id($short_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM boards WHERE BINARY short_id = ? LIMIT 1");
    $stmt->execute([$short_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtiene un tablón por su ID
 * @param int $id ID del tablón
 * @return array|false Datos del tablón o false si no existe
 */
function get_board_by_id($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM boards WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtiene todos los tablones disponibles
 * @return array Lista de todos los tablones
 */
function get_all_boards() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT *, IF(is_nsfw = 1, 'NSFW', '') AS nsfw_label 
        FROM boards 
        ORDER BY id ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===================================
// GESTIÓN DE POSTS
// ===================================

/**
 * Crea un nuevo post
 * @param string $name Nombre del autor
 * @param string $subject Asunto del post
 * @param string $message Contenido del mensaje
 * @param string|null $image_filename Nombre del archivo de imagen
 * @param string|null $image_original_name Nombre original de la imagen
 * @param int|null $parent_id ID del post padre (para respuestas)
 * @param int $board_id ID del tablón
 * @return bool Éxito de la operación
 */
function create_post($name, $subject, $message, $image_filename, $image_original_name, $parent_id = null, $board_id) {
    global $pdo;

    // Asegurar que el nombre nunca esté vacío
    if (empty(trim($name))) {
        $name = 'Anónimo';
    }

    // Validar formatos reservados antes de crear un post
    if (!validate_admin_formats($message, $name === 'Administrador')) {
        error_log("Post rechazado por contener formatos reservados");
        return false;
    }

    try {
        $image_size = null;
        $image_dimensions = null;

        if ($image_filename) {
            $image_path = UPLOAD_DIR . $image_filename;
            $image_size = filesize($image_path);
            $dimensions = getimagesize($image_path);
            $image_dimensions = $dimensions[0] . 'x' . $dimensions[1];
        }

        $stmt = $pdo->prepare(
            "INSERT INTO posts (
                name, subject, message, image_filename, 
                image_original_name, image_size, image_dimensions, ip_address, parent_id, board_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $result = $stmt->execute([
            $name, $subject, $message, $image_filename, 
            $image_original_name, $image_size, $image_dimensions, get_user_ip(), $parent_id, $board_id
        ]);

        // Si es una respuesta, actualizar el updated_at del post padre para hacer "bump"
        if ($result && $parent_id) {
            $bump_stmt = $pdo->prepare("UPDATE posts SET updated_at = NOW() WHERE id = ?");
            $bump_stmt->execute([$parent_id]);
        }

        return $result;
    } catch (PDOException $e) {
        error_log("Error al crear post: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene un post por su ID
 * @param int $post_id ID del post
 * @return array|false Datos del post o false si no existe
 */
function get_post($post_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM posts 
        WHERE id = ? AND is_deleted = 0 
        LIMIT 1
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtiene posts con paginación
 * @param int $limit Número máximo de posts
 * @return array Lista de posts
 */
function get_posts($limit = 100) {
    global $pdo;
    
    // Validar que el límite sea un entero positivo
    $limit = max(1, min(100, (int)$limit));
    
    $stmt = $pdo->query("
        SELECT * FROM posts 
        WHERE is_deleted = 0 AND parent_id IS NULL
        ORDER BY is_pinned DESC, updated_at DESC 
        LIMIT " . $limit
    );
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene posts para el index (excluyendo admin, bloqueados y fijados)
 * @param int $limit Número máximo de posts
 * @return array Lista de posts filtrados
 */
function get_posts_for_index($limit = 100) {
    global $pdo;
    
    // Validar que el límite sea un entero positivo
    $limit = max(1, min(100, (int)$limit));
    
    $stmt = $pdo->query("
        SELECT * FROM posts 
        WHERE is_deleted = 0 
        AND parent_id IS NULL
        AND name != 'Administrador'
        ORDER BY updated_at DESC 
        LIMIT " . $limit
    );
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene posts de un tablón específico
 * @param int $board_id ID del tablón
 * @param int $limit Límite de posts
 * @param int $offset Desplazamiento para paginación
 * @return array Lista de posts del tablón
 */
// Validar el valor de $order_column para evitar inyecciones SQL
function get_posts_by_board($board_id, $limit = 50, $offset = 0, $order_column = 'updated_at') {
    global $pdo;

    // Lista de columnas permitidas para ordenar
    $allowed_columns = ['created_at', 'updated_at', '(SELECT COUNT(*) FROM posts WHERE parent_id = posts.id)'];

    // Validar que $order_column sea una de las permitidas
    if (!in_array($order_column, $allowed_columns)) {
        $order_column = 'updated_at'; // Valor predeterminado - ordenar por bump
    }

    $sql = "SELECT * FROM posts 
            WHERE board_id = ? 
            AND parent_id IS NULL 
            AND is_deleted = 0 
            ORDER BY is_pinned DESC, $order_column DESC 
            LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$board_id, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cuenta el total de posts de un tablón
 * @param int $board_id ID del tablón
 * @return int Número de posts
 */
function count_posts_by_board($board_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM posts 
        WHERE board_id = ? 
        AND is_deleted = 0 
        AND parent_id IS NULL
    ");
    $stmt->execute([$board_id]);
    return $stmt->fetchColumn();
}

/**
 * Obtiene respuestas de un post específico
 * @param int $parent_id ID del post padre
 * @return array Lista de respuestas
 */
function get_replies($parent_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM posts 
        WHERE parent_id = ? AND is_deleted = 0 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$parent_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene posts recientes y con respuestas recientes
 * @param int $limit Número máximo de posts
 * @return array Lista de posts populares
 */
function get_recent_and_replied_posts($limit = 8) {
    global $pdo;

    // Posts recientes con nombre del tablón
    $recent_posts = get_recent_posts_with_board_name($limit);
    
    // Posts con respuestas recientes
    $replied_posts = get_replied_posts_with_board_name($limit);

    // Combinar y eliminar duplicados
    $combined_posts = array_merge($recent_posts, $replied_posts);
    $unique_posts = array_unique($combined_posts, SORT_REGULAR);

    return array_slice($unique_posts, 0, $limit);
}

/**
 * Obtiene posts recientes y con respuestas recientes (filtrados para index)
 * @param int $limit Número máximo de posts
 * @return array Lista de posts populares filtrados
 */
function get_recent_and_replied_posts_for_index($limit = 8) {
    global $pdo;

    // Posts recientes con nombre del tablón (filtrados)
    $recent_posts = get_recent_posts_with_board_name_filtered($limit);
    
    // Posts con respuestas recientes (filtrados)
    $replied_posts = get_replied_posts_with_board_name_filtered($limit);

    // Combinar y eliminar duplicados
    $combined_posts = array_merge($recent_posts, $replied_posts);
    $unique_posts = array_unique($combined_posts, SORT_REGULAR);

    return array_slice($unique_posts, 0, $limit);
}

/**
 * Obtiene posts recientes con nombre del tablón (filtrados)
 * @param int $limit Límite de posts
 * @return array Lista de posts recientes filtrados
 */
function get_recent_posts_with_board_name_filtered($limit) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, b.name AS board_name 
        FROM posts p
        JOIN boards b ON p.board_id = b.id
        WHERE p.is_deleted = 0 
        AND p.parent_id IS NULL
        AND p.name != 'Administrador'
        ORDER BY p.updated_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene posts con respuestas recientes (filtrados)
 * @param int $limit Límite de posts
 * @return array Lista de posts con respuestas filtrados
 */
function get_replied_posts_with_board_name_filtered($limit) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, b.name AS board_name 
        FROM posts p
        JOIN boards b ON p.board_id = b.id
        JOIN posts r ON p.id = r.parent_id
        WHERE p.is_deleted = 0 
        AND p.parent_id IS NULL 
        AND r.is_deleted = 0
        GROUP BY p.id
        ORDER BY p.is_pinned DESC, MAX(r.created_at) DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene posts recientes con nombre del tablón
 * @param int $limit Límite de posts
 * @return array Lista de posts recientes
 */
function get_recent_posts_with_board_name($limit) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, b.name AS board_name 
        FROM posts p
        JOIN boards b ON p.board_id = b.id
        WHERE p.is_deleted = 0 AND p.parent_id IS NULL
        ORDER BY p.is_pinned DESC, p.updated_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene posts con respuestas recientes
 * @param int $limit Límite de posts
 * @return array Lista de posts con respuestas
 */
function get_replied_posts_with_board_name($limit) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, b.name AS board_name 
        FROM posts p
        JOIN boards b ON p.board_id = b.id
        JOIN posts r ON p.id = r.parent_id
        WHERE p.is_deleted = 0 AND p.parent_id IS NULL AND r.is_deleted = 0
        GROUP BY p.id
        ORDER BY p.is_pinned DESC, MAX(r.created_at) DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene todos los posts (para administración)
 * @return array Lista completa de posts
 */
function get_all_posts() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT * FROM posts 
        ORDER BY created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===================================
// GESTIÓN DE POSTS (ADMINISTRACIÓN)
// ===================================

/**
 * Elimina un post (marca como eliminado)
 * @param int $post_id ID del post
 * @return bool Éxito de la operación
 */
function delete_post($post_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$post_id]);
    } catch (PDOException $e) {
        error_log("Error al eliminar post: " . $e->getMessage());
        return false;
    }
}

/**
 * Bloquea un post
 * @param int $post_id ID del post
 * @return bool Éxito de la operación
 */
function lock_post($post_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE posts SET is_locked = 1 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

/**
 * Desbloquea un post
 * @param int $post_id ID del post
 * @return bool Éxito de la operación
 */
function unlock_post($post_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE posts SET is_locked = 0 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

/**
 * Fija un post
 * @param int $post_id ID del post
 * @return bool Éxito de la operación
 */
function pin_post($post_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE posts SET is_pinned = 1 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

/**
 * Desfija un post
 * @param int $post_id ID del post
 * @return bool Éxito de la operación
 */
function unpin_post($post_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE posts SET is_pinned = 0 WHERE id = ?");
    return $stmt->execute([$post_id]);
}

/**
 * Actualiza la imagen de un post
 * @param int $post_id ID del post
 * @param string $image_filename Nombre del archivo de imagen
 * @return bool Éxito de la operación
 */
function update_post_image($post_id, $image_filename) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE posts SET image_filename = ? WHERE id = ?");
        return $stmt->execute([$image_filename, $post_id]);
    } catch (PDOException $e) {
        error_log("Error al actualizar imagen: " . $e->getMessage());
        return false;
    }
}

// ===================================
// GESTIÓN DE IMÁGENES
// ===================================

/**
 * Sube una imagen al servidor
 * @param array $file Datos del archivo de $_FILES
 * @return array Resultado de la subida
 */
function upload_image($file) {
    // Validar tamaño del archivo
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'success' => false, 
            'error' => 'El archivo es demasiado grande. Máximo 5MB.'
        ];
    }
    
    // Validar extensión del archivo
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return [
            'success' => false, 
            'error' => 'Tipo de archivo no permitido.'
        ];
    }
    
    // Generar nombre único para el archivo
    $filename = generate_unique_filename($extension);
    $filepath = UPLOAD_DIR . $filename;
    
    // Mover archivo subido
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'original_name' => $file['name']
        ];
    } else {
        return [
            'success' => false, 
            'error' => 'Error al subir el archivo.'
        ];
    }
}

/**
 * Genera un nombre único para archivos
 * @param string $extension Extensión del archivo
 * @return string Nombre único del archivo
 */
function generate_unique_filename($extension) {
    return uniqid() . '_' . time() . '.' . $extension;
}

// ===================================
// PROCESAMIENTO DE TEXTO Y FORMATO
// ===================================

/**
 * Convierte referencias y aplica formato al texto
 * @param string $text Texto a procesar
 * @param bool $is_admin Si el usuario es administrador
 * @param int|null $parent_post_id ID del post padre para referencias
 * @return string Texto procesado con HTML
 */
function parse_references($text, $is_admin = false, $parent_post_id = null) {
    // 1. Procesar entidades HTML según el tipo de usuario
    $text = process_html_entities($text, $is_admin);
    
    // 2. Separar por líneas para procesar formato
    $lines = preg_split('/\r?\n/', $text);
    
    foreach ($lines as &$line) {
        $line = process_text_line($line, $is_admin, $parent_post_id);
        
        // Para administradores, cerrar etiquetas abiertas automáticamente
        if ($is_admin && preg_match('/<[^>]+>/', $line)) {
            $line = close_open_html_tags($line);
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

/**
 * Procesa entidades HTML según el tipo de usuario
 * @param string $text Texto a procesar
 * @param bool $is_admin Si es administrador
 * @return string Texto procesado
 */
function process_html_entities(string $text, bool $is_admin): string {
    if ($is_admin) {
        // Convertir entidades HTML a caracteres reales primero
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Luego convertir etiquetas HTML escapadas a etiquetas reales
        $text = preg_replace('/&lt;(h1|h2|span|div)([^&]*)&gt;/i', '<$1$2>', $text);
        $text = preg_replace('/&lt;\/(h1|h2|span|div)&gt;/i', '</$1>', $text);
        
        return $text;
    } else {
        // Para usuarios normales, solo desescapar >
        return str_replace('&gt;', '>', $text);
    }
}

/**
 * Procesa una línea individual de texto
 * @param string $line Línea a procesar
 * @param bool $is_admin Si es administrador
 * @param int|null $parent_post_id ID del post padre para referencias
 * @return string Línea procesada
 */
function process_text_line(string $line, bool $is_admin, $parent_post_id = null): string {
    $original_line = $line;
    
    // Verificar si la línea ya contiene HTML válido
    $has_html = preg_match('/<[^>]+>/', $line);
    
    // Solo aplicar formatos de texto si no hay HTML o si es admin
    if (!$has_html || $is_admin) {
        // Aplicar formatos de texto
        $line = apply_text_formatting($line);
    }
    
    // Convertir referencias >>id en enlaces usando la nueva función avanzada
    $line = process_cross_board_references($line, $parent_post_id);
    
    // Verificar nuevamente si hay HTML después de los formatos
    $has_html_after = preg_match('/<[^>]+>/', $line);
    
    // Aplicar greentext y pinktext solo si no hay HTML
    if (!$has_html_after) {
        $line = apply_color_text_formatting($line);
    }

    // 4. Enlaces externos (solo https)
    $line = preg_replace_callback(
        '/(https?:\/\/[^\s<>]+)/i',
        function($matches) {
            $url = htmlspecialchars($matches[1]);
            return '<a href="urlout.php?url=' . urlencode($matches[1]) . '" rel="nofollow noreferrer noopener" target="_blank">' . $url . '</a>';
        },
        $line
    );
    
    return $line;
}

/**
 * Aplica formato de texto básico
 * @param string $line Línea a formatear
 * @return string Línea formateada
 */
function apply_text_formatting(string $line): string {
    $line = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $line);
    $line = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $line);
    $line = preg_replace('/~(.+?)~/s', '<s>$1</s>', $line);
    $line = preg_replace('/_(.+?)_/s', '<u>$1</u>', $line);
    $line = preg_replace('/\[spoiler\](.+?)\[\/spoiler\]/is', '<span class="spoiler">$1</span>', $line);
    
    return $line;
}

/**
 * Aplica formato de greentext y pinktext
 * @param string $line Línea a formatear
 * @return string Línea formateada
 */
function apply_color_text_formatting(string $line): string {
    // Greentext: líneas que empiezan con >
    if (preg_match('/^>(.*)$/', $line, $matches)) {
        return '<span class="greentext">&gt;' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</span>';
    }
    // Pinktext: líneas que empiezan con <
    elseif (preg_match('/^<(.*)$/', $line, $matches)) {
        return '<span class="pinktext">&lt;' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</span>';
    }
    // Pinktext para &lt; ya escapado
    elseif (preg_match('/^&lt;(.*)$/', $line, $matches)) {
        return '<span class="pinktext">&lt;' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</span>';
    }
    
    return $line;
}

/**
 * Procesa referencias cruzadas entre tablones
 * Soporta:
 * - >>ID (post del mismo tablón)
 * - >>/tablón/ID (post de otro tablón) 
 * - >>/tablón/ (enlace a tablón)
 * 
 * @param string $line Línea de texto a procesar
 * @param int|null $parent_post_id ID del post padre si existe
 * @return string Línea con referencias convertidas en enlaces
 */
function process_cross_board_references(string $line, ?int $parent_post_id = null): string {
    global $pdo;
    
    // Patrón 1: >>/tablón/ID (referencia a post de otro tablón)
    $line = preg_replace_callback(
        '/>>\/([a-zA-Z0-9_]+)\/(\d+)/',
        function($matches) use ($pdo) {
            $board_short_id = $matches[1];
            $post_id = $matches[2];
            
            // Verificar que el tablón existe
            $stmt = $pdo->prepare("SELECT id FROM boards WHERE short_id = ?");
            $stmt->execute([$board_short_id]);
            $board_exists = $stmt->fetchColumn();
            
            if ($board_exists) {
                // Verificar que el post existe en ese tablón
                $stmt = $pdo->prepare("
                    SELECT p.id FROM posts p 
                    JOIN boards b ON p.board_id = b.id 
                    WHERE p.id = ? AND b.short_id = ? AND p.is_deleted = 0
                ");
                $stmt->execute([$post_id, $board_short_id]);
                $post_exists = $stmt->fetchColumn();
                
                if ($post_exists) {
                    return '<a href="reply.php?post_id=' . $post_id . '#post-' . $post_id . '" class="ref-link cross-board" title="Post ' . $post_id . ' en /' . htmlspecialchars($board_short_id) . '/">&gt;&gt;/' . htmlspecialchars($board_short_id) . '/' . $post_id . '</a>';
                } else {
                    return '<span class="ref-link dead-link" title="Post inexistente">&gt;&gt;/' . htmlspecialchars($board_short_id) . '/' . $post_id . '</span>';
                }
            } else {
                return '<span class="ref-link dead-link" title="Tablón inexistente">&gt;&gt;/' . htmlspecialchars($board_short_id) . '/' . $post_id . '</span>';
            }
        },
        $line
    );
    
    // Patrón 2: >>/tablón/ (enlace a tablón)
    $line = preg_replace_callback(
        '/>>\/([a-zA-Z0-9_]+)\/$/',
        function($matches) use ($pdo) {
            $board_short_id = $matches[1];
            
            // Verificar que el tablón existe
            $stmt = $pdo->prepare("SELECT id, name FROM boards WHERE short_id = ?");
            $stmt->execute([$board_short_id]);
            $board = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($board) {
                return '<a href="boards.php?board=' . htmlspecialchars($board_short_id) . '" class="ref-link board-link" title="' . htmlspecialchars($board['name']) . '">&gt;&gt;/' . htmlspecialchars($board_short_id) . '/</a>';
            } else {
                return '<span class="ref-link dead-link" title="Tablón inexistente">&gt;&gt;/' . htmlspecialchars($board_short_id) . '/</span>';
            }
        },
        $line
    );
    
    // Patrón 3: >>ID (post del mismo tablón) - comportamiento original mejorado
    $line = preg_replace_callback(
        '/>>(\d+)/',
        function($matches) use ($pdo, $parent_post_id) {
            $post_id = $matches[1];
            
            // Verificar que el post existe
            $stmt = $pdo->prepare("SELECT id, board_id FROM posts WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                if ($parent_post_id) {
                    // Si estamos en un contexto con post padre, usar ese ID para el enlace
                    return '<a href="reply.php?post_id=' . $parent_post_id . '#post-' . $post_id . '" class="ref-link">&gt;&gt;' . $post_id . '</a>';
                } else {
                    // Comportamiento original para posts principales
                    return '<a href="reply.php?post_id=' . $post_id . '#post-' . $post_id . '" class="ref-link">&gt;&gt;' . $post_id . '</a>';
                }
            } else {
                return '<span class="ref-link dead-link" title="Post inexistente">&gt;&gt;' . $post_id . '</span>';
            }
        },
        $line
    );
    
    return $line;
}

/**
 * Convierte referencias dentro de texto de color (greentext/pinktext)
 * @param string $line Línea con colortext
 * @return string Línea con referencias convertidas
 */
function convert_references_in_colortext(string $line): string {
    return preg_replace_callback(
        '/<span class="(greentext|pinktext)">(.*?)<\/span>/',
        function ($m) {
            $content = preg_replace(
                '/&gt;&gt;(\d+)/',
                '<a href="#post-$1" class="ref-link">&gt;&gt;$1</a>',
                $m[2]
            );
            return '<span class="' . $m[1] . '">' . $content . '</span>';
        },
        $line
    );
}

/**
 * Cierra etiquetas HTML abiertas automáticamente
 * @param string $line Línea con HTML
 * @return string Línea con etiquetas cerradas
 */
function close_open_html_tags(string $line): string {
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
    
    return $line;
}

/**
 * Valida que los formatos reservados solo sean usados por administradores
 * @param string $message Mensaje a validar
 * @param bool $is_admin Si el usuario es administrador
 * @return bool True si el mensaje es válido
 */
function validate_admin_formats(string $message, bool $is_admin): bool {
    if ($is_admin) return true;
    return !preg_match('/<(h1|h2)>/i', $message);
}

// ===================================
// GESTIÓN DE REPORTES
// ===================================

/**
 * Crea un nuevo reporte
 * @param int $post_id ID del post reportado
 * @param string $reason Razón del reporte
 * @param string $details Detalles adicionales
 * @param string $reporter_ip IP del reportero
 * @return bool Éxito de la operación
 */
function create_report($post_id, $reason, $details, $reporter_ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reports (post_id, reason, details, reporter_ip) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$post_id, $reason, $details, $reporter_ip]);
    } catch (PDOException $e) {
        error_log("Error al crear reporte: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene todos los reportes
 * @return array Lista de reportes con información del post
 */
function get_all_reports() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT r.*, p.name, p.subject 
        FROM reports r 
        LEFT JOIN posts p ON r.post_id = p.id 
        ORDER BY r.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Elimina un reporte
 * @param int $report_id ID del reporte
 * @return bool Éxito de la operación
 */
function delete_report($report_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    return $stmt->execute([$report_id]);
}

// ===================================
// AUTENTICACIÓN DE ADMINISTRADOR
// ===================================

/**
 * Verifica si el usuario actual es administrador
 * @return bool True si es administrador
 */
function is_admin() {
    if (!isset($_SESSION['admin_token'])) {
        return false;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM admin_sessions 
        WHERE session_token = ? AND expires_at > NOW()
    ");
    $stmt->execute([$_SESSION['admin_token']]);
    
    return $stmt->fetch() !== false;
}

/**
 * Crea una sesión de administrador
 * @return bool Éxito de la operación
 */
function create_admin_session() {
    global $pdo;
    
    $token = bin2hex(random_bytes(32));
    $ip = get_user_ip();
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
    
    $stmt = $pdo->prepare("
        INSERT INTO admin_sessions (session_token, ip_address, expires_at) 
        VALUES (?, ?, ?)
    ");
    
    if ($stmt->execute([$token, $ip, $expires])) {
        $_SESSION['admin_token'] = $token;
        return true;
    }
    
    return false;
}

// ===================================
// ESTADÍSTICAS DEL SITIO
// ===================================

/**
 * Obtiene estadísticas generales del sitio
 * @return array Estadísticas del sitio
 */
function get_site_stats() {
    global $pdo;

    // Total de publicaciones
    $total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

    // Usuarios únicos que han publicado
    $unique_users = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM posts")->fetchColumn();

    // Calcular peso total de archivos (mejor implementación)
    $total_size_bytes = calculate_total_file_size();
    $total_size_formatted = format_file_size($total_size_bytes);

    return [
        'total_posts' => $total_posts,
        'unique_users' => $unique_users,
        'total_size' => $total_size_formatted
    ];
}

/**
 * Calcula el tamaño total real de los archivos subidos
 * @return int Tamaño total en bytes
 */
function calculate_total_file_size() {
    global $pdo;
    
    $total_size = 0;
    
    // Obtener todos los archivos de imagen
    $stmt = $pdo->query("
        SELECT image_filename FROM posts 
        WHERE image_filename IS NOT NULL AND image_filename != ''
    ");
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Calcular el tamaño real de cada archivo
    foreach ($files as $filename) {
        $filepath = UPLOAD_DIR . $filename;
        if (file_exists($filepath)) {
            $total_size += filesize($filepath);
        }
    }
    
    return $total_size;
}

/**
 * Obtiene estadísticas detalladas por tablón
 * @return array Estadísticas por tablón
 */
function get_board_statistics() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            b.name,
            b.short_id,
            COUNT(p.id) as post_count,
            MAX(p.created_at) as last_post
        FROM boards b
        LEFT JOIN posts p ON b.id = p.board_id AND p.is_deleted = 0
        GROUP BY b.id, b.name, b.short_id
        ORDER BY post_count DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene estadísticas de actividad por hora
 * @param int $days Días hacia atrás para calcular
 * @return array Estadísticas de actividad
 */
function get_activity_statistics($days = 7) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            HOUR(created_at) as hour,
            COUNT(*) as post_count
        FROM posts 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        AND is_deleted = 0
        GROUP BY DATE(created_at), HOUR(created_at)
        ORDER BY date DESC, hour ASC
    ");
    $stmt->execute([$days]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===================================
// UTILIDADES DE VALIDACIÓN
// ===================================

/**
 * Valida que un post ID sea válido
 * @param mixed $post_id ID a validar
 * @return int|false ID válido o false si no es válido
 */
function validate_post_id($post_id) {
    $id = filter_var($post_id, FILTER_VALIDATE_INT);
    return ($id !== false && $id > 0) ? $id : false;
}

/**
 * Valida que un board ID sea válido
 * @param mixed $board_id ID a validar
 * @return int|false ID válido o false si no es válido
 */
function validate_board_id($board_id) {
    $id = filter_var($board_id, FILTER_VALIDATE_INT);
    return ($id !== false && $id > 0) ? $id : false;
}

/**
 * Valida el contenido de un mensaje
 * @param string $message Mensaje a validar
 * @param int $max_length Longitud máxima
 * @return array Resultado de la validación
 */
function validate_message_content($message, $max_length = 10000) {
    $result = [
        'valid' => true,
        'errors' => []
    ];
    
    // Verificar que no esté vacío
    if (empty(trim($message))) {
        $result['valid'] = false;
        $result['errors'][] = 'El mensaje no puede estar vacío';
    }
    
    // Verificar longitud
    if (strlen($message) > $max_length) {
        $result['valid'] = false;
        $result['errors'][] = "El mensaje no puede exceder {$max_length} caracteres";
    }
    
    // Verificar contenido spam básico
    if (is_spam_content($message)) {
        $result['valid'] = false;
        $result['errors'][] = 'El mensaje contiene contenido spam';
    }
    
    return $result;
}

/**
 * Detecta contenido spam básico
 * @param string $content Contenido a verificar
 * @return bool True si es spam
 */
function is_spam_content($content) {
    // Lista de patrones spam básicos
    $spam_patterns = [
        '/viagra/i',
        '/casino/i',
        '/buy.*now/i',
        '/click.*here/i',
        '/free.*money/i',
        '/\b(?:https?:\/\/)?(?:www\.)?[a-zA-Z0-9-]+\.(?:com|org|net|edu|gov|mil|int|biz|info|name|museum|coop|aero|[a-z]{2})(?:\/\S*)?\s*(?:https?:\/\/)?(?:www\.)?[a-zA-Z0-9-]+\.(?:com|org|net|edu|gov|mil|int|biz|info|name|museum|coop|aero|[a-z]{2})/i' // Múltiples URLs
    ];
    
    foreach ($spam_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }
    
    return false;
}

// ===================================
// UTILIDADES DE LIMPIEZA Y MANTENIMIENTO
// ===================================

/**
 * Limpia archivos huérfanos del directorio de uploads
 * @return array Resultado de la limpieza
 */
function cleanup_orphaned_files() {
    global $pdo;
    
    $result = [
        'deleted_files' => 0,
        'freed_space' => 0,
        'errors' => []
    ];
    
    if (!is_dir(UPLOAD_DIR)) {
        $result['errors'][] = 'Directorio de uploads no existe';
        return $result;
    }
    
    // Obtener todos los archivos de la base de datos
    $stmt = $pdo->query("
        SELECT DISTINCT image_filename 
        FROM posts 
        WHERE image_filename IS NOT NULL 
        AND image_filename != ''
    ");
    $db_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener todos los archivos del directorio
    $directory_files = array_diff(scandir(UPLOAD_DIR), ['.', '..']);
    
    // Encontrar archivos huérfanos
    $orphaned_files = array_diff($directory_files, $db_files);
    
    foreach ($orphaned_files as $file) {
        $filepath = UPLOAD_DIR . $file;
        if (is_file($filepath)) {
            $filesize = filesize($filepath);
            if (unlink($filepath)) {
                $result['deleted_files']++;
                $result['freed_space'] += $filesize;
            } else {
                $result['errors'][] = "No se pudo eliminar: {$file}";
            }
        }
    }
    
    return $result;
}

/**
 * Limpia sesiones de administrador expiradas
 * @return int Número de sesiones eliminadas
 */
function cleanup_expired_admin_sessions() {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE expires_at <= NOW()");
    $stmt->execute();
    
    return $stmt->rowCount();
}

/**
 * Limpia posts eliminados antiguos
 * @param int $days Días de antigüedad para eliminación permanente
 * @return int Número de posts eliminados permanentemente
 */
function cleanup_old_deleted_posts($days = 30) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        DELETE FROM posts 
        WHERE is_deleted = 1 
        AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$days]);
    
    return $stmt->rowCount();
}

// ===================================
// UTILIDADES DE LOGGING
// ===================================

/**
 * Registra una acción de administrador
 * @param string $action Acción realizada
 * @param array $details Detalles adicionales
 * @return bool Éxito del registro
 */
function log_admin_action($action, $details = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (action, details, ip_address, admin_token) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $action,
            json_encode($details),
            get_user_ip(),
            $_SESSION['admin_token'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Error al registrar acción de admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene logs de administrador
 * @param int $limit Límite de registros
 * @param int $offset Offset para paginación
 * @return array Lista de logs
 */
function get_admin_logs($limit = 100, $offset = 0) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM admin_logs 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===================================
// UTILIDADES DE BÚSQUEDA
// ===================================

/**
 * Busca posts por contenido
 * @param string $query Término de búsqueda
 * @param int|null $board_id ID del tablón (opcional)
 * @param int $limit Límite de resultados
 * @return array Posts encontrados
 */
function search_posts($query, $board_id = null, $limit = 50) {
    global $pdo;
    
    $query = trim($query);
    if (empty($query)) {
        return [];
    }
    
    // Buscar en posts principales
    $sql = "
        SELECT p.*, b.name as board_name 
        FROM posts p
        LEFT JOIN boards b ON p.board_id = b.id
        WHERE p.is_deleted = 0 
        AND p.parent_id IS NULL
        AND (p.subject LIKE ? OR p.message LIKE ?)
    ";
    
    $params = ["%{$query}%", "%{$query}%"];
    
    if ($board_id !== null) {
        $sql .= " AND p.board_id = ?";
        $params[] = $board_id;
    }
    
    $sql .= " ORDER BY p.is_pinned DESC, p.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $main_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no encontramos suficientes posts principales, buscar en respuestas
    if (count($main_posts) < $limit) {
        $remaining_limit = $limit - count($main_posts);
        
        $sql_replies = "
            SELECT DISTINCT parent.*, b.name as board_name 
            FROM posts replies
            LEFT JOIN posts parent ON replies.parent_id = parent.id
            LEFT JOIN boards b ON parent.board_id = b.id
            WHERE replies.is_deleted = 0 
            AND parent.is_deleted = 0
            AND replies.parent_id IS NOT NULL
            AND replies.message LIKE ?
        ";
        
        $params_replies = ["%{$query}%"];
        
        if ($board_id !== null) {
            $sql_replies .= " AND parent.board_id = ?";
            $params_replies[] = $board_id;
        }
        
        $sql_replies .= " ORDER BY parent.is_pinned DESC, replies.created_at DESC LIMIT ?";
        $params_replies[] = $remaining_limit;
        
        $stmt_replies = $pdo->prepare($sql_replies);
        $stmt_replies->execute($params_replies);
        
        $reply_parents = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar resultados sin duplicados
        $all_post_ids = array_column($main_posts, 'id');
        foreach ($reply_parents as $parent) {
            if (!in_array($parent['id'], $all_post_ids)) {
                $main_posts[] = $parent;
            }
        }
    }
    
    return $main_posts;
}

/**
 * Busca posts por IP
 * @param string $ip_address IP a buscar
 * @param int $limit Límite de resultados
 * @return array Posts encontrados
 */
function search_posts_by_ip($ip_address, $limit = 100) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, b.name as board_name 
        FROM posts p
        LEFT JOIN boards b ON p.board_id = b.id
        WHERE p.ip_address = ?
        ORDER BY p.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$ip_address, $limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===================================
// UTILIDADES DE MIGRACIÓN
// ===================================

/**
 * Inicializa el campo updated_at para posts existentes
 * Solo se ejecuta una vez para migrar datos existentes
 * @return bool Éxito de la operación
 */
function initialize_updated_at_field() {
    global $pdo;
    
    try {
        // Verificar si ya se ha ejecutado la migración
        $check_stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE updated_at IS NOT NULL AND updated_at != '0000-00-00 00:00:00'");
        $has_updated_at = $check_stmt->fetchColumn() > 0;
        
        if ($has_updated_at) {
            return true; // Ya se ha ejecutado la migración
        }
        
        // Actualizar posts principales para que updated_at = created_at
        $stmt1 = $pdo->query("UPDATE posts SET updated_at = created_at WHERE parent_id IS NULL");
        
        // Para posts con respuestas, establecer updated_at como la fecha de la respuesta más reciente
        $stmt2 = $pdo->query("
            UPDATE posts p1 
            SET updated_at = (
                SELECT MAX(p2.created_at) 
                FROM posts p2 
                WHERE p2.parent_id = p1.id AND p2.is_deleted = 0
            )
            WHERE p1.parent_id IS NULL 
            AND EXISTS (
                SELECT 1 FROM posts p3 
                WHERE p3.parent_id = p1.id AND p3.is_deleted = 0
            )
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log("Error al inicializar updated_at: " . $e->getMessage());
        return false;
    }
}

?>