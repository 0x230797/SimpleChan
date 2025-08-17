<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

/**
 * Clase CatalogController - Maneja la lógica del catálogo
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
    
    /**
     * Verifica si el usuario está baneado
     */
    private function checkBanStatus() {
        $ban_info = is_user_banned();
        if ($ban_info) {
            $this->redirect('ban.php');
        }
    }
    
    /**
     * Inicializa el board actual
     */
    private function initializeBoard() {
        $board_name = $_GET['board'] ?? null;
        
        if ($board_name) {
            $board_name = urldecode(trim($board_name));
            
            // Buscar por short_id primero, luego por name
            $this->board = get_board_by_short_id($board_name);
            if (!$this->board) {
                $this->board = get_board_by_name($board_name);
            }
            
            if ($this->board) {
                $this->board_id = $this->board['id'];
            } else {
                $this->handleError('El tablón no existe.');
            }
        } else {
            $this->handleError('No se especificó un tablón válido.');
        }
    }
    
    /**
     * Carga los posts del board
     */
    private function loadPosts() {
        $this->posts = get_posts_by_board($this->board_id, 100, 0); // Máximo de 100 publicaciones
    }
    
    /**
     * Obtiene todos los boards organizados por categoría
     */
    public function getAllBoardsByCategory() {
        $all_boards = get_all_boards();
        $boards_by_category = [];
        
        foreach ($all_boards as $nav_board) {
            $category = $nav_board['category'] ?? 'Sin categoría';
            if (!isset($boards_by_category[$category])) {
                $boards_by_category[$category] = [];
            }
            $boards_by_category[$category][] = $nav_board;
        }
        
        return $boards_by_category;
    }
    
    /**
     * Obtiene un banner aleatorio
     */
    public function getRandomBanner() {
        $banner_dir = 'assets/banners/';
        
        if (!is_dir($banner_dir)) {
            return null;
        }
        
        $banners = array_diff(scandir($banner_dir), array('..', '.'));
        
        if (empty($banners)) {
            return null;
        }
        
        return $banner_dir . $banners[array_rand($banners)];
    }
    
    /**
     * Obtiene el board actual
     */
    public function getBoard() {
        return $this->board;
    }
    
    /**
     * Obtiene los posts
     */
    public function getPosts() {
        return $this->posts;
    }
    
    /**
     * Maneja errores fatales
     */
    private function handleError($message) {
        die('Error: ' . $message);
    }
    
    /**
     * Redirige a una URL
     */
    private function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/**
 * Clase CatalogView - Maneja la presentación del catálogo
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
    
    /**
     * Renderiza la página completa
     */
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>/<?php echo htmlspecialchars($this->board['short_id']); ?>/ <?php echo htmlspecialchars($this->board['name']); ?> - Catálogo SimpleChan</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes.css">
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body id="top">
            <?php $this->renderNavigation(); ?>
            <?php $this->renderHeader(); ?>
            <?php $this->renderMiniMenu(); ?>
            
            <main class="catalog">
                <?php $this->renderCatalogSection(); ?>
            </main>
            
            <?php $this->renderThemes(); ?>
            <?php $this->renderFooter(); ?>
            <script src="assets/js/script.js"></script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Renderiza la navegación con un formulario de búsqueda
     */
    private function renderNavigation() {
        ?>
        <nav>
            <ul>
                [<li>
                    <a href="index.php">Inicio</a>/<a href="reglas.php">Reglas</a>
                    <?php if (is_admin()): ?>
                    /<a href="admin.php">Administración</a>
                    <?php endif; ?>
                </li>]
                <?php foreach ($this->boards_by_category as $category => $boards): ?>
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
    
    /**
     * Renderiza el header
     */
    private function renderHeader() {
        ?>
        <header>
            <?php if ($this->random_banner): ?>
                <div class="banner">
                    <img src="<?php echo htmlspecialchars($this->random_banner); ?>" alt="Banner">
                </div>
            <?php endif; ?>
            <h1>Catálogo de /<?php echo htmlspecialchars($this->board['short_id']); ?>/ - <?php echo htmlspecialchars($this->board['name']); ?></h1>
            <p><?php echo htmlspecialchars($this->board['description']); ?></p>
        </header>
        <?php
    }

    /**
     * Renderiza el mini menú
     */
    private function renderMiniMenu() {
        ?>
        <nav>
            <ul class="mini-menu">
                <li>[<a href="boards.php?board=<?php echo htmlspecialchars($this->board['short_id']); ?>">Volver al tablón</a>]</li>
                <li>[<a href="#footer">Bajar</a>]</li>
            </ul>
        </nav>
        <?php
    }
    
    /**
     * Renderiza la sección del catálogo
     */
    private function renderCatalogSection() {
        ?>
        <section>
            <div class="box-outer top-box" id="catalog-threads">
                <div class="box-inner">
                    <div class="boxbar">
                        <h2>Catálogo de Publicaciones</h2>
                    </div>
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
    
    /**
     * Renderiza un post del catálogo
     */
    private function renderCatalogPost($post) {
        global $pdo;
        ?>
        <div class="c-thread">
            <a href="reply.php?post_id=<?php echo $post['id']; ?>" class="boardlink">
                <?php if (!empty($post['image_filename'])): ?>
                <div class="post-image">
                    <img src="uploads/<?php echo htmlspecialchars($post['image_filename']); ?>" alt="Imagen de la publicación" onclick="toggleImageSize(this)">
                </div>
                <?php endif; ?>
            </a>
            <div class="c-stats">
                <?php 
                $reply_stmt = $pdo->prepare("SELECT COUNT(*) as reply_count FROM posts WHERE parent_id = ? AND is_deleted = 0");
                $reply_stmt->execute([$post['id']]);
                $reply_count = $reply_stmt->fetchColumn();
                        
                $reply_imgs_stmt = $pdo->prepare("SELECT COUNT(*) as reply_imgs_count FROM posts WHERE parent_id = ? AND is_deleted = 0 AND image_filename IS NOT NULL AND image_filename != ''");
                $reply_imgs_stmt->execute([$post['id']]);
                $reply_count_imgs = $reply_imgs_stmt->fetchColumn();
                ?>
                <small><b>R:</b> <?php echo $reply_count; ?> / <b>I:</b> <?php echo $reply_count_imgs; ?></small>
            </div>
            <div>
                <b><?php echo htmlspecialchars($post['subject'] ?? 'Sin asunto'); ?></b>
                <?php 
                $message_preview = strip_tags($post['message']);
                echo htmlspecialchars(strlen($message_preview) > 100 ? substr($message_preview, 0, 100) . '...' : $message_preview); 
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza los temas
     */
    private function renderThemes() {
        ?>
        <nav>
            <ul class="mini-menu">
                <div class="theme-selector">
                    <label for="theme-select">Selecciona un tema:</label>
                    <select id="theme-select" onchange="changeTheme(this.value)">
                        <option value="yotsuba">Yotsuba</option>
                        <option value="yotsubab">Yotsuba Blue</option>
                        <option value="futaba">Futaba</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>
                <li>[<a href="#top">Subir</a>]</li>
            </ul>
        </nav>
        <?php
    }
    
    /**
     * Renderiza el footer
     */
    private function renderFooter() {
        ?>
        <footer id="footer">
            <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
        </footer>
        <?php
    }
}

// Inicializar la aplicación
try {
    $controller = new CatalogController();
    $view = new CatalogView($controller);
    $view->render();
} catch (Exception $e) {
    // En caso de error, mostrar página de error básica
    header("Location: 404.php");
    exit;
}
?>
