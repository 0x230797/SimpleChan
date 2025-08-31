<?php

require_once 'BaseView.php';
require_once 'PostRenderer.php';
require_once 'FormRenderer.php';

/**
 * Vista del tablón
 * 
 * Maneja toda la presentación y renderizado del HTML
 * para la página del tablón.
 */
class BoardView extends BaseView
{
    private BoardController $controller;
    private ?array $board;
    private array $posts;
    private array $pagination_info;
    private array $messages;
    private PostRenderer $post_renderer;
    private FormRenderer $form_renderer;
    
    /**
     * Constructor
     */
    public function __construct(BoardController $controller) 
    {
        parent::__construct();
        
        $this->controller = $controller;
        $this->board = $controller->getBoard();
        $this->posts = $controller->getPosts();
        $this->pagination_info = $controller->getPaginationInfo();
        $this->messages = $controller->getMessages();
        
        // Configurar datos comunes de la vista base
        $this->setBoardsByCategory($controller->getAllBoardsByCategory());
        $this->setRandomBanner($controller->getRandomBanner());
        
        // Inicializar renderizadores
        $this->post_renderer = new PostRenderer($this->board);
        $this->form_renderer = new FormRenderer();
    }
    
    /**
     * Renderiza la página completa
     */
    public function render(): void 
    {
        $title = "//{$this->board['short_id']}/ {$this->board['name']}";
        
        $this->renderDocumentStart($title);
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
    // RENDERIZADO DE ESTRUCTURA ESPECÍFICA
    // ==========================================
    
    /**
     * Renderiza el contenido principal
     */
    private function renderMain(): void 
    {
        ?>
        <main>
            <?php 
            $this->renderMessages($this->messages); 
            $this->renderPostsSection(); 
            ?>
        </main>
        <?php
    }
    
    // ==========================================
    // RENDERIZADO DE HEADER Y NAVEGACIÓN
    // ==========================================
    
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
                <li>[<input type="checkbox" name="auto" id="auto" title="Recarga la página cada 10 segundos"> Auto]</li>
            </ul>
        </nav>
        <?php
    }
    
    // ==========================================
    // RENDERIZADO DE FORMULARIOS
    // ==========================================
    
    /**
     * Renderiza la sección de creación de posts
     */
    private function renderCreatePostSection(): void 
    {
        $this->form_renderer->renderCreatePostForm();
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
                        <?php $this->post_renderer->renderPost($post); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <?php
    }
    // ==========================================
    // RENDERIZADO DE PAGINACIÓN
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
}