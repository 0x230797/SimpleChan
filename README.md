# SimpleChan - Imageboard Anónimo Simple

Un imageboard anónimo simple y seguro desarrollado con PHP, HTML, CSS y JavaScript.

## Características

- **Simple y minimalista**: Interfaz limpia sin elementos innecesarios
- **Anónimo**: Los usuarios pueden publicar sin registrarse
- **Subida de imágenes**: Soporte para JPG, PNG, GIF y WebP
- **Sistema de respuestas**: Los usuarios pueden responder a posts
- **Panel de administración**: Herramientas para moderación
- **Sistema de baneos**: Banear IPs temporalmente o permanentemente
- **Eliminación de posts**: Los administradores pueden eliminar contenido inapropiado

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, MySQL, GD (opcional para procesamiento de imágenes)

## Instalación

### 1. Configurar la base de datos

1. Crear una base de datos MySQL llamada `simplechan_db`
2. Ejecutar el script `database.sql` para crear las tablas necesarias:

```sql
mysql -u root -p simplechan_db < database.sql
```

### 2. Configurar la aplicación

1. Copiar todos los archivos al directorio web (ej: `htdocs` para XAMPP)
2. Editar `config.php` para configurar la conexión a la base de datos:
   - Cambiar `$host`, `$username`, `$password` según tu configuración
   - **IMPORTANTE**: Cambiar `ADMIN_PASSWORD` por una contraseña segura
3. Asegurarse de que el directorio `uploads/` tenga permisos de escritura

### 3. Configuración de permisos

```bash
chmod 755 uploads/
```

## Estructura de archivos

```
SimpleChan/
├── index.php          # Página principal del imageboard
├── admin.php          # Panel de administración
├── reglas.php         # Página de reglas y normas de la comunidad
├── ban.php            # Página de información de baneos
├── config.php         # Configuración de la base de datos
├── functions.php      # Funciones principales
├── style.css          # Estilos CSS minimalistas
├── script.js          # JavaScript para interactividad
├── database.sql       # Script de creación de la base de datos
├── uploads/           # Directorio para imágenes subidas
└── README.md          # Este archivo
```

## Uso

### Para usuarios normales:
1. Visitar `index.php`
2. Crear posts con texto y/o imágenes
3. Responder a posts existentes

### Para administradores:
1. Ir a `admin.php`
2. Iniciar sesión con la contraseña configurada
3. Acceder a herramientas de moderación:
   - Eliminar posts
   - Banear/desbanear IPs
   - Ver historial de posts y bans

## Seguridad

- **Validación de entrada**: Todos los datos de usuario son sanitizados
- **Protección contra subida de archivos maliciosos**: Solo se permiten imágenes
- **Sistema de sesiones para admin**: Tokens seguros con expiración
- **Protección contra inyección SQL**: Uso de prepared statements
- **Sistema de baneos**: Control de acceso por IP

## Configuración avanzada

### Cambiar límites de archivo

Editar en `config.php`:

```php
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
```

### Configurar límites de posts

Modificar en `functions.php` la función `get_posts()`:

```php
function get_posts($limit = 100) { // Mostrar más posts
```

## Mantenimiento

### Limpiar archivos huérfanos

Crear un script para eliminar imágenes de posts eliminados:

```php
// cleanup.php
require_once 'config.php';

// Obtener imágenes de posts eliminados
$stmt = $pdo->query("SELECT image_filename FROM posts WHERE is_deleted = 1 AND image_filename IS NOT NULL");
$files = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Eliminar archivos físicos
foreach ($files as $file) {
    $filepath = UPLOAD_DIR . $file;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Limpiar registros de la base de datos
$pdo->query("DELETE FROM posts WHERE is_deleted = 1");
```

### Backup de la base de datos

```bash
mysqldump -u root -p simplechan_db > backup_$(date +%Y%m%d).sql
```

## Licencia

Este proyecto es de dominio público. Puedes usarlo, modificarlo y distribuirlo libremente.

## Soporte

Para problemas o mejoras, consulta el código fuente. El diseño es intencionalmente simple para facilitar la comprensión y modificación.
