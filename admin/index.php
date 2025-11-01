<?php
/**
 * Panel Principal de Administración - SimpleChan
 */

require_once 'includes/AdminController.php';
require_once 'includes/AdminTemplate.php';

class DashboardController extends AdminController {
    
    public function __construct() {
        parent::__construct();
        $this->processRequests();
    }
    
    private function processRequests() {
        // No hay acciones específicas para el dashboard por ahora
        // Las acciones globales las maneja el AdminController padre
    }
    
    /**
     * Obtiene estadísticas del sitio
     */
    public function getStats() {
        $pdo = $this->auth->pdo;
        
        try {
            $stats = [];
            
            // Total de posts
            $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE is_deleted = 0");
            $stats['total_posts'] = $stmt->fetchColumn();
            
            // Posts de hoy
            $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE() AND is_deleted = 0");
            $stats['posts_today'] = $stmt->fetchColumn();
            
            // Total de reportes pendientes
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports");
            $stats['pending_reports'] = $stmt->fetchColumn();
            
            // Bans activos
            $stmt = $pdo->query("SELECT COUNT(*) FROM bans WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
            $stats['active_bans'] = $stmt->fetchColumn();
            
            // Usuarios registrados
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['total_users'] = $stmt->fetchColumn();
            
            // Tablones
            $stmt = $pdo->query("SELECT COUNT(*) FROM boards");
            $stats['total_boards'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return [
                'total_posts' => 0,
                'posts_today' => 0,
                'pending_reports' => 0,
                'active_bans' => 0,
                'total_users' => 0,
                'total_boards' => 0
            ];
        }
    }
    
    /**
     * Obtiene actividad reciente
     */
    public function getRecentActivity() {
        $pdo = $this->auth->pdo;
        
        try {
            // Posts recientes
            $stmt = $pdo->prepare("
                SELECT p.id, p.subject, p.message, p.created_at, b.name as board_name, b.short_id
                FROM posts p
                INNER JOIN boards b ON p.board_id = b.id
                WHERE p.is_deleted = 0
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Reportes recientes
            $stmt = $pdo->prepare("
                SELECT r.*, p.subject, p.message
                FROM reports r
                INNER JOIN posts p ON r.post_id = p.id
                ORDER BY r.created_at DESC
                LIMIT 5
            ");
            $stmt->execute();
            $recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'posts' => $recent_posts,
                'reports' => $recent_reports
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting recent activity: " . $e->getMessage());
            return [
                'posts' => [],
                'reports' => []
            ];
        }
    }
}

class DashboardView {
    private $controller;
    private $stats;
    private $activity;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->stats = $controller->getStats();
        $this->activity = $controller->getRecentActivity();
    }
    
    public function render() {
        $user = $this->controller->getCurrentUser();
        
        AdminTemplate::renderHeader('Panel de Administración', $user);
        ?>
        
        <div style="display: flex; gap: 20px; max-width: 1200px; margin: 20px auto;">
            <?php AdminTemplate::renderSidebar('index', $user['role']); ?>
            
            <main style="flex: 1;">
                <?php AdminTemplate::renderMessages($this->controller->getMessages()); ?>
                <?php $this->renderDashboard(); ?>
            </main>
        </div>
        
        <?php AdminTemplate::renderFooter(); ?>
        <?php
    }
    
    // Métodos de header, sidebar y messages ahora se manejan en AdminTemplate
    
    private function renderDashboard() {
        ?>
        <!-- Estadísticas -->
        <div class="box-outer">
            <div class="boxbar">
                <h3>Estadísticas del Sitio</h3>
            </div>
            <div class="boxcontent">
                <div class="stats-grid">
                    <div class="box-outer">
                        <div class="boxbar"><h4>Posts Totales</h4></div>
                        <div class="boxcontent" style="text-align: center;">
                            <span style="font-size: 24px; font-weight: bold; color: var(--primary-color);">
                                <?php echo number_format($this->stats['total_posts']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="box-outer">
                        <div class="boxbar"><h4>Posts Hoy</h4></div>
                        <div class="boxcontent" style="text-align: center;">
                            <span style="font-size: 24px; font-weight: bold; color: var(--success-color);">
                                <?php echo number_format($this->stats['posts_today']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="box-outer">
                        <div class="boxbar"><h4>Reportes</h4></div>
                        <div class="boxcontent" style="text-align: center;">
                            <span style="font-size: 24px; font-weight: bold; color: var(--accent-color);">
                                <?php echo number_format($this->stats['pending_reports']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="box-outer">
                        <div class="boxbar"><h4>Bans Activos</h4></div>
                        <div class="boxcontent" style="text-align: center;">
                            <span style="font-size: 24px; font-weight: bold; color: var(--danger-color);">
                                <?php echo number_format($this->stats['active_bans']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="box-outer">
                        <div class="boxbar"><h4>Usuarios</h4></div>
                        <div class="boxcontent" style="text-align: center;">
                            <span style="font-size: 24px; font-weight: bold; color: var(--secondary-color);">
                                <?php echo number_format($this->stats['total_users']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="box-outer">
                        <div class="boxbar"><h4>Tablones</h4></div>
                        <div class="boxcontent" style="text-align: center;">
                            <span style="font-size: 24px; font-weight: bold; color: var(--text-color);">
                                <?php echo number_format($this->stats['total_boards']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            
        <!-- Actividad Reciente -->
        <div class="activity-grid" style="margin-top: 20px;">
            <div class="box-outer">
                <div class="boxbar">
                    <h3>Posts Recientes</h3>
                </div>
                <div class="boxcontent">
                    <?php if (empty($this->activity['posts'])): ?>
                        <p><em>No hay posts recientes.</em></p>
                    <?php else: ?>
                        <?php foreach ($this->activity['posts'] as $post): ?>
                            <div class="recent-item">
                                <div class="item-header">
                                    <strong>/<?php echo htmlspecialchars($post['short_id']); ?>/ - <?php echo htmlspecialchars($post['board_name']); ?></strong>
                                    <span class="item-time"><?php echo date('H:i d/m', strtotime($post['created_at'])); ?></span>
                                </div>
                                <?php if (!empty($post['subject'])): ?>
                                    <div style="font-weight: bold; margin: 5px 0;">
                                        <?php echo htmlspecialchars($post['subject']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="item-content">
                                    <?php echo mb_substr(strip_tags($post['message']), 0, 100) . '...'; ?>
                                </div>
                                <div style="text-align: right; margin-top: 5px;">
                                    <a href="../index.php?board=<?php echo $post['short_id']; ?>#post-<?php echo $post['id']; ?>" target="_blank">Ver Post →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="box-outer">
                <div class="boxbar">
                    <h3>Reportes Recientes</h3>
                </div>
                <div class="boxcontent">
                    <?php if (empty($this->activity['reports'])): ?>
                        <p><em>No hay reportes recientes.</em></p>
                    <?php else: ?>
                        <?php foreach ($this->activity['reports'] as $report): ?>
                            <div class="recent-item">
                                <div class="item-header">
                                    <strong>Post #<?php echo $report['post_id']; ?></strong>
                                    <span class="item-time"><?php echo date('H:i d/m', strtotime($report['created_at'])); ?></span>
                                </div>
                                <div style="color: var(--accent-color); font-weight: bold; margin: 5px 0;">
                                    <?php echo htmlspecialchars($report['reason']); ?>
                                </div>
                                <?php if (!empty($report['details'])): ?>
                                    <div style="font-size: 12px; color: var(--text-light);">
                                        <?php echo htmlspecialchars($report['details']); ?>
                                    </div>
                                <?php endif; ?>
                                <div style="text-align: right; margin-top: 5px;">
                                    <a href="reports.php#report-<?php echo $report['id']; ?>">Ver Reporte →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function renderFooter() {
        ?>
        <div class="box-outer" style="margin-top: 20px;">
            <div class="boxcontent" style="text-align: center; font-size: 12px; color: var(--text-light);">
                <p>&copy; 2025 SimpleChan - Panel de Administración | 
                <a href="../index.php">Ir al Sitio Principal</a></p>
            </div>
        </div>
        <?php
    }
}

// Inicializar la aplicación
try {
    $controller = new DashboardController();
    $view = new DashboardView($controller);
    $view->render();
} catch (Exception $e) {
    // Si hay error de autenticación, redirigir al login
    header('Location: login.php');
    exit;
}
?>