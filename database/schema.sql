-- Tabla de tablones
CREATE TABLE boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    short_id VARCHAR(10) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL,
    is_nsfw BOOLEAN DEFAULT FALSE
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