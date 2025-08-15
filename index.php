<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está baneado
$ban_info = is_user_banned();
if ($ban_info) {
    header('Location: ban.php');
    exit;
}

// Procesar envío de post y reporte de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report']) && isset($_POST['report_post_id'])) {
    $post_id = (int)$_POST['report_post_id'];
    $reason = clean_input($_POST['report_reason'] ?? '');
    $details = clean_input($_POST['report_details'] ?? '');
    $reporter_ip = get_user_ip();
    if ($post_id > 0 && !empty($reason)) {
        if (create_report($post_id, $reason, $details, $reporter_ip)) {
            $report_success = true;
            header('Location: ' . $_SERVER['PHP_SELF'] . '?report_success=1');
            exit;
        } else {
            $error = 'Error al enviar el reporte.';
        }
    } else {
        $error = 'Datos de reporte inválidos.';
    }
}
// Redirigir después de procesar un post para evitar reenvío
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
    $name = clean_input($_POST['name'] ?? '');
    // Si el nombre está vacío, usar "Anónimo"
    if (empty(trim($name))) {
        $name = 'Anónimo';
    }
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $board_id = isset($_POST['board_id']) ? (int)$_POST['board_id'] : null;
    
    if (empty($message)) {
        $error = 'El mensaje no puede estar vacío.';
    } else {
        $image_filename = null;
        $image_original_name = null;
        
        // Procesar imagen si se subió una
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['image']);
            if ($upload_result['success']) {
                $image_filename = $upload_result['filename'];
                $image_original_name = $upload_result['original_name'];
            } else {
                $error = $upload_result['error'];
            }
        }
        
        if (!isset($error)) {
            if (create_post($name, $subject, $message, $image_filename, $image_original_name, $parent_id, $board_id)) {
                // Redirigir para evitar reenvío
                header('Location: ' . $_SERVER['PHP_SELF'] . '?post_success=1');
                exit;
            } else {
                $error = 'Error al crear el post.';
            }
        }
    }
}

$posts = get_posts();
$popular_posts = get_recent_and_replied_posts(8);

// Obtener todos los tablones y organizarlos por categoría
$boards = get_all_boards();
$boards_by_category = [];

// Organizar los tablones por categoría
foreach ($boards as $board) {
    $category = $board['category'] ?? 'Sin categoría';
    if (!isset($boards_by_category[$category])) {
        $boards_by_category[$category] = [];
    }
    $boards_by_category[$category][] = $board;
}

$site_stats = get_site_stats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleChan - Imageboard Anónimo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
</head>
<body>
    <header>
        <h1>SimpleChan</h1>
        <p>Imageboard Anónimo Simple</p>
    </header>

    <main>

        <!-- Lista de tablones -->
        <div class="box-outer top-box" id="boards">
            <div class="box-inner">
                <div class="boxbar">
                    <h2>Tablones</h2>
                </div>
                <div class="boxcontent">
                    <?php foreach ($boards_by_category as $category => $boards): ?>
                        <?php 
                        $has_nsfw = false;
                        foreach ($boards as $board) {
                            if (!empty($board['nsfw_label'])) {
                                $has_nsfw = true;
                                break;
                            }
                        }
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
                                        <a href="boards.php?board=<?php echo urlencode($board['short_id']); ?>" class="boardlink">
                                            <?php echo htmlspecialchars($board['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Publicaciones -->
        <div class="box-outer top-box" id="popular-threads">
            <div class="box-inner">
                <div class="boxbar">
                    <h2>Publicaciones Populares</h2>
                </div>
                <div class="boxcontent">
                    <div id="c-threads">
                        <?php foreach ($popular_posts as $post): ?>
                            <div class="c-thread">
                                <div class="c-board">
                                    <?php echo htmlspecialchars($post['board_name']); ?>
                                </div>
                                <a href="reply.php?post_id=<?php echo $post['id']; ?>" class="boardlink">
                                <?php if (!empty($post['image_filename'])): ?>
                                    <?php if (file_exists(UPLOAD_DIR . $post['image_filename'])): ?>
                                        <div class="post-image">
                                            <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                                                alt="<?php echo htmlspecialchars($post['image_original_name']); ?>"
                                                onclick="toggleImageSize(this)">
                                        </div>
                                    <?php else: ?>
                                        <div class="post-image">
                                            <img src="assets/imgs/filedeleted.gif" 
                                                alt="Imagen no disponible">
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                </a>
                                <div class="c-teaser">
                                    <b><?php echo htmlspecialchars($post['subject'] ?? 'Sin título'); ?></b>:<br>
                                    <?php echo htmlspecialchars_decode(substr($post['message'], 0, 50)); ?>...
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadisticas -->
        <div class="box-outer top-box" id="site-stats">
            <div class="box-inner">
                <div class="boxbar">
                    <h2>Estadísticas del Sitio</h2>
                </div>
                <div class="boxcontent">
                    <div class="stat-cell"><b>Total de Publicaciones:</b> <?php echo number_format($site_stats['total_posts']); ?></div>
                    <div class="stat-cell"><b>Usuarios Únicos:</b> <?php echo number_format($site_stats['unique_users']); ?></div>
                    <div class="stat-cell"><b>Peso Total de Archivos:</b> <?php echo $site_stats['total_size']; ?></div>
                </div>
            </div>
        </div>

    </main>

    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>