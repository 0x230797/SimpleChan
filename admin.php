<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Procesar login de admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $password = $_POST['password'] ?? '';
    
    if ($password === ADMIN_PASSWORD) {
        if (create_admin_session()) {
            $success = 'Sesión de administrador iniciada correctamente.';
        } else {
            $error = 'Error al crear la sesión.';
        }
    } else {
        $error = 'Contraseña incorrecta.';
    }
}

// Procesar logout de admin
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_token']);
    header('Location: admin.php');
    exit;
}

// Procesar acciones de admin
if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_post'])) {
        $post_id = (int)($_POST['post_id'] ?? 0);
        if ($post_id > 0 && delete_post($post_id)) {
            $success = 'Post eliminado correctamente.';
        } else {
            $error = 'Error al eliminar el post.';
        }
    }
    
    if (isset($_POST['ban_ip'])) {
        $ip_address = clean_input($_POST['ip_address'] ?? '');
        $reason = clean_input($_POST['reason'] ?? '');
        $duration = isset($_POST['duration']) && !empty($_POST['duration']) ? (int)$_POST['duration'] : null;
        
        if (!empty($ip_address) && ban_ip($ip_address, $reason, $duration)) {
            $success = 'IP baneada correctamente.';
        } else {
            $error = 'Error al banear la IP o IP vacía.';
        }
    }
    
    if (isset($_POST['unban_ip'])) {
        $ban_id = (int)($_POST['ban_id'] ?? 0);
        if ($ban_id > 0 && unban_ip($ban_id)) {
            $success = 'IP desbaneada correctamente.';
        } else {
            $error = 'Error al desbanear la IP.';
        }
    }
}

// Obtener datos para admin
$posts = is_admin() ? get_all_posts() : [];
$bans = is_admin() ? get_active_bans() : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - SimpleChan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Panel de Administración</h1>
        <nav>
            <a href="index.php">Volver al Tablón</a>
            <?php if (is_admin()): ?>
                <a href="?logout=1">Cerrar Sesión</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!is_admin()): ?>
            <!-- Formulario de login -->
            <section class="admin-login">
                <h2>Iniciar Sesión de Administrador</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="admin_login">Iniciar Sesión</button>
                </form>
            </section>
        <?php else: ?>
            <!-- Panel de administración -->
            <section class="admin-panel">
                <h2>Herramientas de Moderación</h2>
                
                <!-- Sección de baneos -->
                <div class="admin-section">
                    <h3>Banear IP</h3>
                    <form method="POST" class="ban-form">
                        <div class="form-group">
                            <label for="ip_address">Dirección IP:</label>
                            <input type="text" id="ip_address" name="ip_address" required placeholder="192.168.1.1">
                        </div>
                        <div class="form-group">
                            <label for="reason">Razón del ban:</label>
                            <input type="text" id="reason" name="reason" placeholder="Spam, contenido inapropiado, etc.">
                        </div>
                        <div class="form-group">
                            <label for="duration">Duración (horas, vacío = permanente):</label>
                            <input type="number" id="duration" name="duration" min="1" placeholder="24">
                        </div>
                        <button type="submit" name="ban_ip">Banear IP</button>
                    </form>
                </div>

                <!-- Lista de bans activos -->
                <div class="admin-section">
                    <h3>Bans Activos</h3>
                    <?php if (empty($bans)): ?>
                        <p>No hay bans activos.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>Razón</th>
                                    <th>Fecha</th>
                                    <th>Expira</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bans as $ban): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ban['ip_address']); ?></td>
                                        <td><?php echo htmlspecialchars($ban['reason']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ban['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($ban['expires_at']) {
                                                echo date('d/m/Y H:i', strtotime($ban['expires_at']));
                                            } else {
                                                echo 'Permanente';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="ban_id" value="<?php echo $ban['id']; ?>">
                                                <button type="submit" name="unban_ip" onclick="return confirm('¿Desbanear esta IP?')">Desbanear</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Lista de posts para moderar -->
                <div class="admin-section">
                    <h3>Moderar Posts</h3>
                    <?php if (empty($posts)): ?>
                        <p>No hay posts.</p>
                    <?php else: ?>
                        <div class="posts-admin">
                            <?php foreach ($posts as $post): ?>
                                <div class="post-admin <?php echo $post['is_deleted'] ? 'deleted' : ''; ?>">
                                    <div class="post-header">
                                        <strong>ID: <?php echo $post['id']; ?></strong>
                                        <?php if ($post['parent_id']): ?>
                                            (Respuesta a: <?php echo $post['parent_id']; ?>)
                                        <?php endif; ?>
                                        - <?php echo htmlspecialchars($post['name']); ?>
                                        - <?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?>
                                        - IP: <?php echo htmlspecialchars($post['ip_address']); ?>
                                        <?php if ($post['is_deleted']): ?>
                                            <span class="deleted-label">[ELIMINADO]</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($post['subject'])): ?>
                                        <div class="post-subject">
                                            <strong>Asunto:</strong> <?php echo htmlspecialchars($post['subject']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="post-message">
                                        <?php echo parse_references($post['message']); ?>
                                    </div>
                                    
                                    <?php if ($post['image_filename']): ?>
                                        <div class="post-image">
                                            <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                                                 alt="<?php echo htmlspecialchars($post['image_original_name']); ?>"
                                                 style="max-width: 200px; max-height: 200px;">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="post-actions">
                                        <?php if (!$post['is_deleted']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" name="delete_post" onclick="return confirm('¿Eliminar este post?')">Eliminar Post</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="ip_address" value="<?php echo $post['ip_address']; ?>">
                                            <input type="hidden" name="reason" value="Post inapropiado">
                                            <button type="submit" name="ban_ip" onclick="return confirm('¿Banear la IP <?php echo $post['ip_address']; ?>?')">Banear IP</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 SimpleChan - Panel de Administración</p>
    </footer>
</body>
</html>
