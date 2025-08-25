<?php

/**
 * Clase base para todas las vistas
 * 
 * Contiene funcionalidad común compartida entre todas las vistas
 * del sistema.
 */
abstract class BaseView 
{
    protected array $boards_by_category;
    protected ?string $random_banner;
    
    /**
     * Constructor base
     */
    public function __construct() 
    {
        $this->boards_by_category = [];
        $this->random_banner = null;
    }
    
    /**
     * Método abstracto para renderizar la vista
     */
    abstract public function render(): void;
    
    // ==========================================
    // ESTRUCTURA HTML COMÚN
    // ==========================================
    
    /**
     * Renderiza el inicio del documento HTML
     */
    protected function renderDocumentStart(string $title, string $favicon = 'favicon.ico'): void 
    {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?> - SimpleChan</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes.css">
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/<?php echo $favicon; ?>" type="image/x-icon">
        </head>
        <body id="top">
        <?php
    }
    
    /**
     * Renderiza el cierre del documento HTML
     */
    protected function renderDocumentEnd(): void 
    {
        ?>
        <script src="assets/js/script.js"></script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Renderiza la navegación principal
     */
    protected function renderNavigation(): void 
    {
        ?>
        <nav>
            <ul>
                <li>
                    <a href="index.php">Inicio</a>/<a href="reglas.php">Reglas</a>
                    <?php if (is_admin()): ?>
                    /<a href="admin.php">Administración</a>
                    <?php endif; ?>
                </li>
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
     * Renderiza el footer
     */
    protected function renderFooter(): void 
    {
        ?>
        <footer id="footer">
            <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
        </footer>
        <?php
    }
    
    /**
     * Renderiza el selector de temas
     */
    protected function renderThemes(): void 
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
     * Renderiza mensajes de estado
     */
    protected function renderMessages(array $messages = []): void 
    {
        // Mensajes pasados como parámetro
        foreach (['error', 'success'] as $type) {
            if (isset($messages[$type])) {
                echo '<div class="' . $type . '">' . htmlspecialchars($messages[$type]) . '</div>';
            }
        }

        // Mensajes de sesión
        if (isset($_SESSION['success_message'])) {
            echo '<div class="success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        
        if (isset($_SESSION['error_message'])) {
            echo '<div class="error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
    }
    
    // ==========================================
    // SETTERS PARA DATOS COMUNES
    // ==========================================
    
    /**
     * Establece los boards por categoría
     */
    public function setBoardsByCategory(array $boards_by_category): void 
    {
        $this->boards_by_category = $boards_by_category;
    }
    
    /**
     * Establece el banner aleatorio
     */
    public function setRandomBanner(?string $random_banner): void 
    {
        $this->random_banner = $random_banner;
    }
}
