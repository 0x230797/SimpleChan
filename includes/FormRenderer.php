<?php

/**
 * Componente para renderizar formularios
 * 
 * Maneja el renderizado de formularios de posts y respuestas,
 * incluyendo campos comunes y botones de formato.
 */
class FormRenderer 
{
    /**
     * Renderiza el formulario de creación de post
     */
    public function renderCreatePostForm(): void 
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
                    <?php $this->renderPostFormFields(true); ?>
                </form>
            </div>
        </section>
        <?php
    }
    
    /**
     * Renderiza el formulario de creación de respuesta
     */
    public function renderCreateReplyForm(): void 
    {
        ?>
        <section class="create-reply">
            [ <button onclick="toggleCreateForm('reply')" id="toggle-reply" class="btn-create-reply">
                Crear Respuesta
            </button> ]
        </section>

        <section class="post-form" id="create-reply" style="display: none;">
            <h2>Responder</h2>
            <form method="POST" enctype="multipart/form-data" class="reply-form">
                <?php $this->renderPostFormFields(false); ?>
            </form>
        </section>
        <?php
    }
    
    /**
     * Renderiza los campos del formulario de post
     */
    public function renderPostFormFields(bool $is_post = true): void 
    {
        $this->renderNameField();
        
        if ($is_post) {
            $this->renderSubjectField();
        }
        
        $this->renderFormatButtons();
        $this->renderMessageField($is_post);
        $this->renderImageUpload($is_post);
        $this->renderSubmitButton($is_post);
    }
    
    /**
     * Renderiza el campo de nombre
     */
    public function renderNameField(): void 
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
        <?php
    }
    
    /**
     * Renderiza el campo de asunto
     */
    public function renderSubjectField(): void 
    {
        ?>
        <div class="form-group">
            <label for="subject">Asunto:</label>
            <input type="text" id="subject" name="subject" maxlength="100" required autocomplete="off">
        </div>
        <?php
    }
    
    /**
     * Renderiza los botones de formato
     */
    public function renderFormatButtons(): void 
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
                <button type="button" onclick="insertFormat('background', this)" title="Fondo de color">Fondo</button>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el campo de mensaje
     */
    public function renderMessageField(bool $is_post = true): void 
    {
        $placeholder = $is_post ? "Tu publicación..." : "Tu respuesta...";
        ?>
        <div class="form-group">
            <label for="message">Mensaje:</label>
            <textarea id="message" name="message" required rows="5" placeholder="<?php echo $placeholder; ?>" autocomplete="off"></textarea>
        </div>
        <?php
    }
    
    /**
     * Renderiza la sección de carga de imagen
     */
    public function renderImageUpload(bool $is_post = true): void 
    {
        $required = $is_post && !is_admin() ? 'required' : '';
        ?>
        <div class="form-group">
            <?php if ($is_post): ?>
                <label for="image">Imagen:</label>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*" <?php echo $required; ?>>
            <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
                Formatos permitidos: JPG, JPEG, PNG, GIF, WEBP. Tamaño máximo: 5MB.
            </span>
            <?php if ($is_post): ?>
                <br>
                <span style="font-size:12px;color:rgb(102, 102, 102);text-align:right">
                    Antes de hacer una publicación, recuerda leer las <a href="reglas.php">reglas</a>.
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza el botón de envío
     */
    public function renderSubmitButton(bool $is_post = true): void 
    {
        $text = $is_post ? "Crear publicación" : "Responder";
        ?>
        <div class="form-buttons">
            <button type="submit" name="submit_post"><?php echo $text; ?></button>
        </div>
        <?php
    }
}