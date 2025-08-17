<?php
// Establecer el código de respuesta HTTP 404
http_response_code(404);

// Incluir configuraciones globales si es necesario
require_once 'config.php';
require_once 'functions.php';

/**
 * Clase ErrorController - Maneja la lógica de la página de error
 */
class ErrorController {
    private $error_message;

    public function __construct($message = 'Página no encontrada') {
        $this->error_message = $message;
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
        ?>
        <div class="error-notice">
            <h2>Error</h2>
            <p><b><?php echo htmlspecialchars($this->controller->getErrorMessage()); ?></b></p>
            <span>[<a href="index.php">Inicio</a>]</span>
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
