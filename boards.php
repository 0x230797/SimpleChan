<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

/**
 * Clase BoardController - Maneja la lógica del board
 */
class BoardController {
    private $board;
    private $board_id;
    private $current_page;
    private $posts_per_page = 10; // Posts por página
    private $messages = [];
    private $search_results = null;
    
    public function __construct() {
        $this->checkBanStatus();
        $this->initializeBoard();
        $this->initializePagination();
        $this->processRequests();
        $this->processGetRequests();
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
        $this->board_id = $_GET['board_id'] ?? null;
        
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
        } elseif ($this->board_id) {
            $this->board_id = (int)$this->board_id;
            $this->board = get_board_by_id($this->board_id);
            
            if (!$this->board) {
                $this->handleError('El tablón no existe.');
            }
        } else {
            $this->handleError('No se especificó un tablón válido.');
        }
    }
    
    /**
     * Inicializa la paginación
     */
    private function initializePagination() {
        $this->current_page = max(1, (int)($_GET['page'] ?? 1));
        
        $total_posts = count_posts_by_board($this->board_id);
        $total_pages = ceil($total_posts / $this->posts_per_page);
        
        // Ajustar página actual si excede el total
        if ($this->current_page > $total_pages && $total_pages > 0) {
            $this->current_page = $total_pages;
        }
    }
    
    /**
     * Procesa las peticiones POST
     */
    private function processRequests() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        if (isset($_POST['submit_report'])) {
            $this->processReport();
        } elseif (isset($_POST['submit_post'])) {
            $this->processPost();
        }
    }
    
    /**
     * Procesa las peticiones GET, incluyendo búsquedas
     */
    private function processGetRequests() {
        if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
            $this->processSearch();
        }
    }

    /**
     * Procesa una búsqueda de publicaciones
     */
    private function processSearch() {
        $query = clean_input($_GET['query']);
        $this->search_results = search_posts($query, $this->board_id);
        if (empty($this->search_results)) {
            $this->addError('No se encontraron publicaciones para la búsqueda: ' . htmlspecialchars($query));
        } else {
            $count = count($this->search_results);
            $this->addSuccess('Resultados para: ' . htmlspecialchars($query) . ' (' . $count . ' publicación' . ($count != 1 ? 'es' : '') . ' encontrada' . ($count != 1 ? 's' : '') . ')');
        }
    }
    
    /**
     * Procesa un reporte
     */
    private function processReport() {
        $post_id = (int)($_POST['report_post_id'] ?? 0);
        $reason = clean_input($_POST['report_reason'] ?? '');
        $details = clean_input($_POST['report_details'] ?? '');
        $reporter_ip = get_user_ip();
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return;
        }
        
        if (empty($reason)) {
            $this->addError('El motivo del reporte es requerido.');
            return;
        }
        
        if (create_report($post_id, $reason, $details, $reporter_ip)) {
            $redirect_url = $this->buildUrl(['report_success' => 1]);
            $this->redirect($redirect_url);
        } else {
            $this->addError('Error al enviar el reporte.');
        }
    }
    
    /**
     * Procesa la creación de un post
     */
    private function processPost() {
        $post_data = $this->validatePostData();
        
        if ($this->hasErrors()) {
            return;
        }
        
        $image_result = $this->processImage();
        
        if ($this->hasErrors()) {
            return;
        }
        
        if ($this->createPost($post_data, $image_result)) {
            $redirect_url = $this->buildUrl(['post_success' => 1], 1);
            $this->redirect($redirect_url);
        } else {
            $this->addError('Error al crear el post.');
        }
    }
    
    /**
     * Valida los datos del post
     */
    private function validatePostData() {
        $name = clean_input($_POST['name'] ?? '');
        $subject = clean_input($_POST['subject'] ?? '');
        $message = clean_input($_POST['message'] ?? '');
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        // Si el nombre está vacío, usar "Anónimo"
        if (empty(trim($name))) {
            $name = 'Anónimo';
        }
        
        if (empty($message)) {
            $this->addError('El mensaje no puede estar vacío.');
            return null;
        }
        
        return [
            'name' => $name,
            'subject' => $subject,
            'message' => $message,
            'parent_id' => $parent_id
        ];
    }
    
    /**
     * Procesa la imagen subida
     */
    private function processImage() {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            // Para usuarios no admin, la imagen es requerida
            if (!is_admin()) {
                $this->addError('La imagen es requerida.');
                return null;
            }
            return ['filename' => null, 'original_name' => null];
        }
        
        $upload_result = upload_image($_FILES['image']);
        
        if (!$upload_result['success']) {
            $this->addError($upload_result['error']);
            return null;
        }
        
        return [
            'filename' => $upload_result['filename'],
            'original_name' => $upload_result['original_name']
        ];
    }
    
    /**
     * Crea el post
     */
    private function createPost($post_data, $image_result) {
        $result = create_post(
            $post_data['name'],
            $post_data['subject'],
            $post_data['message'],
            $image_result['filename'],
            $image_result['original_name'],
            $post_data['parent_id'],
            $this->board_id
        );

        if ($result) {
            $this->enforcePostLimit(); // Verificar y eliminar publicaciones si es necesario
        }

        return $result;
    }
    
    /**
     * Aplica el límite de publicaciones por tablón
     */
    private function enforcePostLimit() {
        $total_posts = count_posts_by_board($this->board_id);
        if ($total_posts > 100) {
            $oldest_post = get_oldest_post($this->board_id);
            if ($oldest_post) {
                delete_post($oldest_post['id']);
                if (!empty($oldest_post['image_filename'])) {
                    $image_path = UPLOAD_DIR . $oldest_post['image_filename'];
                    if (file_exists($image_path)) {
                        unlink($image_path); // Eliminar la imagen asociada
                    }
                }
            }
        }
    }
    
    /**
     * Obtiene los posts del board
     */
    public function getPosts() {
        // Si hay resultados de búsqueda, retornarlos
        if ($this->search_results !== null) {
            return $this->search_results;
        }
        
        // Si no hay búsqueda, obtener posts normales con paginación
        $offset = ($this->current_page - 1) * $this->posts_per_page;
        return get_posts_by_board($this->board_id, $this->posts_per_page, $offset);
    }
    
    /**
     * Obtiene información de paginación
     */
    public function getPaginationInfo() {
        $total_posts = count_posts_by_board($this->board_id);
        $total_pages = ceil($total_posts / $this->posts_per_page);
        
        return [
            'current_page' => $this->current_page,
            'total_pages' => $total_pages,
            'total_posts' => $total_posts,
            'posts_per_page' => $this->posts_per_page
        ];
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
     * Construye URL con parámetros
     */
    private function buildUrl($extra_params = [], $page = null) {
        $params = ['board' => $this->board['short_id']];
        
        if ($page === null) {
            $page = $this->current_page;
        }
        
        if ($page > 1) {
            $params['page'] = $page;
        }
        
        $params = array_merge($params, $extra_params);
        
        return $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
    }
    
    /**
     * Obtiene el board actual
     */
    public function getBoard() {
        return $this->board;
    }
    
    /**
     * Obtiene los mensajes
     */
    public function getMessages() {
        return $this->messages;
    }
    
    /**
     * Obtiene los resultados de búsqueda
     */
    public function getSearchResults() {
        return $this->search_results;
    }
    
    /**
     * Verifica si hay errores
     */
    private function hasErrors() {
        return isset($this->messages['error']);
    }
    
    /**
     * Añade un mensaje de error
     */
    private function addError($message) {
        $this->messages['error'] = $message;
    }
    
    /**
     * Añade un mensaje de éxito
     */
    private function addSuccess($message) {
        $this->messages['success'] = $message;
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
 * Clase BoardView - Maneja la presentación del board
 */
class BoardView {
    private $controller;
    private $board;
    private $posts;
    private $pagination_info;
    private $boards_by_category;
    private $messages;
    private $random_banner;
    
    public function __construct(BoardController $controller) {
        $this->controller = $controller;
        $this->board = $controller->getBoard();
        $this->posts = $controller->getPosts();
        $this->pagination_info = $controller->getPaginationInfo();
        $this->boards_by_category = $controller->getAllBoardsByCategory();
        $this->messages = $controller->getMessages();
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
            <title>/<?php echo htmlspecialchars($this->board['short_id']); ?>/ <?php echo htmlspecialchars($this->board['name']); ?> - SimpleChan</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes.css">
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body id="top">
            <?php $this->renderNavigation(); ?>
            <?php $this->renderHeader(); ?>
            <?php $this->renderMiniMenu(); ?>
            
            <main>
                <?php $this->renderMessages(); ?>
                <?php $this->renderCreatePostSection(); ?>
                <?php $this->renderPostsSection(); ?>
                <?php $this->renderPagination(); ?>
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
                    <a href="index.php">Inicio</a>/
                    <a href="reglas.php">Reglas</a>
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
            <?php $this->renderInfoBoard(); ?>
        </header>
        <?php
    }

    /**
     * Renderiza información del board
     */
    private function renderInfoBoard() {
        ?>
        <h1>/<?php echo htmlspecialchars($this->board['short_id']); ?>/ - <?php echo htmlspecialchars($this->board['name']); ?></h1>
        <p><?php echo htmlspecialchars($this->board['description']); ?></p>
        <?php
    }

    /**
     * Renderiza información del board
     */
    private function renderMiniMenu() {
        ?>
        <nav>
            <ul class="mini-menu">
                <form method="get" action="boards.php" class="search-form" style="margin:0">
                    <input type="hidden" name="board" value="<?php echo htmlspecialchars($this->board['short_id']); ?>">
                    <input type="text" name="query" placeholder="Buscar publicaciones..." 
                        value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required autocomplete="off">
                    [<button type="submit">Buscar</button>]
                    <?php if (isset($_GET['query']) && !empty($_GET['query'])): ?>
                        [<a href="boards.php?board=<?php echo htmlspecialchars($this->board['short_id']); ?>" 
                        class="btn-clear-search" style="font-size:13px">Limpiar</a>]
                    <?php endif; ?>
                </form>
                <li>[<a href="index.php">Retornar</a>]</li>
                <li>[<a href="#footer">Bajar</a>]</li>
                <li>[<a href="catalog.php?board=<?php echo htmlspecialchars($this->board['short_id']); ?>">Catálogo</a>]</li>
            </ul>
        </nav>
        <?php
    }
    
    /**
     * Renderiza los mensajes de estado
     */
    private function renderMessages() {
        // Mensajes de URL (success)
        if (isset($_GET['report_success']) && $_GET['report_success'] == 1) {
            echo '<div class="success">¡Gracias por reportar! El reporte ha sido enviado al administrador.</div>';
        }

        if (isset($_GET['post_success']) && $_GET['post_success'] == 1) {
            echo '<div class="success">¡Post creado exitosamente!</div>';
        }

        // Mensajes del controlador
        if (isset($this->messages['error'])) {
            echo '<div class="error">' . htmlspecialchars($this->messages['error']) . '</div>';
        }

        if (isset($this->messages['success'])) {
            echo '<div class="success">' . htmlspecialchars($this->messages['success']) . '</div>';
        }
    }
    
    /**
     * Renderiza la sección de crear post
     */
    private function renderCreatePostSection() {
        ?>
        <!-- Botón para mostrar formulario -->
        <section class="create-post">
            [ <button onclick="toggleCreateForm('post')" id="toggle-post" class="btn-create-post">
                Crear publicación
            </button> ]
        </section>

        <!-- Formulario para nuevo post -->
        <section class="post-form" id="create-post" style="display: none;">
            <h2>Crear nuevo publicación</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <?php $this->renderPostForm(); ?>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza el formulario de post
     */
    private function renderPostForm() {
        ?>
        <div class="form-group">
            <label for="name">Nombre (opcional):</label>
            <?php if (is_admin()): ?>
                <input type="text" id="name" name="name" value="Administrador" readonly class="admin-name" autocomplete="username">
            <?php else: ?>
                <input type="text" id="name" name="name" placeholder="Anónimo" maxlength="50" autocomplete="username">
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="subject">Asunto:</label>
            <input type="text" id="subject" name="subject" maxlength="100" required autocomplete="off">
        </div>
        
        <div class="form-group">
            <span class="form-label">Formatos:</span>
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
            <textarea id="message" name="message" required rows="5" placeholder="Tu publicación..." autocomplete="off"></textarea>
        </div>
        
        <div class="form-group">
            <label for="image">Imagen:</label>
            <?php if (is_admin()): ?>
                <input type="file" id="image" name="image" accept="image/*">
            <?php else: ?>
                <input type="file" id="image" name="image" accept="image/*" required>
            <?php endif; ?>
            <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
                Formatos permitidos: JPG, JPEG, PNG, GIF, WEBP. Tamaño máximo: 5MB.
            </span>
            <br>
            <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
                Antes de hacer una publicación, recuerda leer las <a href="reglas.php">reglas</a>.
            </span>
        </div>
        
        <div class="form-buttons">
            <button type="submit" name="submit_post">Crear publicación</button>
        </div>
        <?php
    }
    
    /**
     * Renderiza la sección de posts
     */
    private function renderPostsSection() {
        ?>
        <section class="posts">
            <?php if ($this->controller->getSearchResults() !== null): ?>
                <h2>Resultados de búsqueda</h2>
            <?php else: ?>
                <h2>Publicaciones</h2>
            <?php endif; ?>
            
            <?php if (empty($this->posts)): ?>
                <?php if ($this->controller->getSearchResults() !== null): ?>
                    <p>No se encontraron publicaciones que coincidan con tu búsqueda.</p>
                <?php else: ?>
                    <p>No hay posts aún. ¡Sé el primero en publicar!</p>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($this->posts as $post): ?>
                    <?php if ($post['parent_id'] === null): ?>
                        <?php $this->renderPost($post); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <?php
    }
    
    /**
     * Renderiza un post individual
     */
    private function renderPost($post) {
        ?>
        <article class="post" id="post-<?php echo $post['id']; ?>">
            <?php $this->renderPostHeader($post); ?>
            <?php $this->renderPostImage($post); ?>
            <?php $this->renderPostMessage($post); ?>
            <?php $this->renderPostReplies($post); ?>
        </article>
        <?php
    }
    
    /**
     * Renderiza el header del post
     */
    private function renderPostHeader($post) {
        ?>
        <div class="post-header">
            <?php $this->renderPostName($post); ?>
            <?php $this->renderPostSubject($post); ?>
            <?php $this->renderPostDate($post); ?>
            <?php $this->renderPostNumber($post); ?>
            <?php $this->renderPostActions($post); ?>
            <?php $this->renderPostIcons($post); ?>
            <?php $this->renderAdminActions($post); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el nombre del post
     */
    private function renderPostName($post) {
        if ($post['name'] === 'Administrador') {
            echo '<span class="admin-name">Administrador</span>';
        } else {
            echo '<span class="post-name">' . htmlspecialchars($post['name']) . '</span>';
        }
    }
    
    /**
     * Renderiza el asunto del post
     */
    private function renderPostSubject($post) {
        if (!empty($post['subject'])) {
            echo '<span class="post-subject">' . htmlspecialchars($post['subject']) . '</span>';
        }
    }
    
    /**
     * Renderiza la fecha del post
     */
    private function renderPostDate($post) {
        echo '<span>' . date('d/m/Y H:i:s', strtotime($post['created_at'])) . '</span>';
    }
    
    /**
     * Renderiza el número del post
     */
    private function renderPostNumber($post) {
        echo '<span>';
        echo '<a href="reply.php?post_id=' . $post['id'] . '&ref=' . $post['id'] . '">No. ' . $post['id'] . '</a>';
        echo '</span>';
    }
    
    /**
     * Renderiza las acciones del post (responder, reportar)
     */
    private function renderPostActions($post) {
        // Botón responder
        if ($post['is_locked']) {
            echo '[<a href="#" class="btn-reply" onclick="alert(\'No puedes responder a una publicación bloqueada\'); window.location.reload(); return false;">Responder</a>]';
        } else {
            echo '[<a href="reply.php?post_id=' . $post['id'] . '" class="btn-reply">Responder</a>]';
        }
        
        // Menú de reporte
        $this->renderReportMenu($post);
    }
    
    /**
     * Renderiza el menú de reporte
     */
    private function renderReportMenu($post) {
        ?>
        <div class="report-menu-wrapper" style="display:inline-block;position:relative;">
            [<button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>]
            <nav class="report-menu" id="report-menu-<?php echo $post['id']; ?>" 
                 style="display:none;position:fixed;width:1px;z-index:10;background:#f7e5e5;border:1px solid rgb(136 0 0);padding:10px;min-width:150px;">
                <form method="POST" action="index.php" style="margin:0;">
                    <input type="hidden" name="report_post_id" value="<?php echo $post['id']; ?>">
                    <label for="report_reason_<?php echo $post['id']; ?>" style="display:block;margin-bottom:5px;">Motivo:</label>
                    <select id="report_reason_<?php echo $post['id']; ?>" name="report_reason" style="width:100%;margin-bottom:5px;" autocomplete="off">
                        <option value="spam">Spam</option>
                        <option value="contenido ilegal">Contenido ilegal</option>
                        <option value="acoso">Acoso</option>
                        <option value="otro">Otro</option>
                    </select>
                    <input type="text" name="report_details" placeholder="Detalles (opcional)" style="width:100%;margin-bottom:5px;" autocomplete="off">
                    <button type="submit" name="submit_report" style="width:100%;background:#800;color:#fff;padding: 2px;">Enviar reporte</button>
                </form>
            </nav>
        </div>
        <?php
    }
    
    /**
     * Renderiza los iconos del post (fijado, bloqueado)
     */
    private function renderPostIcons($post) {
        if ($post['is_locked']) {
            echo '<img src="assets/imgs/closed.png" alt="Bloqueado">';
        }
        
        if ($post['is_pinned']) {
            echo '<img src="assets/imgs/sticky.png" alt="Fijado">';
        }
    }
    
    /**
     * Renderiza las acciones de administrador
     */
    private function renderAdminActions($post) {
        if (!is_admin()) {
            return;
        }
        ?>
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
        <?php
    }
    
    /**
     * Renderiza la imagen del post
     */
    private function renderPostImage($post) {
        if (empty($post['image_filename'])) {
            return;
        }
        ?>
        <div class="post-image">
            <?php if (file_exists(UPLOAD_DIR . $post['image_filename'])): ?>
                <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                     alt="<?php echo htmlspecialchars($post['image_original_name']); ?>"
                     onclick="toggleImageSize(this)">
            <?php else: ?>
                <img src="assets/imgs/filedeleted.png" alt="Imagen no disponible">
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el mensaje del post
     */
    private function renderPostMessage($post) {
        ?>
        <div class="post-message">
            <?php echo parse_references($post['message'], $post['name'] === 'Administrador'); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza las respuestas del post
     */
    private function renderPostReplies($post) {
        $replies = get_replies($post['id']);
        
        if (empty($replies)) {
            return;
        }
        
        $last_replies = array_slice($replies, -5); // Mostrar últimas 5 respuestas
        ?>
        <div class="replies">
            <?php foreach ($last_replies as $reply): ?>
                <article class="reply" id="post-<?php echo $reply['id']; ?>">
                    <div class="post-header">
                        <?php $this->renderPostName($reply); ?>
                        <?php $this->renderPostDate($reply); ?>
                        <span>
                            <a href="reply.php?post_id=<?php echo $reply['parent_id'] ?: $reply['id']; ?>&ref=<?php echo $reply['id']; ?>">
                                No. <?php echo $reply['id']; ?>
                            </a>
                        </span>
                    </div>
                    <?php $this->renderPostImage($reply); ?>
                    <?php $this->renderPostMessage($reply); ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza la paginación
     */
    private function renderPagination() {
        // No mostrar paginación durante búsquedas
        if ($this->controller->getSearchResults() !== null) {
            return;
        }
        
        if ($this->pagination_info['total_pages'] <= 1) {
            return;
        }
        ?>
        <section class="pagination">
            <div class="pagination-info">
                <p>Página <?php echo $this->pagination_info['current_page']; ?> de <?php echo $this->pagination_info['total_pages']; ?> 
                   (<?php echo $this->pagination_info['total_posts']; ?> posts totales)</p>
            </div>
            <div class="pagination-controls">
                <?php $this->renderPaginationControls(); ?>
            </div>
        </section>
        <?php
    }
    
    /**
     * Renderiza los controles de paginación
     */
    private function renderPaginationControls() {
        $current = $this->pagination_info['current_page'];
        $total = $this->pagination_info['total_pages']; // Siempre 10
        $board_id = $this->board['short_id'];

        // Como siempre habrá exactamente 10 páginas, mostrar todas
        $start_page = 1;
        $end_page = $total; // Será 10

        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current) {
                echo '<span class="pagination-btn current">' . $i . '</span>';
            } else {
                echo '<a href="?board=' . htmlspecialchars($board_id) . '&page=' . $i . '" class="pagination-btn">' . $i . '</a>';
            }
        }
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
    $controller = new BoardController();
    $view = new BoardView($controller);
    $view->render();
} catch (Exception $e) {
    // En caso de error, mostrar página de error básica
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Error - SimpleChan</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
            .error { color: #800; background: #f7e5e5; padding: 20px; margin: 20px auto; max-width: 500px; border: 1px solid #800; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
            <p><a href="index.php">Volver al inicio</a></p>
        </div>
    </body>
    </html>
    <?php
}
?>