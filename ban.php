<?php
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario está baneado
$ban_info = is_user_banned();

// Si no está baneado, redirigir al index
if (!$ban_info || !is_array($ban_info)) {
    header('Location: index.php');
    exit;
}

// Preparar mensaje de ban con información detallada
$ban_message = '';
$ban_message .= '<div class="ban-notice">';
$ban_message .= '<h2>Acceso Denegado</h2>';
$ban_message .= '<p><strong>Tu IP ha sido baneada del imageboard.</strong></p>';

if (isset($ban_info['reason']) && !empty($ban_info['reason'])) {
    $ban_message .= '<p><strong>Razón:</strong> ' . htmlspecialchars($ban_info['reason']) . '</p>';
} else {
    $ban_message .= '<p><strong>Razón:</strong> No especificada</p>';
}

if (isset($ban_info['created_at'])) {
    $ban_message .= '<p><strong>Fecha del ban:</strong> ' . date('d/m/Y H:i:s', strtotime($ban_info['created_at'])) . '</p>';
}

if (isset($ban_info['expires_at']) && $ban_info['expires_at']) {
    $ban_message .= '<p><strong>El ban expira el:</strong> ' . date('d/m/Y H:i:s', strtotime($ban_info['expires_at'])) . '</p>';
    
    // Calcular tiempo restante
    $time_left = strtotime($ban_info['expires_at']) - time();
    if ($time_left > 0) {
        $days = floor($time_left / 86400);
        $hours = floor(($time_left % 86400) / 3600);
        $minutes = floor(($time_left % 3600) / 60);
        
        $time_text = '';
        if ($days > 0) {
            $time_text .= $days . ' día' . ($days != 1 ? 's' : '') . ', ';
        }
        if ($hours > 0) {
            $time_text .= $hours . ' hora' . ($hours != 1 ? 's' : '') . ' y ';
        }
        $time_text .= $minutes . ' minuto' . ($minutes != 1 ? 's' : '');
        
        $ban_message .= '<p><strong>Tiempo restante:</strong> ' . $time_text . '</p>';
    } else {
        // El ban ya expiró pero aún no se ha actualizado en la base de datos
        $ban_message .= '<p><strong>Estado:</strong> El ban ha expirado, intenta refrescar la página</p>';
    }
} else {
    $ban_message .= '<p><strong>Tipo:</strong> Ban permanente</p>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - SimpleChan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .ban-notice {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            background-color: #4d2d2d;
            border: 2px solid #ff6b6b;
            border-radius: 12px;
            text-align: center;
            color: #e0e0e0;
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.1);
        }
        
        .ban-notice h2 {
            color: #ff6b6b;
            margin-bottom: 25px;
            font-size: 28px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .ban-notice p {
            margin: 15px 0;
            line-height: 1.8;
            font-size: 16px;
        }
        
        .ban-notice strong {
            color: #fff;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Auto-refresh cada 30 segundos para bans temporales */
        <?php if (isset($ban_info['expires_at']) && $ban_info['expires_at']): ?>
        body::before {
            content: "Esta página se actualizará automáticamente cada 30 segundos";
            display: block;
            text-align: center;
            background-color: #333;
            color: #ccc;
            padding: 5px;
            font-size: 12px;
        }
        <?php endif; ?>
    </style>
    
    <?php if (isset($ban_info['expires_at']) && $ban_info['expires_at']): ?>
    <script>
        // Auto-refresh para bans temporales
        setTimeout(function() {
            window.location.reload();
        }, 30000); // 30 segundos
        
        // Contador visual
        let timeLeft = 30;
        const updateCounter = () => {
            const counterElement = document.getElementById('refresh-counter');
            if (counterElement) {
                counterElement.textContent = timeLeft;
            }
            timeLeft--;
            
            if (timeLeft >= 0) {
                setTimeout(updateCounter, 1000);
            }
        };
        
        window.onload = function() {
            const banActions = document.querySelector('.ban-actions');
            if (banActions) {
                const counter = document.createElement('p');
                counter.innerHTML = 'Página se actualizará en <span id="refresh-counter">30</span> segundos';
                counter.style.fontSize = '12px';
                counter.style.color = '#888';
                counter.style.marginTop = '20px';
                banActions.appendChild(counter);
                updateCounter();
            }
        };
    </script>
    <?php endif; ?>
</head>
<body>
    <header>
        <h1>SimpleChan</h1>
        <p>Imageboard Anónimo Simple</p>
    </header>
    
    <main>
        <?php echo $ban_message; ?>
    </main>
    
    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>
</body>
</html>
