<?php
/**
 * Gestión de Bans - Panel de Administración SimpleChan
 */

require_once 'includes/AdminController.php';
require_once 'includes/AdminTemplate.php';

class BansController extends AdminController {
    
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
        
        // Banear IP
        if (isset($_POST['ban_ip'])) {
            $redirect = $this->banIp();
        }
        
        // Desbanear IP
        if (isset($_POST['unban_ip'])) {
            $redirect = $this->unbanIp();
        }
        
        // Editar ban
        if (isset($_POST['edit_ban'])) {
            $redirect = $this->editBan();
        }
        
        if ($redirect) {
            $this->redirect('bans.php');
        }
    }
    
    private function banIp() {
        $data = $this->cleanInput($_POST);
        
        // Validación
        $rules = [
            'ip_address' => ['required' => true, 'ip' => true],
            'reason' => ['max_length' => 500],
            'duration' => ['integer' => true]
        ];
        
        $errors = $this->validateInput($data, $rules);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError($error);
            }
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
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
            
            // Calcular fecha de expiración
            $expires_at = null;
            if (!empty($data['duration']) && $data['duration'] > 0) {
                $expires_at = date('Y-m-d H:i:s', time() + ($data['duration'] * 3600));
            }
            
            // Crear el ban
            $stmt = $pdo->prepare("
                INSERT INTO bans (ip_address, reason, banned_by, expires_at, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['ip_address'],
                $data['reason'] ?? '',
                $this->current_user['username'],
                $expires_at
            ]);
            
            $duration_text = $expires_at ? "{$data['duration']} horas" : "permanente";
            $this->addSuccess("IP {$data['ip_address']} baneada correctamente (duración: $duration_text).");
            $this->logActivity('ban_ip', "IP: {$data['ip_address']}, Razón: {$data['reason']}, Duración: $duration_text");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error banning IP: " . $e->getMessage());
            $this->addError('Error al banear la IP.');
            return false;
        }
    }
    
    private function unbanIp() {
        $ban_id = (int)($_POST['ban_id'] ?? 0);
        
        if ($ban_id <= 0) {
            $this->addError('ID de ban inválido.');
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Obtener información del ban
            $stmt = $pdo->prepare("SELECT ip_address FROM bans WHERE id = ? AND is_active = 1");
            $stmt->execute([$ban_id]);
            $ban = $stmt->fetch();
            
            if (!$ban) {
                $this->addError('Ban no encontrado o ya está inactivo.');
                return false;
            }
            
            // Desactivar el ban
            $stmt = $pdo->prepare("UPDATE bans SET is_active = 0 WHERE id = ?");
            $stmt->execute([$ban_id]);
            
            $this->addSuccess("IP {$ban['ip_address']} desbaneada correctamente.");
            $this->logActivity('unban_ip', "Ban ID: $ban_id, IP: {$ban['ip_address']}");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error unbanning IP: " . $e->getMessage());
            $this->addError('Error al desbanear la IP.');
            return false;
        }
    }
    
    private function editBan() {
        $ban_id = (int)($_POST['ban_id'] ?? 0);
        $data = $this->cleanInput($_POST);
        
        if ($ban_id <= 0) {
            $this->addError('ID de ban inválido.');
            return false;
        }
        
        // Validación
        $rules = [
            'reason' => ['max_length' => 500],
            'duration' => ['integer' => true]
        ];
        
        $errors = $this->validateInput($data, $rules);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError($error);
            }
            return false;
        }
        
        $pdo = $this->auth->pdo;
        
        try {
            // Calcular nueva fecha de expiración
            $expires_at = null;
            if (!empty($data['duration']) && $data['duration'] > 0) {
                $expires_at = date('Y-m-d H:i:s', time() + ($data['duration'] * 3600));
            }
            
            // Actualizar el ban
            $stmt = $pdo->prepare("
                UPDATE bans 
                SET reason = ?, expires_at = ?
                WHERE id = ? AND is_active = 1
            ");
            
            $stmt->execute([
                $data['reason'] ?? '',
                $expires_at,
                $ban_id
            ]);
            
            if ($stmt->rowCount() > 0) {
                $duration_text = $expires_at ? "{$data['duration']} horas" : "permanente";
                $this->addSuccess("Ban actualizado correctamente (nueva duración: $duration_text).");
                $this->logActivity('edit_ban', "Ban ID: $ban_id actualizado");
                return true;
            } else {
                $this->addError('Ban no encontrado o ya está inactivo.');
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Error editing ban: " . $e->getMessage());
            $this->addError('Error al editar el ban.');
            return false;
        }
    }
    
    public function getBans($page = 1, $per_page = 50, $filter = []) {
        $pdo = $this->auth->pdo;
        $offset = ($page - 1) * $per_page;
        
        try {
            // Construir query con filtros
            $where_conditions = ['1=1'];
            $params = [];
            
            if (!empty($filter['status'])) {
                if ($filter['status'] === 'active') {
                    $where_conditions[] = 'is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())';
                } elseif ($filter['status'] === 'expired') {
                    $where_conditions[] = 'is_active = 1 AND expires_at IS NOT NULL AND expires_at <= NOW()';
                } elseif ($filter['status'] === 'inactive') {
                    $where_conditions[] = 'is_active = 0';
                }
            } else {
                // Por defecto, mostrar solo bans activos (incluyendo expirados)
                $where_conditions[] = 'is_active = 1';
            }
            
            if (!empty($filter['ip'])) {
                $where_conditions[] = 'ip_address LIKE ?';
                $params[] = '%' . $filter['ip'] . '%';
            }
            
            if (!empty($filter['banned_by'])) {
                $where_conditions[] = 'banned_by LIKE ?';
                $params[] = '%' . $filter['banned_by'] . '%';
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Contar total
            $count_query = "SELECT COUNT(*) FROM bans WHERE $where_clause";
            $stmt = $pdo->prepare($count_query);
            $stmt->execute($params);
            $total_bans = $stmt->fetchColumn();
            
            // Obtener bans
            $params[] = $per_page;
            $params[] = $offset;
            
            $query = "
                SELECT *,
                CASE 
                    WHEN is_active = 0 THEN 'inactive'
                    WHEN expires_at IS NOT NULL AND expires_at <= NOW() THEN 'expired'
                    ELSE 'active'
                END AS status
                FROM bans 
                WHERE $where_clause
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $bans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'bans' => $bans,
                'total' => $total_bans,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total_bans / $per_page)
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting bans: " . $e->getMessage());
            return [
                'bans' => [],
                'total' => 0,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => 0
            ];
        }
    }
    
    public function getBanById($ban_id) {
        $pdo = $this->auth->pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM bans WHERE id = ?");
            $stmt->execute([$ban_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting ban: " . $e->getMessage());
            return null;
        }
    }
}

class BansView {
    private $controller;
    private $bans_data;
    private $current_filter;
    
    public function __construct($controller) {
        $this->controller = $controller;
        
        // Obtener filtros actuales
        $this->current_filter = [
            'status' => $_GET['status'] ?? '',
            'ip' => $_GET['ip'] ?? '',
            'banned_by' => $_GET['banned_by'] ?? ''
        ];
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $this->bans_data = $controller->getBans($page, 50, $this->current_filter);
    }
    
    public function render() {
        $user = $this->controller->getCurrentUser();
        
        AdminTemplate::renderHeader('Gestión de Bans', $user);
        ?>
        
        <div style="display: flex; gap: 20px; max-width: 1200px; margin: 20px auto; padding: 0 20px;">
            <?php AdminTemplate::renderSidebar('bans', $user['role']); ?>
            
            <main style="flex: 1;">
                <?php AdminTemplate::renderMessages($this->controller->getMessages()); ?>
                <?php $this->renderBansManagement(); ?>
            </main>
        </div>
        
        <?php AdminTemplate::renderFooter(); ?>
        <?php
    }
    
    // Header, sidebar y messages ahora se manejan en AdminTemplate
    
    private function renderBansManagement() {
        ?>
        <div class="bans-management">
            <h2>Gestión de Bans</h2>
            
            <!-- Formulario de Ban -->
            <?php $this->renderBanForm(); ?>
            
            <!-- Filtros -->
            <?php $this->renderFilters(); ?>
            
            <!-- Lista de Bans -->
            <?php if (empty($this->bans_data['bans'])): ?>
                <div class="no-results">
                    <p>No se encontraron bans con los filtros aplicados.</p>
                </div>
            <?php else: ?>
                <?php $this->renderBansList(); ?>
                
                <!-- Paginación -->
                <?php $this->renderPagination(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function renderBanForm() {
        // Pre-llenar IP si viene por parámetro
        $prefill_ip = $_GET['ip'] ?? ($_POST['ip_address'] ?? '');
        ?>
        <div class="section">
            <h3>Banear Nueva IP</h3>
            <form method="POST" class="ban-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ip_address">Dirección IP:</label>
                        <input type="text" id="ip_address" name="ip_address" required 
                               value="<?php echo htmlspecialchars($prefill_ip); ?>" 
                               placeholder="192.168.1.1">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duración (horas, vacío = permanente):</label>
                        <input type="number" id="duration" name="duration" min="1" 
                               value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>"
                               placeholder="24">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reason">Razón del ban:</label>
                    <textarea id="reason" name="reason" rows="3" 
                              placeholder="Spam, contenido inapropiado, etc."><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="ban_ip" class="btn btn-danger">Banear IP</button>
            </form>
        </div>
        <?php
    }
    
    private function renderFilters() {
        ?>
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status">Estado:</label>
                        <select id="status" name="status">
                            <option value="" <?php echo $this->current_filter['status'] === '' ? 'selected' : ''; ?>>Todos los activos</option>
                            <option value="active" <?php echo $this->current_filter['status'] === 'active' ? 'selected' : ''; ?>>Solo activos</option>
                            <option value="expired" <?php echo $this->current_filter['status'] === 'expired' ? 'selected' : ''; ?>>Expirados</option>
                            <option value="inactive" <?php echo $this->current_filter['status'] === 'inactive' ? 'selected' : ''; ?>>Desactivados</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="ip">IP:</label>
                        <input type="text" id="ip" name="ip" 
                               value="<?php echo htmlspecialchars($this->current_filter['ip']); ?>" 
                               placeholder="Buscar por IP...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="banned_by">Baneado por:</label>
                        <input type="text" id="banned_by" name="banned_by" 
                               value="<?php echo htmlspecialchars($this->current_filter['banned_by']); ?>" 
                               placeholder="Buscar por usuario...">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="bans.php" class="btn btn-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function renderBansList() {
        ?>
        <div class="section">
            <h3>Lista de Bans (<?php echo $this->bans_data['total']; ?> total)</h3>
            <div class="bans-table-container">
                <table class="bans-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>IP</th>
                            <th>Razón</th>
                            <th>Baneado por</th>
                            <th>Fecha</th>
                            <th>Expira</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->bans_data['bans'] as $ban): ?>
                            <tr class="ban-<?php echo $ban['status']; ?>">
                                <td><?php echo $ban['id']; ?></td>
                                <td class="ip-cell"><?php echo htmlspecialchars($ban['ip_address']); ?></td>
                                <td class="reason-cell">
                                    <?php if (!empty($ban['reason'])): ?>
                                        <?php echo htmlspecialchars(mb_substr($ban['reason'], 0, 50)); ?>
                                        <?php if (mb_strlen($ban['reason']) > 50): ?>...<?php endif; ?>
                                    <?php else: ?>
                                        <em>Sin razón especificada</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($ban['banned_by']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($ban['created_at'])); ?></td>
                                <td>
                                    <?php if ($ban['expires_at']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($ban['expires_at'])); ?>
                                    <?php else: ?>
                                        <strong>Permanente</strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_labels = [
                                        'active' => '<span class="status-badge status-active">Activo</span>',
                                        'expired' => '<span class="status-badge status-expired">Expirado</span>',
                                        'inactive' => '<span class="status-badge status-inactive">Desactivado</span>'
                                    ];
                                    echo $status_labels[$ban['status']] ?? 'Desconocido';
                                    ?>
                                </td>
                                <td class="actions">
                                    <?php if ($ban['status'] === 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="ban_id" value="<?php echo $ban['id']; ?>">
                                            <button type="submit" name="unban_ip" class="btn btn-small btn-success">Desbanear</button>
                                        </form>
                                        <button type="button" class="btn btn-small" onclick="editBan(<?php echo $ban['id']; ?>)">Editar</button>
                                    <?php elseif ($ban['status'] === 'expired'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="ban_id" value="<?php echo $ban['id']; ?>">
                                            <button type="submit" name="unban_ip" class="btn btn-small btn-secondary">Limpiar</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-small btn-secondary" onclick="showBanDetails(<?php echo $ban['id']; ?>)">Ver</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Modal para editar ban -->
        <div id="editBanModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Editar Ban</h3>
                <form method="POST" id="editBanForm">
                    <input type="hidden" id="edit_ban_id" name="ban_id">
                    
                    <div class="form-group">
                        <label>IP:</label>
                        <span id="edit_ban_ip" style="font-weight: bold;"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_reason">Razón:</label>
                        <textarea id="edit_reason" name="reason" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_duration">Nueva duración (horas desde ahora, vacío = permanente):</label>
                        <input type="number" id="edit_duration" name="duration" min="1">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="edit_ban" class="btn btn-primary">Actualizar Ban</button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditBanModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Modal para detalles del ban -->
        <div id="banDetailsModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Detalles del Ban</h3>
                <div id="banDetailsContent">
                    <!-- Se llenará con JavaScript -->
                </div>
            </div>
        </div>
        
        <script>
        // Datos de bans para JavaScript
        const bansData = <?php echo json_encode($this->bans_data['bans']); ?>;
        
        function editBan(banId) {
            const ban = bansData.find(b => b.id == banId);
            if (!ban) return;
            
            document.getElementById('edit_ban_id').value = ban.id;
            document.getElementById('edit_ban_ip').textContent = ban.ip_address;
            document.getElementById('edit_reason').value = ban.reason || '';
            document.getElementById('edit_duration').value = '';
            
            document.getElementById('editBanModal').style.display = 'block';
        }
        
        function closeEditBanModal() {
            document.getElementById('editBanModal').style.display = 'none';
        }
        
        function showBanDetails(banId) {
            const ban = bansData.find(b => b.id == banId);
            if (!ban) return;
            
            const content = document.getElementById('banDetailsContent');
            content.innerHTML = `
                <p><strong>ID:</strong> ${ban.id}</p>
                <p><strong>IP:</strong> ${ban.ip_address}</p>
                <p><strong>Razón:</strong> ${ban.reason || 'Sin razón especificada'}</p>
                <p><strong>Baneado por:</strong> ${ban.banned_by}</p>
                <p><strong>Fecha:</strong> ${new Date(ban.created_at).toLocaleString()}</p>
                <p><strong>Expira:</strong> ${ban.expires_at ? new Date(ban.expires_at).toLocaleString() : 'Permanente'}</p>
                <p><strong>Estado:</strong> ${ban.status}</p>
            `;
            
            document.getElementById('banDetailsModal').style.display = 'block';
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
        $data = $this->bans_data;
        
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
    $controller = new BansController();
    $view = new BansView($controller);
    $view->render();
} catch (Exception $e) {
    header('Location: login.php');
    exit;
}
?>