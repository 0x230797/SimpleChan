<?php
// Iniciar sesión para obtener mensajes de error personalizados
session_start();

// Establecer el código de respuesta HTTP 404
http_response_code(404);

// Incluir configuraciones globales si es necesario
require_once 'config.php';
require_once 'functions.php';

// Obtener mensaje de error personalizado si existe
$custom_error_message = null;
if (isset($_SESSION['error_message'])) {
    $custom_error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Limpiar el mensaje después de usarlo
}

/**
 * Clase ErrorController - Maneja la lógica de la página de error
 */
class ErrorController {
    private $error_message;

    public function __construct($message = 'Página no encontrada') {
        global $custom_error_message;
        
        // Usar mensaje personalizado si está disponible
        if ($custom_error_message) {
            $this->error_message = $custom_error_message;
        } else {
            $this->error_message = $message;
        }
    }

    /**
     * Obtiene el mensaje de error
     */
    public function getErrorMessage() {
        return $this->error_message;
    }
}

/**
 * Clase ErrorView - Maneja la presentación de la página de error
 */
class ErrorView {
    private $controller;

    public function __construct(ErrorController $controller) {
        $this->controller = $controller;
    }

    /**
     * Renderiza la página completa
     */
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <?php $this->renderHead(); ?>
            <?php $this->renderStyles(); ?>
        </head>
        <body>
            <?php $this->renderHeader(); ?>
            <main>
                <?php $this->renderErrorNotice(); ?>
            </main>
            <?php $this->renderFooter(); ?>
        </body>
        </html>
        <?php
    }

    private function renderHead() {
        ?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - SimpleChan</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="stylesheet" href="assets/css/themes.css">
        <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        <?php
    }

    private function renderStyles() {
        ?>
        <style>
            .error-notice {
                margin: var(--spacing-xxl) auto;
                padding: var(--spacing-xl) 0;
                background-color: var(--form-bg);
                border: var(--border-style);
                text-align: center;
                color: var(--text-color);
            }
            
            .error-notice h2 {
                font-size: 35px;
                font-weight: bold;
            }
            
            .error-notice p {
                margin: 15px 0;
                line-height: 1.8;
                font-size: 16px;
                padding: 0 20px;
            }
            
            .error-notice strong {
                font-weight: bold;
            }
        </style>
        <?php
    }

    private function renderHeader() {
        ?>
        <header>
            <img id="site-logo" src="assets/imgs/logo.png" alt="SimpleChan">
        </header>
        <?php
    }

    private function renderErrorNotice() {
        global $custom_error_message;
        ?>
        <div class="error-notice">
            <h2>Error</h2>
            <p><b><?php echo htmlspecialchars($this->controller->getErrorMessage()); ?></b></p>
            
            <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border-left: 4px solid #ccc; font-family: monospace; font-size: 12px;">
                    <strong>Información de Debug:</strong><br>
                    Timestamp: <?php echo date('Y-m-d H:i:s'); ?><br>
                    URL solicitada: <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A'); ?><br>
                    Método: <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A'); ?><br>
                    User Agent: <?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'); ?><br>
                    IP: <?php echo htmlspecialchars(get_user_ip()); ?><br>
                    Referer: <?php echo htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'N/A'); ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <span>[<a href="index.php">Inicio</a>]</span>
                <span>[<a href="boards.php">Tablones</a>]</span>
                <span>[<a href="catalog.php">Catálogo</a>]</span>
            </div>
        </div>
        <?php
    }

    private function renderFooter() {
        ?>
        <script src="assets/js/script.js"></script>
        <?php
    }
}

// Inicializar la aplicación
$message = isset($_GET['error_message']) ? $_GET['error_message'] : 'Página no encontrada';
$controller = new ErrorController($message);
$view = new ErrorView($controller);
$view->render();
?>
