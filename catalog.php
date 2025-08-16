<?php
require_once 'config.php';
require_once 'functions.php';

// Obtener el ID del tablón
$board_short_id = $_GET['board'] ?? null;
if (!$board_short_id) {
    die('Error: No se especificó un tablón.');
}

// Obtener información del tablón usando el short_id
$stmt = $pdo->prepare("SELECT * FROM boards WHERE short_id = ?");
$stmt->execute([$board_short_id]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    die('Error: El tablón especificado no existe.');
}

// Obtener las publicaciones del tablón usando el ID numérico
$posts = get_posts_by_board($board['id'], 100, 0); // Máximo de 100 publicaciones

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - <?php echo htmlspecialchars($board['name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
</head>
<body>
    <header>
        <h1>Catálogo de /<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?></h1>
        <p><?php echo htmlspecialchars($board['description']); ?></p>
        <a href="boards.php?board=<?php echo htmlspecialchars($board['short_id']); ?>" style="padding:10px;display:block;">Volver al tablón</a>
    </header>
    <main style="max-width:100%;margin:auto 20px;">
        <section class="catalog">
            <div class="box-outer top-box" id="catalog-threads">
                <div class="box-inner">
                    <div class="boxbar">
                        <h2>Catálogo de Publicaciones</h2>
                    </div>
                    <div class="boxcontent">
                        <div id="c-threads">
                            <?php foreach ($posts as $post): ?>
                            <div class="c-thread">
                                <a href="reply.php?post_id=<?php echo $post['id']; ?>" class="boardlink">
                                    <?php if (!empty($post['image_filename'])): ?>
                                    <div class="post-image">
                                        <img src="uploads/<?php echo htmlspecialchars($post['image_filename']); ?>" alt="Imagen de la publicación" onclick="toggleImageSize(this)">
                                    </div>
                                    <?php endif; ?>
                                </a>
                                <div class="c-teaser">
                                    <b><?php echo htmlspecialchars($post['subject'] ?? 'Sin asunto'); ?></b><br>
                                    <small>Por: <?php echo htmlspecialchars($post['name'] ?? 'Anónimo'); ?> - <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></small><br>
                                    <?php $message_preview = strip_tags($post['message']);
                                        echo htmlspecialchars(strlen($message_preview) > 100 ? substr($message_preview, 0, 100) . '...' : $message_preview); 
                                    ?>
                                </div>
                                <div class="c-stats">
                                <?php $reply_stmt = $pdo->prepare("SELECT COUNT(*) as reply_count FROM posts WHERE parent_id = ? AND is_deleted = 0");
                                    $reply_stmt->execute([$post['id']]);
                                    $reply_count = $reply_stmt->fetchColumn();
                                ?>
                                <small><?php echo $reply_count; ?> respuesta(s)</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <p>&copy; 2025 SimpleChan - Catálogo</p>
    </footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
