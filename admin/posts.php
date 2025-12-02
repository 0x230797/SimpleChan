<?php
/**
 * Moderación de Posts - Panel de Administración SimpleChan
 */

require_once 'includes/AdminController.php';
require_once 'includes/AdminTemplate.php';

class PostsController extends AdminController {
    
    public function __construct() {
        parent::__construct();
        
        // Moderadores y administradores pueden acceder
        $this->requireModerator();
        
        $this->processRequests();
    }
    
    private function processRequests() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequests();
        }
    }
    
    private function handlePostRequests() {
        $redirect = false;
        
        // Eliminar post
        if (isset($_POST['delete_post'])) {
            $redirect = $this->deletePost();
        }
        
        // Eliminar imagen
        if (isset($_POST['delete_image'])) {
            $redirect = $this->deleteImage();
        }
        
        // Fijar post
        if (isset($_POST['pin_post'])) {
            $redirect = $this->pinPost();
        }
        
        // Desfijar post
        if (isset($_POST['unpin_post'])) {
            $redirect = $this->unpinPost();
        }
        
        // Bloquear post
        if (isset($_POST['lock_post'])) {
            $redirect = $this->lockPost();
        }
        
        // Desbloquear post
        if (isset($_POST['unlock_post'])) {
            $redirect = $this->unlockPost();
        }
        
        if ($redirect) {
            $this->redirect('posts.php');
        }
    }
    
    private function deletePost() {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Obtener información del post
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if (!$post) {
                $this->addError('Post no encontrado.');
                return false;
            }
            
            // Eliminar imagen si existe
            if (!empty($post['image_filename'])) {
                $image_path = ADMIN_UPLOAD_PATH . $post['image_filename'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Marcar como eliminado en lugar de eliminar físicamente
            $stmt = $pdo->prepare("UPDATE posts SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$post_id]);
            
            $this->addSuccess('Post eliminado correctamente.');
            $this->logActivity('delete_post', "Post ID: $post_id eliminado");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error deleting post: " . $e->getMessage());
            $this->addError('Error al eliminar el post.');
            return false;
        }
    }
    
    private function deleteImage() {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT image_filename FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if (!$post || empty($post['image_filename'])) {
                $this->addError('Post no tiene imagen asociada.');
                return false;
            }
            
            // Eliminar archivo físico
            $image_path = ADMIN_UPLOAD_PATH . $post['image_filename'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            
            // Actualizar base de datos
            $stmt = $pdo->prepare("
                UPDATE posts 
                SET image_filename = NULL, image_original_name = NULL, image_size = NULL, image_dimensions = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$post_id]);
            
            $this->addSuccess('Imagen eliminada correctamente.');
            $this->logActivity('delete_image', "Imagen del post ID: $post_id eliminada");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error deleting image: " . $e->getMessage());
            $this->addError('Error al eliminar la imagen.');
            return false;
        }
    }
    
    private function pinPost() {
        return $this->togglePostStatus('is_pinned', 1, 'Post fijado correctamente.', 'pin_post');
    }
    
    private function unpinPost() {
        return $this->togglePostStatus('is_pinned', 0, 'Post desfijado correctamente.', 'unpin_post');
    }
    
    private function lockPost() {
        return $this->togglePostStatus('is_locked', 1, 'Post bloqueado correctamente.', 'lock_post');
    }
    
    private function unlockPost() {
        return $this->togglePostStatus('is_locked', 0, 'Post desbloqueado correctamente.', 'unlock_post');
    }
    
    private function togglePostStatus($field, $value, $successMessage, $action) {
        $post_id = (int)($_POST['post_id'] ?? 0);
        
        if ($post_id <= 0) {
            $this->addError('ID de post inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("UPDATE posts SET $field = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$value, $post_id]);
            
            if ($stmt->rowCount() > 0) {
                $this->addSuccess($successMessage);
                $this->logActivity($action, "Post ID: $post_id");
                return true;
            } else {
                $this->addError('Post no encontrado.');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error toggling post status: " . $e->getMessage());
            $this->addError('Error al actualizar el estado del post.');
            return false;
        }
    }
    
    public function getPosts($page = 1, $per_page = 20, $filter = []) {
        $pdo = $this->auth->pdo;
        $offset = ($page - 1) * $per_page;
        
        try {
            // Construir query con filtros
            $where_conditions = ['1=1'];
            $params = [];
            
            if (!empty($filter['board'])) {
                $where_conditions[] = 'p.board_id = ?';
                $params[] = $filter['board'];
            }
            
            if (!empty($filter['deleted']) && $filter['deleted'] === 'only') {
                $where_conditions[] = 'p.is_deleted = 1';
            } elseif (empty($filter['deleted']) || $filter['deleted'] !== 'include') {
                $where_conditions[] = 'p.is_deleted = 0';
            }
            
            if (!empty($filter['search'])) {
                $where_conditions[] = '(p.subject LIKE ? OR p.message LIKE ? OR p.name LIKE ?)';
                $search_term = '%' . $filter['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Contar total
            $count_query = "
                SELECT COUNT(*) 
                FROM posts p 
                INNER JOIN boards b ON p.board_id = b.id 
                WHERE $where_clause
            ";
            $stmt = $pdo->prepare($count_query);
            $stmt->execute($params);
            $total_posts = $stmt->fetchColumn();
            
            // Obtener posts
            $params[] = $per_page;
            $params[] = $offset;
            
            $query = "
                SELECT p.*, b.name as board_name, b.short_id as board_short_id
                FROM posts p
                INNER JOIN boards b ON p.board_id = b.id
                WHERE $where_clause
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'posts' => $posts,
                'total' => $total_posts,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total_posts / $per_page)
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting posts: " . $e->getMessage());
            return [
                'posts' => [],
                'total' => 0,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => 0
            ];
        }
    }
    
    public function getBoards() {
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT id, short_id, name FROM boards ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting boards: " . $e->getMessage());
            return [];
        }
    }
}

class PostsView {
    private $controller;
    private $posts_data;
    private $boards;
    private $current_filter;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->boards = $controller->getBoards();
        
        // Obtener filtros actuales
        $this->current_filter = [
            'board' => $_GET['board'] ?? '',
            'deleted' => $_GET['deleted'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $this->posts_data = $controller->getPosts($page, 20, $this->current_filter);
    }
    
    public function render() {
        $user = $this->controller->getCurrentUser();
        
        AdminTemplate::renderHeader('Moderación de Posts', $user);
        ?>
        
        <div style="display: flex;margin: 20px auto;">
            <?php AdminTemplate::renderSidebar('posts', $user['role']); ?>
            
            <main style="flex: 1;">
                <?php AdminTemplate::renderMessages($this->controller->getMessages()); ?>
                <?php $this->renderPostsManagement(); ?>
            </main>
        </div>
        
        <?php AdminTemplate::renderFooter(); ?>
        <?php
    }
    
    // Header, sidebar y messages ahora se manejan en AdminTemplate
    
    private function renderPostsManagement() {
        ?>
        <!-- Filtros -->
        <div class="box-outer">
            <div class="boxbar">
                <h3>🔍 Filtros de Moderación</h3>
            </div>
            <div class="boxcontent">
                <?php $this->renderFilters(); ?>
            </div>
        </div>
        
        <!-- Información de paginación -->
        <div class="box-outer" style="margin-top: 15px;">
            <div class="boxbar">
                <h3>📊 Resultados</h3>
            </div>
            <div class="boxcontent">
                <p>Mostrando <?php echo count($this->posts_data['posts']); ?> de <?php echo $this->posts_data['total']; ?> posts</p>
            </div>
        </div>
            
            <!-- Lista de Posts -->
            <?php if (empty($this->posts_data['posts'])): ?>
                <div class="no-results">
                    <p>No se encontraron posts con los filtros aplicados.</p>
                </div>
            <?php else: ?>
                <div class="posts-list">
                    <?php foreach ($this->posts_data['posts'] as $post): ?>
                        <?php $this->renderPost($post); ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación -->
                <?php $this->renderPagination(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function renderFilters() {
        ?>
        <form method="GET" style="display: grid; gap: 15px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label for="board" style="display: block; margin-bottom: 5px; font-weight: bold;">Tablón:</label>
                    <select id="board" name="board" style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                        <option value="">Todos los tablones</option>
                        <?php foreach ($this->boards as $board): ?>
                            <option value="<?php echo $board['id']; ?>" 
                                    <?php echo $this->current_filter['board'] == $board['id'] ? 'selected' : ''; ?>>
                                /{<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="deleted" style="display: block; margin-bottom: 5px; font-weight: bold;">Estado:</label>
                    <select id="deleted" name="deleted" style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                        <option value="" <?php echo $this->current_filter['deleted'] === '' ? 'selected' : ''; ?>>Solo activos</option>
                        <option value="include" <?php echo $this->current_filter['deleted'] === 'include' ? 'selected' : ''; ?>>Todos</option>
                        <option value="only" <?php echo $this->current_filter['deleted'] === 'only' ? 'selected' : ''; ?>>Solo eliminados</option>
                    </select>
                </div>
                
                <div>
                    <label for="search" style="display: block; margin-bottom: 5px; font-weight: bold;">Buscar:</label>
                    <input type="text" id="search" name="search" 
                           style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;"
                           value="<?php echo htmlspecialchars($this->current_filter['search']); ?>" 
                           placeholder="Buscar en posts...">
                </div>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <input type="submit" value="Filtrar" 
                       style="background: var(--primary-color); color: white; padding: 8px 16px; border: none; border-radius: 3px; cursor: pointer;">
                <a href="posts.php" 
                   style="background: var(--border-color); color: var(--text-color); padding: 8px 16px; border: none; border-radius: 3px; text-decoration: none;">Limpiar</a>
            </div>
        </form>
        <?php
    }
    
    private function renderPost($post) {
        ?>
        <div class="post-item <?php echo $post['is_deleted'] ? 'post-deleted' : ''; ?>">
            <div class="post-header">
                <div class="post-meta">
                    <span class="post-id"><strong>#<?php echo $post['id']; ?></strong></span>
                    <span class="board-info">/{<?php echo htmlspecialchars($post['board_short_id']); ?>/ - <?php echo htmlspecialchars($post['board_name']); ?></span>
                    <span class="post-date"><?php echo date('d/m/Y H:i:s', strtotime($post['created_at'])); ?></span>
                    <span class="post-ip">IP: <?php echo htmlspecialchars($post['ip_address']); ?></span>
                    
                    <?php if ($post['is_deleted']): ?>
                        <span class="status-badge status-deleted">ELIMINADO</span>
                    <?php endif; ?>
                    
                    <?php if ($post['is_pinned']): ?>
                        <span class="status-badge status-pinned">FIJADO</span>
                    <?php endif; ?>
                    
                    <?php if ($post['is_locked']): ?>
                        <span class="status-badge status-locked">BLOQUEADO</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="post-content">
                <div class="post-info">
                    <span class="post-name"><?php echo htmlspecialchars($post['name'] ?: 'Anónimo'); ?></span>
                    <?php if (!empty($post['subject'])): ?>
                        <span class="post-subject"> - <?php echo htmlspecialchars($post['subject']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($post['image_filename']): ?>
                    <div class="post-image">
                        <?php 
                        $image_path = ADMIN_UPLOAD_PATH . $post['image_filename'];
                        if (file_exists($image_path)): 
                        ?>
                            <img src="<?php echo UPLOAD_DIR . $post['image_filename']; ?>" 
                                 alt="Post image" style="max-width: 200px; max-height: 200px;">
                        <?php else: ?>
                            <span class="image-deleted">[Imagen no disponible]</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="post-message">
                    <?php echo nl2br(htmlspecialchars(mb_substr($post['message'], 0, 500))); ?>
                    <?php if (mb_strlen($post['message']) > 500): ?>
                        <span class="message-truncated">...</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="post-actions">
                <?php if (!$post['is_deleted']): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" name="delete_post" class="btn btn-small btn-danger">Eliminar</button>
                    </form>
                    
                    <?php if ($post['is_pinned']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="unpin_post" class="btn btn-small">Desfijar</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="pin_post" class="btn btn-small">Fijar</button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($post['is_locked']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="unlock_post" class="btn btn-small">Desbloquear</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="lock_post" class="btn btn-small">Bloquear</button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($post['image_filename']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="delete_image" class="btn btn-small btn-danger">Eliminar Imagen</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                
                <a href="bans.php?ip=<?php echo urlencode($post['ip_address']); ?>" class="btn btn-small">Banear IP</a>
                <a href="../index.php?board=<?php echo $post['board_short_id']; ?>#post-<?php echo $post['id']; ?>" 
                   target="_blank" class="btn btn-small btn-secondary">Ver Post</a>
            </div>
        </div>
        <?php
    }
    
    private function renderPagination() {
        $data = $this->posts_data;
        
        if ($data['total_pages'] <= 1) return;
        
        ?>
        <div class="pagination">
            <?php if ($data['current_page'] > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($this->current_filter, ['page' => $data['current_page'] - 1])); ?>" class="btn btn-secondary">« Anterior</a>
            <?php endif; ?>
            
            <span class="pagination-info">
                Página <?php echo $data['current_page']; ?> de <?php echo $data['total_pages']; ?>
            </span>
            
            <?php if ($data['current_page'] < $data['total_pages']): ?>
                <a href="?<?php echo http_build_query(array_merge($this->current_filter, ['page' => $data['current_page'] + 1])); ?>" class="btn btn-secondary">Siguiente »</a>
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
    $controller = new PostsController();
    $view = new PostsView($controller);
    $view->render();
} catch (Exception $e) {
    header('Location: login.php');
    exit;
}
?>