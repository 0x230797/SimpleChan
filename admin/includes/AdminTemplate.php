<?php
/**
 * Plantilla Base para el Panel de Administración
 * Usa las clases de SimpleChan para mantener coherencia visual
 */

class AdminTemplate {
    
    public static function renderHeader($title, $user, $additional_css = '') {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?> - SimpleChan Admin</title>
            <link rel="stylesheet" href="../assets/css/style.css">
            <link rel="stylesheet" href="../assets/css/themes.css">
            <link id="site-favicon" rel="shortcut icon" href="../assets/favicon/favicon.ico" type="image/x-icon">
            
        </head>
        <body>
            <main style="width: 100%; max-width: 100%;">
            <div class="box-outer top-box">
                <div class="boxbar">
                    <h2><?php echo htmlspecialchars($title); ?> - <a href="?logout=1" class="logout-btn">Cerrar Sesión</a></h2>
                </div>
            </div>
        <?php
    }
    
    public static function renderSidebar($current_page, $user_role) {
        $is_admin = $user_role === 'admin';
        ?>
        <aside style="width: 250px; margin-right: 20px;">
            <div class="box-outer">
                <div class="boxbar">
                    <h3>Dashboard</h3>
                </div>
                <div class="boxcontent">
                    <nav class="admin-nav">
                        <ul>
                            <li><a href="index.php" <?php echo $current_page === 'index' ? 'class="active"' : ''; ?>> Dashboard</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
            
            <div class="box-outer" style="margin-top: 15px;">
                <div class="boxbar">
                    <h3>Moderación</h3>
                </div>
                <div class="boxcontent">
                    <nav class="admin-nav">
                        <ul>
                            <li><a href="posts.php" <?php echo $current_page === 'posts' ? 'class="active"' : ''; ?>>Moderar Posts</a></li>
                            <li><a href="bans.php" <?php echo $current_page === 'bans' ? 'class="active"' : ''; ?>>Gestión de Bans</a></li>
                            <li><a href="reports.php" <?php echo $current_page === 'reports' ? 'class="active"' : ''; ?>>Reportes</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
            
            <?php if ($is_admin): ?>
            <div class="box-outer" style="margin-top: 15px;">
                <div class="boxbar">
                    <h3>Administración</h3>
                </div>
                <div class="boxcontent">
                    <nav class="admin-nav">
                        <ul>
                            <li><a href="users.php" <?php echo $current_page === 'users' ? 'class="active"' : ''; ?>>Gestión de Usuarios</a></li>
                            <li><a href="boards.php" <?php echo $current_page === 'boards' ? 'class="active"' : ''; ?>>Gestión de Tablones</a></li>
                            <li><a href="settings.php" <?php echo $current_page === 'settings' ? 'class="active"' : ''; ?>>Configuración</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
        </aside>
        <?php
    }
    
    public static function renderFooter() {
        ?>
        <div class="box-outer" style="margin-top: 20px;">
            <div class="boxcontent" style="text-align: center; font-size: 12px; color: var(--text-light);">
                <p>&copy; 2025 SimpleChan - Panel de Administración | 
                <a href="../index.php">Ir al Sitio Principal</a></p>
            </div>
        </div>

        </main>
        
        <script src="../assets/js/script.js"></script>
        </body>
        </html>
        <?php
    }
    
    public static function renderMessages($messages) {
        foreach (['error', 'success', 'info'] as $type) {
            if (!empty($messages[$type])) {
                foreach ($messages[$type] as $message) {
                    echo '<div class="message message-' . $type . '">' . htmlspecialchars($message) . '</div>';
                }
            }
        }
    }
}
?>