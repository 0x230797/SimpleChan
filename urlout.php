<?php
require_once 'config.php';
require_once 'functions.php';

/**
 * UrlOutController - Maneja la redirección segura a sitios externos
 */
class UrlOutController {
    private ?string $target_url;
    private ?string $error_message;

    public function __construct() {
        $this->validateUrl();
    }

    /**
     * Valida la URL proporcionada
     */
    private function validateUrl(): void {
        $url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);
        
        if (!$url || !preg_match('/^https?:\/\//i', $url)) {
            $this->error_message = 'Enlace no válido o formato incorrecto';
            $this->target_url = null;
            return;
        }

        // Lista de dominios bloqueados (opcional)
        $blocked_domains = ['malware.com', 'spam.com']; // Agregar dominios peligrosos
        $parsed_url = parse_url($url);
        
        if (isset($parsed_url['host']) && in_array($parsed_url['host'], $blocked_domains)) {
            $this->error_message = 'Este enlace ha sido bloqueado por seguridad';
            $this->target_url = null;
            return;
        }

        $this->target_url = $url;
        $this->error_message = null;
    }

    public function getTargetUrl(): ?string {
        return $this->target_url;
    }

    public function getErrorMessage(): ?string {
        return $this->error_message;
    }

    public function hasError(): bool {
        return $this->error_message !== null;
    }
}

/**
 * UrlOutView - Maneja la presentación de la página de redirección
 */
class UrlOutView {
    private UrlOutController $controller;

    public function __construct(UrlOutController $controller) {
        $this->controller = $controller;
    }

    /**
     * Renderiza la página completa
     */
    public function render(): void {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Redirección segura - SimpleChan</title>
            <link rel="stylesheet" href="assets/css/style.css">
            <link rel="stylesheet" href="assets/css/themes.css">
            <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body id="top">
            <?php $this->renderHeader(); ?>
            
            <main>
                <?php if ($this->controller->hasError()): ?>
                    <?php $this->renderError(); ?>
                <?php else: ?>
                    <?php $this->renderRedirection(); ?>
                <?php endif; ?>
            </main>
            
            <?php $this->renderFooter(); ?>
            <script src="assets/js/script.js"></script>
        </body>
        </html>
        <?php
    }

    /**
     * Renderiza el header
     */
    private function renderHeader(): void {
        ?>
        <header>
            <h1>Redirección segura</h1>
            <p>Salida controlada de SimpleChan</p>
        </header>
        <br>
        <?php
    }

    /**
     * Renderiza el mensaje de error
     */
    private function renderError(): void {
        ?>
        <section class="box-outer top-box">
            <div class="box-inner">
                <div class="boxbar">
                    <h2>Error</h2>
                </div>
                <div class="boxcontent">
                    <div class="error">
                        <p><strong><?php echo htmlspecialchars($this->controller->getErrorMessage()); ?></strong></p>
                        <p>Por favor, verifica el enlace e intenta nuevamente.</p>
                    </div>
                    <br>
                    <p>[<a href="javascript:history.back()">« Regresar</a>] [<a href="index.php">Inicio</a>]</p>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Renderiza la página de redirección
     */
    private function renderRedirection(): void {
        $url = $this->controller->getTargetUrl();
        ?>
        <section class="box-outer top-box">
            <div class="box-inner">
                <div class="boxbar">
                    <h2>Sitio externo</h2>
                </div>
                <div class="boxcontent">
                    <div class="warning-box">
                        <p><strong>Estás a punto de salir de SimpleChan</strong></p>
                        <p>Serás redirigido a un sitio externo:</p>
                        <p class="target-url" style="word-break: break-all; padding: 8px 0; border-radius: 3px; font-family: monospace;">
                            <?php echo htmlspecialchars($url); ?>
                        </p>
                        <p><small>SimpleChan no se hace responsable del contenido de sitios externos.</small></p>
                    </div>
                    
                    <div class="redirect-actions" style="text-align: center; margin: 20px 0;">
                        <p><strong id="countdown">Redirección automática en 5 segundos...</strong></p>
                        <br>
                        <p>
                            [<a href="<?php echo htmlspecialchars($url); ?>" class="btn-continue" id="continue-btn">Continuar ahora</a>]
                            [<a href="javascript:history.back()">Cancelar</a>]
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <script>
        (function() {
            let countdown = 5;
            const countdownEl = document.getElementById('countdown');
            const continueBtn = document.getElementById('continue-btn');
            
            function updateCountdown() {
                if (countdown > 0) {
                    countdownEl.textContent = `Redirección automática en ${countdown} segundos...`;
                    countdown--;
                    setTimeout(updateCountdown, 1000);
                } else {
                    countdownEl.textContent = 'Redirigiendo...';
                    window.location.href = <?php echo json_encode($url); ?>;
                }
            }
            
            // Cancelar redirección automática si el usuario hace clic en continuar
            continueBtn.addEventListener('click', function() {
                countdown = 0;
                countdownEl.textContent = 'Redirigiendo...';
            });
            
            // Iniciar countdown
            updateCountdown();
        })();
        </script>
        <?php
    }

    /**
     * Renderiza el footer
     */
    private function renderFooter(): void {
        ?>
        <footer id="footer">
            <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
        </footer>
        <?php
    }
}

// Inicializar controlador y vista
$controller = new UrlOutController();
$view = new UrlOutView($controller);
$view->render();
?>