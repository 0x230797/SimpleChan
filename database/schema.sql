-- Tabla de posts
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) DEFAULT 'Anónimo',
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    image_filename VARCHAR(255),
    image_original_name VARCHAR(255),
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    parent_id INT DEFAULT NULL,
    is_locked BOOLEAN DEFAULT FALSE,
    is_pinned BOOLEAN DEFAULT FALSE,
    INDEX idx_parent_id (parent_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (parent_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Tabla de reportes
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    reason VARCHAR(100) NOT NULL,
    details TEXT,
    reporter_ip VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post_id (post_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Tabla de bans
CREATE TABLE bans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    reason TEXT,
    banned_by VARCHAR(50) DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_ip_address (ip_address),
    INDEX idx_expires_at (expires_at)
);

-- Tabla de sesiones de admin
CREATE TABLE admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
);
