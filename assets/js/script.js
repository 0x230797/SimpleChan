/**
 * SimpleChan - Client-side JavaScript
 * Funcionalidades principales del foro
 */

/**
 * Gestión de URL y parámetros
 */
const URLManager = {
    /**
     * Cambia el criterio de ordenamiento en el catálogo
     * @param {string} orderBy - Criterio de ordenamiento
     */
    changeOrderBy(orderBy) {
        const urlParams = new URLSearchParams(window.location.search);
        const currentBoard = urlParams.get('board');
        
        urlParams.set('order_by', orderBy);
        
        if (currentBoard) {
            urlParams.set('board', currentBoard);
        }
        
        window.location.search = urlParams.toString();
    },

    /**
     * Scroll suave a un post específico
     * @param {string|number} postId - ID del post
     */
    scrollToPost(postId) {
        const post = document.getElementById('post-' + postId);
        if (post) {
            post.scrollIntoView({ behavior: 'smooth', block: 'center' });
            post.style.backgroundColor = '#fff3cd';
            setTimeout(() => {
                post.style.backgroundColor = '';
            }, 2000);
        }
    },

    /**
     * Inserta referencia automática al cargar la página
     */
    handleAutoReference() {
        const params = new URLSearchParams(window.location.search);
        const ref = params.get('ref');
        if (ref) {
            const textarea = document.querySelector('textarea[name="message"]');
            if (textarea) {
                textarea.value = '>>' + ref + '\n';
                textarea.focus();
            }
        }
    }
};

// Función global para compatibilidad
function changeBy(orderBy) {
    URLManager.changeOrderBy(orderBy);
}

/**
 * Gestión de formularios
 */
const FormManager = {
    /**
     * Muestra/oculta formularios de creación
     * @param {string} type - Tipo de formulario ('post' o 'reply')
     */
    toggleCreateForm(type = 'post') {
        const formPost = document.getElementById('create-post');
        const buttonPost = document.getElementById('toggle-post');
        const formReply = document.getElementById('create-reply');
        const buttonReply = document.getElementById('toggle-reply');
        
        if (type === 'post') {
            if (formPost && buttonPost) {
                this.toggleSingleForm(formPost, buttonPost, 'Crear publicación', 'Cancelar', 'btn-cancel');
            }
            if (formReply && buttonReply) {
                this.hideSingleForm(formReply, buttonReply, 'Crear Respuesta', 'btn-cancel');
            }
        } else if (type === 'reply') {
            if (formReply && buttonReply) {
                this.toggleSingleForm(formReply, buttonReply, 'Crear Respuesta', 'Cancelar', 'btn-cancel');
            }
            if (formPost && buttonPost) {
                this.hideSingleForm(formPost, buttonPost, 'Crear publicación', 'btn-cancel');
            }
        }
    },

    /**
     * Alterna la visibilidad de un formulario específico
     */
    toggleSingleForm(form, button, showText, hideText, cancelClass) {
        if (!form || !button) return;
        
        const isHidden = form.style.display === 'none' || form.style.display === '';
        
        if (isHidden) {
            this.showSingleForm(form, button, hideText, cancelClass);
        } else {
            this.hideSingleForm(form, button, showText, cancelClass);
        }
    },

    /**
     * Muestra un formulario
     */
    showSingleForm(form, button, hideText, cancelClass) {
        if (!form || !button) return;
        
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        button.textContent = hideText;
        button.classList.add(cancelClass);
        
        setTimeout(() => {
            const nameInput = form.querySelector('input[name="name"]:not([readonly])') || 
                             form.querySelector('input[name="name"]') || 
                             form.querySelector('textarea[name="message"]');
            if (nameInput) nameInput.focus();
        }, 300);
    },

    /**
     * Oculta un formulario
     */
    hideSingleForm(form, button, showText, cancelClass) {
        if (!form || !button) return;
        
        form.style.display = 'none';
        button.textContent = showText;
        button.classList.remove(cancelClass);
        
        const formElement = form.querySelector('form');
        if (formElement) {
            formElement.reset();
            
            const nameInputs = formElement.querySelectorAll('input[name="name"]:not([readonly])');
            nameInputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.classList.add('anonymous-style');
                }
            });
        }
    },

    /**
     * Muestra/oculta formulario de respuesta a un post específico
     * @param {string|number} postId - ID del post
     */
    toggleReplyForm(postId) {
        const replyForm = document.getElementById('reply-form-' + postId);
        if (!replyForm) return;

        if (replyForm.style.display === 'none') {
            replyForm.style.display = 'block';
            replyForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            setTimeout(() => {
                const nameInput = replyForm.querySelector('input[name="name"]');
                if (nameInput) nameInput.focus();
            }, 300);
        } else {
            replyForm.style.display = 'none';
            
            const formElement = replyForm.querySelector('form');
            if (formElement) {
                formElement.reset();
                const nameInputs = formElement.querySelectorAll('input[name="name"]');
                nameInputs.forEach(input => {
                    if (input.value.trim() === '') {
                        input.classList.add('anonymous-style');
                    }
                });
            }
        }
    },

    /**
     * Valida el formulario de post
     * @param {HTMLFormElement} form - Formulario a validar
     * @returns {boolean} - True si es válido
     */
    validatePost(form) {
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
};

// Funciones globales para compatibilidad
function toggleCreateForm(type) {
    FormManager.toggleCreateForm(type);
}

function toggleCreatePost() {
    FormManager.toggleCreateForm('post');
}

function toggleCreateReply() {
    FormManager.toggleCreateForm('reply');
}

function toggleReplyForm(postId) {
    FormManager.toggleReplyForm(postId);
}

function validatePost(form) {
    return FormManager.validatePost(form);
}

/**
 * Gestión de imágenes e interacciones (versión mejorada)
 */
const MediaManager = {
    /**
     * Alterna el tamaño de las imágenes con animación suave
     * @param {HTMLImageElement} img - Elemento de imagen
     */
    toggleImageSize(img) {
        img.classList.toggle('fullsize');
        
        const container = img.closest('.post-image');
        if (container) {
            container.classList.toggle('fullsize');
        }
    },

    /**
     * Inserta referencia en el textarea activo con mejor manejo de posición
     * @param {string|number} id - ID del post a referenciar
     */
    insertReference(id) {
        const textarea = document.querySelector('textarea[name="message"]:focus') || 
                        document.querySelector('textarea[name="message"]');
        if (!textarea) return;

        const ref = `>>${id}`;
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);

        // Insertar con espacio si es necesario
        const insertText = (textBefore.endsWith(' ') || textBefore === '') ? ref : ` ${ref}`;
        
        textarea.value = textBefore + insertText + textAfter;
        
        // Posicionar cursor después de la referencia
        const newCursorPos = cursorPos + insertText.length;
        textarea.selectionStart = newCursorPos;
        textarea.selectionEnd = newCursorPos;
        textarea.focus();
        
        // Desplazar el textarea si es necesario
        textarea.scrollTop = textarea.scrollHeight;
    },

    /**
     * Inserta formato de texto con validación de permisos
     * @param {string} type - Tipo de formato
     * @param {HTMLElement} btn - Botón que activó la función
     * @param {boolean} isAdmin - Si el usuario es administrador
     */
    insertFormat(type, btn, isAdmin = false) {
        const form = btn?.closest('form');
        const textarea = form?.querySelector('textarea[name="message"]:focus') || 
                        document.querySelector('textarea[name="message"]:focus') ||
                        form?.querySelector('textarea[name="message"]') || 
                        document.querySelector('textarea[name="message"]');
        
        if (!textarea) return;

        const { selectionStart: start, selectionEnd: end, value } = textarea;
        const selectedText = value.substring(start, end) || "texto";
        const beforeText = value.substring(0, start);
        const afterText = value.substring(end);

        // Formatos disponibles para todos los usuarios
        const userFormats = {
            bold: `**${selectedText}**`,
            italic: `*${selectedText}*`,
            strike: `~${selectedText}~`,
            underline: `_${selectedText}_`,
            spoiler: `[spoiler]${selectedText}[/spoiler]`
        };

        // Formatos solo para administradores
        const adminFormats = {
            h1: `<h1>${selectedText}</h1>`,
            h2: `<h2>${selectedText}</h2>`,
            color: (color) => `<span style="color:${color}">${selectedText}</span>`,
            center: `<div style="text-align:center">${selectedText}</div>`
        };

        let formattedText = '';

        if (userFormats[type]) {
            formattedText = userFormats[type];
        } else if (isAdmin && adminFormats[type]) {
            if (type === 'color') {
                // Mejor UI para selección de color
                const colorPicker = document.createElement('input');
                colorPicker.type = 'color';
                colorPicker.value = '#ff0000';
                
                colorPicker.addEventListener('change', (e) => {
                    formattedText = adminFormats.color(e.target.value);
                    updateTextarea();
                });
                
                colorPicker.click(); // Abre el selector de color nativo
            } else {
                formattedText = adminFormats[type];
            }
        } else {
            console.warn(`Formato no permitido: ${type}`);
            return;
        }

        const updateTextarea = () => {
            textarea.value = beforeText + formattedText + afterText;
            textarea.focus();
            
            // Posicionar cursor correctamente
            if (type !== 'color') { // El color picker ya maneja su propio focus
                const newStart = start + formattedText.indexOf(selectedText);
                textarea.selectionStart = newStart;
                textarea.selectionEnd = newStart + selectedText.length;
            }
            
            // Disparar evento de cambio
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        };

        if (type !== 'color') {
            updateTextarea();
        }
    },

    /**
     * Inicializa los event listeners para los botones de formato
     * @param {boolean} isAdmin - Si el usuario es administrador
     */
    initFormatButtons(isAdmin = false) {
        document.querySelectorAll('[data-format]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const formatType = btn.dataset.format;
                this.insertFormat(formatType, btn, isAdmin);
            });
        });
    }
};

// Funciones globales para compatibilidad
function toggleImageSize(img) {
    MediaManager.toggleImageSize(img);
}

function insertReference(id) {
    MediaManager.insertReference(id);
}

function insertFormat(type, btn) {
    // Puedes pasar si el usuario es admin desde tu template PHP
    const isAdmin = document.body.classList.contains('admin');
    MediaManager.insertFormat(type, btn, isAdmin);
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const isAdmin = document.body.classList.contains('admin');
    MediaManager.initFormatButtons(isAdmin);
    
    // Mejor manejo de clics en referencias
    document.querySelectorAll('.ref-link').forEach(link => {
        link.addEventListener('click', (e) => {
            if (e.ctrlKey || e.metaKey) {
                // Permitir abrir en nueva pestaña con Ctrl/Cmd+click
                return;
            }
            e.preventDefault();
            const postId = link.href.split('-')[1];
            const targetPost = document.getElementById(`post-${postId}`);
            if (targetPost) {
                targetPost.scrollIntoView({ behavior: 'smooth' });
                targetPost.classList.add('highlight');
                setTimeout(() => targetPost.classList.remove('highlight'), 2000);
            }
        });
    });
});

/**
 * Utilidades de administración y confirmaciones
 */
const AdminUtils = {
    /**
     * Confirma la eliminación de posts
     * @param {string|number} postId - ID del post
     * @returns {boolean} - Confirmación del usuario
     */
    confirmDelete(postId) {
        return confirm('¿Estás seguro de que quieres eliminar este post?');
    },

    /**
     * Confirma el baneo de una IP
     * @param {string} ip - Dirección IP
     * @returns {boolean} - Confirmación del usuario
     */
    confirmBan(ip) {
        return confirm('¿Estás seguro de que quieres banear la IP ' + ip + '?');
    },

    /**
     * Establece la IP en el campo de entrada y enfoca
     * @param {string} ip - Dirección IP
     */
    setBanIp(ip) {
        const input = document.getElementById('ip_address');
        if (input) {
            input.value = ip;
            input.focus();
            window.scrollTo(0, input.getBoundingClientRect().top + window.scrollY - 100);
        }
    },

    /**
     * Muestra una sección específica del panel de admin
     * @param {string} sectionId - ID de la sección
     */
    showSection(sectionId) {
        document.querySelectorAll('.admin-section').forEach(section => {
            section.style.display = 'none';
        });
        
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.style.display = 'block';
        }
    },

    /**
     * Alterna la visibilidad de una sección
     * @param {string} sectionId - ID de la sección
     */
    toggleAdminSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }
    }
};

/**
 * Gestión de reportes
 */
const ReportManager = {
    /**
     * Alterna el menú de reportes
     * @param {string|number} postId - ID del post
     */
    toggleReportMenu(postId) {
        // Ocultar otros menús abiertos
        document.querySelectorAll('.report-menu').forEach(menu => {
            if (menu.id !== 'report-menu-' + postId) {
                menu.style.display = 'none';
            }
        });
        
        const menu = document.getElementById('report-menu-' + postId);
        if (menu) {
            menu.style.display = menu.style.display === 'none' || menu.style.display === '' ? 'block' : 'none';
        }
    },

    /**
     * Inicializa los event listeners para reportes
     */
    init() {
        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('btn-report') && !e.target.closest('.report-menu')) {
                document.querySelectorAll('.report-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
    }
};

// Funciones globales para compatibilidad
function confirmDelete(postId) {
    return AdminUtils.confirmDelete(postId);
}

function confirmBan(ip) {
    return AdminUtils.confirmBan(ip);
}

function setBanIp(ip) {
    AdminUtils.setBanIp(ip);
}

function showSection(sectionId) {
    AdminUtils.showSection(sectionId);
}

function toggleAdminSection(sectionId) {
    AdminUtils.toggleAdminSection(sectionId);
}

// Auto-refresh deshabilitado por defecto
// Para activar: setInterval(() => window.location.reload(), 60000);

/**
 * Inicialización y configuración principal
 */
const AppInitializer = {
    /**
     * Inicializa la aplicación
     */
    init() {
        this.initImageCache();
        this.initTheme();
        this.initFormHandlers();
        this.initCharacterCounters();
        this.initImagePreviews();
        this.initNameFields();
        this.initOrderSelector();
        this.initReports();
        this.handleErrorDisplay();
        this.handleAutoReference();
    },

    /**
     * Inicializa el sistema de caché de imágenes
     */
    initImageCache() {
        ImageCache.init();
        
        setTimeout(() => {
            ImageCache.applyCachedImages();
        }, 1000);
        
        window.SimpleChanImageCache = ImageCache;
    },

    /**
     * Inicializa el tema
     */
    initTheme() {
        ThemeManager.applySavedTheme();
    },

    /**
     * Inicializa los manejadores de formularios
     */
    initFormHandlers() {
        const postForms = document.querySelectorAll('form');
        
        postForms.forEach(form => {
            if (form.querySelector('textarea[name="message"]')) {
                form.addEventListener('submit', function(e) {
                    if (!FormManager.validatePost(this)) {
                        e.preventDefault();
                    }
                });
            }
        });
    },

    /**
     * Inicializa los contadores de caracteres
     */
    initCharacterCounters() {
        const textareas = document.querySelectorAll('textarea[name="message"]');
        
        textareas.forEach(textarea => {
            const counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.style.cssText = 'font-size: 12px; color: #666; text-align: right;';
            textarea.parentNode.appendChild(counter);
            
            const updateCounter = () => {
                const remaining = 1000 - textarea.value.length;
                counter.textContent = remaining + ' caracteres restantes';
                
                if (remaining < 0) {
                    counter.style.color = 'red';
                } else if (remaining < 100) {
                    counter.style.color = 'orange';
                } else {
                    counter.style.color = '#666';
                }
            };
            
            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    },

    /**
     * Inicializa las previsualizaciones de imágenes
     */
    initImagePreviews() {
        const imageInputs = document.querySelectorAll('input[type="file"][name="image"]');
        
        imageInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                // Validaciones
                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo es demasiado grande. Máximo 5MB.');
                    this.value = '';
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de archivo no permitido. Solo JPG, PNG, GIF y WebP.');
                    this.value = '';
                    return;
                }
                
                // Previsualización
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = input.parentNode.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        preview.style.marginTop = '10px';
                        input.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border: 1px solid #ccc;">`;
                };
                reader.readAsDataURL(file);
            });
        });
    },

    /**
     * Inicializa los campos de nombre
     */
    initNameFields() {
        const nameInputs = document.querySelectorAll('input[name="name"]');
        
        nameInputs.forEach(input => {
            const updateNameDisplay = () => {
                if (input.value.trim() === '') {
                    input.classList.add('anonymous-style');
                } else {
                    input.classList.remove('anonymous-style');
                }
            };
            
            input.addEventListener('focus', function() {
                this.classList.remove('anonymous-style');
            });
            
            input.addEventListener('blur', updateNameDisplay);
            input.addEventListener('input', updateNameDisplay);
            
            updateNameDisplay();
            input.placeholder = 'Anónimo';
            
            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    if (input.value.trim() === '') {
                        input.value = 'Anónimo';
                    }
                });
            }
        });
    },

    /**
     * Inicializa el selector de ordenamiento
     */
    initOrderSelector() {
        const bySelect = document.getElementById('by-select');
        if (bySelect) {
            bySelect.addEventListener('change', function() {
                URLManager.changeOrderBy(this.value);
            });
        }
    },

    /**
     * Inicializa el sistema de reportes
     */
    initReports() {
        window.toggleReportMenu = function(postId) {
            ReportManager.toggleReportMenu(postId);
        };
        
        ReportManager.init();
    },

    /**
     * Maneja la visualización de errores
     */
    handleErrorDisplay() {
        const errorDiv = document.querySelector('.error');
        if (errorDiv) {
            if (document.getElementById('create-post')) {
                FormManager.toggleCreateForm('post');
            } else if (document.getElementById('create-reply')) {
                FormManager.toggleCreateForm('reply');
            }
        }
    },

    /**
     * Maneja las referencias automáticas
     */
    handleAutoReference() {
        URLManager.handleAutoReference();
    }
};

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    AppInitializer.init();
});

/**
 * Gestión de temas
 */
const ThemeManager = {
    /**
     * Cambia el tema y guarda la configuración
     * @param {string} theme - Nombre del tema
     */
    changeTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('selectedTheme', theme);
        this.changeThemeAssets(theme);
    },

    /**
     * Cambia los assets según el tema
     * @param {string} theme - Nombre del tema
     */
    changeThemeAssets(theme) {
        this.changeLogo(theme);
        this.changeFavicon(theme);
    },

    /**
     * Cambia el logo según el tema
     * @param {string} theme - Nombre del tema
     */
    changeLogo(theme) {
        const logoImg = document.getElementById('site-logo') || 
                       document.querySelector('header img[alt="SimpleChan"]');
        if (!logoImg) return;

        let logoPath;
        switch(theme) {
            case 'yotsubab':
                logoPath = 'assets/imgs/logob.png';
                break;
            case 'futaba':
                logoPath = 'assets/imgs/logo.png';
                break;
            case 'dark':
                logoPath = 'assets/imgs/logod.png';
                break;
            default:
                logoPath = 'assets/imgs/logo.png';
                break;
        }

        const cachedImage = ImageCache.getCachedImage(logoPath);
        if (cachedImage) {
            logoImg.src = cachedImage;
        } else {
            const currentSrc = logoImg.src;
            const assetsPath = currentSrc.substring(0, currentSrc.lastIndexOf('/') + 1);
            logoImg.src = assetsPath + logoPath.split('/').pop();
        }
    },

    /**
     * Cambia el favicon según el tema
     * @param {string} theme - Nombre del tema
     */
    changeFavicon(theme) {
        const faviconLink = document.getElementById('site-favicon') || 
                            document.querySelector('link[rel="shortcut icon"]') || 
                            document.querySelector('link[rel="icon"]');
        if (!faviconLink) return;

        const currentHref = faviconLink.href;
        const assetsPath = currentHref.substring(0, currentHref.lastIndexOf('/') + 1);

        switch(theme) {
            case 'yotsubab':
            case 'futaba':
                faviconLink.href = assetsPath + 'faviconb.ico';
                break;
            case 'dark':
                faviconLink.href = assetsPath + 'favicond.ico';
                break;
            default:
                faviconLink.href = assetsPath + 'favicon.ico';
                break;
        }
    },

    /**
     * Aplica el tema guardado
     */
    applySavedTheme() {
        const savedTheme = localStorage.getItem('selectedTheme') || 'yotsuba';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        const themeSelect = document.getElementById('theme-select');
        if (themeSelect) {
            themeSelect.value = savedTheme;
        }

        this.changeThemeAssets(savedTheme);
    }
};

// Función global para compatibilidad
function changeTheme(theme) {
    ThemeManager.changeTheme(theme);
}

/**
 * Sistema de caché para imágenes
 */
const ImageCache = {
    prefix: 'simplechan_img_',
    version: '1.2',
    
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
    
    favicons: [
        'assets/favicon/favicon.ico',
        'assets/favicon/faviconb.ico',
        'assets/favicon/favicond.ico'
    ],
    
    /**
     * Inicializa el sistema de caché
     */
    init() {
        this.checkVersion();
        this.preloadImages();
    },
    
    /**
     * Verifica la versión del caché
     */
    checkVersion() {
        const storedVersion = localStorage.getItem(this.prefix + 'version');
        if (storedVersion !== this.version) {
            this.clearCache();
            localStorage.setItem(this.prefix + 'version', this.version);
        }
    },
    
    /**
     * Limpia el caché anterior
     */
    clearCache() {
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith(this.prefix)) {
                localStorage.removeItem(key);
            }
        });
    },
    
    /**
     * Pre-carga todas las imágenes
     */
    preloadImages() {
        this.images.forEach(imagePath => {
            this.cacheImage(imagePath);
        });
        
        this.favicons.forEach(faviconPath => {
            this.preloadFavicon(faviconPath);
        });
    },
    
    /**
     * Pre-carga favicon en caché del navegador
     */
    preloadFavicon(faviconPath) {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = faviconPath;
        document.head.appendChild(link);
    },
    
    /**
     * Cachea una imagen específica
     */
    cacheImage(imagePath) {
        const cacheKey = this.prefix + imagePath.replace(/[^a-zA-Z0-9]/g, '_');
        
        if (localStorage.getItem(cacheKey)) {
            return Promise.resolve();
        }
        
        return new Promise((resolve) => {
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
                    
                    if (dataURL.length < 500000) {
                        try {
                            localStorage.setItem(cacheKey, dataURL);
                        } catch (e) {
                            if (e.name === 'QuotaExceededError') {
                                this.clearOldestCache();
                                try {
                                    localStorage.setItem(cacheKey, dataURL);
                                } catch (e2) {
                                    // Error silencioso
                                }
                            }
                        }
                    }
                    resolve();
                } catch (error) {
                    resolve();
                }
            };
            
            img.onerror = () => resolve();
            img.src = imagePath;
        });
    },
    
    /**
     * Obtiene imagen del caché
     */
    getCachedImage(imagePath) {
        const cacheKey = this.prefix + imagePath.replace(/[^a-zA-Z0-9]/g, '_');
        return localStorage.getItem(cacheKey);
    },
    
    /**
     * Limpia caché más antiguo
     */
    clearOldestCache() {
        const keys = Object.keys(localStorage);
        const cacheKeys = keys.filter(key => key.startsWith(this.prefix) && key !== this.prefix + 'version');
        
        const keysToRemove = cacheKeys.slice(0, Math.floor(cacheKeys.length / 2));
        keysToRemove.forEach(key => {
            localStorage.removeItem(key);
        });
    },
    
    /**
     * Aplica imágenes cacheadas a elementos existentes
     */
    applyCachedImages() {
        const imageSelectors = [
            { selector: 'img[src*="filedeleted"]', path: 'assets/imgs/filedeleted.png' },
            { selector: 'img[src*="sticky"]', path: 'assets/imgs/sticky.png' },
            { selector: 'img[src*="closed"]', path: 'assets/imgs/closed.png' }
        ];

        imageSelectors.forEach(({ selector, path }) => {
            const images = document.querySelectorAll(selector);
            images.forEach(img => {
                const cachedSrc = this.getCachedImage(path);
                if (cachedSrc) {
                    img.src = cachedSrc;
                }
            });
        });
    },
    
    /**
     * Obtiene información del caché
     */
    getCacheInfo() {
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
    
    /**
     * Limpia caché manualmente
     */
    clearCacheManual() {
        this.clearCache();
        setTimeout(() => this.preloadImages(), 100);
    }
};

/**
 * Utilidades para desarrolladores
 */
window.SimpleChanUtils = {
    /**
     * Verifica el estado del caché
     */
    checkCache() {
        const info = ImageCache.getCacheInfo();
        return {
            message: `=== SimpleChan Image Cache Status ===
Imágenes cacheadas: ${info.imageCount}
Tamaño total: ${info.totalSizeMB}MB
Versión del caché: ${ImageCache.version}`,
            ...info
        };
    },
    
    /**
     * Limpia y regenera el caché
     */
    refreshCache() {
        ImageCache.clearCacheManual();
        return 'Caché regenerado';
    },
    
    /**
     * Prueba un tema específico
     */
    testTheme(theme) {
        ThemeManager.changeTheme(theme);
        return `Tema cambiado a: ${theme}`;
    },
    
    /**
     * Aplica imágenes cacheadas
     */
    applyCachedImages() {
        ImageCache.applyCachedImages();
        return 'Imágenes cacheadas aplicadas';
    }
};

/**
 * Función global para usar imágenes cacheadas
 */
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
            case 'futaba':
                logoPath = 'assets/imgs/logo.png';
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
            case 'futaba':
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
    
    // Configurar el selector de ordenamiento del catálogo
    const bySelect = document.getElementById('by-select');
    if (bySelect) {
        bySelect.addEventListener('change', function() {
            changeBy(this.value);
        });
    }
    
    // Aplicar el logo y favicon correctos según el tema guardado
    changeThemeAssets(savedTheme);
    
    // Aplicar imágenes cacheadas a elementos existentes
    setTimeout(() => {
        ImageCache.applyCachedImages();
    }, 1000);
    
    // Hacer disponible el sistema de caché globalmente para debugging
    window.SimpleChanImageCache = ImageCache;
});

/**
 * Busca una imagen en Google usando búsqueda por imagen
 * @param {string} imageUrl - URL de la imagen a buscar
 */
function searchImageOnGoogle(imageUrl) {
    // Construir la URL absoluta de la imagen
    let fullImageUrl;
    
    if (imageUrl.startsWith('http')) {
        // Ya es una URL absoluta
        fullImageUrl = imageUrl;
    } else {
        // Construir URL absoluta desde la URL relativa
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
        fullImageUrl = baseUrl + imageUrl;
    }
    
    // URL para búsqueda de imagen en Google
    const googleSearchUrl = 'https://www.google.com/searchbyimage?image_url=' + encodeURIComponent(fullImageUrl);
    
    // Abrir en nueva pestaña
    window.open(googleSearchUrl, '_blank');
}