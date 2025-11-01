-- Tabla de tablones
CREATE TABLE boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    short_id VARCHAR(10) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL,
    is_nsfw BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    max_file_size INT DEFAULT 8388608,
    allowed_file_types TEXT DEFAULT 'jpg,jpeg,png,gif,webp',
    display_order INT DEFAULT 0,
    INDEX idx_display_order (display_order),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
);

-- Insertar los tablones
INSERT INTO boards (short_id, category, name, description) VALUES ('am', 'Cultura Japonesa', 'Anime y Manga', 'Discusión sobre anime y manga');
INSERT INTO boards (short_id, category, name, description) VALUES ('af', 'Cultura Japonesa', 'Anime/Fondos', 'Fondos de pantalla y wallpapers');
INSERT INTO boards (short_id, category, name, description) VALUES ('co', 'Cultura Japonesa', 'Cultura Otaku', 'Discusión sobre la cultura otaku');
INSERT INTO boards (short_id, category, name, description) VALUES ('dp', 'Cultura Japonesa', 'Diseño de Personajes', 'Discusión sobre el diseño de personajes');
INSERT INTO boards (short_id, category, name, description) VALUES ('cos', 'Cultura Japonesa', 'Cosplay', 'Discusión sobre cosplay');

INSERT INTO boards (short_id, category, name, description) VALUES ('g', 'Intereses', 'General', 'Discusión general');
INSERT INTO boards (short_id, category, name, description) VALUES ('cc', 'Intereses', 'Comics y Cartoons', 'Discusión comics y cartoons');
INSERT INTO boards (short_id, category, name, description) VALUES ('t', 'Intereses', 'Tecnología', 'Discusión sobre tecnología');
INSERT INTO boards (short_id, category, name, description) VALUES ('ps', 'Intereses', 'Películas y Series', 'Discusión sobre películas y series');
INSERT INTO boards (short_id, category, name, description) VALUES ('ar', 'Intereses', 'Armas', 'Discusión sobre armas y armamento');
INSERT INTO boards (short_id, category, name, description) VALUES ('au', 'Intereses', 'Autos', 'Discusión sobre autos');

INSERT INTO boards (short_id, category, name, description) VALUES ('o', 'Creativo', 'Oekaki', 'Discusión sobre arte oekaki digital');
INSERT INTO boards (short_id, category, name, description) VALUES ('fo', 'Creativo', 'Fotografía', 'Discusión sobre fotografías');
INSERT INTO boards (short_id, category, name, description) VALUES ('fg', 'Creativo', 'Fondos/General', 'Discusión sobre fondos en general');
INSERT INTO boards (short_id, category, name, description) VALUES ('cyc', 'Creativo', 'Comida y Cocina', 'Discusión sobre comida y cocina');
INSERT INTO boards (short_id, category, name, description) VALUES ('mu', 'Creativo', 'Música', 'Discusión sobre música');
INSERT INTO boards (short_id, category, name, description) VALUES ('dg', 'Creativo', 'Diseño Gráfico', 'Discusión sobre diseño gráfico');

INSERT INTO boards (short_id, category, name, description) VALUES ('r', 'Otros', 'Random', 'Discusión sobre cosas al azar');
INSERT INTO boards (short_id, category, name, description) VALUES ('ri', 'Otros', 'Random Internacional', 'Discusión sobre cosas al azar internacional');
INSERT INTO boards (short_id, category, name, description) VALUES ('p', 'Otros', 'Paranormal', 'Discusión sobre cosas paranormales');
INSERT INTO boards (short_id, category, name, description) VALUES ('pi', 'Otros', 'Políticamente Incorrecto', 'Discusión sobre cosas políticamente incorrectas');
INSERT INTO boards (short_id, category, name, description) VALUES ('lgbt', 'Otros', 'LGBT', 'Discusión LGBT');

INSERT INTO boards (short_id, category, name, description) VALUES ('ms', 'Adultos', 'Mujeres Sexys', 'Discusión sobre mujeres sexys');
INSERT INTO boards (short_id, category, name, description) VALUES ('hs', 'Adultos', 'Hombres Sexys', 'Discusión sobre hombres sexys');
INSERT INTO boards (short_id, category, name, description) VALUES ('h', 'Adultos', 'Hentai', 'Discusión sobre hentai');
INSERT INTO boards (short_id, category, name, description) VALUES ('e', 'Adultos', 'Ecchi', 'Discusión sobre ecchi');
INSERT INTO boards (short_id, category, name, description) VALUES ('ya', 'Adultos', 'Yaoi', 'Discusión sobre yaoi');
INSERT INTO boards (short_id, category, name, description) VALUES ('far', 'Adultos', 'Alta Resolución', 'Fotografías en alta resolución');

-- Actualizar tablones para marcar como NSFW
UPDATE boards SET is_nsfw = TRUE WHERE category IN ('Adultos', 'Otros');

-- Configurar orden de tablones y valores por defecto
UPDATE boards SET display_order = 1 WHERE short_id = 'am';
UPDATE boards SET display_order = 2 WHERE short_id = 'af';
UPDATE boards SET display_order = 3 WHERE short_id = 'co';
UPDATE boards SET display_order = 4 WHERE short_id = 'dp';
UPDATE boards SET display_order = 5 WHERE short_id = 'cos';
UPDATE boards SET display_order = 6 WHERE short_id = 'g';
UPDATE boards SET display_order = 7 WHERE short_id = 'cc';
UPDATE boards SET display_order = 8 WHERE short_id = 't';
UPDATE boards SET display_order = 9 WHERE short_id = 'ps';
UPDATE boards SET display_order = 10 WHERE short_id = 'ar';
UPDATE boards SET display_order = 11 WHERE short_id = 'au';
UPDATE boards SET display_order = 12 WHERE short_id = 'o';
UPDATE boards SET display_order = 13 WHERE short_id = 'fo';
UPDATE boards SET display_order = 14 WHERE short_id = 'fg';
UPDATE boards SET display_order = 15 WHERE short_id = 'cyc';
UPDATE boards SET display_order = 16 WHERE short_id = 'mu';
UPDATE boards SET display_order = 17 WHERE short_id = 'dg';
UPDATE boards SET display_order = 18 WHERE short_id = 'r';
UPDATE boards SET display_order = 19 WHERE short_id = 'ri';
UPDATE boards SET display_order = 20 WHERE short_id = 'p';
UPDATE boards SET display_order = 21 WHERE short_id = 'pi';
UPDATE boards SET display_order = 22 WHERE short_id = 'lgbt';
UPDATE boards SET display_order = 23 WHERE short_id = 'ms';
UPDATE boards SET display_order = 24 WHERE short_id = 'hs';
UPDATE boards SET display_order = 25 WHERE short_id = 'h';
UPDATE boards SET display_order = 26 WHERE short_id = 'e';
UPDATE boards SET display_order = 27 WHERE short_id = 'ya';
UPDATE boards SET display_order = 28 WHERE short_id = 'far';

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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    parent_id INT DEFAULT NULL,
    is_locked BOOLEAN DEFAULT FALSE,
    is_pinned BOOLEAN DEFAULT FALSE,
    board_id INT NOT NULL,
    image_size BIGINT NULL,
    image_dimensions VARCHAR(50) NULL,
    INDEX idx_parent_id (parent_id),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at),
    INDEX idx_board_id (board_id),
    FOREIGN KEY (parent_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE
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

-- Tabla de usuarios (admin y moderadores)
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

-- Insertar usuario admin por defecto
INSERT INTO users (username, password, role, is_active) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);
-- Contraseña por defecto: password (cambiar después del primer login)

-- Tabla de sesiones de usuarios
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

-- Tabla de sesiones de admin (mantener para compatibilidad)
CREATE TABLE admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at)
);

-- Tabla de configuraciones
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insertar configuraciones por defecto
INSERT INTO settings (setting_key, setting_value) VALUES 
('site_name', 'SimpleChan'),
('site_description', 'Un imageboard simple y funcional'),
('admin_email', ''),
('default_theme', 'default'),
('posts_per_page', '20'),
('enable_captcha', '0'),
('maintenance_mode', '0'),
('max_file_size', '8388608'),
('allowed_extensions', 'jpg,jpeg,png,gif,webp'),
('image_quality', '85'),
('max_image_width', '2000'),
('max_image_height', '2000'),
('create_thumbnails', '1'),
('thumbnail_size', '200'),
('max_posts_per_hour', '10'),
('ban_duration_default', '24'),
('auto_ban_reports', '10'),
('enable_ip_logging', '1'),
('enable_user_agents', '0'),
('blocked_words', ''),
('require_subject', '0'),
('min_message_length', '10');