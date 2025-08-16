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

// Obtener el tablón actual
$board_name = isset($_GET['board']) ? urldecode(trim($_GET['board'])) : null;
$board_id = isset($_GET['board_id']) ? (int)$_GET['board_id'] : null;

if ($board_name) {
    // Primero intentar buscar por short_id, luego por name
    $board = get_board_by_short_id($board_name);
    if (!$board) {
        $board = get_board_by_name($board_name);
    }
    if ($board) {
        $board_id = $board['id'];
    } else {
        die('Error: El tablón no existe.');
    }
} elseif ($board_id) {
    $board = get_board_by_id($board_id);
    if (!$board) {
        die('Error: El tablón no existe.');
    }
} else {
    die('Error: No se especificó un tablón válido.');
}

// Configuración de paginación
$posts_per_page = 10; // Número de posts por página
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $posts_per_page;

// Obtener el total de posts para calcular las páginas
$total_posts = count_posts_by_board($board_id);
$total_pages = ceil($total_posts / $posts_per_page);

// Asegurar que la página actual no exceda el total de páginas
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $posts_per_page;
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
            $redirect_url = $_SERVER['PHP_SELF'] . '?board=' . $board['short_id'] . '&report_success=1';
            if ($current_page > 1) {
                $redirect_url .= '&page=' . $current_page;
            }
            header('Location: ' . $redirect_url);
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
                // Redirigir a la primera página después de crear un post para verlo
                header('Location: ' . $_SERVER['PHP_SELF'] . '?board=' . $board['short_id'] . '&post_success=1');
                exit;
            } else {
                $error = 'Error al crear el post.';
            }
        }
    }
}

// Obtener posts del tablón con paginación
$posts = get_posts_by_board($board_id, $posts_per_page, $offset);

$all_boards = get_all_boards();
$boards_by_category = [];
foreach ($all_boards as $nav_board) {
    $category = $nav_board['category'] ?? 'Sin categoría';
    if (!isset($boards_by_category[$category])) {
        $boards_by_category[$category] = [];
    }
    $boards_by_category[$category][] = $nav_board;
}

// Obtener un banner aleatorio de la carpeta banners
$banner_dir = 'assets/banners/';
$banners = array_diff(scandir($banner_dir), array('..', '.'));
$random_banner = $banners ? $banner_dir . $banners[array_rand($banners)] : null;
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
                        <a href="boards.php?board=<?php echo htmlspecialchars($nav_board['short_id']); ?>" title="<?php echo htmlspecialchars($nav_board['name']); ?>"> /<?php echo htmlspecialchars($nav_board['short_id']); ?>/</a>
                    </li>
                <?php endforeach; ?>]
            <?php endforeach; ?>
        </ul>
    </nav>
    <header>
        <h1>/<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?></h1>
        <p><?php echo htmlspecialchars($board['description']); ?></p>
        <div class="banner">
            <?php if ($random_banner): ?>
                <img src="<?php echo htmlspecialchars($random_banner); ?>" alt="Banner">
            <?php endif; ?>
        </div>
    </header>

    <main>

        <!-- Mostrar mensaje de éxito si el reporte fue enviado -->
        <?php if (isset($_GET['report_success']) && $_GET['report_success'] == 1): ?>
            <div class="success">¡Gracias por reportar! El reporte ha sido enviado al administrador.</div>
        <?php endif; ?>

        <!-- Mostrar mensaje de éxito si el post fue creado -->
        <?php if (isset($_GET['post_success']) && $_GET['post_success'] == 1): ?>
            <div class="success">¡Post creado exitosamente!</div>
        <?php endif; ?>

        <!-- Botón para mostrar formulario -->
        <section class="create-post">
            [ <button onclick="toggleCreateForm('post')" id="toggle-post" class="btn-create-post">
                Crear publicación
            </button> ]
        </section>

        <!-- Formulario para nuevo post -->
        <section class="post-form" id="create-post" style="display: none;">
            <h2>Crear nuevo publicación</h2>
            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Nombre (opcional):</label>
                    <?php if (is_admin()): ?>
                        <input type="text" id="name" name="name" value="Administrador" readonly class="admin-name" style="background:#f7e5e5;">
                    <?php else: ?>
                        <input type="text" id="name" name="name" placeholder="Anónimo" maxlength="50">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="subject">Asunto:</label>
                    <input type="text" id="subject" name="subject" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label>Formatos:</label>
                    <button type="button" onclick="insertFormat('bold')" title="Negrita"><b>B</b></button>
                    <button type="button" onclick="insertFormat('italic')" title="Cursiva"><i>I</i></button>
                    <button type="button" onclick="insertFormat('strike')" title="Tachado"><s>T</s></button>
                    <button type="button" onclick="insertFormat('subline')" title="Sublinea"><u>S</u></button>
                    <button type="button" onclick="insertFormat('spoiler')" title="Spoiler">SPOILER</button>
                    <?php if (is_admin()): ?>
                        <button type="button" onclick="insertFormat('h1', this)" title="Título grande">H1</button>
                        <button type="button" onclick="insertFormat('h2', this)" title="Título mediano">H2</button>
                        <button type="button" onclick="insertFormat('color', this)" title="Color de texto">Color</button>
                        <button type="button" onclick="insertFormat('center', this)" title="Centrar texto">Centrar</button>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="message">Mensaje:</label>
                    <textarea id="message" name="message" required rows="5" placeholder="Tu publicación..."></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Imagen:</label>
                    <?php if (is_admin()): ?>
                        <input type="file" id="image" name="image" accept="image/*">
                    <?php else: ?>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    <?php endif; ?>
                    <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">Formatos permitidos: JPG, JPEG, PNG, GIF, WEBP. Tamaño máximo: 5MB.</span>
                    <br>
                    <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">Antes de hacer una publicación, recuerda leer las <a href="reglas.php">reglas</a>.</span>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="submit_post">Crear publicación</button>
                </div>
            </form>
        </section>

        <!-- Lista de posts -->
        <section class="posts">
            <h2>Publicaciones <?php echo ($total_pages > 1) ? "(Página $current_page de $total_pages)" : ""; ?></h2>
            <?php if (empty($posts)): ?>
                <p>No hay posts aún. ¡Sé el primero en publicar!</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php if ($post['parent_id'] === null): // Solo mostrar posts principales ?>
                        <article class="post" id="post-<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <?php
                                if ($post['name'] === 'Administrador') {
                                    echo '<span class="admin-name">Administrador</span>';
                                } else {
                                    echo '<span class="post-name">' . htmlspecialchars($post['name']) . '</span>';
                                }
                                ?>
                                <?php if (!empty($post['subject'])): ?>
                                    <span class="post-subject">
                                        <?php echo htmlspecialchars($post['subject']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="post-date">
                                    <?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?>
                                </span>
                                <span class="post-number">
                                    <a href="reply.php?post_id=<?php echo $post['id']; ?>&ref=<?php echo $post['id']; ?>">No. <?php echo $post['id']; ?></a>
                                </span>
                                <?php if ($post['is_locked']): ?>
                                    [<a href="#" class="btn-reply" onclick="alert('No puedes responder a una publicación bloqueada'); window.location.reload(); return false;">Responder</a>]
                                <?php else: ?>
                                    [<a href="reply.php?post_id=<?php echo $post['id']; ?>" class="btn-reply">Responder</a>]
                                <?php endif; ?>
                                <div class="report-menu-wrapper" style="display:inline-block;position:relative;">
                                    [<button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>]
                                    <nav class="report-menu" id="report-menu-<?php echo $post['id']; ?>" style="display:none;position:fixed;width:1px;z-index:10;background:#f7e5e5;border:1px solid rgb(136 0 0);padding:10px;min-width:150px;">
                                        <form method="POST" action="index.php" style="margin:0;">
                                            <input type="hidden" name="report_post_id" value="<?php echo $post['id']; ?>">
                                            <label style="display:block;margin-bottom:5px;">Motivo:</label>
                                            <select name="report_reason" style="width:100%;margin-bottom:5px;">
                                                <option value="spam">Spam</option>
                                                <option value="contenido ilegal">Contenido ilegal</option>
                                                <option value="acoso">Acoso</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                            <input type="text" name="report_details" placeholder="Detalles (opcional)" style="width:100%;margin-bottom:5px;">
                                            <button type="submit" name="submit_report" style="width:100%;background:#800;color:#fff;padding: 2px;">Enviar reporte</button>
                                        </form>
                                    </nav>
                                </div>
                                <?php if ($post['is_pinned']): ?>
                                    <img src="assets/imgs/sticky.gif" alt="Fijado">
                                <?php endif; ?>
                                <?php if ($post['is_locked']): ?>
                                    <img src="assets/imgs/closed.gif" alt="Bloqueado">
                                <?php endif; ?>
                                <?php if (is_admin()): ?>
                                    <form method="POST" action="admin_actions.php" style="display:inline;">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <?php if ($post['is_locked']): ?>
                                            [<button type="submit" name="unlock_post" class="btn-unlock">Desbloquear</button>]
                                        <?php else: ?>
                                            [<button type="submit" name="lock_post" class="btn-lock">Bloquear</button>]
                                        <?php endif; ?>
                                        <?php if ($post['is_pinned']): ?>
                                            [<button type="submit" name="unpin_post" class="btn-unpin">Desfijar</button>]
                                        <?php else: ?>
                                            [<button type="submit" name="pin_post" class="btn-pin">Fijar</button>]
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
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
                            
                            <div class="post-message">
                                <?php echo parse_references($post['message'], $post['name'] === 'Administrador'); ?>
                            </div>
                            
                            <!-- Respuestas -->
                            <?php
                            $replies = get_replies($post['id']);
                            if (!empty($replies)):
                                $last_replies = array_slice($replies, -5); // 5: Número de respuestas a mostrar
                            ?>
                                <div class="replies">
                                    <?php foreach ($last_replies as $reply): ?>
                                        <article class="reply" id="post-<?php echo $reply['id']; ?>">
                                            <div class="post-header">
                                                <?php
                                                if ($reply['name'] === 'Administrador') {
                                                    echo '<span class="admin-name">Administrador</span>';
                                                } else {
                                                    echo '<span class="post-name">' . htmlspecialchars($reply['name']) . '</span>';
                                                }
                                                ?>
                                                <span class="post-date"><?php echo date('d/m/Y H:i:s', strtotime($reply['created_at'])); ?></span>
                                                <span class="post-number"><a href="reply.php?post_id=<?php echo $reply['parent_id'] ? $reply['parent_id'] : $reply['id']; ?>&ref=<?php echo $reply['id']; ?>">No. <?php echo $reply['id']; ?></a></span>
                                            </div>
                                            <?php if ($reply['image_filename']): ?>
                                                <div class="post-image">
                                                    <img src="<?php echo UPLOAD_DIR . $reply['image_filename']; ?>" alt="<?php echo htmlspecialchars($reply['image_original_name']); ?>" onclick="toggleImageSize(this)">
                                                </div>
                                            <?php endif; ?>
                                            <div class="post-message">
                                                <?php echo parse_references($reply['message'], $reply['name'] === 'Administrador'); ?>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
            <section class="pagination">
                <div class="pagination-info">
                    <p>Página <?php echo $current_page; ?> de <?php echo $total_pages; ?> (<?php echo $total_posts; ?> posts totales)</p>
                </div>
                <div class="pagination-controls">
                    <?php if ($current_page > 1): ?>
                        <a href="?board=<?php echo htmlspecialchars($board['short_id']); ?>&page=1" class="pagination-btn first">« Primera</a>
                        <a href="?board=<?php echo htmlspecialchars($board['short_id']); ?>&page=<?php echo $current_page - 1; ?>" class="pagination-btn prev">‹ Anterior</a>
                    <?php endif; ?>

                    <?php
                    // Mostrar números de página
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $current_page): ?>
                            <span class="pagination-btn current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?board=<?php echo htmlspecialchars($board['short_id']); ?>&page=<?php echo $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?board=<?php echo htmlspecialchars($board['short_id']); ?>&page=<?php echo $current_page + 1; ?>" class="pagination-btn next">Siguiente ›</a>
                        <a href="?board=<?php echo htmlspecialchars($board['short_id']); ?>&page=<?php echo $total_pages; ?>" class="pagination-btn last">Última »</a>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>