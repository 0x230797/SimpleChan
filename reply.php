<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está baneado
$ban_info = is_user_banned();
if ($ban_info) {
    header('Location: ban.php');
    exit;
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
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
            if (create_post($name, '', $message, $image_filename, $image_original_name, $post_id)) {
                header('Location: reply.php?post_id=' . $post_id);
                exit;
            } else {
                $error = 'Error al crear la respuesta.';
            }
        }
    }
}

// Obtener todas las respuestas
$replies = get_replies($post_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas - SimpleChan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>SimpleChan</h1>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="reglas.php">Reglas</a>
        </nav>
    </header>
    <main>
        <section class="post-view">
            <h2>Publicación</h2>
            <article class="post" id="post-<?php echo $post['id']; ?>">
                <div class="post-header">
                    <span class="post-name"><?php echo htmlspecialchars($post['name']); ?></span>
                    <?php if (!empty($post['subject'])): ?>
                        <span class="post-subject"><?php echo htmlspecialchars($post['subject']); ?></span>
                    <?php endif; ?>
                    <span class="post-date"><?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?></span>
                    <span class="post-number"><a href="#post-<?php echo $post['id']; ?>" onclick="insertReference(<?php echo $post['id']; ?>); return false;">No. <?php echo $post['id']; ?></a></span>
                </div>
                <?php if ($post['image_filename']): ?>
                    <div class="post-image">
                        <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                             alt="<?php echo htmlspecialchars($post['image_original_name']); ?>"
                             onclick="toggleImageSize(this)">
                    </div>
                <?php endif; ?>
                <div class="post-message">
                    <?php echo nl2br(parse_references($post['message'])); ?>
                </div>
            </article>
        </section>
        <section class="replies-view">
            <h2>Respuestas</h2>
            <?php if (empty($replies)): ?>
                <p>No hay respuestas aún.</p>
            <?php else: ?>
                <div class="replies">
                    <?php foreach ($replies as $reply): ?>
                        <article class="reply" id="post-<?php echo $reply['id']; ?>">
                            <div class="post-header">
                                <span class="post-name"><?php echo htmlspecialchars($reply['name']); ?></span>
                                <span class="post-date"><?php echo date('d/m/Y H:i:s', strtotime($reply['created_at'])); ?></span>
                                <span class="post-number"><a href="#post-<?php echo $reply['id']; ?>" onclick="insertReference(<?php echo $reply['id']; ?>); return false;">No. <?php echo $reply['id']; ?></a></span>
                            </div>
                            <?php if ($reply['image_filename']): ?>
                                <div class="post-image">
                                    <img src="<?php echo UPLOAD_DIR . $reply['image_filename']; ?>" 
                                         alt="<?php echo htmlspecialchars($reply['image_original_name']); ?>"
                                         onclick="toggleImageSize(this)">
                                </div>
                            <?php endif; ?>
                            <div class="post-message">
                                <?php echo nl2br(parse_references($reply['message'])); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="reply-form">
            <h2>Responder</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="text" name="name" placeholder="Anónimo" maxlength="50">
                </div>
                <div class="form-group">
                    <button type="button" onclick="insertFormat('bold', this)" title="Negrita"><b>B</b></button>
                    <button type="button" onclick="insertFormat('italic', this)" title="Cursiva"><i>I</i></button>
                    <button type="button" onclick="insertFormat('strike', this)" title="Tachado"><s>T</s></button>
                    <button type="button" onclick="insertFormat('spoiler', this)" title="Spoiler">SPOILER</button>
                </div>
                <div class="form-group">
                    <textarea name="message" required rows="3" placeholder="Tu respuesta..."></textarea>
                </div>
                <div class="form-group">
                    <input type="file" name="image" accept="image/*">
                    <em>Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.</em>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="submit_post">Responder</button>
                </div>
            </form>
        </section>
    </main>
    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>
    <script src="script.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const ref = params.get('ref');
            if (ref) {
                var textarea = document.querySelector('textarea[name="message"]');
                if (textarea) {
                    textarea.value = '>>' + ref + '\n';
                    textarea.focus();
                }
            }
        });
    </script>
</body>
</html>
