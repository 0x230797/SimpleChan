<?php
/**
 * Login del Panel de Administración - SimpleChan
 */

require_once 'includes/Auth.php';

class LoginController {
    private $auth;
    private $messages = [];
    
    public function __construct() {
        $this->auth = new Auth();
        
        // Si ya está logueado, redirigir al panel
        if ($this->auth->isLoggedIn()) {
            $this->redirect('index.php');
        }
        
        $this->processLogin();
    }
    
    private function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $this->addError('Debe completar todos los campos.');
                return;
            }
            
            if ($this->auth->login($username, $password)) {
                $this->redirect('index.php');
            } else {
                $this->addError('Credenciales incorrectas.');
            }
        }
    }
    
    public function getMessages() {
        return $this->messages;
    }
    
    private function addError($message) {
        $this->messages['error'][] = $message;
    }
    
    private function redirect($url) {
        header("Location: $url");
        exit;
    }
}

class LoginView {
    private $messages;
    
    public function __construct($messages) {
        $this->messages = $messages;
    }
    
    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Iniciar Sesión - SimpleChan Admin</title>
            <link rel="stylesheet" href="../assets/css/style.css">
            <link rel="stylesheet" href="../assets/css/themes.css">
            <link rel="stylesheet" href="assets/css/admin.css">
            <link id="site-favicon" rel="shortcut icon" href="../assets/favicon/favicon.ico" type="image/x-icon">
        </head>
        <body class="login-page">
            <div class="login-container">
                <section class="form-login">
                    <div class="admin-login">
                        <h2>Panel de Administración</h2>
                        <form method="POST" action="login.php">
                            <div class="form-group">
                                <label for="name">Usuario:</label>
                                <input type="text" id="username" name="username" required  value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="subject">Contraseña:</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" name="login">Iniciar Sesión</button>
                            </div>
                        </form>
                        <div class="login-footer">
                            <a href="../index.php">← Volver al sitio</a>
                        </div>
                    </div>
                </section>
            </div>
            
            <script>
                // Enfocar el campo de usuario al cargar
                document.getElementById('username').focus();
                
                // Envío del formulario con Enter
                document.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.target.form?.submit();
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    private function renderMessages() {
        if (!empty($this->messages['error'])) {
            foreach ($this->messages['error'] as $error) {
                echo '<div class="message message-error">' . htmlspecialchars($error) . '</div>';
            }
        }
        
        if (!empty($this->messages['success'])) {
            foreach ($this->messages['success'] as $success) {
                echo '<div class="message message-success">' . htmlspecialchars($success) . '</div>';
            }
        }
    }
}

// Inicializar la aplicación
$controller = new LoginController();
$view = new LoginView($controller->getMessages());
$view->render();
?>