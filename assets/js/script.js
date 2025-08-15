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