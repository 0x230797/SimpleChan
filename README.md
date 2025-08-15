# SimpleChan

SimpleChan es un imageboard anónimo simple. Este proyecto ha sido actualizado recientemente con nuevas funcionalidades para mejorar la experiencia del usuario y la administración.

## Nuevas Funcionalidades

### 1. Imagen Predeterminada para Publicaciones
- **Descripción**: Si una publicación o respuesta tenía una imagen asignada pero esta fue eliminada del servidor, ahora se muestra una imagen predeterminada (`filedeleted.gif`) ubicada en la carpeta `assets/imgs/`.
- **Archivos Afectados**:
  - `index.php`
  - `reply.php`
  - `admin.php`

### 2. Reportar Respuestas
- **Descripción**: Ahora es posible reportar respuestas individuales, además de las publicaciones principales.
- **Archivos Afectados**:
  - `index.php`
  - `reply.php`

### 3. Responder a Respuestas
- **Descripción**: Se agregó un botón "Responder" en cada respuesta que permite insertar automáticamente una referencia (`>>id`) en el campo de texto del formulario de respuesta.
- **Archivos Afectados**:
  - `reply.php`

### 4. Validación de `post_id`
- **Descripción**: Se agregó una validación para asegurarse de que el parámetro `post_id` sea un entero positivo antes de procesar cualquier acción.
- **Archivos Afectados**:
  - `reply.php`

### 5. Manejo de Referencias Automáticas
- **Descripción**: Al cargar la página con un parámetro `ref` en la URL, se inserta automáticamente una referencia (`>>id`) en el campo de texto del formulario de respuesta.
- **Archivos Afectados**:
  - `script.js`

## Cómo Usar
1. **Reportar Respuestas**:
   - Haz clic en el botón "Reportar" debajo de una respuesta.
   - Selecciona un motivo y envía el reporte.

2. **Responder a Respuestas**:
   - Haz clic en el botón "Responder" debajo de una respuesta para insertar automáticamente una referencia en el formulario de respuesta.

3. **Validación de `post_id`**:
   - Asegúrate de que el parámetro `post_id` en la URL sea válido para evitar redirecciones inesperadas.

4. **Manejo de Imágenes**:
   - Si una imagen es eliminada, asegúrate de que exista la imagen predeterminada `fade.png` en la carpeta `assets/imgs/`.

## Formatos de Texto

SimpleChan permite a los usuarios aplicar diferentes formatos de texto en sus publicaciones y respuestas. A continuación, se detallan los formatos disponibles:

- **Negrita**: Usa `**texto**` para aplicar negrita.
- **Cursiva**: Usa `*texto*` para aplicar cursiva.
- **Tachado**: Usa `~texto~` para tachar texto.
- **Subrayado**: Usa `_texto_` para subrayar.
- **Spoiler**: Usa `[spoiler]texto[/spoiler]` para ocultar texto como spoiler.
- **Título Grande (H1)**: Usa `<h1>texto</h1>` para un título grande.
- **Título Mediano (H2)**: Usa `<h2>texto</h2>` para un título mediano.
- **Texto de Color**: Usa `<span style="color:color">texto</span>` para aplicar color al texto (reemplaza `color` con un valor CSS válido, como `red` o `#ff0000`).
- **Texto Centrado**: Usa `<div style="text-align:center">texto</div>` para centrar el texto.

## Requisitos
- PHP 7.4 o superior
- Servidor web (por ejemplo, Apache)
- Base de datos MySQL

## Instalación
1. Clona este repositorio.
2. Configura la base de datos en el archivo `config.php`.
3. Asegúrate de que las carpetas `uploads/` y `assets/imgs/` tengan los permisos adecuados.
4. Coloca la imagen predeterminada `filedeleted.gif` en `assets/imgs/`.

## Créditos
Desarrollado por 0x230797.
