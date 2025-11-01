<?php
/**
 * Gestión de Reportes - Panel de Administración SimpleChan
 */

require_once 'includes/AdminController.php';
require_once 'includes/AdminTemplate.php';

class ReportsController extends AdminController {
    
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
        
        // Eliminar reporte
        if (isset($_POST['delete_report'])) {
            $redirect = $this->deleteReport();
        }
        
        // Marcar como resuelto
        if (isset($_POST['resolve_report'])) {
            $redirect = $this->resolveReport();
        }
        
        // Eliminar post reportado
        if (isset($_POST['delete_reported_post'])) {
            $redirect = $this->deleteReportedPost();
        }
        
        // Banear IP del post reportado
        if (isset($_POST['ban_reported_ip'])) {
            $redirect = $this->banReportedIp();
        }
        
        if ($redirect) {
            $this->redirect('reports.php');
        }
    }
    
    private function deleteReport() {
        $report_id = (int)($_POST['report_id'] ?? 0);
        
        if ($report_id <= 0) {
            $this->addError('ID de reporte inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$report_id]);
            
            if ($stmt->rowCount() > 0) {
                $this->addSuccess('Reporte eliminado correctamente.');
                $this->logActivity('delete_report', "Report ID: $report_id");
                return true;
            } else {
                $this->addError('Reporte no encontrado.');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error deleting report: " . $e->getMessage());
            $this->addError('Error al eliminar el reporte.');
            return false;
        }
    }
    
    private function resolveReport() {
        $report_id = (int)($_POST['report_id'] ?? 0);
        $resolution = $this->cleanInput($_POST['resolution'] ?? '');
        
        if ($report_id <= 0) {
            $this->addError('ID de reporte inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Verificar si la tabla reports tiene columnas de resolución, si no, simular
            $stmt = $pdo->prepare("SHOW COLUMNS FROM reports LIKE 'resolved_at'");
            $stmt->execute();
            $has_resolution_fields = $stmt->rowCount() > 0;
            
            if ($has_resolution_fields) {
                // Actualizar con campos de resolución
                $stmt = $pdo->prepare("
                    UPDATE reports 
                    SET resolved_at = NOW(), resolved_by = ?, resolution = ?
                    WHERE id = ?
                ");
                $stmt->execute([$this->current_user['username'], $resolution, $report_id]);
            } else {
                // Si no hay campos de resolución, simplemente eliminamos el reporte
                $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
                $stmt->execute([$report_id]);
            }
            
            if ($stmt->rowCount() > 0) {
                $this->addSuccess('Reporte marcado como resuelto.');
                $this->logActivity('resolve_report', "Report ID: $report_id, Resolution: $resolution");
                return true;
            } else {
                $this->addError('Reporte no encontrado.');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error resolving report: " . $e->getMessage());
            $this->addError('Error al resolver el reporte.');
            return false;
        }
    }
    
    private function deleteReportedPost() {
        $report_id = (int)($_POST['report_id'] ?? 0);
        
        if ($report_id <= 0) {
            $this->addError('ID de reporte inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Obtener información del reporte y post
            $stmt = $pdo->prepare("
                SELECT r.post_id, p.image_filename 
                FROM reports r 
                INNER JOIN posts p ON r.post_id = p.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$report_id]);
            $data = $stmt->fetch();
            
            if (!$data) {
                $this->addError('Reporte o post no encontrado.');
                return false;
            }
            
            // Eliminar imagen si existe
            if (!empty($data['image_filename'])) {
                $image_path = ADMIN_UPLOAD_PATH . $data['image_filename'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Marcar post como eliminado
            $stmt = $pdo->prepare("UPDATE posts SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$data['post_id']]);
            
            // Eliminar el reporte
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$report_id]);
            
            $this->addSuccess('Post reportado eliminado y reporte resuelto.');
            $this->logActivity('delete_reported_post', "Report ID: $report_id, Post ID: {$data['post_id']}");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error deleting reported post: " . $e->getMessage());
            $this->addError('Error al eliminar el post reportado.');
            return false;
        }
    }
    
    private function banReportedIp() {
        $report_id = (int)($_POST['report_id'] ?? 0);
        $reason = $this->cleanInput($_POST['ban_reason'] ?? 'Contenido reportado');
        $duration = (int)($_POST['ban_duration'] ?? 24);
        
        if ($report_id <= 0) {
            $this->addError('ID de reporte inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Obtener IP del post reportado
            $stmt = $pdo->prepare("
                SELECT p.ip_address, p.id as post_id
                FROM reports r 
                INNER JOIN posts p ON r.post_id = p.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$report_id]);
            $data = $stmt->fetch();
            
            if (!$data) {
                $this->addError('Reporte o post no encontrado.');
                return false;
            }
            
            // Verificar si ya existe un ban activo para esta IP
            $stmt = $pdo->prepare("
                SELECT id FROM bans 
                WHERE ip_address = ? AND is_active = 1 
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$data['ip_address']]);
            
            if ($stmt->fetch()) {
                $this->addError('Esta IP ya tiene un ban activo.');
                return false;
            }
            
            // Crear el ban
            $expires_at = $duration > 0 ? date('Y-m-d H:i:s', time() + ($duration * 3600)) : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO bans (ip_address, reason, banned_by, expires_at, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['ip_address'],
                $reason,
                $this->current_user['username'],
                $expires_at
            ]);
            
            // Marcar reporte como resuelto eliminándolo
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$report_id]);
            
            $duration_text = $expires_at ? "$duration horas" : "permanente";
            $this->addSuccess("IP {$data['ip_address']} baneada ($duration_text) y reporte resuelto.");
            $this->logActivity('ban_reported_ip', "Report ID: $report_id, IP: {$data['ip_address']}, Duration: $duration_text");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error banning reported IP: " . $e->getMessage());
            $this->addError('Error al banear la IP reportada.');
            return false;
        }
    }
    
    public function getReports($page = 1, $per_page = 30, $filter = []) {
        $pdo = $this->auth->pdo;
        $offset = ($page - 1) * $per_page;
        
        try {
            // Construir query con filtros
            $where_conditions = ['1=1'];
            $params = [];
            
            if (!empty($filter['reason'])) {
                $where_conditions[] = 'r.reason LIKE ?';
                $params[] = '%' . $filter['reason'] . '%';
            }
            
            if (!empty($filter['board'])) {
                $where_conditions[] = 'b.id = ?';
                $params[] = $filter['board'];
            }
            
            if (!empty($filter['reporter_ip'])) {
                $where_conditions[] = 'r.reporter_ip LIKE ?';
                $params[] = '%' . $filter['reporter_ip'] . '%';
            }
            
            // Solo mostrar reportes de posts no eliminados por defecto
            if (!isset($filter['include_deleted']) || !$filter['include_deleted']) {
                $where_conditions[] = 'p.is_deleted = 0';
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Contar total
            $count_query = "
                SELECT COUNT(*) 
                FROM reports r
                INNER JOIN posts p ON r.post_id = p.id
                INNER JOIN boards b ON p.board_id = b.id
                WHERE $where_clause
            ";
            $stmt = $pdo->prepare($count_query);
            $stmt->execute($params);
            $total_reports = $stmt->fetchColumn();
            
            // Obtener reportes
            $params[] = $per_page;
            $params[] = $offset;
            
            $query = "
                SELECT r.*, 
                       p.subject, p.message, p.name as post_author, p.ip_address as post_ip, p.created_at as post_created_at, p.is_deleted,
                       b.name as board_name, b.short_id as board_short_id
                FROM reports r
                INNER JOIN posts p ON r.post_id = p.id
                INNER JOIN boards b ON p.board_id = b.id
                WHERE $where_clause
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'reports' => $reports,
                'total' => $total_reports,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total_reports / $per_page)
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting reports: " . $e->getMessage());
            return [
                'reports' => [],
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
    
    public function getReportStats() {
        $pdo = $this->auth->pdo;
        
        try {
            $stats = [];
            
            // Total de reportes pendientes
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM reports r 
                INNER JOIN posts p ON r.post_id = p.id 
                WHERE p.is_deleted = 0
            ");
            $stmt->execute();
            $stats['pending'] = $stmt->fetchColumn();
            
            // Reportes de hoy
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM reports r 
                INNER JOIN posts p ON r.post_id = p.id 
                WHERE DATE(r.created_at) = CURDATE() AND p.is_deleted = 0
            ");
            $stmt->execute();
            $stats['today'] = $stmt->fetchColumn();
            
            // Reportes por razón (top 5)
            $stmt = $pdo->prepare("
                SELECT r.reason, COUNT(*) as count
                FROM reports r 
                INNER JOIN posts p ON r.post_id = p.id 
                WHERE p.is_deleted = 0
                GROUP BY r.reason 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $stats['by_reason'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error getting report stats: " . $e->getMessage());
            return [
                'pending' => 0,
                'today' => 0,
                'by_reason' => []
            ];
        }
    }
}

class ReportsView {
    private $controller;
    private $reports_data;
    private $boards;
    private $stats;
    private $current_filter;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->boards = $controller->getBoards();
        $this->stats = $controller->getReportStats();
        
        // Obtener filtros actuales
        $this->current_filter = [
            'reason' => $_GET['reason'] ?? '',
            'board' => $_GET['board'] ?? '',
            'reporter_ip' => $_GET['reporter_ip'] ?? '',
            'include_deleted' => isset($_GET['include_deleted'])
        ];
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $this->reports_data = $controller->getReports($page, 30, $this->current_filter);
    }
    
    public function render() {
        $user = $this->controller->getCurrentUser();
        
        AdminTemplate::renderHeader('Gestión de Reportes', $user);
        ?>
        
        <div style="display: flex; gap: 20px; max-width: 1200px; margin: 20px auto; padding: 0 20px;">
            <?php AdminTemplate::renderSidebar('reports', $user['role']); ?>
            
            <main style="flex: 1;">
                <?php AdminTemplate::renderMessages($this->controller->getMessages()); ?>
                <?php $this->renderReportsManagement(); ?>
            </main>
        </div>
        
        <?php AdminTemplate::renderFooter(); ?>
        <?php
    }
    
    // Header, sidebar y messages ahora se manejan en AdminTemplate
    
    private function renderReportsManagement() {
        ?>
        <div class="reports-management">
            <h2>Gestión de Reportes</h2>
            
            <!-- Estadísticas -->
            <?php $this->renderStats(); ?>
            
            <!-- Filtros -->
            <?php $this->renderFilters(); ?>
            
            <!-- Lista de Reportes -->
            <?php if (empty($this->reports_data['reports'])): ?>
                <div class="no-results">
                    <p>No se encontraron reportes con los filtros aplicados.</p>
                </div>
            <?php else: ?>
                <?php $this->renderReportsList(); ?>
                
                <!-- Paginación -->
                <?php $this->renderPagination(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function renderStats() {
        ?>
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Reportes Pendientes</h4>
                    <div class="stat-number"><?php echo $this->stats['pending']; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Reportes Hoy</h4>
                    <div class="stat-number"><?php echo $this->stats['today']; ?></div>
                </div>
            </div>
            
            <?php if (!empty($this->stats['by_reason'])): ?>
                <div class="reasons-stats">
                    <h4>Razones más comunes:</h4>
                    <ul>
                        <?php foreach ($this->stats['by_reason'] as $reason_stat): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($reason_stat['reason']); ?></strong>: 
                                <?php echo $reason_stat['count']; ?> reportes
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function renderFilters() {
        ?>
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="reason">Razón:</label>
                        <input type="text" id="reason" name="reason" 
                               value="<?php echo htmlspecialchars($this->current_filter['reason']); ?>" 
                               placeholder="Buscar por razón...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="board">Tablón:</label>
                        <select id="board" name="board">
                            <option value="">Todos los tablones</option>
                            <?php foreach ($this->boards as $board): ?>
                                <option value="<?php echo $board['id']; ?>" 
                                        <?php echo $this->current_filter['board'] == $board['id'] ? 'selected' : ''; ?>>
                                    /{<?php echo htmlspecialchars($board['short_id']); ?>/ - <?php echo htmlspecialchars($board['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="reporter_ip">IP Reportador:</label>
                        <input type="text" id="reporter_ip" name="reporter_ip" 
                               value="<?php echo htmlspecialchars($this->current_filter['reporter_ip']); ?>" 
                               placeholder="IP del reportador...">
                    </div>
                    
                    <div class="filter-group">
                        <label>
                            <input type="checkbox" name="include_deleted" 
                                   <?php echo $this->current_filter['include_deleted'] ? 'checked' : ''; ?>>
                            Incluir posts eliminados
                        </label>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="reports.php" class="btn btn-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function renderReportsList() {
        ?>
        <div class="section">
            <h3>Lista de Reportes (<?php echo $this->reports_data['total']; ?> total)</h3>
            
            <?php foreach ($this->reports_data['reports'] as $report): ?>
                <div class="report-item <?php echo $report['is_deleted'] ? 'post-deleted' : ''; ?>">
                    <div class="report-header">
                        <div class="report-meta">
                            <span class="report-id"><strong>Reporte #<?php echo $report['id']; ?></strong></span>
                            <span class="board-info">/{<?php echo htmlspecialchars($report['board_short_id']); ?>/ - <?php echo htmlspecialchars($report['board_name']); ?></span>
                            <span class="report-date"><?php echo date('d/m/Y H:i:s', strtotime($report['created_at'])); ?></span>
                            <span class="reporter-ip">Reportado por: <?php echo htmlspecialchars($report['reporter_ip']); ?></span>
                            
                            <?php if ($report['is_deleted']): ?>
                                <span class="status-badge status-deleted">POST ELIMINADO</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="report-content">
                        <div class="report-info">
                            <div class="report-reason">
                                <strong>Razón:</strong> <?php echo htmlspecialchars($report['reason']); ?>
                            </div>
                            
                            <?php if (!empty($report['details'])): ?>
                                <div class="report-details">
                                    <strong>Detalles:</strong> <?php echo htmlspecialchars($report['details']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="reported-post">
                            <h4>Post Reportado (ID: <?php echo $report['post_id']; ?>)</h4>
                            
                            <div class="post-info">
                                <span><strong>Autor:</strong> <?php echo htmlspecialchars($report['post_author'] ?: 'Anónimo'); ?></span>
                                <span><strong>IP:</strong> <?php echo htmlspecialchars($report['post_ip']); ?></span>
                                <span><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s', strtotime($report['post_created_at'])); ?></span>
                            </div>
                            
                            <?php if (!empty($report['subject'])): ?>
                                <div class="post-subject">
                                    <strong>Asunto:</strong> <?php echo htmlspecialchars($report['subject']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-message">
                                <?php echo nl2br(htmlspecialchars(mb_substr($report['message'], 0, 300))); ?>
                                <?php if (mb_strlen($report['message']) > 300): ?>
                                    <span class="message-truncated">...</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-actions">
                        <?php if (!$report['is_deleted']): ?>
                            <!-- Acciones para posts activos -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                <button type="submit" name="delete_reported_post" class="btn btn-small btn-danger">Eliminar Post</button>
                            </form>
                            
                            <button type="button" class="btn btn-small btn-warning" onclick="showBanForm(<?php echo $report['id']; ?>, '<?php echo htmlspecialchars($report['post_ip']); ?>')">Banear IP</button>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                            <button type="submit" name="delete_report" class="btn btn-small btn-secondary">Descartar Reporte</button>
                        </form>
                        
                        <button type="button" class="btn btn-small" onclick="showResolveForm(<?php echo $report['id']; ?>)">Resolver</button>
                        
                        <a href="../index.php?board=<?php echo $report['board_short_id']; ?>#post-<?php echo $report['post_id']; ?>" 
                           target="_blank" class="btn btn-small btn-secondary">Ver Post</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Modal para banear IP -->
        <div id="banModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Banear IP del Post Reportado</h3>
                <form method="POST" id="banForm">
                    <input type="hidden" id="ban_report_id" name="report_id">
                    
                    <div class="form-group">
                        <label>IP a banear:</label>
                        <span id="ban_ip_display" style="font-weight: bold;"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="ban_reason">Razón del ban:</label>
                        <input type="text" id="ban_reason" name="ban_reason" value="Contenido reportado">
                    </div>
                    
                    <div class="form-group">
                        <label for="ban_duration">Duración (horas):</label>
                        <input type="number" id="ban_duration" name="ban_duration" value="24" min="1">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="ban_reported_ip" class="btn btn-danger">Banear IP</button>
                        <button type="button" class="btn btn-secondary" onclick="closeBanModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Modal para resolver reporte -->
        <div id="resolveModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Resolver Reporte</h3>
                <form method="POST" id="resolveForm">
                    <input type="hidden" id="resolve_report_id" name="report_id">
                    
                    <div class="form-group">
                        <label for="resolution">Resolución (opcional):</label>
                        <textarea id="resolution" name="resolution" rows="3" placeholder="Describir cómo se resolvió el reporte..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="resolve_report" class="btn btn-success">Marcar como Resuelto</button>
                        <button type="button" class="btn btn-secondary" onclick="closeResolveModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function showBanForm(reportId, ip) {
            document.getElementById('ban_report_id').value = reportId;
            document.getElementById('ban_ip_display').textContent = ip;
            document.getElementById('banModal').style.display = 'block';
        }
        
        function closeBanModal() {
            document.getElementById('banModal').style.display = 'none';
        }
        
        function showResolveForm(reportId) {
            document.getElementById('resolve_report_id').value = reportId;
            document.getElementById('resolveModal').style.display = 'block';
        }
        
        function closeResolveModal() {
            document.getElementById('resolveModal').style.display = 'none';
        }
        
        // Cerrar modales
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        </script>
        <?php
    }
    
    private function renderPagination() {
        $data = $this->reports_data;
        
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
    $controller = new ReportsController();
    $view = new ReportsView($controller);
    $view->render();
} catch (Exception $e) {
    header('Location: login.php');
    exit;
}
?>