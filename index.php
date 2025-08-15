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

// Procesar envío de post
// Procesar reporte de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report']) && isset($_POST['report_post_id'])) {
    $post_id = (int)$_POST['report_post_id'];
    $reason = clean_input($_POST['report_reason'] ?? '');
    $details = clean_input($_POST['report_details'] ?? '');
    $reporter_ip = get_user_ip();
    if ($post_id > 0 && !empty($reason)) {
        if (create_report($post_id, $reason, $details, $reporter_ip)) {
            $report_success = true;
        } else {
            $error = 'Error al enviar el reporte.';
        }
    } else {
        $error = 'Datos de reporte inválidos.';
    }
}
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
            if (create_post($name, $subject, $message, $image_filename, $image_original_name, $parent_id)) {
                // Redirigir para evitar reenvío
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = 'Error al crear el post.';
            }
        }
    }
}

// Obtener posts
$posts = get_posts();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleChan - Imageboard Anónimo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>SimpleChan</h1>
        <p>Imageboard Anónimo Simple</p>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="reglas.php">Reglas</a>
        </nav>
    </header>

    <main>
        <?php if (isset($report_success) && $report_success): ?>
            <div class="success">¡Gracias por reportar! El reporte ha sido enviado al administrador.</div>
        <?php endif; ?>
        <!-- Botón para mostrar formulario -->
        <section class="create-post-toggle">
            <button onclick="toggleCreatePostForm()" id="toggle-post-btn" class="btn-create-post">
                Crear nuevo publicación
            </button>
        </section>

        <!-- Formulario para nuevo post (oculto por defecto) -->
        <section class="post-form" id="create-post-form" style="display: none;">
            <h2>Crear nuevo publicación</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
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
                    <label for="button">Formatos:</label>
                    <button type="button" onclick="insertFormat('bold')" title="Negrita"><b>B</b></button>
                    <button type="button" onclick="insertFormat('italic')" title="Cursiva"><i>I</i></button>
                    <button type="button" onclick="insertFormat('strike')" title="Tachado"><s>T</s></button>
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
                    <textarea id="message" name="message" required rows="3" placeholder="Tu publicación..."></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Imagen:</label>
                    <?php if (is_admin()): ?>
                        <input type="file" id="image" name="image" accept="image/*">
                    <?php else: ?>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    <?php endif; ?>
                        <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</span>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="submit_post">Crear publicación</button>
                </div>
            </form>
        </section>

        <!-- Lista de posts -->
        <section class="posts">
            <h2>Publicaciones</h2>
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
                                    <span class="post-subject"><?php echo htmlspecialchars($post['subject']); ?></span>
                                <?php endif; ?>
                                <span class="post-date"><?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?></span>
                                <span class="post-number"><a href="reply.php?post_id=<?php echo $post['id']; ?>&ref=<?php echo $post['id']; ?>">No. <?php echo $post['id']; ?></a></span>
                                <a href="reply.php?post_id=<?php echo $post['id']; ?>" class="btn-reply">Responder</a>
                                <div class="report-menu-wrapper" style="display:inline-block;position:relative;">
                                    <button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>
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
                                            <button type="submit" name="submit_report" style="width:100%;background:#d00;color:#fff;">Enviar reporte</button>
                                        </form>
                                    </nav>
                                </div>
                            </div>
                            
                            <?php if ($post['image_filename']): ?>
                                <div class="post-image">
                                    <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                                         alt="<?php echo htmlspecialchars($post['image_original_name']); ?>"
                                         onclick="toggleImageSize(this)">
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-message">
                                <?php echo parse_references($post['message'], $post['name'] === 'Administrador'); ?>
                            </div>
                            
                            <!-- Respuestas -->
                            <?php
                                $replies = get_replies($post['id']);
                                if (!empty($replies)):
                                    $last_replies = array_slice($replies, -5);
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
                                                    <img src="<?php echo UPLOAD_DIR . $reply['image_filename']; ?>" 
                                                         alt="<?php echo htmlspecialchars($reply['image_original_name']); ?>"
                                                         onclick="toggleImageSize(this)">
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
    </main>

    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>