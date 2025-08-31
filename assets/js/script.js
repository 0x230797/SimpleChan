/**
 * SimpleChan
 * Version 1.0
 */

/**
 * Configuración global
 */
const CONFIG = {
    MAX_MESSAGE_LENGTH: 1000,
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    ALLOWED_IMAGE_TYPES: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
    CACHE_VERSION: '1.0',
    CACHE_PREFIX: 'simplechan_',
    ASSETS: {
        images: [
            'assets/imgs/logo.png',
            'assets/imgs/logob.png', 
            'assets/imgs/logod.png',
            'assets/imgs/logof.png',
            'assets/imgs/logog.png',
            'assets/imgs/blue.png',
            'assets/imgs/closed.png',
            'assets/imgs/girls.png',
            'assets/imgs/dark.png',
            'assets/imgs/fade.png',
            'assets/imgs/filedeleted.png',
            'assets/imgs/sticky.png'
        ],
        favicons: [
            'assets/favicon/favicon.ico',
            'assets/favicon/faviconb.ico',
            'assets/favicon/faviconf.ico',
            'assets/favicon/favicong.ico',
            'assets/favicon/favicond.ico'
        ]
    },
    THEMES: {
        yotsuba: { logo: 'logo.png', favicon: 'favicon.ico' },
        yotsubab: { logo: 'logob.png', favicon: 'faviconb.ico' },
        futaba: { logo: 'logof.png', favicon: 'faviconf.ico' },
        girls: { logo: 'logog.png', favicon: 'favicong.ico' },
        dark: { logo: 'logod.png', favicon: 'favicond.ico' }
    }
};

/**
 * Utilidades base
 */
const Utils = {
    /**
     * Selector mejorado con fallback
     */
    $(selector, context = document) {
        return context.querySelector(selector);
    },

    /**
     * Selector múltiple
     */
    $$(selector, context = document) {
        return context.querySelectorAll(selector);
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Animate element
     */
    animate(element, styles, duration = 300) {
        return new Promise(resolve => {
            element.style.transition = `all ${duration}ms ease`;
            Object.assign(element.style, styles);
            setTimeout(resolve, duration);
        });
    },

    /**
     * Scroll to element with highlighting
     */
    scrollToElement(element, highlight = true) {
        if (!element) return;
        
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        if (highlight) {
            element.style.backgroundColor = '#fff3cd';
            setTimeout(() => {
                element.style.backgroundColor = '';
            }, 2000);
        }
    }
};

/**
 * Sistema de caché optimizado
 */
const CacheManager = {
    version: CONFIG.CACHE_VERSION,
    prefix: CONFIG.CACHE_PREFIX,

    init() {
        this.checkVersion();
        this.preloadAssets();
    },

    checkVersion() {
        const storedVersion = localStorage.getItem(this.prefix + 'version');
        if (storedVersion !== this.version) {
            this.clearCache();
            localStorage.setItem(this.prefix + 'version', this.version);
        }
    },

    clearCache() {
        Object.keys(localStorage)
            .filter(key => key.startsWith(this.prefix))
            .forEach(key => localStorage.removeItem(key));
    },

    async preloadAssets() {
        const allAssets = [...CONFIG.ASSETS.images, ...CONFIG.ASSETS.favicons];
        const promises = allAssets.map(asset => this.cacheAsset(asset));
        
        try {
            await Promise.allSettled(promises);
            this.applyCache();
        } catch (error) {
            console.warn('Cache preload failed:', error);
        }
    },

    async cacheAsset(assetPath) {
        const cacheKey = this.prefix + assetPath.replace(/[^a-zA-Z0-9]/g, '_');
        
        if (localStorage.getItem(cacheKey)) return;
        
        return new Promise(resolve => {
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
                        this.setStorageItem(cacheKey, dataURL);
                    }
                } catch (error) {
                    console.warn('Failed to cache asset:', assetPath, error);
                }
                resolve();
            };
            
            img.onerror = () => resolve();
            img.src = assetPath;
        });
    },

    setStorageItem(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            if (e.name === 'QuotaExceededError') {
                this.clearOldCache();
                try {
                    localStorage.setItem(key, value);
                } catch (e2) {
                    console.warn('Storage quota exceeded, cannot cache more items');
                }
            }
        }
    },

    clearOldCache() {
        const cacheKeys = Object.keys(localStorage)
            .filter(key => key.startsWith(this.prefix) && key !== this.prefix + 'version');
        
        const keysToRemove = cacheKeys.slice(0, Math.floor(cacheKeys.length / 2));
        keysToRemove.forEach(key => localStorage.removeItem(key));
    },

    getCached(assetPath) {
        const cacheKey = this.prefix + assetPath.replace(/[^a-zA-Z0-9]/g, '_');
        return localStorage.getItem(cacheKey);
    },

    applyCache() {
        const imageSelectors = [
            { selector: 'img[src*="filedeleted"]', path: 'assets/imgs/filedeleted.png' },
            { selector: 'img[src*="sticky"]', path: 'assets/imgs/sticky.png' },
            { selector: 'img[src*="closed"]', path: 'assets/imgs/closed.png' }
        ];

        imageSelectors.forEach(({ selector, path }) => {
            Utils.$$(selector).forEach(img => {
                const cachedSrc = this.getCached(path);
                if (cachedSrc) img.src = cachedSrc;
            });
        });
    },

    getStats() {
        let totalSize = 0;
        let count = 0;
        
        Object.keys(localStorage)
            .filter(key => key.startsWith(this.prefix) && key !== this.prefix + 'version')
            .forEach(key => {
                const value = localStorage.getItem(key);
                if (value) {
                    totalSize += value.length;
                    count++;
                }
            });
        
        return {
            count,
            sizeMB: (totalSize / (1024 * 1024)).toFixed(2),
            version: this.version
        };
    }
};

/**
 * Gestión de temas unificada
 */
const ThemeManager = {
    init() {
        this.applySavedTheme();
        this.bindEvents();
    },

    bindEvents() {
        const themeSelect = Utils.$('#theme-select');
        if (themeSelect) {
            themeSelect.addEventListener('change', (e) => {
                this.changeTheme(e.target.value);
            });
        }
    },

    changeTheme(theme) {
        if (!CONFIG.THEMES[theme]) {
            console.warn('Unknown theme:', theme);
            return;
        }

        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('selectedTheme', theme);
        this.updateAssets(theme);
    },

    updateAssets(theme) {
        const themeConfig = CONFIG.THEMES[theme];
        this.updateLogo(themeConfig.logo);
        this.updateFavicon(themeConfig.favicon);
    },

    updateLogo(logoFile) {
        const logoImg = Utils.$('#site-logo') || Utils.$('header img[alt="SimpleChan"]');
        if (!logoImg) return;

        const logoPath = `assets/imgs/${logoFile}`;
        const cachedImage = CacheManager.getCached(logoPath);
        
        if (cachedImage) {
            logoImg.src = cachedImage;
        } else {
            const currentSrc = logoImg.src;
            const basePath = currentSrc.substring(0, currentSrc.lastIndexOf('/') + 1);
            logoImg.src = basePath + logoFile;
        }
    },

    updateFavicon(faviconFile) {
        const faviconLink = Utils.$('#site-favicon') || 
                            Utils.$('link[rel="shortcut icon"]') || 
                            Utils.$('link[rel="icon"]');
        if (!faviconLink) return;

        const currentHref = faviconLink.href;
        const basePath = currentHref.substring(0, currentHref.lastIndexOf('/') + 1);
        faviconLink.href = basePath + faviconFile;
    },

    applySavedTheme() {
        const savedTheme = localStorage.getItem('selectedTheme') || 'yotsuba';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        const themeSelect = Utils.$('#theme-select');
        if (themeSelect) themeSelect.value = savedTheme;
        
        this.updateAssets(savedTheme);
    }
};

/**
 * Gestión de URL optimizada
 */
const URLManager = {
    changeOrderBy(orderBy) {
        const urlParams = new URLSearchParams(window.location.search);
        const currentBoard = urlParams.get('board');
        
        urlParams.set('order_by', orderBy);
        if (currentBoard) urlParams.set('board', currentBoard);
        
        window.location.search = urlParams.toString();
    },

    scrollToPost(postId) {
        const post = Utils.$('#post-' + postId);
        if (post) Utils.scrollToElement(post);
    },

    handleAutoReference() {
        const params = new URLSearchParams(window.location.search);
        const ref = params.get('ref');
        if (ref) {
            const textarea = Utils.$('textarea[name="message"]');
            if (textarea) {
                textarea.value = '>>' + ref + '\n';
                textarea.focus();
            }
        }
    }
};

/**
 * Gestión de formularios optimizada
 */
const FormManager = {
    init() {
        this.bindValidation();
        this.initCharacterCounters();
        this.initImagePreviews();
        this.initNameFields();
    },

    toggleCreateForm(type = 'post') {
        const forms = {
            post: { form: Utils.$('#create-post'), button: Utils.$('#toggle-post') },
            reply: { form: Utils.$('#create-reply'), button: Utils.$('#toggle-reply') }
        };

        Object.entries(forms).forEach(([formType, elements]) => {
            if (formType === type) {
                this.toggleSingleForm(elements.form, elements.button, 'Crear ' + formType, 'Cancelar');
            } else {
                this.hideSingleForm(elements.form, elements.button, 'Crear ' + formType);
            }
        });
    },

    async toggleSingleForm(form, button, showText, hideText) {
        if (!form || !button) return;
        
        const isHidden = form.style.display === 'none' || form.style.display === '';
        
        if (isHidden) {
            form.style.display = 'block';
            Utils.scrollToElement(form, false);
            button.textContent = hideText;
            button.classList.add('btn-cancel');
            
            // Focus first input after animation
            setTimeout(() => {
                const firstInput = form.querySelector('input[name="name"], textarea[name="message"]');
                if (firstInput) firstInput.focus();
            }, 300);
        } else {
            await Utils.animate(form, { opacity: '0' });
            form.style.display = 'none';
            form.style.opacity = '1';
            button.textContent = showText;
            button.classList.remove('btn-cancel');
            
            const formElement = form.querySelector('form');
            if (formElement) formElement.reset();
        }
    },

    hideSingleForm(form, button, showText) {
        if (!form || !button) return;
        
        form.style.display = 'none';
        button.textContent = showText;
        button.classList.remove('btn-cancel');
    },

    toggleReplyForm(postId) {
        const replyForm = Utils.$('#reply-form-' + postId);
        if (!replyForm) return;

        const isHidden = replyForm.style.display === 'none';
        replyForm.style.display = isHidden ? 'block' : 'none';
        
        if (isHidden) {
            Utils.scrollToElement(replyForm, false);
            setTimeout(() => {
                const nameInput = replyForm.querySelector('input[name="name"]');
                if (nameInput) nameInput.focus();
            }, 300);
        } else {
            const formElement = replyForm.querySelector('form');
            if (formElement) formElement.reset();
        }
    },

    validatePost(form) {
        const message = form.querySelector('textarea[name="message"]').value.trim();
        
        if (message.length === 0) {
            alert('El mensaje no puede estar vacío.');
            return false;
        }
        
        if (message.length > CONFIG.MAX_MESSAGE_LENGTH) {
            alert(`El mensaje es demasiado largo. Máximo ${CONFIG.MAX_MESSAGE_LENGTH} caracteres.`);
            return false;
        }
        
        return true;
    },

    bindValidation() {
        Utils.$$('form').forEach(form => {
            if (form.querySelector('textarea[name="message"]')) {
                form.addEventListener('submit', (e) => {
                    if (!this.validatePost(form)) {
                        e.preventDefault();
                    }
                });
            }
        });
    },

    initCharacterCounters() {
        Utils.$$('textarea[name="message"]').forEach(textarea => {
            const counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.style.cssText = 'font-size: 12px; color: #666; text-align: right; margin-top: 5px;';
            textarea.parentNode.appendChild(counter);
            
            const updateCounter = () => {
                const remaining = CONFIG.MAX_MESSAGE_LENGTH - textarea.value.length;
                counter.textContent = `${remaining} caracteres restantes`;
                
                counter.style.color = remaining < 0 ? 'red' : 
                                    remaining < 100 ? 'orange' : '#666';
            };
            
            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    },

    initImagePreviews() {
        Utils.$$('input[type="file"][name="image"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                // Validations
                if (file.size > CONFIG.MAX_FILE_SIZE) {
                    alert('El archivo es demasiado grande. Máximo 5MB.');
                    input.value = '';
                    return;
                }
                
                if (!CONFIG.ALLOWED_IMAGE_TYPES.includes(file.type)) {
                    alert('Tipo de archivo no permitido. Solo JPG, PNG, GIF y WebP.');
                    input.value = '';
                    return;
                }
                
                // Preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    let preview = input.parentNode.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        preview.style.marginTop = '10px';
                        input.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border: 1px solid #ccc; border-radius: 4px;">`;
                };
                reader.readAsDataURL(file);
            });
        });
    },

    initNameFields() {
        Utils.$$('input[name="name"]').forEach(input => {
            input.placeholder = 'Anónimo';
            
            const updateStyle = () => {
                if (input.value.trim() === '') {
                    input.classList.add('anonymous-style');
                } else {
                    input.classList.remove('anonymous-style');
                }
            };
            
            input.addEventListener('focus', () => input.classList.remove('anonymous-style'));
            input.addEventListener('blur', updateStyle);
            input.addEventListener('input', updateStyle);
            
            updateStyle();
            
            // Set default value on form submit
            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', () => {
                    if (input.value.trim() === '') {
                        input.value = 'Anónimo';
                    }
                });
            }
        });
    }
};

/**
 * Gestión de medios optimizada
 */
const MediaManager = {
    init() {
        this.bindImageEvents();
        this.bindFormatButtons();
    },

    toggleImageSize(img) {
        img.classList.toggle('fullsize');
        const container = img.closest('.post-image');
        if (container) {
            container.classList.toggle('fullsize');
        }
    },

    insertReference(id) {
        const textarea = document.activeElement?.name === 'message' ? document.activeElement :
                        Utils.$('textarea[name="message"]');
        if (!textarea) return;

        const ref = `>>${id}`;
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);

        const insertText = (textBefore.endsWith(' ') || textBefore === '') ? ref : ` ${ref}`;
        
        textarea.value = textBefore + insertText + textAfter;
        
        const newCursorPos = cursorPos + insertText.length;
        textarea.selectionStart = newCursorPos;
        textarea.selectionEnd = newCursorPos;
        textarea.focus();
    },

    insertFormat(type, btn, isAdmin = false) {
        const form = btn?.closest('form');
        const textarea = document.activeElement?.name === 'message' ? document.activeElement :
                        form?.querySelector('textarea[name="message"]') || 
                        Utils.$('textarea[name="message"]');
        
        if (!textarea) return;

        const { selectionStart: start, selectionEnd: end, value } = textarea;
        const selectedText = value.substring(start, end) || "texto";

        const formats = {
            bold: `**${selectedText}**`,
            italic: `*${selectedText}*`,
            strike: `~${selectedText}~`,
            underline: `_${selectedText}_`,
            spoiler: `[spoiler]${selectedText}[/spoiler]`,
            ...(isAdmin && {
                h1: `<h1>${selectedText}</h1>`,
                h2: `<h2>${selectedText}</h2>`,
                center: `<div style="text-align:center">${selectedText}</div>`
            })
        };

        const formattedText = formats[type];
        if (!formattedText) {
            console.warn(`Formato no permitido: ${type}`);
            return;
        }

        textarea.value = value.substring(0, start) + formattedText + value.substring(end);
        textarea.focus();
        
        const newStart = start + formattedText.indexOf(selectedText);
        textarea.selectionStart = newStart;
        textarea.selectionEnd = newStart + selectedText.length;
    },

    bindImageEvents() {
        // Event delegation for dynamic images
        document.addEventListener('click', (e) => {
            // Solo procesar imágenes que NO estén dentro de enlaces
            if (e.target.matches('.post-image img, .clickable-image, img[onclick*="toggleImageSize"]') && 
                !e.target.closest('a')) {
                e.preventDefault(); // Prevenir la ejecución del onclick attribute
                this.toggleImageSize(e.target);
            }
        });
    },

    bindFormatButtons() {
        const isAdmin = document.body.classList.contains('admin');
        
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-format]')) {
                e.preventDefault();
                const formatType = e.target.dataset.format;
                this.insertFormat(formatType, e.target, isAdmin);
            }
        });
    }
};

/**
 * Utilidades de administración
 */
const AdminUtils = {
    confirmDelete(postId) {
        return confirm('¿Estás seguro de que quieres eliminar este post?');
    },

    confirmBan(ip) {
        return confirm(`¿Estás seguro de que quieres banear la IP ${ip}?`);
    },

    setBanIp(ip) {
        const input = Utils.$('#ip_address');
        if (input) {
            input.value = ip;
            input.focus();
            Utils.scrollToElement(input, false);
        }
    },

    showSection(sectionId) {
        Utils.$$('.admin-section').forEach(section => {
            section.style.display = 'none';
        });
        
        const targetSection = Utils.$('#' + sectionId);
        if (targetSection) targetSection.style.display = 'block';
    },

    toggleAdminSection(sectionId) {
        const section = Utils.$('#' + sectionId);
        if (section) {
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }
    }
};

/**
 * Gestión de reportes
 */
const ReportManager = {
    init() {
        this.bindEvents();
    },

    toggleReportMenu(postId) {
        // Close other menus
        Utils.$$('.report-menu').forEach(menu => {
            if (menu.id !== 'report-menu-' + postId) {
                menu.style.display = 'none';
                menu.classList.remove('force-visible');
            }
        });
        
        const menu = Utils.$('#report-menu-' + postId);
        
        if (menu) {
            const isHidden = menu.style.display === 'none' || menu.style.display === '';
            
            if (isHidden) {
                menu.style.display = 'block';
                menu.classList.add('force-visible');
            } else {
                menu.style.display = 'none';
                menu.classList.remove('force-visible');
            }
        }
    },

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (!e.target.matches('.btn-report') && !e.target.closest('.report-menu')) {
                Utils.$$('.report-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
    }
};

/**
 * Búsqueda de imágenes
 */
const ImageSearch = {
    searchOnBing(imageUrl) {
        let fullImageUrl;
        
        if (imageUrl.startsWith('http')) {
            fullImageUrl = imageUrl;
        } else {
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
            fullImageUrl = baseUrl + imageUrl;
        }
        
        const bingSearchUrl = 'https://www.bing.com/images/search?view=detailv2&iss=sbi&FORM=SBIHMP&sbisrc=UrlPaste&q=imgurl:' + encodeURIComponent(fullImageUrl);
        window.open(bingSearchUrl, '_blank');
    }
};

/**
 * Auto Refresh Manager
 * Maneja la recarga automática de páginas
 */
const AutoRefreshManager = {
    intervalId: null,
    countdownId: null,
    isActive: false,
    refreshRate: 10000, // 10 segundos
    countdown: 10,
    storageKey: 'simplechan_auto_refresh',

    init() {
        const autoCheckbox = Utils.$('#auto');
        if (autoCheckbox) {
            // Restaurar estado desde localStorage
            this.restoreState(autoCheckbox);
            
            autoCheckbox.addEventListener('change', (e) => {
                this.toggle(e.target.checked);
                this.saveState(e.target.checked);
            });
        }
    },

    restoreState(checkbox) {
        const savedState = localStorage.getItem(this.storageKey);
        if (savedState === 'true') {
            checkbox.checked = true;
            this.start();
        }
    },

    saveState(enabled) {
        localStorage.setItem(this.storageKey, enabled.toString());
    },

    toggle(enabled) {
        if (enabled) {
            this.start();
        } else {
            this.stop();
        }
    },

    start() {
        if (this.isActive) return;
        
        this.isActive = true;
        this.countdown = 10;
        
        // Actualizar el texto inmediatamente
        this.updateCountdownDisplay();
        
        // Iniciar cuenta regresiva
        this.countdownId = setInterval(() => {
            this.countdown--;
            this.updateCountdownDisplay();
            
            if (this.countdown <= 0) {
                window.location.reload();
            }
        }, 1000);
        
        console.log('Auto-refresh activado (cada 10 segundos)');
    },

    stop() {
        if (!this.isActive) return;
        
        this.isActive = false;
        
        if (this.countdownId) {
            clearInterval(this.countdownId);
            this.countdownId = null;
        }
        
        // Restaurar texto original
        this.resetCountdownDisplay();
        
        console.log('Auto-refresh desactivado');
    },

    updateCountdownDisplay() {
        const autoCheckbox = Utils.$('#auto');
        if (autoCheckbox && autoCheckbox.nextSibling) {
            // Actualizar el texto que está junto al checkbox
            autoCheckbox.nextSibling.textContent = ` Auto] (${this.countdown}s)`;
        }
    },

    resetCountdownDisplay() {
        const autoCheckbox = Utils.$('#auto');
        if (autoCheckbox && autoCheckbox.nextSibling) {
            // Restaurar texto original
            autoCheckbox.nextSibling.textContent = ' Auto]';
        }
    }
};

/**
 * Inicializador principal
 */
const SimpleChan = {
    async init() {
        try {
            // Initialize cache first
            await CacheManager.init();
            
            // Initialize all modules
            ThemeManager.init();
            FormManager.init();
            MediaManager.init();
            ReportManager.init();
            AutoRefreshManager.init();
            
            // Handle URL params
            URLManager.handleAutoReference();
            
            // Initialize catalog order selector
            this.initCatalogSelector();
            
            // Show any errors
            this.handleErrors();
            
            console.log('SimpleChan initialized successfully');
        } catch (error) {
            console.error('SimpleChan initialization failed:', error);
        }
    },

    initCatalogSelector() {
        const bySelect = Utils.$('#by-select');
        if (bySelect) {
            bySelect.addEventListener('change', (e) => {
                URLManager.changeOrderBy(e.target.value);
            });
        }
    },

    handleErrors() {
        const errorDiv = Utils.$('.error');
        if (errorDiv) {
            if (Utils.$('#create-post')) {
                FormManager.toggleCreateForm('post');
            } else if (Utils.$('#create-reply')) {
                FormManager.toggleCreateForm('reply');
            }
        }
    }
};

// Global API for compatibility
window.SimpleChanAPI = {
    // URL Management
    changeBy: (orderBy) => URLManager.changeOrderBy(orderBy),
    scrollToPost: (postId) => URLManager.scrollToPost(postId),
    
    // Form Management  
    toggleCreateForm: (type) => FormManager.toggleCreateForm(type),
    toggleCreatePost: () => FormManager.toggleCreateForm('post'),
    toggleCreateReply: () => FormManager.toggleCreateForm('reply'),
    toggleReplyForm: (postId) => FormManager.toggleReplyForm(postId),
    validatePost: (form) => FormManager.validatePost(form),
    
    // Media Management
    toggleImageSize: (img) => MediaManager.toggleImageSize(img),
    insertReference: (id) => MediaManager.insertReference(id),
    insertFormat: (type, btn) => MediaManager.insertFormat(type, btn, document.body.classList.contains('admin')),
    
    // Theme Management
    changeTheme: (theme) => ThemeManager.changeTheme(theme),
    
    // Admin Utils
    confirmDelete: (postId) => AdminUtils.confirmDelete(postId),
    confirmBan: (ip) => AdminUtils.confirmBan(ip),
    setBanIp: (ip) => AdminUtils.setBanIp(ip),
    showSection: (sectionId) => AdminUtils.showSection(sectionId),
    toggleAdminSection: (sectionId) => AdminUtils.toggleAdminSection(sectionId),
    
    // Report Management
    toggleReportMenu: (postId) => ReportManager.toggleReportMenu(postId),
    
    // Auto Refresh Management
    toggleAutoRefresh: (enabled) => AutoRefreshManager.toggle(enabled),
    startAutoRefresh: () => AutoRefreshManager.start(),
    stopAutoRefresh: () => AutoRefreshManager.stop(),
    saveAutoRefreshState: (enabled) => AutoRefreshManager.saveState(enabled),
    updateCountdownDisplay: () => AutoRefreshManager.updateCountdownDisplay(),
    
    // Image Search
    searchImageOnGoogle: (imageUrl) => ImageSearch.searchOnBing(imageUrl),
    
    // Cache Management
    getCacheStats: () => CacheManager.getStats(),
    clearCache: () => CacheManager.clearCache(),
    
    // Utils
    $: Utils.$,
    $$: Utils.$$
};

// Backward compatibility - Global functions
Object.entries(window.SimpleChanAPI).forEach(([key, value]) => {
    if (typeof value === 'function') {
        window[key] = value;
    }
});

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => SimpleChan.init());
} else {
    SimpleChan.init();
}