# Sistema de Manejo Global de Errores - SimpleChan

## Descripción

Se ha implementado un sistema robusto de manejo global de errores que redirige automáticamente a `404.php` cuando ocurre cualquier error en el sistema.

## Características Implementadas

### 1. Manejo de Errores PHP
- **Error Handler**: Captura todos los errores PHP (warnings, notices, fatal errors)
- **Exception Handler**: Captura excepciones no manejadas
- **Shutdown Handler**: Captura errores fatales que no pueden ser manejados por el error handler

### 2. Redirección Automática
- Todos los errores redirigen automáticamente a `404.php`
- Soporte para mensajes de error personalizados
- Prevención de redirecciones infinitas

### 3. Funciones de Seguridad
- `safe_database_operation()`: Ejecuta operaciones de BD con manejo de errores
- `safe_get_parameter()` / `safe_post_parameter()`: Validación segura de parámetros
- `safe_require()` / `safe_include()`: Carga segura de archivos
- `validate_file_access()`: Valida acceso a archivos

### 4. Logging de Errores
- Todos los errores se registran en `logs/error.log`
- Información detallada para debugging
- Timestamps y stack traces

### 5. Configuración de Apache (.htaccess)
- Redirección de errores HTTP (400, 401, 403, 404, 500, 502, 503) a `404.php`
- Protección de archivos sensibles
- Configuraciones de seguridad adicionales

## Archivos Modificados

### Archivos Principales
- `functions.php`: Nuevas funciones de manejo de errores
- `config.php`: Inicialización del sistema de errores y modo DEBUG
- `404.php`: Página de error mejorada con mensajes personalizados
- `.htaccess`: Configuración de redirección de errores HTTP

### Archivos de Aplicación
- `index.php`: Implementación de manejo seguro de errores
- `reply.php`: Validación segura de parámetros y operaciones de BD
- `admin.php`: Manejo de errores en panel administrativo
- `boards.php`: Carga segura de componentes
- `catalog.php`: Manejo de errores en catálogo
- `ban.php`: Carga segura de archivos críticos
- `admin_actions.php`: Verificación segura de permisos
- `reglas.php`: Carga segura de componentes

## Nuevas Funciones de Seguridad

### `initialize_global_error_handling()`
Inicializa todos los manejadores de errores globales.

### `redirect_to_error_page($message = null)`
Redirige a la página de error con un mensaje personalizado opcional.

### `safe_database_operation($callback, $error_message = "Error de base de datos")`
Ejecuta operaciones de base de datos de forma segura con manejo automático de errores.

### `safe_get_parameter($parameter, $type = 'string', $default = null)`
Valida y obtiene parámetros GET de forma segura.

### `safe_post_parameter($parameter, $type = 'string', $default = null)`
Valida y obtiene parámetros POST de forma segura.

### `safe_require($filepath)` / `safe_include($filepath)`
Carga archivos de forma segura con manejo de errores.

## Configuración

### Modo Debug
En `config.php` se puede activar/desactivar el modo debug:
```php
define('DEBUG_MODE', true); // Mostrar información adicional en errores
```

### Logging
Los logs se guardan automáticamente en:
- `logs/error.log`: Errores del sistema

## Uso y Ejemplos

### Operaciones de Base de Datos Seguras
```php
$posts = safe_database_operation(function() {
    return get_all_posts();
}, "Error al obtener posts");
```

### Validación de Parámetros
```php
$post_id = safe_get_parameter('post_id', 'int');
$name = safe_post_parameter('name', 'string', 'Anónimo');
```

### Carga Segura de Archivos
```php
safe_require('includes/Controller.php');
```

### Redirección Manual con Mensaje
```php
redirect_to_error_page("El recurso solicitado no está disponible");
```

## Archivo de Pruebas

Se ha creado `test_error.php` para probar el sistema de manejo de errores:
- Error fatal
- Excepción no capturada
- Error de base de datos
- Redirección personalizada
- Archivo no encontrado
- Parámetro inválido

**IMPORTANTE**: Eliminar `test_error.php` en producción.

## Beneficios

1. **Experiencia de Usuario Mejorada**: Los usuarios siempre ven una página de error amigable
2. **Seguridad**: Previene la exposición de información sensible del sistema
3. **Debugging**: Información detallada para desarrolladores (modo debug)
4. **Mantenibilidad**: Sistema centralizado de manejo de errores
5. **Robustez**: El sitio continúa funcionando incluso con errores internos

## Notas de Producción

1. Desactivar `DEBUG_MODE` en producción
2. Eliminar `test_error.php`
3. Configurar permisos adecuados en el directorio `logs/`
4. Revisar regularmente los logs de errores
5. Cambiar la contraseña de administrador por defecto

## Mantenimiento

- Revisar periódicamente `logs/error.log`
- Limpiar logs antiguos según sea necesario
- Actualizar mensajes de error según el contexto de la aplicación
