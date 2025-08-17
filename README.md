# SimpleChan

SimpleChan es un proyecto de foro simple inspirado en los imageboards. Este proyecto permite a los usuarios interactuar mediante publicaciones y respuestas, mientras que los administradores tienen herramientas para moderar el contenido.

## Estructura del Proyecto

```
SimpleChan
├── assets/
│   ├── banners/
│   ├── css/
│   │   ├── admin.css
│   │   ├── style.css
│   │   └── themes.css
│   ├── favicon/
│   │   ├── favicon.ico
│   │   ├── faviconb.ico
│   │   └── favicond.ico
│   ├── imgs/
│   │   ├── blue.png
│   │   ├── closed.png
│   │   ├── dark.png
│   │   ├── fade.png
│   │   ├── filedeleted.png
│   │   ├── logo.png
│   │   ├── logob.png
│   │   ├── logod.png
│   │   └── sticky.png
│   └── js/
│       └── script.js
├── database/
│   └── schema.sql
├── uploads/
│   └── banners...
├── admin_actions.php
├── admin.php
├── ban.php
├── boards.php
├── catalog.php
├── config.php
├── functions.php
├── index.php
├── reglas.php
└── reply.php
```

## Configuración

1. **Servidor Web**: Este proyecto está diseñado para ejecutarse en un servidor local como XAMPP.
2. **Base de Datos**: El esquema de la base de datos se encuentra en `database/schema.sql`. Asegúrate de importar este archivo en tu servidor MySQL.
3. **Archivos Estáticos**: Los recursos como CSS, JavaScript e imágenes están organizados en la carpeta `assets/`.

## Descripción de Archivos Principales

- **index.php**: Página principal del foro.
- **admin.php**: Panel de administración.
- **boards.php**: Gestión de tableros.
- **reply.php**: Manejo de respuestas a publicaciones.
- **catalog.php**: Vista de catálogo de publicaciones.
- **config.php**: Configuraciones globales del proyecto.
- **functions.php**: Funciones reutilizables del proyecto.

## Funciones Disponibles

### Para Usuarios
- Navegar por los tableros y publicaciones.
- Crear nuevas publicaciones.
- Responder a publicaciones existentes.
- Ver el catálogo de publicaciones.
- Reportar publicaciones y respuestas.
- Consultar las reglas del foro.

### Para Administradores
- Acceso al panel de administración.
- Fijar y bloquear publicaciones.
- Banear usuarios.
- Eliminar publicaciones inapropiadas.
- Gestionar configuraciones globales del foro.

## Cómo Empezar

1. Clona este repositorio en tu servidor local.
2. Configura tu base de datos utilizando el archivo `database/schema.sql`.
3. Asegúrate de que los permisos de escritura estén habilitados para la carpeta `uploads/`.
4. Accede al proyecto desde tu navegador a través de `http://localhost/SimpleChan`.

## Créditos

Desarrollado por [0x230797](https://github.com/0x230797).

## Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo LICENSE para más detalles.