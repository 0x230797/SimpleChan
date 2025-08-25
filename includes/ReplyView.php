<?php

require_once 'BaseView.php';
require_once 'PostRenderer.php';
require_once 'FormRenderer.php';

/**
 * Vista de respuestas
 * 
 * Maneja la presentación y renderizado del HTML
 * para la página de respuestas a un post.
 */
class ReplyView extends BaseView {
    
    private PostRenderer $post_renderer;
    private FormRenderer $form_renderer;
    
    /**
     * Constructor
     */
    public function __construct() 
    {
        parent::__construct();
        $this->post_renderer = new PostRenderer();
        $this->form_renderer = new FormRenderer();
    }
    
    /**
     * Renderiza la vista completa (implementación de BaseView)
     */
    public function render(): void 
    {
        // Este método se implementa para cumplir con BaseView pero no se usa directamente
        // Se usa renderReplyPage en su lugar
    }
    
    /**
     * Renderiza la página de respuestas
     */
    public function renderReplyPage($post, $view_data, $error = null, $success_message = null) {
        // Configurar datos para la vista base
        $this->setBoardsByCategory($view_data['boards_by_category']);
        $this->setRandomBanner($view_data['random_banner']);
        $this->post_renderer->setBoard($view_data['board']);
        
        $this->renderHTML($post, $view_data, $error, $success_message);
    }

    private function renderHTML($post, $view_data, $error, $success_message) {
        $title = "{$post['subject']} - /{$view_data['board']['short_id']}/ {$view_data['board']['name']}";
        
        $this->renderDocumentStart($title);
        $this->renderNavigation();
        $this->renderHeader($view_data['board'], $post);
        $this->renderMiniMenu($view_data['board']);
        
        ?>
        <main>
            <?php 
            $messages = [];
            if ($error) $messages['error'] = $error;
            if ($success_message) $messages['success'] = $success_message;
            $this->renderMessages($messages); 
            ?>
            
            <?php $this->renderCreateReplySection(); ?>
            
            <?php $this->renderMainPost($post); ?>
            
            <?php $this->renderRepliesSection($view_data['replies'], $post['id']); ?>
        </main>
        <?php
        
        $this->renderThemes();
        $this->renderFooter();
        $this->renderDocumentEnd();
    }

    private function renderHeader($board, $post) {
        ?>
        <header>
            <h1><?php echo htmlspecialchars($post['subject']); ?></h1>
            <h1>/<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?></h1>
            <p><?php echo htmlspecialchars($board['description']); ?></p>
            <?php if ($this->random_banner): ?>
                <div class="banner">
                    <img src="<?php echo htmlspecialchars($this->random_banner); ?>" alt="Banner">
                </div>
            <?php endif; ?>
        </header>
        <?php
    }

    private function renderMiniMenu($board) {
        ?>
        <nav>
            <ul class="mini-menu">
                <li>[<a href="boards.php?board=<?php echo htmlspecialchars($board['short_id']); ?>">Retornar</a>]</li>
                <li>[<a href="#footer">Bajar</a>]</li>
            </ul>
        </nav>
        <?php
    }

    private function renderCreateReplySection() {
        $this->form_renderer->renderCreateReplyForm();
    }

    private function renderMainPost($post) {
        ?>
        <section class="reply-section">
            <h2>Publicación</h2>
            <?php $this->post_renderer->renderPost($post); ?>
        </section>
        <?php
    }

    private function renderRepliesSection($replies, $post_id) {
        ?>
        <section>
            <?php if (empty($replies)): ?>
                <p>No hay respuestas aún.</p><br>
            <?php else: ?>
                <div class="replies no-b">
                    <?php foreach ($replies as $reply): ?>
                        <?php $this->post_renderer->renderReply($reply, false); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }
}
?>