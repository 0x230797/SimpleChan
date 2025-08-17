<?php

/**
 * Vista del tablón
 * 
 * Maneja toda la presentación y renderizado del HTML
 * para la página del tablón.
 */
class BoardView 
{
    private BoardController $controller;
    private ?array $board;
    private array $posts;
    private array $pagination_info;
    private array $boards_by_category;
    private array $messages;
    private ?string $random_banner;
    
    /**
     * Constructor
     */
    public function __construct(BoardController $controller) 
    {
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
    public function render(): void 
    {
        $this->renderDocumentStart();
        $this->renderNavigation();
        $this->renderHeader();
        $this->renderCreatePostSection();
        $this->renderMiniMenu();
        $this->renderMain();
        $this->renderPagination();
        $this->renderThemes();
        $this->renderFooter();
        $this->renderDocumentEnd();
    }
    
    // ==========================================
    // RENDERIZADO DE ESTRUCTURA PRINCIPAL
    // ==========================================
    
    /**
     * Renderiza el inicio del documento HTML
     */
    private function renderDocumentStart(): void 
    {
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
        <?php
    }
    
    /**
     * Renderiza el cierre del documento HTML
     */
    private function renderDocumentEnd(): void 
    {
        ?>
        <script src="assets/js/script.js"></script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Renderiza el contenido principal
     */
    private function renderMain(): void 
    {
        ?>
        <main>
            <?php 
            $this->renderMessages(); 
            $this->renderPostsSection(); 
            ?>
        </main>
        <?php
    }
    
    // ==========================================
    // RENDERIZADO DE NAVEGACIÓN Y HEADER
    // ==========================================
    
    /**
     * Renderiza la navegación principal
     */
    private function renderNavigation(): void 
    {
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
     * Renderiza el header con banner e información del board
     */
    private function renderHeader(): void 
    {
        ?>
        <header>
            <?php if ($this->random_banner): ?>
                <div class="banner">
                    <img src="<?php echo htmlspecialchars($this->random_banner); ?>" alt="Banner">
                </div>
            <?php endif; ?>
            
            <h1>/<?php echo htmlspecialchars($this->board['short_id']); ?>/ - <?php echo htmlspecialchars($this->board['name']); ?></h1>
            <p><?php echo htmlspecialchars($this->board['description']); ?></p>
        </header>
        <?php
    }
    
    /**
     * Renderiza el mini menú con búsqueda
     */
    private function renderMiniMenu(): void 
    {
        ?>
        <nav>
            <ul class="mini-menu">
                <form method="get" action="boards.php" class="search-form" style="margin:0">
                    <input type="hidden" name="board" value="<?php echo htmlspecialchars($this->board['short_id']); ?>">
                    <input type="text" name="query" placeholder="Buscar publicaciones..." 
                        value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" 
                        required autocomplete="off">
                    [<button type="submit">Buscar</button>]
                    <?php if (isset($_GET['query']) && !empty($_GET['query'])): ?>
                        [<a href="boards.php?board=<?php echo htmlspecialchars($this->board['short_id']); ?>" 
                        class="btn-clear-search" style="font-size:13px">Limpiar</a>]
                    <?php endif; ?>
                </form>
                <li>[<a href="#footer">Bajar</a>]</li>
                <li>[<a href="catalog.php?board=<?php echo htmlspecialchars($this->board['short_id']); ?>">Catálogo</a>]</li>
            </ul>
        </nav>
        <?php
    }
    
    // ==========================================
    // RENDERIZADO DE MENSAJES Y FORMULARIOS
    // ==========================================
    
    /**
     * Renderiza los mensajes de estado
     */
    private function renderMessages(): void 
    {
        // Mensajes del controlador
        foreach (['error', 'success'] as $type) {
            if (isset($this->messages[$type])) {
                echo '<div class="' . $type . '">' . htmlspecialchars($this->messages[$type]) . '</div>';
            }
        }

        // Mensajes de sesión
        if (isset($_SESSION['success_message'])) {
            echo '<div class="success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
    }
    
    /**
     * Renderiza la sección de creación de posts
     */
    private function renderCreatePostSection(): void 
    {
        ?>
        <section class="create-post">
            [ <button onclick="toggleCreateForm('post')" id="toggle-post" class="btn-create-post">
                Crear publicación
            </button> ]
        </section>

        <section class="form-create-post">
            <div class="post-form" id="create-post" style="display: none;">
                <h2>Crear nueva publicación</h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php $this->renderPostForm(); ?>
                </form>
            </div>
        </section>
        <?php
    }
    
    /**
     * Renderiza el formulario de post
     */
    private function renderPostForm(): void 
    {
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
        
        <?php $this->renderFormatButtons(); ?>
        
        <div class="form-group">
            <label for="message">Mensaje:</label>
            <textarea id="message" name="message" required rows="5" placeholder="Tu publicación..." autocomplete="off"></textarea>
        </div>
        
        <?php $this->renderImageUpload(); ?>
        
        <div class="form-buttons">
            <button type="submit" name="submit_post">Crear publicación</button>
        </div>
        <?php
    }
    
    /**
     * Renderiza los botones de formato
     */
    private function renderFormatButtons(): void 
    {
        ?>
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
        <?php
    }
    
    /**
     * Renderiza la sección de carga de imagen
     */
    private function renderImageUpload(): void 
    {
        ?>
        <div class="form-group">
            <label for="image">Imagen:</label>
            <input type="file" id="image" name="image" accept="image/*" <?php echo is_admin() ? '' : 'required'; ?>>
            <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
                Formatos permitidos: JPG, JPEG, PNG, GIF, WEBP. Tamaño máximo: 5MB.
            </span>
            <br>
            <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
                Antes de hacer una publicación, recuerda leer las <a href="reglas.php">reglas</a>.
            </span>
        </div>
        <?php
    }
    
    // ==========================================
    // RENDERIZADO DE POSTS
    // ==========================================
    
    /**
     * Renderiza la sección de posts
     */
    private function renderPostsSection(): void 
    {
        ?>
        <section class="posts">
            <?php if ($this->controller->getSearchResults() !== null): ?>
                <h2>Resultados de búsqueda</h2>
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
    private function renderPost(array $post): void 
    {
        ?>
        <article class="post" id="post-<?php echo $post['id']; ?>">
            <?php 
            $this->renderPostFileInfo($post);
            $this->renderPostImage($post);
            $this->renderPostHeader($post);
            $this->renderPostMessage($post);
            $this->renderPostReplies($post);
            ?>
        </article>
        <?php
    }
    
    /**
     * Renderiza información del archivo del post
     */
    private function renderPostFileInfo(array $post): void 
    {
        if (empty($post['image_filename'])) {
            return;
        }
        ?>
        <div class="post-header-file">
            Archivo: <a href="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" target="_blank" 
                     title="<?php echo UPLOAD_DIR . $post['image_filename']; ?>">
                <?php echo htmlspecialchars($post['image_filename']); ?>
            </a> 
            (<?php echo format_file_size($post['image_size']) . ', ' . $post['image_dimensions']; ?>)
        </div>
        <?php
    }
    
    /**
     * Renderiza la imagen del post
     */
    private function renderPostImage(array $post): void 
    {
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
     * Renderiza el header del post
     */
    private function renderPostHeader(array $post): void 
    {
        ?>
        <div class="post-header">
            <?php 
            $this->renderPostSubject($post);
            $this->renderPostName($post);
            $this->renderPostDate($post);
            $this->renderPostNumber($post);
            $this->renderPostActions($post);
            $this->renderPostIcons($post);
            $this->renderAdminActions($post);
            ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el asunto del post
     */
    private function renderPostSubject(array $post): void 
    {
        if (!empty($post['subject'])) {
            echo '<span class="post-subject">' . htmlspecialchars($post['subject']) . '</span>';
        }
    }
    
    /**
     * Renderiza el nombre del post
     */
    private function renderPostName(array $post): void 
    {
        if ($post['name'] === 'Administrador') {
            echo '<span class="admin-name">Administrador</span>';
        } else {
            echo '<span class="post-name">' . htmlspecialchars($post['name']) . '</span>';
        }
    }
    
    /**
     * Renderiza la fecha del post
     */
    private function renderPostDate(array $post): void 
    {
        echo '<span>' . date('d/m/Y H:i:s', strtotime($post['created_at'])) . '</span>';
    }
    
    /**
     * Renderiza el número del post
     */
    private function renderPostNumber(array $post): void 
    {
        echo '<span>';
        echo '<a href="reply.php?post_id=' . $post['id'] . '&ref=' . $post['id'] . '">No. ' . $post['id'] . '</a>';
        echo '</span>';
    }
    
    /**
     * Renderiza las acciones del post (responder, reportar)
     */
    private function renderPostActions(array $post): void 
    {
        // Botón responder
        if ($post['is_locked']) {
            echo '<span>[<a href="#" class="btn-reply" onclick="alert(\'No puedes responder a una publicación bloqueada\'); window.location.reload(); return false;">Responder</a></span>]';
        } else {
            echo '<span>[<a href="reply.php?post_id=' . $post['id'] . '" class="btn-reply">Responder</a>]</span>';
        }
        
        // Menú de reporte
        $this->renderReportMenu($post);
    }
    
    /**
     * Renderiza el menú de reporte
     */
    private function renderReportMenu(array $post): void 
    {
        ?>
        <div class="report-menu-wrapper">
            <span>[<button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>]</span>
            <nav class="report-menu" id="report-menu-<?php echo $post['id']; ?>">
                <form method="POST" action="boards.php">
                    <input type="hidden" name="report_post_id" value="<?php echo $post['id']; ?>">
                    <input type="hidden" name="board" value="<?php echo htmlspecialchars($this->board['short_id']); ?>">
                    <label for="report_reason_<?php echo $post['id']; ?>">Motivo:</label>
                    <select id="report_reason_<?php echo $post['id']; ?>" name="report_reason" autocomplete="off">
                        <option value="spam">Spam</option>
                        <option value="contenido ilegal">Contenido ilegal</option>
                        <option value="acoso">Acoso</option>
                        <option value="otro">Otro</option>
                    </select>
                    <input type="text" name="report_details" placeholder="Detalles (opcional)" autocomplete="off">
                    <button type="submit" name="submit_report">Enviar reporte</button>
                </form>
            </nav>
        </div>
        <?php
    }
    
    /**
     * Renderiza los iconos del post (fijado, bloqueado)
     */
    private function renderPostIcons(array $post): void 
    {
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
    private function renderAdminActions(array $post): void 
    {
        if (!is_admin()) {
            return;
        }
        ?>
        <form method="POST" action="admin_actions.php" style="display:inline;">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
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
            [<button type="submit" name="delete_post" class="btn-delete" onclick="return confirm('¿Estás seguro de que quieres eliminar este post?')">Eliminar</button>]
        </form>
        <?php
    }
    
    /**
     * Renderiza el mensaje del post
     */
    private function renderPostMessage(array $post): void 
    {
        ?>
        <div class="post-message">
            <?php echo parse_references($post['message'], $post['name'] === 'Administrador'); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza las respuestas del post
     */
    private function renderPostReplies(array $post): void 
    {
        $replies = get_replies($post['id']);
        
        if (empty($replies)) {
            return;
        }
        
        $last_replies = array_slice($replies, -5); // Mostrar últimas 5 respuestas
        $total_replies = count($replies);
        ?>
        <div class="replies">
            <?php if ($total_replies > 5): ?>
                <p class="replies-omitted">
                    <?php echo ($total_replies - 5); ?> respuesta(s) omitida(s). 
                    <a href="reply.php?post_id=<?php echo $post['id']; ?>">Ver todas</a>
                </p>
            <?php endif; ?>
            <?php foreach ($last_replies as $reply): ?>
                <article class="reply" id="post-<?php echo $reply['id']; ?>">
                    <div class="post-header">
                        <?php 
                        $this->renderPostFileInfo($reply);
                        $this->renderPostName($reply);
                        $this->renderPostDate($reply);
                        ?>
                        <span>
                            <a href="reply.php?post_id=<?php echo $reply['parent_id'] ?: $reply['id']; ?>&ref=<?php echo $reply['id']; ?>">
                                No. <?php echo $reply['id']; ?>
                            </a>
                        </span>
                        <?php $this->renderReportMenu($reply); ?>
                        <?php if (is_admin()): ?>
                            <form method="POST" action="admin_actions.php" style="display:inline;">
                                <input type="hidden" name="post_id" value="<?php echo $reply['id']; ?>">
                                <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                [<button type="submit" name="delete_post" class="btn-delete" onclick="return confirm('¿Estás seguro?')">Eliminar</button>]
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php 
                    $this->renderPostImage($reply);
                    $this->renderPostMessage($reply);
                    ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    // ==========================================
    // RENDERIZADO DE PAGINACIÓN Y FOOTER
    // ==========================================
    
    /**
     * Renderiza la paginación
     */
    private function renderPagination(): void 
    {
        // No mostrar paginación si hay solo 1 página o menos
        if ($this->pagination_info['total_pages'] <= 1) {
            return;
        }
        ?>
        <section class="pagination">
            <div class="pagination-controls">
                <?php $this->renderPaginationControls(); ?>
            </div>
        </section>
        <?php
    }

    /**
     * Renderiza los controles de paginación
     */
    private function renderPaginationControls(): void 
    {
        $current = $this->pagination_info['current_page'];
        $total = $this->pagination_info['total_pages'];
        $board_id = $this->board['short_id'];
        $query = isset($_GET['query']) ? '&query=' . urlencode($_GET['query']) : '';

        // Botón anterior
        if ($current > 1) {
            echo '<span class="pagination-item">[<a href="?board=' . htmlspecialchars($board_id) . '&page=' . ($current - 1) . $query . '" class="pagination-btn">« Anterior</a>]</span>';
        }

        // Números de página
        $start = max(1, $current - 2);
        $end = min($total, $current + 2);

        if ($start > 1) {
            echo '<span class="pagination-item">[<a href="?board=' . htmlspecialchars($board_id) . '&page=1' . $query . '" class="pagination-btn">1</a>]</span>';
            if ($start > 2) {
                echo '<span class="pagination-item">...</span>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($i == $current) {
                echo '<span class="pagination-item">[<span class="pagination-btn current">' . $i . '</span>]</span>';
            } else {
                echo '<span class="pagination-item">[<a href="?board=' . htmlspecialchars($board_id) . '&page=' . $i . $query . '" class="pagination-btn">' . $i . '</a>]</span>';
            }
        }

        if ($end < $total) {
            if ($end < $total - 1) {
                echo '<span class="pagination-item">...</span>';
            }
            echo '<span class="pagination-item">[<a href="?board=' . htmlspecialchars($board_id) . '&page=' . $total . $query . '" class="pagination-btn">' . $total . '</a>]</span>';
        }

        // Botón siguiente
        if ($current < $total) {
            echo '<span class="pagination-item">[<a href="?board=' . htmlspecialchars($board_id) . '&page=' . ($current + 1) . $query . '" class="pagination-btn">Siguiente »</a>]</span>';
        }
    }

    /**
     * Renderiza el selector de temas
     */
    private function renderThemes(): void 
    {
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
    
    /**
     * Renderiza el footer
     */
    private function renderFooter(): void 
    {
        ?>
        <footer id="footer">
            <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
        </footer>
        <?php
    }
}