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

// Validar y obtener el ID del post
$post_id = validatePostId();
if (!$post_id) {
    redirectToHome();
}

// Obtener y validar el post principal
$post = validateMainPost($post_id);
if (!$post) {
    redirectToHome();
}

// Verificar si el post está bloqueado
if (isPostLocked($post)) {
    redirectToHome();
}

$error = null;
$success_message = null;

// Mostrar mensaje de éxito si viene de redirección
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Respuesta creada exitosamente.';
}

// Mostrar mensaje de éxito para reportes
if (isset($_GET['report_success']) && $_GET['report_success'] == '1') {
    $success_message = 'Gracias por reportar! El reporte ha sido enviado al administrador.';
}

// Procesar nueva respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
    $error = processNewReply($post_id);
}

// Procesar reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $result = processReport();
    if ($result['success']) {
        redirectWithSuccess('reply.php?post_id=' . $post_id . '&report_success=1');
    } else {
        $error = $result['error'];
    }
}

$view_data = loadReplyPageData($post_id);

function validatePostId() {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    return ($post_id > 0) ? $post_id : false;
}

function validateMainPost($post_id) {
    $post = get_post($post_id);
    
    // Debe existir y no ser una respuesta
    if (!$post || $post['parent_id'] !== null) {
        return false;
    }
    
    return $post;
}

function isPostLocked($post) {
    return $post['is_locked'] && !is_admin();
}

function redirectToHome() {
    header('Location: index.php');
    exit;
}

function redirectWithSuccess($url) {
    header('Location: ' . $url);
    exit;
}

function processNewReply($post_id) {
    // Obtener datos del tablón para la respuesta
    global $post;
    $board_id = $post['board_id'] ?? null;
    
    // Sanitizar y validar datos de entrada
    $name = clean_input($_POST['name'] ?? '');
    $name = empty(trim($name)) ? 'Anónimo' : $name;
    $message = clean_input($_POST['message'] ?? '');
    
    // Validar mensaje obligatorio
    if (empty($message)) {
        return 'El mensaje no puede estar vacío.';
    }
    
    // Procesar imagen subida
    $image_data = processImageUpload();
    if ($image_data['error']) {
        return $image_data['error'];
    }
    
    // Crear la respuesta en la base de datos
    $reply_created = create_post(
        $name, 
        '', // Las respuestas no tienen subject
        $message, 
        $image_data['filename'], 
        $image_data['original_name'], 
        $post_id, // parent_id
        $board_id
    );
    
    if ($reply_created) {
        redirectWithSuccess('reply.php?post_id=' . $post_id . '&success=1');
    } else {
        return 'Error al crear la respuesta.';
    }
    
    return null;
}

function processImageUpload() {
    $result = [
        'filename' => null,
        'original_name' => null,
        'error' => null
    ];
    
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

function processReport() {
    $post_id = isset($_POST['report_post_id']) ? (int)$_POST['report_post_id'] : 0;
    $reason = clean_input($_POST['report_reason'] ?? '');
    $details = clean_input($_POST['report_details'] ?? '');
    
    if ($post_id <= 0) {
        return ['success' => false, 'error' => 'ID de post inválido'];
    }
    
    if (empty($reason)) {
        return ['success' => false, 'error' => 'El motivo es requerido'];
    }
    
    $reporter_ip = get_user_ip();
    
    if (create_report($post_id, $reason, $details, $reporter_ip)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Error al crear el reporte'];
    }
}

function loadReplyPageData($post_id) {
    return [
        'replies' => get_replies($post_id),
        'boards_by_category' => organizeBoardsByCategory(),
        'random_banner' => getRandomBanner(),
        'board' => getBoardFromPost()
    ];
}

function organizeBoardsByCategory() {
    $boards_by_category = [];
    $all_boards = get_all_boards();
    
    foreach ($all_boards as $board) {
        $category = $board['category'] ?? 'Sin categoría';
        if (!isset($boards_by_category[$category])) {
            $boards_by_category[$category] = [];
        }
        $boards_by_category[$category][] = $board;
    }
    
    return $boards_by_category;
}

function getRandomBanner() {
    $banner_dir = 'assets/banners/';
    if (!is_dir($banner_dir)) {
        return null;
    }
    
    $banners = array_diff(scandir($banner_dir), ['.', '..']);
    return $banners ? $banner_dir . $banners[array_rand($banners)] : null;
}

function getBoardFromPost() {
    global $post;
    return get_board_by_id($post['board_id']);
}

function renderNavigation($boards_by_category) {
    ?>
    <nav>
        <ul>
            [<li>
                <a href="index.php">Inicio</a>/
                <a href="reglas.php">Reglas</a>
                <?php if (is_admin()): ?>
                /<a href="admin.php">Administración</a>
                <?php endif; ?>
            </li>]
            <?php foreach ($boards_by_category as $category => $boards): ?>
                [<?php foreach ($boards as $nav_board): ?>
                    <li>
                        <a href="boards.php?board=<?php echo htmlspecialchars($nav_board['short_id']); ?>" 
                           title="<?php echo htmlspecialchars($nav_board['name']); ?>">
                            /<?php echo htmlspecialchars($nav_board['short_id']); ?>/
                        </a>
                    </li>
                <?php endforeach; ?>]
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
}

function renderHeader($board, $post, $random_banner) {
    ?>
    <header>
        <h1>/<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?> - Post <?php echo $post['id']; ?></h1>
        <p><?php echo htmlspecialchars($board['description']); ?></p>
        <div class="banner">
            <?php if ($random_banner): ?>
                <img src="<?php echo htmlspecialchars($random_banner); ?>" alt="Banner">
            <?php endif; ?>
        </div>
    </header>
    <?php
}

function renderMessages($error, $success_message) {
    if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif;
    
    if ($success_message): ?>
        <div class="success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif;
}

function renderCreateReplyButton() {
    ?>
    <section class="create-reply">
        [ <button onclick="toggleCreateForm('reply')" id="toggle-reply" class="btn-create-reply">
            Crear Respuesta
        </button> ]
    </section>
    <?php
}

function renderReplyForm($error) {
    ?>
    <section class="post-form" id="create-reply" style="display: none;">
        <h2>Responder</h2>
        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="reply-form">
            <?php renderNameField(); ?>
            <?php renderFormatButtons(); ?>
            <?php renderMessageField(); ?>
            <?php renderImageField(); ?>
            <div class="form-buttons">
                <button type="submit" name="submit_post">Responder</button>
            </div>
        </form>
    </section>
    <?php
}

function renderNameField() {
    ?>
    <div class="form-group">
        <label for="name">Nombre (opcional):</label>
        <?php if (is_admin()): ?>
            <input type="text" id="name" name="name" value="Administrador" readonly class="admin-name" autocomplete="username">
        <?php else: ?>
            <input type="text" id="name" name="name" placeholder="Anónimo" maxlength="50" autocomplete="username">
        <?php endif; ?>
    </div>
    <?php
}

function renderFormatButtons() {
    ?>
    <div class="form-group">
        <span class="form-label">Formatos:</span>
        <button type="button" onclick="insertFormat('bold', this)" title="Negrita"><b>B</b></button>
        <button type="button" onclick="insertFormat('italic', this)" title="Cursiva"><i>I</i></button>
        <button type="button" onclick="insertFormat('strike', this)" title="Tachado"><s>T</s></button>
        <button type="button" onclick="insertFormat('subline', this)" title="Subrayado"><u>S</u></button>
        <button type="button" onclick="insertFormat('spoiler', this)" title="Spoiler">SPOILER</button>
        <?php if (is_admin()): ?>
            <button type="button" onclick="insertFormat('h1', this)" title="Título grande">H1</button>
            <button type="button" onclick="insertFormat('h2', this)" title="Título mediano">H2</button>
            <button type="button" onclick="insertFormat('color', this)" title="Color de texto">Color</button>
            <button type="button" onclick="insertFormat('center', this)" title="Centrar texto">Centrar</button>
        <?php endif; ?>
    </div>
    <?php
}

function renderMessageField() {
    ?>
    <div class="form-group">
        <label for="message">Mensaje:</label>
        <textarea id="message" name="message" required rows="5" placeholder="Tu respuesta..." autocomplete="off"></textarea>
    </div>
    <?php
}

function renderImageField() {
    ?>
    <div class="form-group">
        <input type="file" name="image" accept="image/*">
        <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
            Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.
        </span>
    </div>
    <?php
}

function renderMainPost($post) {
    ?>
    <section>
        <h2>Publicación</h2>
        <article class="post" id="post-<?php echo $post['id']; ?>">
            <?php renderPostHeader($post, true); ?>
            <?php renderPostImage($post); ?>
            <div class="post-message">
                <?php echo parse_references($post['message'], $post['name'] === 'Administrador'); ?>
            </div>
        </article>
    </section>
    <?php
}

function renderRepliesSection($replies, $post_id) {
    ?>
    <section>
        <h2>Respuestas</h2>
        <?php if (empty($replies)): ?>
            <p>No hay respuestas aún.</p><br>
        <?php else: ?>
            <div class="replies">
                <?php foreach ($replies as $reply): ?>
                    <article class="reply" id="post-<?php echo $reply['id']; ?>">
                        <?php renderPostHeader($reply, false, $post_id); ?>
                        <?php renderPostImage($reply); ?>
                        <div class="post-message">
                            <?php echo parse_references($reply['message'], $reply['name'] === 'Administrador'); ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function renderPostHeader($post, $is_main_post = false, $post_id = null) {
    ?>
    <div class="post-header">
        <?php renderPostName($post); ?>
        <?php renderPostSubject($post); ?>
        <span><?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?></span>
        <span>
            <a href="#post-<?php echo $post['id']; ?>" onclick="insertReference(<?php echo $post['id']; ?>); return false;">
                No. <?php echo $post['id']; ?>
            </a>
        </span>
        <?php renderPostActions($post, $is_main_post, $post_id); ?>
        <?php renderPostFlags($post); ?>
    </div>
    <?php
}

function renderPostName($post) {
    if ($post['name'] === 'Administrador') {
        echo '<span class="admin-name">Administrador</span>';
    } else {
        echo '<span class="post-name">' . htmlspecialchars($post['name']) . '</span>';
    }
}

function renderPostSubject($post) {
    if (!empty($post['subject'])): ?>
        <span class="post-subject">
            <?php echo htmlspecialchars($post['subject']); ?>
        </span>
    <?php endif;
}

function renderPostActions($post, $is_main_post, $post_id = null) {
    if ($is_main_post): ?>
        [<a href="reply.php?post_id=<?php echo $post['id']; ?>" class="btn-reply">Responder</a>]
    <?php else: ?>
        [<a href="#" class="btn-reply" onclick="insertReference(<?php echo $post['id']; ?>); return false;">Responder</a>]
    <?php endif;
    
    renderReportMenu($post, $post_id);
}

function renderReportMenu($post, $post_id = null) {
    $form_action = $post_id ? "reply.php?post_id={$post_id}" : "index.php";
    ?>
    <div class="report-menu-wrapper" style="display:inline-block;position:relative;">
        [<button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>]
        <nav class="report-menu" id="report-menu-<?php echo $post['id']; ?>" 
             style="display:none;position:absolute;z-index:10;background:#f7e5e5;border:1px solid rgb(136 0 0);padding:10px;min-width:150px;">
            <form method="POST" action="<?php echo $form_action; ?>" style="margin:0;">
                <input type="hidden" name="report_post_id" value="<?php echo $post['id']; ?>">
                <label for="report_reason_<?php echo $post['id']; ?>" style="display:block;margin-bottom:5px;">Motivo:</label>
                <select id="report_reason_<?php echo $post['id']; ?>" name="report_reason" style="width:100%;margin-bottom:5px;" autocomplete="off">
                    <option value="spam">Spam</option>
                    <option value="contenido ilegal">Contenido ilegal</option>
                    <option value="acoso">Acoso</option>
                    <option value="otro">Otro</option>
                </select>
                <input type="text" name="report_details" placeholder="Detalles (opcional)" 
                       style="width:100%;margin-bottom:5px;" autocomplete="off">
                <button type="submit" name="submit_report" 
                        style="width:100%;background:#800;color:#fff;padding:2px;">
                    Enviar reporte
                </button>
            </form>
        </nav>
    </div>
    <?php
}

function renderPostFlags($post) {
    if ($post['is_pinned']): ?>
        <img src="assets/imgs/sticky.gif" alt="Fijado">
    <?php endif;
    
    if ($post['is_locked']): ?>
        <img src="assets/imgs/closed.gif" alt="Bloqueado">
    <?php endif;
}

function renderPostImage($post) {
    if (empty($post['image_filename'])) {
        return;
    }
    
    if (file_exists(UPLOAD_DIR . $post['image_filename'])): ?>
        <div class="post-image">
            <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                 alt="<?php echo htmlspecialchars($post['image_original_name']); ?>"
                 onclick="toggleImageSize(this)">
        </div>
    <?php else: ?>
        <div class="post-image">
            <img src="assets/imgs/filedeleted.gif" alt="Imagen no disponible">
        </div>
    <?php endif;
}

    function renderThemes() {
        ?>
        <div class="theme-selector" style="margin:0 var(--spacing-sm);">
            <label for="theme-select">Selecciona un tema:</label>
            <select id="theme-select" onchange="changeTheme(this.value)">
                <option value="default">Predeterminado</option>
                <option value="blue">Blue</option>
                <option value="dark">Oscuro</option>
            </select>
        </div>
        <?php
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas - SimpleChan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
</head>
<body>
    <?php renderNavigation($view_data['boards_by_category']); ?>
    
    <?php renderHeader($view_data['board'], $post, $view_data['random_banner']); ?>

    <main>
        <?php renderMessages($error, $success_message); ?>
        
        <?php renderCreateReplyButton(); ?>
        
        <?php renderReplyForm($error); ?>
        
        <?php renderMainPost($post); ?>
        
        <?php renderRepliesSection($view_data['replies'], $post_id); ?>
    </main>

    <?php renderThemes(); ?>
    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>
    
    <script src="assets/js/script.js"></script>
</body>
</html>