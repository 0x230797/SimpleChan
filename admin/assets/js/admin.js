/**
 * JavaScript para el Panel de Administración SimpleChan
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar funcionalidades cuando el DOM esté listo
    initializeAdminPanel();
});

/**
 * Inicializa el panel de administración
 */
function initializeAdminPanel() {
    // Auto-cerrar mensajes después de unos segundos
    autoCloseMessages();
    
    // Confirmar acciones peligrosas
    setupConfirmations();
}

/**
 * Auto-cierra los mensajes después de 5 segundos
 */
function autoCloseMessages() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(function(message) {
        setTimeout(function() {
            message.style.opacity = '0';
            setTimeout(function() {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 300);
        }, 5000);
    });
}

/**
 * Configura confirmaciones para acciones peligrosas
 */
function setupConfirmations() {
    // Confirmar eliminaciones
    const deleteButtons = document.querySelectorAll('button[name*="delete"], button[name*="ban"], button[name*="deactivate"]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const action = this.textContent.trim().toLowerCase();
            const confirmMessage = getConfirmMessage(action);
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Obtiene el mensaje de confirmación según la acción
 */
function getConfirmMessage(action) {
    if (action.includes('eliminar')) {
        return '¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.';
    }
    if (action.includes('banear') || action.includes('ban')) {
        return '¿Está seguro de que desea banear esta IP?';
    }
    if (action.includes('desactivar')) {
        return '¿Está seguro de que desea desactivar este usuario?';
    }
    return '¿Está seguro de que desea realizar esta acción?';
}

/**
 * Muestra/oculta secciones del admin (para el panel antiguo si es necesario)
 */
function showSection(sectionId) {
    // Ocultar todas las secciones
    const sections = document.querySelectorAll('.admin-section');
    sections.forEach(function(section) {
        section.style.display = 'none';
    });
    
    // Mostrar la sección seleccionada
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.style.display = 'block';
    }
}

/**
 * Función para pre-llenar el formulario de ban con IP
 */
function setBanIp(ip) {
    const ipField = document.getElementById('ip_address');
    if (ipField) {
        ipField.value = ip;
        // Cambiar a la sección de ban
        showSection('ban-ip');
        // Scroll al formulario
        document.getElementById('ban-ip').scrollIntoView({behavior: 'smooth'});
    }
}