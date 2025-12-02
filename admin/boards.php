<?php
/**
 * Gestión de Tablones - Panel de Administración SimpleChan
 */

require_once 'includes/AdminController.php';
require_once 'includes/AdminTemplate.php';

class BoardsController extends AdminController {
    
    public function __construct() {
        parent::__construct();
        
        // Solo administradores pueden gestionar tablones
        $this->requireAdmin();
        
        $this->processRequests();
    }
    
    private function processRequests() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequests();
        }
    }
    
    private function handlePostRequests() {
        $redirect = false;
        
        // Crear tablón
        if (isset($_POST['create_board'])) {
            $redirect = $this->createBoard();
        }
        
        // Actualizar tablón
        if (isset($_POST['update_board'])) {
            $redirect = $this->updateBoard();
        }
        
        // Eliminar tablón
        if (isset($_POST['delete_board'])) {
            $redirect = $this->deleteBoard();
        }
        
        // Cambiar orden de tablones
        if (isset($_POST['reorder_boards'])) {
            $redirect = $this->reorderBoards();
        }
        
        if ($redirect) {
            $this->redirect('boards.php');
        }
    }
    
    private function createBoard() {
        $short_id = strtolower(trim($_POST['short_id'] ?? ''));
        $name = $this->cleanInput($_POST['name'] ?? '');
        $description = $this->cleanInput($_POST['description'] ?? '');
        $category = $this->cleanInput($_POST['category'] ?? '');
        $is_nsfw = isset($_POST['is_nsfw']);
        $max_file_size = max(1, min(50, (int)($_POST['max_file_size'] ?? 8))); // MB
        $allowed_file_types = $this->cleanInput($_POST['allowed_file_types'] ?? '');
        
        // Validaciones
        if (empty($short_id) || empty($name) || empty($description) || empty($category)) {
            $this->addError('Todos los campos obligatorios deben estar completos.');
            return false;
        }
        
        // Validar short_id
        if (!preg_match('/^[a-z0-9]{1,10}$/', $short_id)) {
            $this->addError('El ID corto debe contener solo letras minúsculas y números (máximo 10 caracteres).');
            return false;
        }
        
        // Procesar tipos de archivos permitidos
        $file_types = array_map('trim', explode(',', $allowed_file_types));
        $valid_types = [];
        foreach ($file_types as $type) {
            $type = strtolower(ltrim($type, '.'));
            if (preg_match('/^[a-z0-9]+$/', $type)) {
                $valid_types[] = $type;
            }
        }
        
        if (empty($valid_types)) {
            $this->addError('Debe especificar al menos un tipo de archivo válido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Verificar si ya existe el short_id
            $stmt = $pdo->prepare("SELECT id FROM boards WHERE short_id = ?");
            $stmt->execute([$short_id]);
            if ($stmt->fetch()) {
                $this->addError('Ya existe un tablón con ese ID corto.');
                return false;
            }
            
            // Obtener el siguiente orden
            $stmt = $pdo->prepare("SELECT MAX(display_order) FROM boards");
            $stmt->execute();
            $max_order = $stmt->fetchColumn() ?: 0;
            
            // Crear tablón
            $stmt = $pdo->prepare("
                INSERT INTO boards (short_id, name, description, category, is_nsfw, max_file_size, allowed_file_types, display_order, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                $short_id,
                $name,
                $description,
                $category,
                $is_nsfw ? 1 : 0,
                $max_file_size * 1024 * 1024, // Convertir a bytes
                implode(',', $valid_types),
                $max_order + 1
            ]);
            
            if ($success) {
                $this->addSuccess("Tablón '/{$short_id}/ - {$name}' creado correctamente.");
                $this->logActivity('create_board', "Board: $short_id - $name");
                return true;
            } else {
                $this->addError('Error al crear el tablón.');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error creating board: " . $e->getMessage());
            $this->addError('Error al crear el tablón.');
            return false;
        }
    }
    
    private function updateBoard() {
        $board_id = (int)($_POST['board_id'] ?? 0);
        $name = $this->cleanInput($_POST['name'] ?? '');
        $description = $this->cleanInput($_POST['description'] ?? '');
        $category = $this->cleanInput($_POST['category'] ?? '');
        $is_nsfw = isset($_POST['is_nsfw']);
        $max_file_size = max(1, min(50, (int)($_POST['max_file_size'] ?? 8)));
        $allowed_file_types = $this->cleanInput($_POST['allowed_file_types'] ?? '');
        $is_active = isset($_POST['is_active']);
        
        if ($board_id <= 0 || empty($name) || empty($description) || empty($category)) {
            $this->addError('Datos inválidos para actualizar el tablón.');
            return false;
        }
        
        // Procesar tipos de archivos
        $file_types = array_map('trim', explode(',', $allowed_file_types));
        $valid_types = [];
        foreach ($file_types as $type) {
            $type = strtolower(ltrim($type, '.'));
            if (preg_match('/^[a-z0-9]+$/', $type)) {
                $valid_types[] = $type;
            }
        }
        
        if (empty($valid_types)) {
            $this->addError('Debe especificar al menos un tipo de archivo válido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE boards 
                SET name = ?, description = ?, category = ?, is_nsfw = ?, 
                    max_file_size = ?, allowed_file_types = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $success = $stmt->execute([
                $name,
                $description,
                $category,
                $is_nsfw ? 1 : 0,
                $max_file_size * 1024 * 1024,
                implode(',', $valid_types),
                $is_active ? 1 : 0,
                $board_id
            ]);
            
            if ($success && $stmt->rowCount() > 0) {
                $this->addSuccess("Tablón actualizado correctamente.");
                $this->logActivity('update_board', "Board ID: $board_id");
                return true;
            } else {
                $this->addError('No se realizaron cambios o el tablón no existe.');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error updating board: " . $e->getMessage());
            $this->addError('Error al actualizar el tablón.');
            return false;
        }
    }
    
    private function deleteBoard() {
        $board_id = (int)($_POST['board_id'] ?? 0);
        
        if ($board_id <= 0) {
            $this->addError('ID de tablón inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Verificar si tiene posts
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE board_id = ?");
            $stmt->execute([$board_id]);
            $post_count = $stmt->fetchColumn();
            
            if ($post_count > 0) {
                $this->addError("No se puede eliminar el tablón porque tiene $post_count posts. Elimine o mueva los posts primero.");
                return false;
            }
            
            // Obtener información del tablón antes de eliminarlo
            $stmt = $pdo->prepare("SELECT short_id, name FROM boards WHERE id = ?");
            $stmt->execute([$board_id]);
            $board = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$board) {
                $this->addError('Tablón no encontrado.');
                return false;
            }
            
            // Eliminar tablón
            $stmt = $pdo->prepare("DELETE FROM boards WHERE id = ?");
            $success = $stmt->execute([$board_id]);
            
            if ($success && $stmt->rowCount() > 0) {
                $this->addSuccess("Tablón '/{$board['short_id']}/ - {$board['name']}' eliminado correctamente.");
                $this->logActivity('delete_board', "Board: {$board['short_id']} - {$board['name']}");
                return true;
            } else {
                $this->addError('Error al eliminar el tablón.');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error deleting board: " . $e->getMessage());
            $this->addError('Error al eliminar el tablón.');
            return false;
        }
    }
    
    private function reorderBoards() {
        $board_orders = $_POST['board_order'] ?? [];
        
        if (empty($board_orders) || !is_array($board_orders)) {
            $this->addError('Datos de orden inválidos.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE boards SET display_order = ? WHERE id = ?");
            
            foreach ($board_orders as $board_id => $order) {
                $board_id = (int)$board_id;
                $order = (int)$order;
                
                if ($board_id > 0 && $order >= 0) {
                    $stmt->execute([$order, $board_id]);
                }
            }
            
            $pdo->commit();
            
            $this->addSuccess('Orden de tablones actualizado correctamente.');
            $this->logActivity('reorder_boards', 'Board order updated');
            
            return true;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error reordering boards: " . $e->getMessage());
            $this->addError('Error al actualizar el orden de los tablones.');
            return false;
        }
    }
    
    public function getAllBoards() {
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT b.*, 
                       COUNT(p.id) as post_count,
                       MAX(p.created_at) as last_post_at
                FROM boards b
                LEFT JOIN posts p ON b.id = p.board_id AND p.is_deleted = 0
                GROUP BY b.id
                ORDER BY b.display_order ASC, b.id ASC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting boards: " . $e->getMessage());
            return [];
        }
    }
    
    public function getBoard($board_id) {
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM boards WHERE id = ?");
            $stmt->execute([$board_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting board: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCategories() {
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT DISTINCT category FROM boards ORDER BY category");
            $stmt->execute();
            
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'category');
            
        } catch (PDOException $e) {
            error_log("Error getting categories: " . $e->getMessage());
            return [];
        }
    }
    
    public function getBoardStats() {
        $pdo = $this->auth->pdo;
        
        try {
            $stats = [];
            
            // Total de tablones
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM boards");
            $stmt->execute();
            $stats['total_boards'] = $stmt->fetchColumn();
            
            // Tablones activos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE is_active = 1");
            $stmt->execute();
            $stats['active_boards'] = $stmt->fetchColumn();
            
            // Tablones NSFW
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE is_nsfw = 1");
            $stmt->execute();
            $stats['nsfw_boards'] = $stmt->fetchColumn();
            
            // Tablón más activo
            $stmt = $pdo->prepare("
                SELECT b.short_id, b.name, COUNT(p.id) as post_count
                FROM boards b
                LEFT JOIN posts p ON b.id = p.board_id AND p.is_deleted = 0
                GROUP BY b.id
                ORDER BY post_count DESC
                LIMIT 1
            ");
            $stmt->execute();
            $stats['most_active_board'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error getting board stats: " . $e->getMessage());
            return [
                'total_boards' => 0,
                'active_boards' => 0,
                'nsfw_boards' => 0,
                'most_active_board' => null
            ];
        }
    }
}

class BoardsView {
    private $controller;
    private $boards;
    private $categories;
    private $stats;
    private $edit_board;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->boards = $controller->getAllBoards();
        $this->categories = $controller->getCategories();
        $this->stats = $controller->getBoardStats();
        
        // Verificar si estamos editando un tablón
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $this->edit_board = $controller->getBoard((int)$_GET['edit']);
        }
    }
    
    public function render() {
        $user = $this->controller->getCurrentUser();
        
        AdminTemplate::renderHeader('Gestión de Tablones', $user);
        ?>
        
        <div style="display: flex;margin: 20px auto;">
            <?php AdminTemplate::renderSidebar('boards', $user['role']); ?>
            
            <main style="flex: 1;">
                <?php AdminTemplate::renderMessages($this->controller->getMessages()); ?>
                <?php $this->renderBoardsManagement(); ?>
            </main>
        </div>
        
        <?php AdminTemplate::renderFooter(); ?>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
            <script>
                // Sistema de arrastrar y soltar para reordenar tablones
                const boardsList = document.getElementById('boards-list');
                if (boardsList) {
                    new Sortable(boardsList, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function(evt) {
                            updateBoardOrder();
                        }
                    });
                }
                
                function updateBoardOrder() {
                    const boardItems = document.querySelectorAll('#boards-list .board-item');
                    const orderData = {};
                    
                    boardItems.forEach((item, index) => {
                        const boardId = item.dataset.boardId;
                        orderData[boardId] = index + 1;
                    });
                    
                    // Enviar orden actualizado
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const actionInput = document.createElement('input');
                    actionInput.name = 'reorder_boards';
                    actionInput.value = '1';
                    form.appendChild(actionInput);
                    
                    for (const [boardId, order] of Object.entries(orderData)) {
                        const input = document.createElement('input');
                        input.name = `board_order[${boardId}]`;
                        input.value = order;
                        form.appendChild(input);
                    }
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            </script>
        </body>
        </html>
        <?php
    }
    
    private function renderHeader() {
        $user = $this->controller->getCurrentUser();
        ?>
        <header class="admin-header">
            <div class="admin-header-content">
                <h1>SimpleChan Admin</h1>
                <div class="admin-user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="user-role">(<?php echo ucfirst($user['role']); ?>)</span>
                    <a href="?logout=1" class="logout-btn">Cerrar Sesión</a>
                </div>
            </div>
        </header>
        <?php
    }
    
    private function renderSidebar() {
        ?>
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php"><i>🏠</i> Dashboard</a></li>
                    <li><a href="posts.php"><i>📄</i> Moderar Posts</a></li>
                    <li><a href="bans.php"><i>🚫</i> Gestión de Bans</a></li>
                    <li><a href="reports.php"><i>🚨</i> Reportes</a></li>
                    <li class="nav-divider"></li>
                    <li><a href="users.php"><i>👥</i> Gestión de Usuarios</a></li>
                    <li><a href="boards.php" class="active"><i>📋</i> Gestión de Tablones</a></li>
                    <li><a href="settings.php"><i>⚙️</i> Configuración</a></li>
                    <li class="nav-divider"></li>
                    <li><a href="../index.php" target="_blank"><i>🌐</i> Ver Sitio</a></li>
                </ul>
            </nav>
        </aside>
        <?php
    }
    
    private function renderMessages() {
        $messages = $this->controller->getMessages();
        
        foreach (['error', 'success', 'info'] as $type) {
            if (!empty($messages[$type])) {
                foreach ($messages[$type] as $message) {
                    echo '<div class="message message-' . $type . '">' . htmlspecialchars($message) . '</div>';
                }
            }
        }
    }
    
    private function renderBoardsManagement() {
        ?>
        <div class="boards-management">
            <h2>Gestión de Tablones</h2>
            
            <!-- Estadísticas -->
            <?php $this->renderStats(); ?>
            
            <!-- Formulario para crear/editar tablón -->
            <?php $this->renderBoardForm(); ?>
            
            <!-- Lista de tablones -->
            <?php $this->renderBoardsList(); ?>
        </div>
        <?php
    }
    
    private function renderStats() {
        ?>
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total de Tablones</h4>
                    <div class="stat-number"><?php echo $this->stats['total_boards']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Tablones Activos</h4>
                    <div class="stat-number"><?php echo $this->stats['active_boards']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Tablones NSFW</h4>
                    <div class="stat-number"><?php echo $this->stats['nsfw_boards']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Más Activo</h4>
                    <div class="stat-number">
                        <?php 
                        if ($this->stats['most_active_board']) {
                            echo '/' . htmlspecialchars($this->stats['most_active_board']['short_id']) . '/';
                            echo '<small>(' . $this->stats['most_active_board']['post_count'] . ' posts)</small>';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function renderBoardForm() {
        $board = $this->edit_board;
        $is_editing = $board !== null;
        
        ?>
        <div class="section">
            <h3><?php echo $is_editing ? 'Editar Tablón' : 'Crear Nuevo Tablón'; ?></h3>
            
            <?php if ($is_editing): ?>
                <div class="edit-info">
                    <p><strong>Editando:</strong> /<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?></p>
                    <a href="boards.php" class="btn btn-secondary">Cancelar Edición</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="board-form">
                <?php if ($is_editing): ?>
                    <input type="hidden" name="board_id" value="<?php echo $board['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="short_id">ID Corto:</label>
                        <input type="text" id="short_id" name="short_id" 
                               value="<?php echo htmlspecialchars($board['short_id'] ?? ''); ?>" 
                               <?php echo $is_editing ? 'readonly' : 'required'; ?>
                               pattern="[a-z0-9]{1,10}" 
                               placeholder="ej: g, am, tech" 
                               maxlength="10">
                        <small>Solo letras minúsculas y números, máximo 10 caracteres</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Categoría:</label>
                        <input type="text" id="category" name="category" 
                               value="<?php echo htmlspecialchars($board['category'] ?? ''); ?>" 
                               required 
                               placeholder="ej: Cultura Japonesa, Intereses">
                        <?php if (!empty($this->categories)): ?>
                            <datalist id="categories-list">
                                <?php foreach ($this->categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <script>document.getElementById('category').setAttribute('list', 'categories-list');</script>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name">Nombre del Tablón:</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($board['name'] ?? ''); ?>" 
                           required 
                           placeholder="ej: General, Anime y Manga">
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción:</label>
                    <textarea id="description" name="description" 
                              rows="3" required 
                              placeholder="Descripción del tablón"><?php echo htmlspecialchars($board['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_file_size">Tamaño Máximo de Archivo (MB):</label>
                        <input type="number" id="max_file_size" name="max_file_size" 
                               value="<?php echo $board ? round($board['max_file_size'] / 1024 / 1024) : 8; ?>" 
                               min="1" max="50" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="allowed_file_types">Tipos de Archivos Permitidos:</label>
                        <input type="text" id="allowed_file_types" name="allowed_file_types" 
                               value="<?php echo htmlspecialchars($board['allowed_file_types'] ?? 'jpg,jpeg,png,gif,webp'); ?>" 
                               required 
                               placeholder="jpg,png,gif,webp">
                        <small>Separados por comas, sin puntos</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_nsfw" 
                                   <?php echo ($board['is_nsfw'] ?? false) ? 'checked' : ''; ?>>
                            Contenido NSFW (No Seguro para el Trabajo)
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_active" 
                                   <?php echo ($board['is_active'] ?? true) ? 'checked' : ''; ?>>
                            Tablón Activo
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php if ($is_editing): ?>
                        <button type="submit" name="update_board" class="btn btn-primary">Actualizar Tablón</button>
                        <a href="boards.php" class="btn btn-secondary">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="create_board" class="btn btn-success">Crear Tablón</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function renderBoardsList() {
        ?>
        <div class="section">
            <h3>Lista de Tablones</h3>
            <p class="help-text">Arrastra y suelta para reordenar los tablones</p>
            
            <?php if (empty($this->boards)): ?>
                <div class="no-results">
                    <p>No hay tablones creados aún.</p>
                </div>
            <?php else: ?>
                <div id="boards-list" class="boards-list">
                    <?php foreach ($this->boards as $board): ?>
                        <div class="board-item" data-board-id="<?php echo $board['id']; ?>">
                            <div class="board-header">
                                <div class="board-info">
                                    <div class="board-title">
                                        <span class="board-short-id">/<?php echo htmlspecialchars($board['short_id']); ?>/</span>
                                        <span class="board-name"><?php echo htmlspecialchars($board['name']); ?></span>
                                        
                                        <?php if ($board['is_nsfw']): ?>
                                            <span class="badge nsfw">NSFW</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!$board['is_active']): ?>
                                            <span class="badge inactive">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="board-meta">
                                        <span class="board-category"><?php echo htmlspecialchars($board['category']); ?></span>
                                        <span class="board-posts"><?php echo $board['post_count']; ?> posts</span>
                                        <?php if ($board['last_post_at']): ?>
                                            <span class="board-last-post">Último post: <?php echo date('d/m/Y H:i', strtotime($board['last_post_at'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="board-actions">
                                    <a href="?edit=<?php echo $board['id']; ?>" class="btn btn-small">Editar</a>
                                    <a href="../index.php?board=<?php echo $board['short_id']; ?>" target="_blank" class="btn btn-small btn-secondary">Ver</a>
                                    
                                    <?php if ($board['post_count'] == 0): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este tablón?')">
                                            <input type="hidden" name="board_id" value="<?php echo $board['id']; ?>">
                                            <button type="submit" name="delete_board" class="btn btn-small btn-danger">Eliminar</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-small btn-danger" disabled title="No se puede eliminar: tiene posts">Eliminar</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="board-description">
                                <?php echo htmlspecialchars($board['description']); ?>
                            </div>
                            
                            <div class="board-settings">
                                <small>
                                    <strong>Archivos:</strong> <?php echo htmlspecialchars($board['allowed_file_types']); ?> 
                                    (max <?php echo round($board['max_file_size'] / 1024 / 1024); ?>MB)
                                    | <strong>Orden:</strong> <?php echo $board['display_order']; ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function renderFooter() {
        ?>
        <footer class="admin-footer">
            <p>&copy; 2025 SimpleChan - Panel de Administración</p>
        </footer>
        <?php
    }
}

// Inicializar la aplicación
try {
    $controller = new BoardsController();
    $view = new BoardsView($controller);
    $view->render();
} catch (Exception $e) {
    header('Location: login.php');
    exit;
}
?>