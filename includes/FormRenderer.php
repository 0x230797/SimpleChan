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
    // Incluir token CSRF en todos los formularios de post/reply
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';

    $this->renderNameField();
        
        if ($is_post) {
            $this->renderSubjectField();
        }
        
        $this->renderFormatButtons();
        $this->renderMessageField($is_post);
        
        // Agregar vista previa para administradores
        if (is_admin()) {
            $this->renderPreviewSection();
        }
        
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
    
    /**
     * Renderiza la sección de vista previa para administradores
     */
    public function renderPreviewSection(): void 
    {
        ?>
        <div class="form-group admin-preview-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span class="form-label">Vista Previa:</span>
                <button type="button" onclick="updatePreview()" class="btn-preview" style="padding: 5px 10px; font-size: 12px;">
                    Actualizar Vista Previa
                </button>
            </div>
            <div id="admin-preview" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; min-height: 80px; border-radius: 4px;">
                <p style="color: #666; font-style: italic;">Escribe algo en el mensaje y haz clic en "Actualizar Vista Previa" para ver el resultado.</p>
            </div>
        </div>
        
        <script>
        function updatePreview() {
            const textarea = document.querySelector('textarea[name="message"]');
            const preview = document.getElementById('admin-preview');
            
            if (!textarea || !preview) return;
            
            const text = textarea.value.trim();
            
            if (text) {
                // Aplicar formatos de administrador
                let formattedText = text;
                
                // Formatos básicos
                formattedText = formattedText.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
                formattedText = formattedText.replace(/\*(.+?)\*/g, '<i>$1</i>');
                formattedText = formattedText.replace(/~(.+?)~/g, '<s>$1</s>');
                formattedText = formattedText.replace(/_(.+?)_/g, '<u>$1</u>');
                formattedText = formattedText.replace(/\[spoiler\](.+?)\[\/spoiler\]/gi, '<span style="background: #000; color: #000; cursor: pointer;" onclick="this.style.color=\'inherit\'" title="Haz clic para revelar">$1</span>');
                
                // Formatos de administrador
                formattedText = formattedText.replace(/\[H1\](.+?)\[\/H1\]/gi, '<h1 style="color: #FF6600; text-align: center; margin: 10px 0; font-size: 24px;">$1</h1>');
                formattedText = formattedText.replace(/\[H2\](.+?)\[\/H2\]/gi, '<h2 style="color: #FF6600; text-align: center; margin: 8px 0; font-size: 20px;">$1</h2>');
                formattedText = formattedText.replace(/\[Color=([^\\]]+?)\](.+?)\[\/Color\]/gi, '<span style="color: $1;">$2</span>');
                formattedText = formattedText.replace(/\[Centrar\](.+?)\[\/Centrar\]/gi, '<div style="text-align: center;">$1</div>');
                formattedText = formattedText.replace(/\[Fondo=([^\\]]+?)\](.+?)\[\/Fondo\]/gi, '<span style="background-color: $1; padding: 2px 4px; border-radius: 2px;">$2</span>');
                
                // Referencias (simuladas)
                formattedText = formattedText.replace(/>>(\d+)/g, '<a href="#post-$1" style="color: #0066cc; text-decoration: none;">&gt;&gt;$1</a>');
                
                // Greentext y pinktext
                formattedText = formattedText.replace(/^>(.*)$/gm, '<span style="color: #789922;">&gt;$1</span>');
                formattedText = formattedText.replace(/^&lt;(.*)$/gm, '<span style="color: #E0727F;">&lt;$1</span>');
                
                // Saltos de línea
                formattedText = formattedText.replace(/\n/g, '<br>');
                
                preview.innerHTML = formattedText;
            } else {
                preview.innerHTML = '<p style="color: #666; font-style: italic;">No hay contenido para mostrar.</p>';
            }
        }
        
        // Auto-actualizar la vista previa cuando se escribe (con debounce)
        let previewTimeout;
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.querySelector('textarea[name="message"]');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    clearTimeout(previewTimeout);
                    previewTimeout = setTimeout(updatePreview, 500); // Actualizar después de 500ms de inactividad
                });
            }
        });
        </script>
        <?php
    }
}
