<?php
/**
 * Gestión de Usuarios - Panel de Administración SimpleChan
 */

require_once 'includes/AdminController.php';
require_once 'includes/AdminTemplate.php';

class UsersController extends AdminController {
    
    public function __construct() {
        parent::__construct();
        
        // Solo administradores pueden gestionar usuarios
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
        
        // Crear usuario
        if (isset($_POST['create_user'])) {
            $redirect = $this->createUser();
        }
        
        // Actualizar usuario
        if (isset($_POST['update_user'])) {
            $redirect = $this->updateUser();
        }
        
        // Desactivar usuario
        if (isset($_POST['deactivate_user'])) {
            $redirect = $this->deactivateUser();
        }
        
        // Activar usuario
        if (isset($_POST['activate_user'])) {
            $redirect = $this->activateUser();
        }
        
        if ($redirect) {
            $this->redirect('users.php');
        }
    }
    
    private function createUser() {
        $data = $this->cleanInput($_POST);
        
        // Validación
        $rules = [
            'username' => ['required' => true, 'min_length' => 3, 'max_length' => 50],
            'password' => ['required' => true, 'min_length' => 6],
            'email' => ['email' => true, 'max_length' => 255],
            'role' => ['required' => true]
        ];
        
        $errors = $this->validateInput($data, $rules);
        
        // Validar role
        if (!in_array($data['role'], ['admin', 'moderator'])) {
            $errors['role'] = 'Rol inválido.';
        }
        
        // Confirmar contraseña
        if ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Las contraseñas no coinciden.';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError($error);
            }
            return false;
        }
        
        // Crear usuario
        if ($this->auth->createUser($data, $this->current_user['id'])) {
            $this->addSuccess('Usuario creado correctamente.');
            $this->logActivity('create_user', "Usuario '{$data['username']}' creado con rol '{$data['role']}'");
            return true;
        } else {
            $this->addError('Error al crear el usuario. El nombre de usuario puede estar en uso.');
            return false;
        }
    }
    
    private function updateUser() {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $data = $this->cleanInput($_POST);
        
        if ($user_id <= 0) {
            $this->addError('ID de usuario inválido.');
            return false;
        }
        
        // Validación
        $rules = [
            'username' => ['required' => true, 'min_length' => 3, 'max_length' => 50],
            'email' => ['email' => true, 'max_length' => 255],
            'role' => ['required' => true]
        ];
        
        // Solo validar contraseña si se proporcionó
        if (!empty($data['password'])) {
            $rules['password'] = ['min_length' => 6];
            
            if ($data['password'] !== $data['confirm_password']) {
                $this->addError('Las contraseñas no coinciden.');
                return false;
            }
        }
        
        $errors = $this->validateInput($data, $rules);
        
        // Validar role
        if (!in_array($data['role'], ['admin', 'moderator'])) {
            $errors['role'] = 'Rol inválido.';
        }
        
        // No permitir que un usuario se quite el rol de admin si es el último
        $user = $this->auth->getUserById($user_id);
        if ($user['role'] === 'admin' && $data['role'] !== 'admin') {
            $pdo = $this->auth->pdo;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
            $stmt->execute([$user_id]);
            $admin_count = $stmt->fetchColumn();
            
            if ($admin_count < 1) {
                $errors['role'] = 'No puede quitar el rol de administrador al último administrador activo.';
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError($error);
            }
            return false;
        }
        
        // Actualizar usuario
        $update_data = [
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $data['role']
        ];
        
        if (!empty($data['password'])) {
            $update_data['password'] = $data['password'];
        }
        
        if ($this->auth->updateUser($user_id, $update_data)) {
            $this->addSuccess('Usuario actualizado correctamente.');
            $this->logActivity('update_user', "Usuario '{$data['username']}' actualizado");
            return true;
        } else {
            $this->addError('Error al actualizar el usuario.');
            return false;
        }
    }
    
    private function deactivateUser() {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id <= 0) {
            $this->addError('ID de usuario inválido.');
            return false;
        }
        
        // No permitir desactivar al usuario actual
        if ($user_id == $this->current_user['id']) {
            $this->addError('No puede desactivar su propia cuenta.');
            return false;
        }
        
        $user = $this->auth->getUserById($user_id);
        if (!$user) {
            $this->addError('Usuario no encontrado.');
            return false;
        }
        
        if ($this->auth->updateUser($user_id, ['is_active' => 0])) {
            $this->addSuccess('Usuario desactivado correctamente.');
            $this->logActivity('deactivate_user', "Usuario '{$user['username']}' desactivado");
            return true;
        } else {
            $this->addError('Error al desactivar el usuario.');
            return false;
        }
    }
    
    private function activateUser() {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id <= 0) {
            $this->addError('ID de usuario inválido.');
            return false;
        }
        
        $user = $this->auth->getUserById($user_id);
        if (!$user) {
            $this->addError('Usuario no encontrado.');
            return false;
        }
        
        if ($this->auth->updateUser($user_id, ['is_active' => 1])) {
            $this->addSuccess('Usuario activado correctamente.');
            $this->logActivity('activate_user', "Usuario '{$user['username']}' activado");
            return true;
        } else {
            $this->addError('Error al activar el usuario.');
            return false;
        }
    }
    
    public function getUsers() {
        return $this->auth->getAllUsers();
    }
    
    public function getUserById($user_id) {
        return $this->auth->getUserById($user_id);
    }
}

class UsersView {
    private $controller;
    private $users;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->users = $controller->getUsers();
    }
    
    public function render() {
        $user = $this->controller->getCurrentUser();
        
        AdminTemplate::renderHeader('Gestión de Usuarios', $user);
        ?>
        
        <div style="display: flex; gap: 20px; max-width: 1200px; margin: 20px auto; padding: 0 20px;">
            <?php AdminTemplate::renderSidebar('users', $user['role']); ?>
            
            <main style="flex: 1;">
                <?php AdminTemplate::renderMessages($this->controller->getMessages()); ?>
                <?php $this->renderUsersManagement(); ?>
            </main>
        </div>
        
        <?php AdminTemplate::renderFooter(); ?>
        <script>
        function editUser(userId) {
            <?php foreach ($this->users as $user_data): ?>
            if (userId === <?php echo $user_data['id']; ?>) {
                document.getElementById('edit_user_id').value = <?php echo $user_data['id']; ?>;
                document.getElementById('edit_username').value = '<?php echo htmlspecialchars($user_data['username']); ?>';
                document.getElementById('edit_email').value = '<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>';
                document.getElementById('edit_role').value = '<?php echo $user_data['role']; ?>';
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_confirm_password').value = '';
                
                document.getElementById('editUserModal').style.display = 'block';
                return;
            }
            <?php endforeach; ?>
        }
        
        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editUserModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.querySelector('.close');
            if (closeBtn) {
                closeBtn.onclick = closeEditModal;
            }
        });
        </script>
        <?php
    }
    
    // Header, sidebar y messages ahora se manejan en AdminTemplate
    
    private function renderUsersManagement() {
        ?>
        <!-- Crear Nuevo Usuario -->
        <div class="box-outer">
            <div class="boxbar">
                <h3>👥 Crear Nuevo Usuario</h3>
            </div>
            <div class="boxcontent">
                <form method="POST" style="display: grid; gap: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label for="username" style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre de Usuario:</label>
                            <input type="text" id="username" name="username" required 
                                   style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="email" style="display: block; margin-bottom: 5px; font-weight: bold;">Email (opcional):</label>
                            <input type="email" id="email" name="email" 
                                   style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">Contraseña:</label>
                            <input type="password" id="password" name="password" required
                                   style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                        </div>
                        <div>
                            <label for="confirm_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Confirmar Contraseña:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                        <div>
                            <label for="role" style="display: block; margin-bottom: 5px; font-weight: bold;">Rol:</label>
                            <select id="role" name="role" required
                                    style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                                <option value="">Seleccionar rol...</option>
                                <option value="moderator" <?php echo ($_POST['role'] ?? '') === 'moderator' ? 'selected' : ''; ?>>Moderador</option>
                                <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                        <div style="align-self: end;">
                            <input type="submit" name="create_user" value="Crear Usuario" 
                                   style="background: var(--primary-color); color: white; padding: 8px 16px; border: none; border-radius: 3px; cursor: pointer;">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de Usuarios -->
        <div class="box-outer" style="margin-top: 20px;">
            <div class="boxbar">
                <h3>📋 Usuarios Existentes</h3>
            </div>
            <div class="boxcontent">
                <?php if (empty($this->users)): ?>
                    <p>No hay usuarios registrados.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; border: var(--border-style);"
                            <thead>
                                <tr style="background: var(--boxbar-bg);">
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">ID</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Usuario</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Email</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Rol</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Estado</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Creado</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Último Login</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Creado por</th>
                                    <th style="padding: 10px; border: var(--border-style); font-weight: bold;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->users as $user): ?>
                                    <tr style="<?php echo !$user['is_active'] ? 'opacity: 0.6;' : ''; ?>">
                                        <td style="padding: 8px; border: var(--border-style);"><?php echo $user['id']; ?></td>
                                        <td style="padding: 8px; border: var(--border-style);">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['id'] == $this->controller->getCurrentUser()['id']): ?>
                                                <span style="color: var(--primary-color); font-weight: bold;">(Tú)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 8px; border: var(--border-style);"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                        <td style="padding: 8px; border: var(--border-style);">
                                            <span style="background: var(--primary-color); color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 8px; border: var(--border-style);">
                                            <span style="background: <?php echo $user['is_active'] ? 'var(--success-color)' : 'var(--danger-color)'; ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                                <?php echo $user['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 8px; border: var(--border-style); font-size: 12px;"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td style="padding: 8px; border: var(--border-style); font-size: 12px;">
                                            <?php 
                                            if ($user['last_login']) {
                                                echo date('d/m/Y H:i', strtotime($user['last_login']));
                                            } else {
                                                echo 'Nunca';
                                            }
                                            ?>
                                        </td>
                                        <td style="padding: 8px; border: var(--border-style); font-size: 12px;"><?php echo htmlspecialchars($user['created_by_username'] ?? 'Sistema'); ?></td>
                                        <td style="padding: 8px; border: var(--border-style);"
                                            <?php if ($user['id'] != $this->controller->getCurrentUser()['id']): ?>
                                                <button type="button" onclick="editUser(<?php echo $user['id']; ?>)"
                                                        style="background: var(--primary-color); color: white; padding: 4px 8px; border: none; border-radius: 3px; cursor: pointer; margin-right: 5px; font-size: 12px;">Editar</button>
                                                
                                                <?php if ($user['is_active']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="deactivate_user" 
                                                                style="background: var(--danger-color); color: white; padding: 4px 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;"
                                                                onclick="return confirm('¿Desactivar este usuario?')">Desactivar</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="activate_user" 
                                                                style="background: var(--success-color); color: white; padding: 4px 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Activar</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button type="button" onclick="editUser(<?php echo $user['id']; ?>)"
                                                        style="background: var(--primary-color); color: white; padding: 4px 8px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Editar Perfil</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal para editar usuario -->
        <div id="editUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px;">
                <div class="box-outer">
                    <div class="boxbar">
                        <h3>✏️ Editar Usuario</h3>
                        <span class="close" style="float: right; cursor: pointer; font-size: 18px;">&times;</span>
                    </div>
                    <div class="boxcontent">
                        <form method="POST" id="editUserForm" style="display: grid; gap: 15px;">
                            <input type="hidden" id="edit_user_id" name="user_id">
                            
                            <div>
                                <label for="edit_username" style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre de Usuario:</label>
                                <input type="text" id="edit_username" name="username" required
                                       style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                            </div>
                            
                            <div>
                                <label for="edit_email" style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
                                <input type="email" id="edit_email" name="email"
                                       style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                            </div>
                            
                            <div>
                                <label for="edit_role" style="display: block; margin-bottom: 5px; font-weight: bold;">Rol:</label>
                                <select id="edit_role" name="role" required
                                        style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                                    <option value="moderator">Moderador</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Nueva Contraseña (dejar vacío para no cambiar):</label>
                                <input type="password" id="edit_password" name="password"
                                       style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                            </div>
                            
                            <div>
                                <label for="edit_confirm_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Confirmar Nueva Contraseña:</label>
                                <input type="password" id="edit_confirm_password" name="confirm_password"
                                       style="width: 100%; padding: 8px; border: var(--border-style); border-radius: 3px;">
                            </div>
                            
                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" onclick="closeEditModal()" 
                                        style="background: var(--border-color); color: var(--text-color); padding: 8px 16px; border: none; border-radius: 3px; cursor: pointer;">Cancelar</button>
                                <button type="submit" name="update_user" 
                                        style="background: var(--primary-color); color: white; padding: 8px 16px; border: none; border-radius: 3px; cursor: pointer;">Actualizar Usuario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function editUser(userId) {
            // Aquí cargarías los datos del usuario via AJAX
            // Por simplicidad, abrir el modal y rellenar manualmente
            
            <?php foreach ($this->users as $user): ?>
            if (userId === <?php echo $user['id']; ?>) {
                document.getElementById('edit_user_id').value = <?php echo $user['id']; ?>;
                document.getElementById('edit_username').value = '<?php echo htmlspecialchars($user['username']); ?>';
                document.getElementById('edit_email').value = '<?php echo htmlspecialchars($user['email'] ?? ''); ?>';
                document.getElementById('edit_role').value = '<?php echo $user['role']; ?>';
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_confirm_password').value = '';
                
                document.getElementById('editUserModal').style.display = 'block';
                return;
            }
            <?php endforeach; ?>
        }
        
        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('editUserModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
        
        // Cerrar modal con el botón X
        document.querySelector('.close').onclick = closeEditModal;
        </script>
        <?php
    }
    
    // Footer removido - se maneja en AdminTemplate
}

// Inicializar la aplicación
try {
    $controller = new UsersController();
    $view = new UsersView($controller);
    $view->render();
} catch (Exception $e) {
    header('Location: login.php');
    exit;
}
?>