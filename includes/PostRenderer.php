<?php

/**
 * Componente para renderizar posts y respuestas
 * 
 * Maneja todo el renderizado relacionado con posts individuales,
 * incluyendo headers, imágenes, mensajes y acciones.
 */
class PostRenderer 
{
    private ?array $board;
    
    /**
     * Constructor
     */
    public function __construct(?array $board = null) 
    {
        $this->board = $board;
    }
    
    /**
     * Establece el board actual
     */
    public function setBoard(?array $board): void 
    {
        $this->board = $board;
    }
    
    // ==========================================
    // RENDERIZADO DE POSTS COMPLETOS
    // ==========================================
    
    /**
     * Renderiza un post completo (para la vista del board)
     */
    public function renderPost(array $post): void 
    {
        ?>
        <article class="post" id="post-<?php echo $post['id']; ?>">
            <?php 
            $this->renderPostFileInfo($post);
            $this->renderPostImage($post);
            $this->renderPostHeader($post, true);
            $this->renderPostMessage($post);
            $this->renderPostReplies($post);
            ?>
        </article>
        <?php
    }
    
    /**
     * Renderiza una respuesta (para la vista de respuestas)
     */
    public function renderReply(array $reply, bool $show_reply_button = true): void 
    {
        ?>
        <article class="reply" id="post-<?php echo $reply['id']; ?>">
            <?php
            $this->renderPostFileInfo($reply);
            $this->renderPostImage($reply);
            $this->renderPostHeader($reply, false, $show_reply_button);
            $this->renderPostMessage($reply);
            ?>
        </article>
        <?php
    }
    
    // ==========================================
    // RENDERIZADO DE COMPONENTES DE POST
    // ==========================================
    
    /**
     * Renderiza información del archivo del post
     */
    public function renderPostFileInfo(array $post): void 
    {
        if (empty($post['image_filename'])) {
            return;
        }
        ?>
        <div class="post-header-file">
            <b>Archivo:</b>
            <a href="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" target="_blank" title="<?php echo UPLOAD_DIR . $post['image_filename']; ?>">
                <?php echo htmlspecialchars($post['image_filename']); ?>
            </a>
            <b><a href="javascript:void(0)" onclick="searchImageOnGoogle('<?php echo UPLOAD_DIR . $post['image_filename']; ?>')" title="Buscar imagen en Google">[S]</a></b>
            (<?php echo format_file_size($post['image_size']) . ', ' . $post['image_dimensions']; ?>)
        </div>
        <?php
    }
    
    /**
     * Renderiza la imagen del post
     */
    public function renderPostImage(array $post): void 
    {
        if (empty($post['image_filename'])) {
            return;
        }
        ?>
        <div class="post-image">
            <?php if (file_exists(UPLOAD_DIR . $post['image_filename'])): ?>
                <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" title="<?php echo htmlspecialchars($post['image_filename']); ?>" class="clickable-image">
            <?php else: ?>
                <img src="assets/imgs/filedeleted.png" alt="Imagen no disponible">
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el header del post
     */
    public function renderPostHeader(array $post, bool $is_main_post = false, bool $show_reply_button = true): void 
    {
        ?>
        <div class="post-header">
            <?php 
            $this->renderPostSubject($post);
            $this->renderPostName($post);
            $this->renderPostDate($post);
            $this->renderPostNumber($post, $is_main_post);
            $this->renderPostActions($post, $is_main_post, $show_reply_button);
            $this->renderPostIcons($post);
            $this->renderAdminActions($post);
            ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el asunto del post
     */
    public function renderPostSubject(array $post): void 
    {
        if (!empty($post['subject'])) {
            echo '<span class="post-subject">' . htmlspecialchars($post['subject']) . '</span>';
        }
    }
    
    /**
     * Renderiza el nombre del post
     */
    public function renderPostName(array $post): void 
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
    public function renderPostDate(array $post): void 
    {
        echo '<span>' . date('d/m/Y H:i:s', strtotime($post['created_at'])) . '</span>';
    }
    
    /**
     * Renderiza el número del post
     */
    public function renderPostNumber(array $post, bool $is_main_post = false): void 
    {
        echo '<span>';
        if ($is_main_post) {
            echo '<a href="reply.php?post_id=' . $post['id'] . '&ref=' . $post['id'] . '">No. ' . $post['id'] . '</a>';
        } else {
            echo '<a href="#post-' . $post['id'] . '" onclick="insertReference(' . $post['id'] . '); return false;">No. ' . $post['id'] . '</a>';
        }
        echo '</span>';
    }
    
    /**
     * Renderiza las acciones del post (responder, reportar)
     */
    public function renderPostActions(array $post, bool $is_main_post = false, bool $show_reply_button = true): void 
    {
        if ($show_reply_button) {
            // Botón responder
            if ($is_main_post) {
                if ($post['is_locked']) {
                    echo '<span>[<a href="#" class="btn-reply" onclick="alert(\'No puedes responder a una publicación bloqueada\'); window.location.reload(); return false;">Responder</a></span>]';
                } else {
                    echo '<span>[<a href="reply.php?post_id=' . $post['id'] . '" class="btn-reply">Responder</a>]</span>';
                }
            } else {
                echo '<span>[<a href="#" class="btn-reply" onclick="insertReference(' . $post['id'] . '); return false;">Responder</a>]</span>';
            }
        }
        
        // Menú de reporte
        $this->renderReportMenu($post);
    }

    /**
     * Renderiza el menú de reporte
     */
    public function renderReportMenu(array $post): void 
    {
        // Verificar si el post está bloqueado o fijado
        $is_blocked_or_pinned = $post['is_locked'] || $post['is_pinned'];
        
        if ($is_blocked_or_pinned) {
            ?>
            <span>[<button class="btn-report btn-disabled" onclick="alert('No puedes reportar una publicación bloqueada o fijada'); return false;">Reportar</button>]</span>
            <?php
            return;
        }
        
        $form_action = $this->board ? 'boards.php' : 'index.php';
        ?>
        <div class="report-menu-wrapper">
            <span>[<button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>]</span>
            <nav class="report-menu" id="report-menu-<?php echo $post['id']; ?>">
                <form method="POST" action="<?php echo $form_action; ?>">
                    <input type="hidden" name="report_post_id" value="<?php echo $post['id']; ?>">
                    <?php if ($this->board): ?>
                        <input type="hidden" name="board" value="<?php echo htmlspecialchars($this->board['short_id']); ?>">
                    <?php endif; ?>
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
    public function renderPostIcons(array $post): void 
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
    public function renderAdminActions(array $post): void 
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
    public function renderPostMessage(array $post): void 
    {
        // Determinar el post padre para las referencias
        $parent_post_id = $post['parent_id'] ? $post['parent_id'] : $post['id'];
        
        ?>
        <div class="post-message">
            <?php echo parse_references($post['message'], $post['name'] === 'Administrador', $parent_post_id); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza las respuestas del post (solo para vista de board)
     */
    public function renderPostReplies(array $post): void 
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
                <?php
                // Contar imágenes en las respuestas omitidas
                $omitted_replies = array_slice($replies, 0, -5);
                $omitted_images = 0;
                foreach ($omitted_replies as $omitted_reply) {
                    if (!empty($omitted_reply['image_filename'])) {
                        $omitted_images++;
                    }
                }
                ?>
                <p class="replies-omitted">
                    Se omitieron <?php echo ($total_replies - 5); ?> respuestas<?php if ($omitted_images > 0): ?> y <?php echo $omitted_images; ?> imagenes<?php endif; ?>. 
                    <a href="reply.php?post_id=<?php echo $post['id']; ?>">Haga clic aquí</a> para verlas todas.
                </p>
            <?php endif; ?>
            <?php foreach ($last_replies as $reply): ?>
                <?php $this->renderReply($reply, false); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
