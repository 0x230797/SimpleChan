<?php
require_once 'config.php';
require_once 'functions.php';

/**
 * Clase BanController - Maneja la lógica de la página de ban
 */
class BanController {
    private $ban_info;
    
    public function __construct() {
        $this->validateBanStatus();
    }
    
    /**
     * Verifica si el usuario está baneado y maneja redirección
     */
    private function validateBanStatus() {
        $this->ban_info = is_user_banned();
        
        // Si no está baneado, redirigir al index
        if (!$this->ban_info || !is_array($this->ban_info)) {
            $this->redirect('index.php');
        }
    }
    
    /**
     * Obtiene la información del ban
     */
    public function getBanInfo() {
        return $this->ban_info;
    }
    
    /**
     * Verifica si el ban es temporal
     */
    public function isTemporaryBan() {
        return isset($this->ban_info['expires_at']) && !empty($this->ban_info['expires_at']);
    }
    
    /**
     * Verifica si el ban ha expirado
     */
    public function isBanExpired() {
        if (!$this->isTemporaryBan()) {
            return false;
        }
        
        return strtotime($this->ban_info['expires_at']) <= time();
    }
    
    /**
     * Calcula el tiempo restante del ban
     */
    public function calculateTimeRemaining() {
        if (!$this->isTemporaryBan()) {
            return null;
        }
        
        $time_left = strtotime($this->ban_info['expires_at']) - time();
        
        if ($time_left <= 0) {
            return null;
        }
        
        $days = floor($time_left / 86400);
        $hours = floor(($time_left % 86400) / 3600);
        $minutes = floor(($time_left % 3600) / 60);
        
        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'total_seconds' => $time_left
        ];
    }
    
    /**
     * Formatea el tiempo restante en texto legible
     */
    public function formatTimeRemaining() {
        $time_data = $this->calculateTimeRemaining();
        
        if (!$time_data) {
            return null;
        }
        
        $time_text = '';
        
        if ($time_data['days'] > 0) {
            $time_text .= $time_data['days'] . ' día' . ($time_data['days'] != 1 ? 's' : '') . ', ';
        }
        
        if ($time_data['hours'] > 0) {
            $time_text .= $time_data['hours'] . ' hora' . ($time_data['hours'] != 1 ? 's' : '') . ' y ';
        }
        
        $time_text .= $time_data['minutes'] . ' minuto' . ($time_data['minutes'] != 1 ? 's' : '');
        
        return $time_text;
    }
    
    /**
     * Obtiene la razón del ban
     */
    public function getBanReason() {
        return isset($this->ban_info['reason']) && !empty($this->ban_info['reason']) 
            ? htmlspecialchars($this->ban_info['reason']) 
            : 'No especificada';
    }
    
    /**
     * Obtiene la fecha del ban formateada
     */
    public function getBanDate() {
        return isset($this->ban_info['created_at']) 
            ? date('d/m/Y H:i:s', strtotime($this->ban_info['created_at'])) 
            : 'Fecha desconocida';
    }
    
    /**
     * Obtiene la fecha de expiración formateada
     */
    public function getExpirationDate() {
        return $this->isTemporaryBan() 
            ? date('d/m/Y H:i:s', strtotime($this->ban_info['expires_at'])) 
            : null;
    }
    
    /**
     * Redirige a una URL
     */
    private function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/**
 * Clase BanView - Maneja la presentación de la página de ban
 */
class BanView {
    private $controller;
    
    public function __construct(BanController $controller) {
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
        </head>
        <body>
            <?php $this->renderHeader(); ?>
            <main>
                <?php $this->renderBanNotice(); ?>
                <div class="ban-actions"></div>
            </main>
            <?php $this->renderFooter(); ?>
            <?php $this->renderJavaScript(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * Renderiza el head del documento
     */
    private function renderHead() {
        ?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado - SimpleChan</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
        <?php $this->renderStyles(); ?>
        <?php
    }
    
    /**
     * Renderiza los estilos CSS
     */
    private function renderStyles() {
        ?>
        <style>
            .ban-notice {
                margin: 50px auto;
                padding: 30px 0;
                background-color: #f7e5e5;
                border: 1px solid #800;
                text-align: center;
                color: #800;
                max-width: 800px;
            }
            
            .ban-notice h2 {
                margin-bottom: 25px;
                font-size: 28px;
                font-weight: bold;
            }
            
            .ban-notice p {
                margin: 15px 0;
                line-height: 1.8;
                font-size: 16px;
                padding: 0 20px;
            }
            
            .ban-notice strong {
                color: #800;
                font-weight: bold;
            }
            
            .ban-status {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 15px;
            }
            
            .ban-expired {
                color: #060;
                background-color: #e6ffe6;
            }
            
            .ban-active {
                color: #800;
            }
            
            .ban-permanent {
                color: #600;
            }
            
            .refresh-info {
                margin-top: 20px;
                font-size: 12px;
                color: #666;
                font-style: italic;
            }
            
            .countdown {
                font-weight: bold;
                color: #800;
            }
        </style>
        <?php
    }
    
    /**
     * Renderiza el header
     */
    private function renderHeader() {
        ?>
        <header>
            <h1>SimpleChan</h1>
            <p>Imageboard Anónimo Simple</p>
        </header>
        <?php
    }
    
    /**
     * Renderiza el aviso de ban
     */
    private function renderBanNotice() {
        ?>
        <div class="ban-notice">
            <h2>Acceso Denegado</h2>
            <p><strong>Tu IP ha sido baneada del imageboard.</strong></p>
            
            <div>
                <?php $this->renderBanStatus(); ?>
                <?php $this->renderBanDetails(); ?>
                <?php $this->renderTimeInformation(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza el estado del ban
     */
    private function renderBanStatus() {
        if ($this->controller->isBanExpired()) {
            echo '<div class="ban-status ban-expired">El ban ha expirado - Intenta refrescar la página</div>';
        } elseif ($this->controller->isTemporaryBan()) {
            echo '<div class="ban-status ban-active">Ban temporal activo</div>';
        } else {
            echo '<div class="ban-status ban-permanent">Ban permanente</div>';
        }
    }
    
    /**
     * Renderiza los detalles del ban
     */
    private function renderBanDetails() {
        ?>
        <p><strong>Razón del ban:</strong> <?php echo $this->controller->getBanReason(); ?></p>
        <p><strong>Fecha del ban:</strong> <?php echo $this->controller->getBanDate(); ?></p>
        <?php
    }
    
    /**
     * Renderiza la información de tiempo
     */
    private function renderTimeInformation() {
        if (!$this->controller->isTemporaryBan()) {
            echo '<p><strong>Tipo:</strong> Ban permanente</p>';
            return;
        }
        
        echo '<p><strong>El ban expira el:</strong> ' . $this->controller->getExpirationDate() . '</p>';
        
        if ($this->controller->isBanExpired()) {
            echo '<p><strong>Estado:</strong> <span class="ban-expired">El ban ha expirado</span></p>';
        } else {
            $time_remaining = $this->controller->formatTimeRemaining();
            if ($time_remaining) {
                echo '<p><strong>Tiempo restante:</strong> <span class="countdown">' . $time_remaining . '</span></p>';
            }
        }
    }
    
    /**
     * Renderiza el footer
     */
    private function renderFooter() {
        ?>
        <footer>
            <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
        </footer>
        <?php
    }
    
    /**
     * Renderiza el JavaScript para bans temporales
     */
    private function renderJavaScript() {
        if (!$this->controller->isTemporaryBan()) {
            return;
        }
        ?>
        <script>
        (function() {
            'use strict';
            
            const REFRESH_INTERVAL = 30000; // 30 segundos
            let timeLeft = 30;
            let countdownInterval;
            let refreshTimeout;
            
            /**
             * Actualiza el contador visual
             */
            function updateCounter() {
                const counterElement = document.getElementById('refresh-counter');
                if (counterElement) {
                    counterElement.textContent = timeLeft;
                }
                
                timeLeft--;
                
                if (timeLeft < 0) {
                    clearInterval(countdownInterval);
                    window.location.reload();
                }
            }
            
            /**
             * Inicia el contador de refresco automático
             */
            function startAutoRefresh() {
                // Auto-refresh después de 30 segundos
                refreshTimeout = setTimeout(() => {
                    window.location.reload();
                }, REFRESH_INTERVAL);
                
                // Crear elemento contador
                createCounterElement();
                
                // Iniciar contador visual
                countdownInterval = setInterval(updateCounter, 1000);
                updateCounter(); // Llamada inicial
            }
            
            /**
             * Crea el elemento visual del contador
             */
            function createCounterElement() {
                const banActions = document.querySelector('.ban-actions');
                if (!banActions) return;
                
                const counterContainer = document.createElement('div');
                counterContainer.className = 'refresh-info';
                counterContainer.innerHTML = `
                    <p>Página se actualizará en <span id="refresh-counter" class="countdown">30</span> segundos</p>
                    <button onclick="clearAutoRefresh(); location.reload();" style="margin-top: 10px; padding: 5px 10px;">
                        Refrescar ahora
                    </button>
                `;
                
                banActions.appendChild(counterContainer);
            }
            
            /**
             * Cancela el refresco automático
             */
            window.clearAutoRefresh = function() {
                if (refreshTimeout) {
                    clearTimeout(refreshTimeout);
                }
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
            };
            
            // Inicializar cuando la página cargue
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startAutoRefresh);
            } else {
                startAutoRefresh();
            }
            
            // Limpiar timers si el usuario navega fuera de la página
            window.addEventListener('beforeunload', function() {
                clearAutoRefresh();
            });
        })();
        </script>
        <?php
    }
}

// Inicializar la aplicación
$controller = new BanController();
$view = new BanView($controller);
$view->render();
?>