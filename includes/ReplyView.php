<?php

class ReplyView {
    
    public function render($post, $view_data, $error = null, $success_message = null) {
        $this->renderHTML($post, $view_data, $error, $success_message);
    }

    private function renderHTML($post, $view_data, $error, $success_message) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Publicación <?php echo $post['id']; ?> /<?php echo htmlspecialchars($view_data['board']['short_id']); ?>/ <?php echo htmlspecialchars($view_data['board']['name']); ?> - SimpleChan</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes.css">
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body>
            <?php $this->renderNavigation($view_data['boards_by_category']); ?>
            
            <?php $this->renderHeader($view_data['board'], $post, $view_data['random_banner']); ?>

            <?php $this->renderMiniMenu($view_data['board']); ?>

            <main>
                <?php $this->renderMessages($error, $success_message); ?>
                
                <?php $this->renderCreateReplyButton(); ?>
                
                <?php $this->renderReplyForm($error); ?>
                
                <?php $this->renderMainPost($post); ?>
                
                <?php $this->renderRepliesSection($view_data['replies'], $post['id']); ?>
            </main>

            <?php $this->renderThemes(); ?>
            
            <footer id="footer">
                <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
            </footer>
            
            <script src="assets/js/script.js"></script>
        </body>
        </html>
        <?php
    }

    private function renderNavigation($boards_by_category) {
        ?>
        <nav>
            <ul>
                [<li>
                    <a href="index.php">Inicio</a>/<a href="reglas.php">Reglas</a>
                    <?php if (is_admin()): ?>
                    /<a href="admin.php">Administración</a>
                    <?php endif; ?>
                </li>]
                <?php foreach ($boards_by_category as $category => $boards): ?>
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

    private function renderHeader($board, $post, $random_banner) {
        ?>
        <header>
            <h1>/<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?> - Post <?php echo $post['id']; ?></h1>
            <p><?php echo htmlspecialchars($board['description']); ?></p>
            <div class="banner">
                <?php if ($random_banner): ?>
                    <img src="<?php echo htmlspecialchars($random_banner); ?>" alt="Banner">
                <?php endif; ?>
            </div>
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

    private function renderMessages($error, $success_message) {
        if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif;
        
        if ($success_message): ?>
            <div class="success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif;
    }

    private function renderCreateReplyButton() {
        ?>
        <section class="create-reply">
            [ <button onclick="toggleCreateForm('reply')" id="toggle-reply" class="btn-create-reply">
                Crear Respuesta
            </button> ]
        </section>
        <?php
    }

    private function renderReplyForm($error) {
        ?>
        <section class="post-form" id="create-reply" style="display: none;">
            <h2>Responder</h2>
            <?php if ($error): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="reply-form">
                <?php $this->renderNameField(); ?>
                <?php $this->renderFormatButtons(); ?>
                <?php $this->renderMessageField(); ?>
                <?php $this->renderImageField(); ?>
                <div class="form-buttons">
                    <button type="submit" name="submit_post">Responder</button>
                </div>
            </form>
        </section>
        <?php
    }

    private function renderNameField() {
        ?>
        <div class="form-group">
            <label for="name">Nombre (opcional):</label>
            <?php if (is_admin()): ?>
                <input type="text" id="name" name="name" value="Administrador" readonly class="admin-name" autocomplete="username">
            <?php else: ?>
                <input type="text" id="name" name="name" placeholder="Anónimo" maxlength="50" autocomplete="username">
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderFormatButtons() {
        ?>
        <div class="form-group">
            <span class="form-label">Formatos:</span>
            <button type="button" onclick="insertFormat('bold', this)" title="Negrita"><b>B</b></button>
            <button type="button" onclick="insertFormat('italic', this)" title="Cursiva"><i>I</i></button>
            <button type="button" onclick="insertFormat('strike', this)" title="Tachado"><s>T</s></button>
            <button type="button" onclick="insertFormat('subline', this)" title="Subrayado"><u>S</u></button>
            <button type="button" onclick="insertFormat('spoiler', this)" title="Spoiler">SPOILER</button>
            <?php if (is_admin()): ?>
                <button type="button" onclick="insertFormat('h1', this)" title="Título grande">H1</button>
                <button type="button" onclick="insertFormat('h2', this)" title="Título mediano">H2</button>
                <button type="button" onclick="insertFormat('color', this)" title="Color de texto">Color</button>
                <button type="button" onclick="insertFormat('center', this)" title="Centrar texto">Centrar</button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderMessageField() {
        ?>
        <div class="form-group">
            <label for="message">Mensaje:</label>
            <textarea id="message" name="message" required rows="5" placeholder="Tu respuesta..." autocomplete="off"></textarea>
        </div>
        <?php
    }

    private function renderImageField() {
        ?>
        <div class="form-group">
            <input type="file" name="image" accept="image/*">
            <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.
            </span>
        </div>
        <?php
    }

    private function renderMainPost($post) {
        ?>
        <section class="reply-section">
            <h2>Publicación</h2>
            <article class="post" id="post-<?php echo $post['id']; ?>">
                <?php $this->renderPostHeader($post, true); ?>
                <?php $this->renderPostImage($post); ?>
                <div class="post-message">
                    <?php echo parse_references($post['message'], $post['name'] === 'Administrador'); ?>
                </div>
            </article>
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
                        <article class="reply" id="post-<?php echo $reply['id']; ?>">
                            <?php $this->renderPostHeader($reply, false, $post_id); ?>
                            <?php $this->renderPostImage($reply); ?>
                            <div class="post-message">
                                <?php echo parse_references($reply['message'], $reply['name'] === 'Administrador'); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    private function renderPostHeader($post, $is_main_post = false, $post_id = null) {
        ?>
        <div class="post-header">
            <?php $this->renderPostName($post); ?>
            <?php $this->renderPostSubject($post); ?>
            <span><?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?></span>
            <span>
                <a href="#post-<?php echo $post['id']; ?>" onclick="insertReference(<?php echo $post['id']; ?>); return false;">
                    No. <?php echo $post['id']; ?>
                </a>
            </span>
            <?php $this->renderPostActions($post, $is_main_post, $post_id); ?>
            <?php $this->renderPostFlags($post); ?>
        </div>
        <?php
    }

    private function renderPostName($post) {
        if ($post['name'] === 'Administrador') {
            echo '<span class="admin-name">Administrador</span>';
        } else {
            echo '<span class="post-name">' . htmlspecialchars($post['name']) . '</span>';
        }
    }

    private function renderPostSubject($post) {
        if (!empty($post['subject'])): ?>
            <span class="post-subject">
                <?php echo htmlspecialchars($post['subject']); ?>
            </span>
        <?php endif;
    }

    private function renderPostActions($post, $is_main_post, $post_id = null) {
        if ($is_main_post): ?>
            [<a href="reply.php?post_id=<?php echo $post['id']; ?>" class="btn-reply">Responder</a>]
        <?php else: ?>
            [<a href="#" class="btn-reply" onclick="insertReference(<?php echo $post['id']; ?>); return false;">Responder</a>]
        <?php endif;
        
        $this->renderReportMenu($post, $post_id);
    }

    private function renderReportMenu($post, $post_id = null) {
        $form_action = $post_id ? "reply.php?post_id={$post_id}" : "index.php";
        ?>
        <div class="report-menu-wrapper">
            [<button class="btn-report" onclick="toggleReportMenu(<?php echo $post['id']; ?>)">Reportar</button>]
            <nav class="report-menu" id="report-menu-<?php echo $post['id']; ?>">
                <form method="POST" action="<?php echo $form_action; ?>">
                    <input type="hidden" name="report_post_id" value="<?php echo $post['id']; ?>">
                    <label for="report_reason_<?php echo $post['id']; ?>">Motivo:</label>
                    <select id="report_reason_<?php echo $post['id']; ?>" name="report_reason" autocomplete="off">
                        <option value="spam">Spam</option>
                        <option value="contenido ilegal">Contenido ilegal</option>
                        <option value="acoso">Acoso</option>
                        <option value="otro">Otro</option>
                    </select>
                    <input type="text" name="report_details" placeholder="Detalles (opcional)" autocomplete="off">
                    <button type="submit" name="submit_report">
                        Enviar reporte
                    </button>
                </form>
            </nav>
        </div>
        <?php
    }

    private function renderPostFlags($post) {
        if ($post['is_pinned']): ?>
            <img src="assets/imgs/sticky.png" alt="Fijado">
        <?php endif;
        
        if ($post['is_locked']): ?>
            <img src="assets/imgs/closed.png" alt="Bloqueado">
        <?php endif;
    }

    private function renderPostImage($post) {
        if (empty($post['image_filename'])) {
            return;
        }
        
        if (file_exists(UPLOAD_DIR . $post['image_filename'])): ?>
            <div class="post-image">
                <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                     alt="<?php echo htmlspecialchars($post['image_original_name']); ?>"
                     onclick="toggleImageSize(this)">
            </div>
        <?php else: ?>
            <div class="post-image">
                <img src="assets/imgs/filedeleted.png" alt="Imagen no disponible">
            </div>
        <?php endif;
    }

    private function renderThemes() {
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
}
?>