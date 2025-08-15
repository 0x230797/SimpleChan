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

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

// Validar el parámetro 'post_id' para asegurarse de que sea un entero positivo antes de continuar.
if ($post_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener el post principal
$post = get_post($post_id);
if (!$post || $post['parent_id'] !== null) {
    header('Location: index.php');
    exit;
}

// Verificar si el post está bloqueado antes de permitir respuestas
if ($post['is_locked'] && !is_admin()) {
    $error = 'Este post está bloqueado. Solo el administrador puede responder.';
    header('Location: index.php');
    exit;
}

// Procesar respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
    $name = clean_input($_POST['name'] ?? '');
    if (empty(trim($name))) {
        $name = 'Anónimo';
    }
    $message = clean_input($_POST['message'] ?? '');
    if (empty($message)) {
        $error = 'El mensaje no puede estar vacío.';
    } else {
        $image_filename = null;
        $image_original_name = null;
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
            if (create_post($name, '', $message, $image_filename, $image_original_name, $post_id, $board_id)) {
                header('Location: reply.php?post_id=' . $post_id . '&success=1');
                exit;
            } else {
                $error = 'Error al crear la respuesta.';
            }
        }
    }
}

// Obtener todas las respuestas
$replies = get_replies($post_id);

// Organizar los tablones por categoría
$boards_by_category = [];
$all_boards = get_all_boards();
foreach ($all_boards as $board) {
    $category = $board['category'] ?? 'Sin categoría';
    if (!isset($boards_by_category[$category])) {
        $boards_by_category[$category] = [];
    }
    $boards_by_category[$category][] = $board;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas - SimpleChan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
</head>
<body>
    <nav>
        <ul>
            [<li>
                <a href="index.php">Inicio</a>
                <a href="reglas.php">Reglas</a>
                <?php if (is_admin()): ?>
                <a href="admin.php">Administración</a>
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
        <h1>/<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?> - Post <?php echo $post['id']; ?></h1>
        <p><?php echo htmlspecialchars($board['description']); ?></p>
    </header>

    <main>

        <!-- Mostrar mensaje de éxito si el reporte fue enviado -->
        <?php if (isset($_GET['report_success']) && $_GET['report_success'] == 1): ?>
            <div class="success">¡Gracias por reportar! El reporte ha sido enviado al administrador.</div>
        <?php endif; ?>

        <!-- Botón para mostrar formulario -->
        <section class="create-reply">
            [ <button onclick="toggleCreateForm('reply')" id="toggle-reply" class="btn-create-reply">
                Crear Respuesta
            </button> ]
        </section>

        <!-- Sección para que los usuarios puedan responder a la publicación -->
        <section class="post-form" id="create-reply" style="display: none;">
            <h2>Responder</h2>
            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="reply-form">
                <div class="form-group">
                    <label for="name">Nombre (opcional):</label>
                    <?php if (is_admin()): ?>
                        <input type="text" name="name" value="Administrador" readonly class="admin-name" style="background:#f7e5e5;">
                    <?php else: ?>
                        <input type="text" name="name" placeholder="Anónimo" maxlength="50">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Formatos:</label>
                    <button type="button" onclick="insertFormat('bold', this)" title="Negrita"><b>B</b></button>
                    <button type="button" onclick="insertFormat('italic', this)" title="Cursiva"><i>I</i></button>
                    <button type="button" onclick="insertFormat('strike', this)" title="Tachado"><s>T</s></button>
                    <button type="button" onclick="insertFormat('subline', this)" title="Sublinea"><u>S</u></button>
                    <button type="button" onclick="insertFormat('spoiler', this)" title="Spoiler">SPOILER</button>
                    <?php if (is_admin()): ?>
                        <button type="button" onclick="insertFormat('h1', this)" title="Título grande">H1</button>
                        <button type="button" onclick="insertFormat('h2', this)" title="Título mediano">H2</button>
                        <button type="button" onclick="insertFormat('color', this)" title="Color de texto">Color</button>
                        <button type="button" onclick="insertFormat('center', this)" title="Centrar texto">Centrar</button>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="message">Mensaje:</label>
                    <textarea name="message" required rows="5" placeholder="Tu respuesta..."></textarea>
                </div>
                <div class="form-group">
                    <input type="file" name="image" accept="image/*">
                    <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</span>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="submit_post">Responder</button>
                </div>
            </form>
        </section>

        <!-- Sección principal para mostrar la publicación original -->
        <section>
            <h2>Publicación</h2>
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
                    <span class="post-date"><?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?></span>
                    <span class="post-number"><a href="#post-<?php echo $post['id']; ?>" onclick="insertReference(<?php echo $post['id']; ?>); return false;">No. <?php echo $post['id']; ?></a></span>
                    [<a href="reply.php?post_id=<?php echo $post['id']; ?>" class="btn-reply">Responder</a>]
                    <div class="report-menu-wrapper" style="display:inline-block;position:relative;">
                        [<button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>]
                        <nav class="report-menu" id="report-menu-<?php echo $post['id']; ?>" style="display:none;position: absolute;z-index: 10;background: #f7e5e5;border: 1px solid rgb(136 0 0);padding: 10px;min-width: 150px;">
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
                </div>
                <?php if (!empty($post['image_filename']) && $post['image_filename'] !== null): ?>
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
            </article>
        </section>

        <!-- Sección para mostrar las respuestas a la publicación -->
        <section>
            <h2>Respuestas</h2>
            <?php if (empty($replies)): ?>
                <p>No hay respuestas aún.</p><br>
            <?php else: ?>
                <div class="replies">
                    <?php foreach ($replies as $reply): ?>
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
                                <span class="post-number"><a href="#post-<?php echo $reply['id']; ?>" onclick="insertReference(<?php echo $reply['id']; ?>); return false;">No. <?php echo $reply['id']; ?></a></span>
                                [<a href="#" class="btn-reply" onclick="insertReference(<?php echo $reply['id']; ?>); return false;">Responder</a>]
                                <div class="report-menu-wrapper" style="display:inline-block;position:relative;">
                                    [<button class="btn-report" onclick="toggleReportMenu(<?php echo $reply['id']; ?>)">Reportar</button>]
                                    <nav class="report-menu" id="report-menu-<?php echo $reply['id']; ?>" style="display:none;position: absolute;z-index: 10;background: #f7e5e5;border: 1px solid rgb(136 0 0);padding: 10px;min-width: 150px;">
                                        <form method="POST" action="reply.php?post_id=<?php echo $post_id; ?>" style="margin:0;">
                                            <input type="hidden" name="report_post_id" value="<?php echo $reply['id']; ?>">
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
                            </div>
                            <?php if (!empty($reply['image_filename'])): ?>
                                <?php if (file_exists(UPLOAD_DIR . $reply['image_filename'])): ?>
                                    <div class="post-image">
                                        <img src="<?php echo UPLOAD_DIR . $reply['image_filename']; ?>" 
                                             alt="<?php echo htmlspecialchars($reply['image_original_name']); ?>"
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
                                <?php echo parse_references($reply['message'], $reply['name'] === 'Administrador'); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>
    <script src="assets/js/script.js"></script>
</body>
</html>