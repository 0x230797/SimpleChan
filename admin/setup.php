<?php
/**
 * Script de verificación y configuración inicial del sistema de administración
 */

require_once '../config.php';

try {
    // Usar la función de configuración para crear PDO
    $pdo = initializeDatabase();
    
    echo "<h2>Verificación del Sistema de Administración</h2>";
    
    // Verificar si existe la tabla users
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>❌ La tabla 'users' no existe.</p>";
        echo "<p>Ejecute el archivo schema.sql para crear las tablas necesarias.</p>";
        
        // Crear la tabla automáticamente
        echo "<p>Creando tabla 'users'...</p>";
        
        $sql = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            role ENUM('admin', 'moderator') NOT NULL DEFAULT 'moderator',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            created_by INT NULL,
            INDEX idx_username (username),
            INDEX idx_role (role),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        );
        ";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Tabla 'users' creada.</p>";
        
        // Insertar usuario admin por defecto
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, is_active) 
            VALUES (?, ?, 'admin', TRUE)
        ");
        
        $admin_password = password_hash('password', PASSWORD_DEFAULT);
        $stmt->execute(['admin', $admin_password]);
        
        echo "<p style='color: green;'>✅ Usuario administrador creado (usuario: admin, contraseña: password).</p>";
    } else {
        echo "<p style='color: green;'>✅ La tabla 'users' existe.</p>";
        
        // Verificar si existe el usuario admin
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
        $stmt->execute();
        $admin_count = $stmt->fetchColumn();
        
        if ($admin_count == 0) {
            echo "<p style='color: orange;'>⚠️ No hay usuarios administradores activos.</p>";
            echo "<p>Creando usuario administrador por defecto...</p>";
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, role, is_active) 
                VALUES (?, ?, 'admin', TRUE)
            ");
            
            $admin_password = password_hash('password', PASSWORD_DEFAULT);
            $stmt->execute(['admin', $admin_password]);
            
            echo "<p style='color: green;'>✅ Usuario administrador creado (usuario: admin, contraseña: password).</p>";
        } else {
            echo "<p style='color: green;'>✅ Usuario(s) administrador(es) encontrado(s): $admin_count</p>";
        }
    }
    
    // Verificar tabla user_sessions
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_sessions'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "<p>Creando tabla 'user_sessions'...</p>";
        
        $sql = "
        CREATE TABLE user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL UNIQUE,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_session_token (session_token),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        ";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Tabla 'user_sessions' creada.</p>";
    } else {
        echo "<p style='color: green;'>✅ La tabla 'user_sessions' existe.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Sistema listo para usar</h3>";
    echo "<p><a href='login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Ir al Panel de Administración</a></p>";
    echo "<p><strong>Credenciales por defecto:</strong><br>";
    echo "Usuario: admin<br>";
    echo "Contraseña: password</p>";
    echo "<p style='color: red;'><strong>¡IMPORTANTE!</strong> Cambie la contraseña después del primer login.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Verifique la configuración de la base de datos en config.php</p>";
}
?>