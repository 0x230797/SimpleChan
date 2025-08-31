# SimpleChan

SimpleChan es un proyecto de foro simple inspirado en los imageboards. Este proyecto permite a los usuarios interactuar mediante publicaciones y respuestas, mientras que el administrador tienen herramientas para moderar el contenido.

## Estructura del Proyecto

```
SimpleChan
├── assets/
│   ├── banners/
│   │   ├── banner-01.gif
│   │   ├── banner-02.gif
│   │   └── banner-03.jpg
│   │
│   ├── css/
│   │   ├── admin.css
│   │   ├── style.css
│   │   └── themes.css
│   │
│   ├── favicon/
│   │   ├── favicon.ico
│   │   ├── favicon.psd
│   │   ├── faviconb.ico
│   │   ├── favicond.ico
│   │   ├── faviconf.ico
│   │   └── favicong.ico
│   │
│   ├── imgs/
│   │   ├── blue.png
│   │   ├── closed.png
│   │   ├── dark.png
│   │   ├── fade.png
│   │   ├── filedeleted.png
│   │   ├── girls.png
│   │   ├── logo.png
│   │   ├── logob.png
│   │   ├── logod.png
│   │   ├── logof.png
│   │   ├── logog.png
│   │   └── sticky.png
│   │
│   ├── js/
│   │   └── script.js
│   │
│   └── psd/
│       ├── banner.psd
│       ├── favicon.psd
│       └── logo-template.psd
│
├── database/
│   └── schema.sql
│
├── incluides/
│   ├── BaseView.php
│   ├── BoardController.php
│   ├── BoardView.php
│   ├── FormRenderer.php
│   ├── PostRenderer.php
│   ├── ReplyController.php
│   └── ReplyView.php
│
├── uploads/
│   └── imagens.(.jpg, .jpeg, .png, .gif, .webp)
│
├── .htaccess
├── 404.php
├── admin_actions.php
├── admin.php
├── ban.php
├── boards.php
├── catalog.php
├── config.php
├── functions.php
├── index.php
├── README.md (Tú estás aquí)
├── reglas.php
├── reply.php
└── urlout.php
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

#### **Navegación y Exploración**
- Navegar por la página principal con publicaciones recientes y populares
- Explorar tableros organizados por categorías (con indicadores NSFW)
- Ver catálogo de publicaciones por tablero con diferentes ordenamientos:
  - Por actividad (bump order)
  - Por fecha de creación  
  - Por número de respuestas
- Navegar entre páginas con paginación automática
- Usar enlaces de navegación rápida (Subir/Bajar)
- Acceder a páginas de error (404) personalizadas

#### **Creación de Contenido**
- Crear nuevas publicaciones con:
  - Campo de nombre (opcional, por defecto "Anónimo")
  - Asunto (obligatorio para posts principales)
  - Mensaje con formato de texto enriquecido
  - Subida de imágenes (JPG, JPEG, PNG, GIF, WEBP - máximo 5MB)
- Responder a publicaciones existentes
- Usar botones de formato de texto:
  - **Negrita** (`**texto**`)
  - *Cursiva* (`*texto*`)
  - ~~Tachado~~ (`~texto~`)
  - Subrayado (`_texto_`)
  - Spoiler (`[spoiler]texto[/spoiler]`)

#### **Interacción Social**
- Responder rápidamente usando botones "Responder" 
- Sistema completo de referencias cruzadas entre tablones:
  - **Referencias locales**: `>>ID` para referenciar posts del mismo tablón
  - **Referencias cruzadas**: `>>/tablón/ID` para referenciar posts de otros tablones
  - **Enlaces a tablones**: `>>/tablón/` para enlazar directamente a un tablón
  - Validación automática de enlaces (muestra enlaces "muertos" si no existen)
  - Colores distintivos: azul para locales, rojo para cruzadas, gris para enlaces muertos
- Ver lista de respuestas en headers de posts (muestra todos los >>ID que referencian el post)
- Citar automáticamente posts al hacer clic en números
- Interactuar en hilos de conversación
- Navegación fluida entre tablones mediante referencias

#### **Búsqueda y Descubrimiento**
- Buscar publicaciones por texto dentro de tableros específicos
- Buscar imágenes usando búsqueda inversa en Bing (botón [S])
- Filtrar y limpiar resultados de búsqueda
- Explorar publicaciones populares en la página principal

#### **Personalización**
- Cambiar entre 5 temas visuales:
  - Yotsuba (clásico)
  - Yotsuba Blue 
  - Futaba
  - Girls
  - Dark
- Temas se guardan automáticamente en el navegador
- Logos y favicons cambian según el tema seleccionado

#### **Funciones Interactivas**
- Auto-refresh automático cada 15 segundos con:
  - Checkbox para activar/desactivar
  - Contador visual regresivo
  - Estado guardado en localStorage
- Expandir/contraer imágenes haciendo clic
- Formularios emergentes (mostrar/ocultar)

#### **Reportes y Moderación**
- Reportar publicaciones y respuestas inapropiadas con:
  - Menú desplegable con motivos predefinidos (Spam, Contenido ilegal, Acoso, Otro)
  - Campo opcional para detalles adicionales
  - Envío directo al administrador

#### **Enlaces y Redirección**
- Salida segura a enlaces externos con:
  - Página de advertencia previa
  - Contador regresivo de 5 segundos
  - Opción de continuar inmediatamente o cancelar
  - Protección contra dominios maliciosos

#### **Información y Ayuda**
- Consultar reglas detalladas del foro con:
  - Reglas generales de comportamiento
  - Guías de formato de texto
  - Ejemplos de uso correcto
- Ver estadísticas del sitio
- Acceder a información de contacto

#### **Funciones Técnicas**
- URLs amigables para compartir
- Carga optimizada de imágenes con fallbacks
- Validación en tiempo real de formularios
- Manejo de errores con mensajes informativos
- Soporte para dispositivos móviles (responsive)
- Protección contra usuarios baneados con página dedicada
- Limpieza automática de parámetros de éxito en URLs (evita mensajes repetidos al recargar)
- Sistema de referencias cruzadas con validación en tiempo real

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