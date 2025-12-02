<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Ejecutar migración de updated_at (solo se ejecuta una vez)
initialize_updated_at_field();

// Verificar si el usuario está baneado
$ban_info = is_user_banned();
if ($ban_info) {
    header('Location: ban.php');
    exit;
}

$error = null;
$success_message = null;

// Mostrar mensaje de éxito si viene de redirección
if (isset($_GET['post_success']) && $_GET['post_success'] == '1') {
    $success_message = 'Post creado exitosamente.';
}

// Procesar envío de nuevo post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
    $error = processNewPost();
}

function processNewPost() {
    // Sanitizar y validar datos de entrada
    $name = clean_input($_POST['name'] ?? '');
    $name = empty(trim($name)) ? 'Anónimo' : $name;
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $board_id = isset($_POST['board_id']) ? (int)$_POST['board_id'] : null;
    
    // Validar mensaje obligatorio
    if (empty($message)) {
        return 'El mensaje no puede estar vacío.';
    }
    
    // Procesar imagen subida
    $image_data = processImageUpload();
    if ($image_data['error']) {
        return $image_data['error'];
    }
    
    // Crear el post en la base de datos
    $post_created = create_post(
        $name, 
        $subject, 
        $message, 
        $image_data['filename'], 
        $image_data['original_name'], 
        $parent_id, 
        $board_id
    );
    
    if ($post_created) {
        // Redirigir para evitar reenvío del formulario
        header('Location: ' . $_SERVER['PHP_SELF'] . '?post_success=1');
        exit;
    } else {
        return 'Error al crear el post.';
    }
}

function processImageUpload() {
    $result = [
        'filename' => null,
        'original_name' => null,
        'error' => null
    ];
    
    // Verificar si se subió una imagen
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_image($_FILES['image']);
        if ($upload_result['success']) {
            $result['filename'] = $upload_result['filename'];
            $result['original_name'] = $upload_result['original_name'];
        } else {
            $result['error'] = $upload_result['error'];
        }
    }
    
    return $result;
}

// Obtener datos necesarios para la página
$data = loadPageData();

function loadPageData() {
    return [
        'posts' => get_posts_for_index(),
        'boards_by_category' => organizeBoardsByCategory(),
        'info' => get_info_for_index(),
        'popular_posts' => get_recent_and_replied_posts_for_index(8),
        'site_stats' => get_site_stats()
    ];
}

function organizeBoardsByCategory() {
    $boards = get_all_boards();
    $boards_by_category = [];
    
    foreach ($boards as $board) {
        $category = $board['category'] ?? 'Sin categoría';
        if (!isset($boards_by_category[$category])) {
            $boards_by_category[$category] = [];
        }
        $boards_by_category[$category][] = $board;
    }
    
    return $boards_by_category;
}

function renderBoardsSection($boards_by_category) {
    ?>
    <div class="box-outer top-box" id="boards">
        <div class="boxbar">
            <h2>Tablones</h2>
        </div>
        <div class="boxcontent">
            <?php foreach ($boards_by_category as $category => $boards): ?>
                <?php renderBoardCategory($category, $boards); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function renderInfoSection($info) {
    ?>
    <div class="box-outer top-box" id="info">
        <div class="boxbar">
            <h2>Información</h2>
        </div>
        <div class="boxcontent">
            <?php if (!empty($info['announcements'])): ?>
                <div class="info-section">
                    <?php foreach ($info['announcements'] as $announcement): ?>
                        <span><?php echo htmlspecialchars($announcement); ?></span><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function renderBoardCategory($category, $boards) {
    $has_nsfw = array_reduce($boards, function($carry, $board) {
        return $carry || !empty($board['nsfw_label']);
    }, false);
    ?>
    <div class="column">
        <u>
            <?php echo htmlspecialchars($category); ?>
            <?php if ($has_nsfw): ?>
                <span class="nsfw-label">(NSFW)</span>
            <?php endif; ?>
        </u>
        <ul>
            <?php foreach ($boards as $board): ?>
                <li>
                    <a href="boards.php?board=<?php echo urlencode($board['short_id']); ?>" title="<?php echo htmlspecialchars($board['description']); ?>">
                        /<?php echo htmlspecialchars($board['short_id']); ?>/
                        <?php echo htmlspecialchars($board['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

function renderPopularPostsSection($popular_posts) {
    ?>
    <div class="box-outer top-box" id="popular-threads">
        <div class="boxbar">
            <h2>Publicaciones Populares</h2>
        </div>
        <div class="boxcontent">
            <div id="c-threads">
                <?php foreach ($popular_posts as $post): ?>
                    <?php renderPopularPost($post); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

function renderPopularPost($post) {
    ?>
    <div class="c-thread">
        <div class="c-board">
            <?php echo htmlspecialchars($post['board_name']); ?>
        </div>
        <a href="reply.php?post_id=<?php echo $post['id']; ?>" class="boardlink">
            <?php renderPostImage($post); ?>
        </a>
        <div class="c-teaser">
            <b><?php echo htmlspecialchars($post['subject'] ?? 'Sin título'); ?></b>
        </div>
    </div>
    <?php
}

function renderPostImage($post) {
    if (!empty($post['image_filename'])) {
        if (file_exists(UPLOAD_DIR . $post['image_filename'])) {
            ?>
            <div class="post-image">
                <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" alt="<?php echo htmlspecialchars($post['image_original_name']); ?>">
            </div>
            <?php
        } else {
            ?>
            <div class="post-image">
                <img src="assets/imgs/filedeleted.png" 
                     alt="Imagen no disponible">
            </div>
            <?php
        }
    }
}

function renderStatsSection($site_stats) {
    ?>
    <div class="box-outer top-box" id="site-stats">
        <div class="boxbar">
            <h2>Estadísticas del Sitio</h2>
        </div>
        <div class="boxcontent">
            <div class="stat-cell">
                <b>Total de Publicaciones:</b> <?php echo number_format($site_stats['total_posts']); ?>
            </div>
            <div class="stat-cell">
                <b>Usuarios Únicos:</b> <?php echo number_format($site_stats['unique_users']); ?>
            </div>
            <div class="stat-cell">
                <b>Peso Total de Archivos:</b> <?php echo $site_stats['total_size']; ?>
            </div>
        </div>
    </div>
    <?php
}

function renderMessages($error, $success_message) {
    if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif;
    
    if ($success_message): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleChan - Imageboard Anónimo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
    <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
</head>
<body>
    <header>
        <a href="index.php">
            <img id="site-logo" src="assets/imgs/logo.png" alt="SimpleChan">
        </a>
    </header>

    <main>
        <?php renderMessages($error, $success_message); ?>

        <?php renderInfoSection($data['info']); ?>
        
        <?php renderBoardsSection($data['boards_by_category']); ?>
        
        <?php renderPopularPostsSection($data['popular_posts']); ?>
        
        <?php renderStatsSection($data['site_stats']); ?>
    </main>

    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>