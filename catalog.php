<?php
session_start();

// Usar manejo seguro para cargar archivos críticos
try {
    require_once 'config.php';
    require_once 'functions.php';
} catch (Exception $e) {
    error_log("Critical file loading error in catalog.php: " . $e->getMessage());
    redirect_to_error_page("Error al cargar componentes del catálogo");
}

/**
 * CatalogController
 * Maneja la lógica del catálogo (carga de boards, posts, banners, etc.)
 */
class CatalogController {
    private $board;
    private $board_id;
    private $posts;

    public function __construct() {
        $this->checkBanStatus();
        $this->initializeBoard();
        $this->loadPosts();
    }

    /** Verifica si el usuario está baneado */
    private function checkBanStatus(): void {
        if ($ban_info = is_user_banned()) {
            $this->redirect('ban.php');
        }
    }

    /** Inicializa el board actual */
    private function initializeBoard(): void {
        $board_name = $_GET['board'] ?? null;

        if (!$board_name) {
            $this->handleError('No se especificó un tablón válido.');
        }

        $board_name = urldecode(trim($board_name));

        // Buscar por short_id primero, luego por name
        $this->board = get_board_by_short_id($board_name) ?? get_board_by_name($board_name);

        if (!$this->board) {
            $this->handleError('El tablón no existe.');
        }

        $this->board_id = $this->board['id'];
    }

    /** Carga los posts del board */
    private function loadPosts(): void {
        $order_by = $_GET['order_by'] ?? 'recientes'; // Valor predeterminado: recientes

        // Debug: mostrar qué parámetro se está recibiendo
        error_log("Catalog Debug - order_by recibido: " . $order_by);

        switch ($order_by) {
            case 'actualizacion':
                $order_column = 'updated_at';
                break;
            case 'respuestas':
                $order_column = '(SELECT COUNT(*) FROM posts WHERE parent_id = posts.id)';
                break;
            case 'recientes':
            default:
                $order_column = 'created_at';
                break;
        }

        // Debug: mostrar qué columna se está usando para ordenar
        error_log("Catalog Debug - order_column: " . $order_column);

        $this->posts = get_posts_by_board($this->board_id, 100, 0, $order_column);
        
        // Debug: mostrar cuántos posts se obtuvieron
        error_log("Catalog Debug - posts obtenidos: " . count($this->posts));
    }

    /** Obtiene todos los boards organizados por categoría */
    public function getAllBoardsByCategory(): array {
        $all_boards = get_all_boards();
        $boards_by_category = [];

        foreach ($all_boards as $nav_board) {
            $category = $nav_board['category'] ?? 'Sin categoría';
            $boards_by_category[$category][] = $nav_board;
        }

        return $boards_by_category;
    }

    /** Obtiene un banner aleatorio */
    public function getRandomBanner(): ?string {
        $banner_dir = 'assets/banners/';
        if (!is_dir($banner_dir)) return null;

        $banners = array_diff(scandir($banner_dir), ['..', '.']);
        if (empty($banners)) return null;

        return $banner_dir . $banners[array_rand($banners)];
    }

    public function getBoard(): array {
        return $this->board;
    }

    public function getPosts(): array {
        return $this->posts;
    }

    /** Maneja errores fatales */
    private function handleError(string $message): void {
        die('Error: ' . htmlspecialchars($message));
    }

    /** Redirige a una URL */
    private function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
}

/**
 * CatalogView
 * Maneja la presentación del catálogo
 */
class CatalogView {
    private $controller;
    private $board;
    private $posts;
    private $boards_by_category;
    private $random_banner;

    public function __construct(CatalogController $controller) {
        $this->controller = $controller;
        $this->board = $controller->getBoard();
        $this->posts = $controller->getPosts();
        $this->boards_by_category = $controller->getAllBoardsByCategory();
        $this->random_banner = $controller->getRandomBanner();
    }

    /** Renderiza la página completa */
    public function render(): void {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>/<?= htmlspecialchars($this->board['short_id']) ?>/ <?= htmlspecialchars($this->board['name']) ?> - Catálogo</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes.css">
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body id="top">
            <?php 
            $this->renderNavigation();
            $this->renderHeader();
            $this->renderMiniMenu();
            ?>
            <main class="catalog">
                <?php $this->renderCatalogSection(); ?>
            </main>
            <?php 
            $this->renderThemes();
            $this->renderFooter();
            ?>
            <script src="assets/js/script.js" defer></script>
        </body>
        </html>
        <?php
    }

    /** ---------- Render Helpers ---------- */

    private function renderNavigation(): void {
        ?>
        <nav>
            <ul>
                [<li>
                    <a href="index.php">Inicio</a>/<a href="reglas.php">Reglas</a>
                    <?php if (is_admin()): ?>/ <a href="admin.php">Administración</a><?php endif; ?>
                </li>]
                <?php foreach ($this->boards_by_category as $category => $boards): ?>
                    [<?php foreach ($boards as $nav_board): ?>
                        <li>
                            <a href="boards.php?board=<?= htmlspecialchars($nav_board['short_id']) ?>" 
                               title="<?= htmlspecialchars($nav_board['name']) ?>">
                               /<?= htmlspecialchars($nav_board['short_id']) ?>/
                            </a>
                        </li>
                    <?php endforeach; ?>]
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
    }

    private function renderHeader(): void {
        ?>
        <header>
            <?php if ($this->random_banner): ?>
                <div class="banner">
                    <img src="<?= htmlspecialchars($this->random_banner) ?>" alt="Banner">
                </div>
            <?php endif; ?>
            <h1>Catálogo de /<?= htmlspecialchars($this->board['short_id']) ?>/ - <?= htmlspecialchars($this->board['name']) ?></h1>
            <p><?= htmlspecialchars($this->board['description']) ?></p>
        </header>
        <?php
    }

    private function renderMiniMenu(): void {
        ?>
        <nav>
            <ul class="mini-menu">
                <div>
                    <label for="by-select">Ordenar hilos por:</label>
                    <select id="by-select">
                        <option value="recientes" <?= ($_GET['order_by'] ?? 'recientes') === 'recientes' ? 'selected' : '' ?>>Recientes</option>
                        <option value="actualizacion" <?= ($_GET['order_by'] ?? '') === 'actualizacion' ? 'selected' : '' ?>>Actualización</option>
                        <option value="respuestas" <?= ($_GET['order_by'] ?? '') === 'respuestas' ? 'selected' : '' ?>>Respuestas</option>
                    </select>
                </div>
                <li>[<a href="boards.php?board=<?= htmlspecialchars($this->board['short_id']) ?>">Volver al tablón</a>]</li>
                <li>[<a href="#footer">Bajar</a>]</li>
            </ul>
        </nav>
        <?php
    }

    private function renderCatalogSection(): void {
        ?>
        <section>
            <div class="box-outer top-box" id="catalog-threads">
                <div class="box-inner">
                    <div class="boxbar"><h2>Catálogo de Publicaciones</h2></div>
                    <div class="boxcontent">
                        <div id="c-threads">
                            <?php foreach ($this->posts as $post): ?>
                                <?php $this->renderCatalogPost($post); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    private function renderCatalogPost(array $post): void {
        global $pdo;

        // Consulta reutilizable
        $reply_count = $this->countReplies($post['id']);
        $reply_count_imgs = $this->countReplies($post['id'], true);
        $message_preview = strip_tags($post['message']);
        $message_preview = strlen($message_preview) > 100 
            ? substr($message_preview, 0, 100) . '...' 
            : $message_preview;
        ?>
        <div class="c-thread">
            <a href="reply.php?post_id=<?= $post['id'] ?>" class="boardlink">
                <?php if (!empty($post['image_filename'])): ?>
                <div class="post-image">
                    <img src="uploads/<?= htmlspecialchars($post['image_filename']) ?>" 
                         alt="Imagen de la publicación" onclick="toggleImageSize(this)">
                </div>
                <?php endif; ?>
            </a>
            <div class="c-stats">
                <small><b>R:</b> <?= $reply_count ?> / <b>I:</b> <?= $reply_count_imgs ?></small>
            </div>
            <div>
                <b><?= htmlspecialchars($post['subject'] ?? 'Sin asunto') ?></b>
                <?= htmlspecialchars($message_preview) ?>
            </div>
        </div>
        <?php
    }

    /** Helpers para contar respuestas */
    private function countReplies(int $post_id, bool $withImages = false): int {
        global $pdo;
        $sql = "SELECT COUNT(*) FROM posts WHERE parent_id = ? AND is_deleted = 0";
        if ($withImages) $sql .= " AND image_filename IS NOT NULL AND image_filename != ''";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$post_id]);
        return (int) $stmt->fetchColumn();
    }

    private function renderThemes(): void {
        ?>
        <nav>
            <ul class="mini-menu">
                <div>
                    <label for="theme-select">Selecciona un tema:</label>
                    <select id="theme-select" onchange="changeTheme(this.value)">
                        <option value="yotsuba">Yotsuba</option>
                        <option value="yotsubab">Yotsuba Blue</option>
                        <option value="futaba">Futaba</option>
                        <option value="girls">Girls</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>
                <li>[<a href="#top">Subir</a>]</li>
            </ul>
        </nav>
        <?php
    }

    private function renderFooter(): void {
        ?>
        <footer id="footer">
            <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
        </footer>
        <?php
    }
}

/** Inicialización de la app */
function initApp() {
    try {
        $controller = new CatalogController();
        $view = new CatalogView($controller);
        $view->render();
    } catch (Exception $e) {
        header("Location: 404.php");
        exit;
    }
}

initApp();
