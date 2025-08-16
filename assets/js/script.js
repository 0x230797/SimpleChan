// Función mejorada para mostrar/ocultar formularios
function toggleCreateForm(type = 'post') {
    const formPost = document.getElementById('create-post');
    const buttonPost = document.getElementById('toggle-post');
    const formReply = document.getElementById('create-reply');
    const buttonReply = document.getElementById('toggle-reply');
    
    if (type === 'post') {
        if (formPost && buttonPost) {
            toggleSingleForm(formPost, buttonPost, 'Crear publicación', 'Cancelar', 'btn-cancel');
        }
        // Ocultar el formulario de respuesta si está visible y existe
        if (formReply && buttonReply) {
            hideSingleForm(formReply, buttonReply, 'Crear Respuesta', 'btn-cancel');
        }
    } else if (type === 'reply') {
        if (formReply && buttonReply) {
            toggleSingleForm(formReply, buttonReply, 'Crear Respuesta', 'Cancelar', 'btn-cancel');
        }
        // Ocultar el formulario de post si está visible y existe
        if (formPost && buttonPost) {
            hideSingleForm(formPost, buttonPost, 'Crear publicación', 'btn-cancel');
        }
    }
}

// Función auxiliar para alternar un formulario específico
function toggleSingleForm(form, button, showText, hideText, cancelClass) {
    if (!form || !button) return;
    
    const isHidden = form.style.display === 'none' || form.style.display === '';
    
    if (isHidden) {
        showSingleForm(form, button, hideText, cancelClass);
    } else {
        hideSingleForm(form, button, showText, cancelClass);
    }
}

// Función auxiliar para mostrar un formulario
function showSingleForm(form, button, hideText, cancelClass) {
    if (!form || !button) return;
    
    form.style.display = 'block';
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    button.textContent = hideText;
    button.classList.add(cancelClass);
    
    // Enfocar en el primer campo de entrada disponible
    setTimeout(() => {
        const nameInput = form.querySelector('input[name="name"]:not([readonly])') || 
                         form.querySelector('input[name="name"]') || 
                         form.querySelector('textarea[name="message"]');
        if (nameInput) nameInput.focus();
    }, 300);
}

// Función auxiliar para ocultar un formulario
function hideSingleForm(form, button, showText, cancelClass) {
    if (!form || !button) return;
    
    form.style.display = 'none';
    button.textContent = showText;
    button.classList.remove(cancelClass);
    
    // Limpiar formulario al ocultar
    const formElement = form.querySelector('form');
    if (formElement) {
        formElement.reset();
        
        // Actualizar estilos de los campos de nombre si es necesario
        const nameInputs = formElement.querySelectorAll('input[name="name"]:not([readonly])');
        nameInputs.forEach(input => {
            if (input.value.trim() === '') {
                input.classList.add('anonymous-style');
            }
        });
    }
}

// Funciones específicas que pueden ser llamadas desde los botones
function toggleCreatePost() {
    toggleCreateForm('post');
}

function toggleCreateReply() {
    toggleCreateForm('reply');
}

// Función para alternar el tamaño de las imágenes
function toggleImageSize(img) {
    img.classList.toggle('fullsize');
    var container = img.closest('.post-image');
    if (container) {
        container.classList.toggle('fullsize');
        // Agregar clase al contenedor del post para asegurar el ancho completo
        var post = img.closest('.post');
        if (post) {
            post.classList.toggle('fullsize');
        }
    }
}

// Función para mostrar/ocultar formulario de respuesta
function toggleReplyForm(postId) {
    const replyForm = document.getElementById('reply-form-' + postId);
    if (replyForm.style.display === 'none') {
        replyForm.style.display = 'block';
        replyForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Enfocar en el primer campo de texto (nombre)
        setTimeout(() => {
            const nameInput = replyForm.querySelector('input[name="name"]');
            if (nameInput) nameInput.focus();
        }, 300);
    } else {
        replyForm.style.display = 'none';
        
        // Limpiar formulario al ocultar
        const formElement = replyForm.querySelector('form');
        if (formElement) {
            formElement.reset();
            // Actualizar estilos de los campos de nombre
            const nameInputs = formElement.querySelectorAll('input[name="name"]');
            nameInputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.classList.add('anonymous-style');
                }
            });
        }
    }
}

// Función para confirmar eliminación de posts
function confirmDelete(postId) {
    return confirm('¿Estás seguro de que quieres eliminar este post?');
}

// Función para confirmar baneos
function confirmBan(ip) {
    return confirm('¿Estás seguro de que quieres banear la IP ' + ip + '?');
}

// Auto-refresh de la página cada 60 segundos (deshabilitado por defecto)
// Descomenta las siguientes líneas si quieres auto-refresh
// setInterval(() => {
//     window.location.reload();
// }, 60000);

// Función para validar formulario de post
function validatePost(form) {
    const message = form.querySelector('textarea[name="message"]').value.trim();
    if (message.length === 0) {
        alert('El mensaje no puede estar vacío.');
        return false;
    }
    
    if (message.length > 1000) {
        alert('El mensaje es demasiado largo. Máximo 1000 caracteres.');
        return false;
    }
    
    return true;
}

// Aplicar validación a todos los formularios de post
document.addEventListener('DOMContentLoaded', function() {
    // Menú de reportes desplegable
    window.toggleReportMenu = function(postId) {
        // Ocultar otros menús abiertos
        document.querySelectorAll('.report-menu').forEach(menu => {
            if (menu.id !== 'report-menu-' + postId) menu.style.display = 'none';
        });
        const menu = document.getElementById('report-menu-' + postId);
        if (menu) {
            menu.style.display = 'block';
        }
    };
    // Ocultar menú si se hace click fuera
    document.addEventListener('click', function(e) {
        // Si el click no es dentro del menú ni en el botón, ocultar todos los menús
        if (!e.target.classList.contains('btn-report') && !e.target.closest('.report-menu')) {
            document.querySelectorAll('.report-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    // Mostrar formulario si hay error
    const errorDiv = document.querySelector('.error');
    if (errorDiv) {
        // Determinar qué tipo de formulario mostrar basado en la página actual
        if (document.getElementById('create-post')) {
            toggleCreateForm('post');
        } else if (document.getElementById('create-reply')) {
            toggleCreateForm('reply');
        }
    }
    
    const postForms = document.querySelectorAll('form');
    
    postForms.forEach(form => {
        if (form.querySelector('textarea[name="message"]')) {
            form.addEventListener('submit', function(e) {
                if (!validatePost(this)) {
                    e.preventDefault();
                }
            });
        }
    });
    
    // Contador de caracteres para textarea
    const textareas = document.querySelectorAll('textarea[name="message"]');
    textareas.forEach(textarea => {
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.fontSize = '12px';
        counter.style.color = '#666';
        counter.style.textAlign = 'right';
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = 1000 - textarea.value.length;
            counter.textContent = remaining + ' caracteres restantes';
            
            if (remaining < 0) {
                counter.style.color = 'red';
            } else if (remaining < 100) {
                counter.style.color = 'orange';
            } else {
                counter.style.color = '#666';
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
    
    // Previsualización de imágenes
    const imageInputs = document.querySelectorAll('input[type="file"][name="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Verificar tamaño
                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo es demasiado grande. Máximo 5MB.');
                    this.value = '';
                    return;
                }
                
                // Verificar tipo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de archivo no permitido. Solo JPG, PNG, GIF y WebP.');
                    this.value = '';
                    return;
                }
                
                // Mostrar previsualización
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = input.parentNode.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        preview.style.marginTop = '10px';
                        input.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px; border: 1px solid #ccc;">';
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Manejar campos de nombre para mostrar "Anónimo" automáticamente
    const nameInputs = document.querySelectorAll('input[name="name"]');
    nameInputs.forEach(input => {
        // Función para actualizar el estilo visual
        function updateNameDisplay() {
            if (input.value.trim() === '') {
                input.classList.add('anonymous-style');
            } else {
                input.classList.remove('anonymous-style');
            }
        }
        
        // Eventos para manejar el comportamiento
        input.addEventListener('focus', function() {
            this.classList.remove('anonymous-style');
        });
        
        input.addEventListener('blur', function() {
            updateNameDisplay();
        });
        
        input.addEventListener('input', function() {
            updateNameDisplay();
        });
        
        // Aplicar estilo inicial
        updateNameDisplay();
        
        // Asegurar que el placeholder sea "Anónimo"
        input.placeholder = 'Anónimo';
        
        // Al enviar el formulario, si está vacío, establecer "Anónimo"
        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                if (input.value.trim() === '') {
                    input.value = 'Anónimo';
                }
            });
        }
    });
});

// Función para scroll suave a un post
function scrollToPost(postId) {
    const post = document.getElementById('post-' + postId);
    if (post) {
        post.scrollIntoView({ behavior: 'smooth', block: 'center' });
        post.style.backgroundColor = '#fff3cd';
        setTimeout(() => {
            post.style.backgroundColor = '';
        }, 2000);
    }
}

// Funciones para el panel de admin
function toggleAdminSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
    }
}

// Función para insertar automáticamente >>id en el textarea al hacer click en el número de post/respuesta.
function insertReference(id) {
    var textarea = document.querySelector('textarea[name="message"]');
    if (textarea) {
        var ref = '>>' + id + '\n';
        if (textarea.value.indexOf(ref) === -1) {
            textarea.value += ref;
            textarea.focus();
        }
    }
}

// Función para insertar formato en el textarea activo
function insertFormat(type, btn) {
    // Buscar el textarea más cercano al botón
    let textarea;
    if (btn) {
        // reply.php: buscar el textarea en el mismo form
        const form = btn.closest('form');
        textarea = form ? form.querySelector('textarea[name="message"]') : document.querySelector('textarea[name="message"]');
    } else {
        // index.php: solo hay un textarea principal
        textarea = document.getElementById('message') || document.querySelector('textarea[name="message"]');
    }
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    let selected = textarea.value.substring(start, end);
    let before = textarea.value.substring(0, start);
    let after = textarea.value.substring(end);
    let insertText = '';
    switch(type) {
        case 'bold':
            insertText = `**${selected || 'texto'}**`;
            break;
        case 'italic':
            insertText = `*${selected || 'texto'}*`;
            break;
        case 'strike':
            insertText = `~${selected || 'texto'}~`;
            break;
        case 'subline':
            insertText = `_${selected || 'texto'}_`;
            break;
        case 'spoiler':
            insertText = `[spoiler]${selected || 'texto'}[/spoiler]`;
            break;
        case 'h1':
            if (selected) {
                insertText = `<h1>${selected}</h1>`;
            } else {
                insertText = `<h1></h1>`;
                // Colocar el cursor entre las etiquetas
                textarea.value = before + insertText + after;
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = before.length + 4; // después de <h1>
                return;
            }
            break;
        case 'h2':
            if (selected) {
                insertText = `<h2>${selected}</h2>`;
            } else {
                insertText = `<h2></h2>`;
                textarea.value = before + insertText + after;
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = before.length + 4;
                return;
            }
            break;
        case 'color':
            let color = prompt('Color en formato CSS (ej: red, #ff0000):', '#d00');
            if (color) {
                insertText = `<span style="color:${color}">${selected || 'Texto de color'}</span>`;
            } else {
                insertText = selected;
            }
            break;
        case 'center':
            insertText = `<div style="text-align:center">${selected || 'Texto centrado'}</div>`;
            break;
    }
    textarea.value = before + insertText + after;
    // Reposicionar el cursor
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = before.length + insertText.length;
}

// Manejar referencias automáticas al cargar la página
window.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const ref = params.get('ref');
    if (ref) {
        var textarea = document.querySelector('textarea[name="message"]');
        if (textarea) {
            textarea.value = '>>' + ref + '\n';
            textarea.focus();
        }
    }
});

// Establece la dirección IP en el campo de entrada correspondiente y enfoca el campo.
// Además, realiza un desplazamiento suave para que el campo sea visible en la pantalla.
function setBanIp(ip) {
    var input = document.getElementById('ip_address');
     if (input) {
        input.value = ip;
        input.focus();
        window.scrollTo(0, input.getBoundingClientRect().top + window.scrollY - 100);
    }
}

function showSection(sectionId) {
    // Ocultar todas las secciones
    document.querySelectorAll('.admin-section').forEach(section => {
        section.style.display = 'none';
    });
    // Mostrar la sección seleccionada
    document.getElementById(sectionId).style.display = 'block';
}

// Función para cambiar el tema y guardar la configuración en localStorage
function changeTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('selectedTheme', theme);
    
    // Cambiar el logo y favicon según el tema
    changeThemeAssets(theme);
}

// Sistema de caché para imágenes
const ImageCache = {
    // Prefijo para las claves del localStorage
    prefix: 'simplechan_img_',
    
    // Versión del caché (incrementar cuando cambien las imágenes)
    version: '1.2',
    
    // Hash de las imágenes para detectar cambios
    imageHashes: {
        'assets/imgs/logo.png': Date.now(),
        'assets/imgs/logob.png': Date.now(),
        'assets/imgs/logod.png': Date.now(),
        'assets/imgs/blue.png': Date.now(),
        'assets/imgs/closed.png': Date.now(),
        'assets/imgs/dark.png': Date.now(),
        'assets/imgs/fade.png': Date.now(),
        'assets/imgs/filedeleted.png': Date.now(),
        'assets/imgs/sticky.png': Date.now()
    },
    
    // Lista de imágenes a cachear
    images: [
        'assets/imgs/logo.png',
        'assets/imgs/logob.png', 
        'assets/imgs/logod.png',
        'assets/imgs/blue.png',
        'assets/imgs/closed.png',
        'assets/imgs/dark.png',
        'assets/imgs/fade.png',
        'assets/imgs/filedeleted.png',
        'assets/imgs/sticky.png'
    ],
    
    // Lista de favicons para pre-cargar (no cachear como base64)
    favicons: [
        'assets/favicon/favicon.ico',
        'assets/favicon/faviconb.ico',
        'assets/favicon/favicond.ico'
    ],
    
    // Inicializar el sistema de caché
    init: function() {
        this.checkVersion();
        this.preloadImages();
    },
    
    // Verificar si la versión del caché es actual
    checkVersion: function() {
        const storedVersion = localStorage.getItem(this.prefix + 'version');
        if (storedVersion !== this.version) {
            this.clearCache();
            localStorage.setItem(this.prefix + 'version', this.version);
        }
    },
    
    // Limpiar caché anterior
    clearCache: function() {
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith(this.prefix)) {
                localStorage.removeItem(key);
            }
        });
    },
    
    // Pre-cargar todas las imágenes
    preloadImages: function() {
        this.images.forEach(imagePath => {
            this.cacheImage(imagePath);
        });
        
        // Pre-cargar favicons (sin cachear, solo para que estén en caché del navegador)
        this.favicons.forEach(faviconPath => {
            this.preloadFavicon(faviconPath);
        });
    },
    
    // Pre-cargar favicon en caché del navegador
    preloadFavicon: function(faviconPath) {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = faviconPath;
        document.head.appendChild(link);
    },
    
    // Cachear una imagen específica
    cacheImage: function(imagePath) {
        const cacheKey = this.prefix + imagePath.replace(/[^a-zA-Z0-9]/g, '_');
        
        // Si ya está cacheada, no hacer nada
        if (localStorage.getItem(cacheKey)) {
            return Promise.resolve();
        }
        
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = () => {
                try {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    
                    const dataURL = canvas.toDataURL('image/png', 0.8);
                    
                    // Verificar que no exceda el límite de localStorage (aprox 5MB)
                    if (dataURL.length < 500000) { // ~500KB por imagen para más imágenes
                        try {
                            localStorage.setItem(cacheKey, dataURL);
                            console.log(`Imagen cacheada: ${imagePath} (${(dataURL.length/1024).toFixed(1)}KB)`);
                        } catch (e) {
                            if (e.name === 'QuotaExceededError') {
                                console.warn('localStorage lleno, limpiando caché anterior...');
                                this.clearOldestCache();
                                try {
                                    localStorage.setItem(cacheKey, dataURL);
                                    console.log(`Imagen cacheada tras limpieza: ${imagePath}`);
                                } catch (e2) {
                                    console.warn('No se pudo cachear imagen:', imagePath);
                                }
                            }
                        }
                    } else {
                        console.warn(`Imagen demasiado grande para cachear: ${imagePath} (${(dataURL.length/1024).toFixed(1)}KB)`);
                    }
                    resolve();
                } catch (error) {
                    console.warn(`Error al cachear imagen: ${imagePath}`, error);
                    resolve(); // No fallar si no se puede cachear
                }
            };
            
            img.onerror = () => {
                console.warn(`Error al cargar imagen para cachear: ${imagePath}`);
                resolve(); // No fallar si no se puede cargar
            };
            
            img.src = imagePath;
        });
    },
    
    // Obtener imagen del caché
    getCachedImage: function(imagePath) {
        const cacheKey = this.prefix + imagePath.replace(/[^a-zA-Z0-9]/g, '_');
        return localStorage.getItem(cacheKey);
    },
    
    // Limpiar caché más antiguo cuando localStorage se llena
    clearOldestCache: function() {
        const keys = Object.keys(localStorage);
        const cacheKeys = keys.filter(key => key.startsWith(this.prefix) && key !== this.prefix + 'version');
        
        // Limpiar la mitad del caché más antiguo
        const keysToRemove = cacheKeys.slice(0, Math.floor(cacheKeys.length / 2));
        keysToRemove.forEach(key => {
            localStorage.removeItem(key);
        });
        
        console.log(`Limpiadas ${keysToRemove.length} imágenes del caché`);
    },
    
    // Función para usar imágenes cacheadas en elementos existentes
    applyCachedImages: function() {
        // Aplicar a imágenes de archivos eliminados
        const deletedImages = document.querySelectorAll('img[src*="filedeleted"]');
        deletedImages.forEach(img => {
            const cachedSrc = this.getCachedImage('assets/imgs/filedeleted.png');
            if (cachedSrc) {
                img.src = cachedSrc;
            }
        });
        
        // Aplicar a imágenes de posts pegajosos
        const stickyImages = document.querySelectorAll('img[src*="sticky"]');
        stickyImages.forEach(img => {
            const cachedSrc = this.getCachedImage('assets/imgs/sticky.png');
            if (cachedSrc) {
                img.src = cachedSrc;
            }
        });
        
        // Aplicar a imágenes de hilos cerrados
        const closedImages = document.querySelectorAll('img[src*="closed"]');
        closedImages.forEach(img => {
            const cachedSrc = this.getCachedImage('assets/imgs/closed.png');
            if (cachedSrc) {
                img.src = cachedSrc;
            }
        });
    },
    
    // Obtener información del caché
    getCacheInfo: function() {
        let totalSize = 0;
        let imageCount = 0;
        const keys = Object.keys(localStorage);
        
        keys.forEach(key => {
            if (key.startsWith(this.prefix) && key !== this.prefix + 'version') {
                const value = localStorage.getItem(key);
                if (value) {
                    totalSize += value.length;
                    imageCount++;
                }
            }
        });
        
        return {
            imageCount,
            totalSize,
            totalSizeMB: (totalSize / (1024 * 1024)).toFixed(2)
        };
    },
    
    // Limpiar caché manualmente
    clearCacheManual: function() {
        this.clearCache();
        console.log('Caché de imágenes limpiado manualmente');
        // Re-inicializar después de limpiar
        setTimeout(() => this.preloadImages(), 100);
    }
};

// Funciones de utilidad para desarrolladores
window.SimpleChanUtils = {
    // Verificar estado del caché
    checkCache: function() {
        const info = ImageCache.getCacheInfo();
        console.log('=== SimpleChan Image Cache Status ===');
        console.log(`Imágenes cacheadas: ${info.imageCount}`);
        console.log(`Tamaño total: ${info.totalSizeMB}MB`);
        console.log(`Versión del caché: ${ImageCache.version}`);
        return info;
    },
    
    // Limpiar y regenerar caché
    refreshCache: function() {
        console.log('Regenerando caché de imágenes...');
        ImageCache.clearCacheManual();
    },
    
    // Probar cambio de tema
    testTheme: function(theme) {
        console.log(`Probando tema: ${theme}`);
        changeTheme(theme);
    },
    
    // Aplicar imágenes cacheadas a nuevos elementos
    applyCachedImages: function() {
        ImageCache.applyCachedImages();
        console.log('Imágenes cacheadas aplicadas a elementos actuales');
    }
};

// Función global para usar imágenes cacheadas (útil para contenido dinámico)
window.useCachedImage = function(imagePath, fallbackPath) {
    const cachedImage = ImageCache.getCachedImage(imagePath);
    return cachedImage || fallbackPath || imagePath;
};

// Función para cambiar el logo y favicon según el tema
function changeThemeAssets(theme) {
    changeLogo(theme);
    changeFavicon(theme);
}

// Función para cambiar el logo según el tema (con caché)
function changeLogo(theme) {
    const logoImg = document.getElementById('site-logo') || document.querySelector('header img[alt="SimpleChan"]');
    if (logoImg) {
        let logoPath;
        
        switch(theme) {
            case 'yotsubab':
                logoPath = 'assets/imgs/logob.png';
                break;
            case 'dark':
                logoPath = 'assets/imgs/logod.png';
                break;
            default:
                logoPath = 'assets/imgs/logo.png';
                break;
        }
        
        // Intentar usar imagen cacheada primero
        const cachedImage = ImageCache.getCachedImage(logoPath);
        if (cachedImage) {
            logoImg.src = cachedImage;
        } else {
            // Usar ruta normal si no está cacheada
            const currentSrc = logoImg.src;
            const assetsPath = currentSrc.substring(0, currentSrc.lastIndexOf('/') + 1);
            logoImg.src = assetsPath + logoPath.split('/').pop();
        }
    }
}

// Función para cambiar el favicon según el tema
function changeFavicon(theme) {
    const faviconLink = document.getElementById('site-favicon') || document.querySelector('link[rel="shortcut icon"]') || document.querySelector('link[rel="icon"]');
    if (faviconLink) {
        // Detectar la ruta base de assets basándose en la ruta actual del favicon
        const currentHref = faviconLink.href;
        const assetsPath = currentHref.substring(0, currentHref.lastIndexOf('/') + 1);
        
        switch(theme) {
            case 'yotsubab':
                faviconLink.href = assetsPath + 'faviconb.ico';
                break;
            case 'dark':
                faviconLink.href = assetsPath + 'favicond.ico';
                break;
            default:
                faviconLink.href = assetsPath + 'favicon.ico';
                break;
        }
    }
}

// Aplicar el tema guardado al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar sistema de caché de imágenes
    ImageCache.init();
    
    const savedTheme = localStorage.getItem('selectedTheme') || 'yotsuba';
    document.documentElement.setAttribute('data-theme', savedTheme);
    const themeSelect = document.getElementById('theme-select');
    if (themeSelect) {
        themeSelect.value = savedTheme;
    }
    
    // Aplicar el logo y favicon correctos según el tema guardado
    changeThemeAssets(savedTheme);
    
    // Aplicar imágenes cacheadas a elementos existentes
    setTimeout(() => {
        ImageCache.applyCachedImages();
    }, 1000);
    
    // Hacer disponible el sistema de caché globalmente para debugging
    window.SimpleChanImageCache = ImageCache;
    
    // Log de información del caché (solo en desarrollo)
    setTimeout(() => {
        const cacheInfo = ImageCache.getCacheInfo();
        console.log(`SimpleChan Image Cache: ${cacheInfo.imageCount} imágenes, ${cacheInfo.totalSizeMB}MB`);
    }, 2000);
});