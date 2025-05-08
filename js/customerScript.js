document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const customerModal = document.getElementById('customer-modal');
    const deleteConfirmationModal = document.getElementById('delete-confirmation-modal');
    const addCustomerBtn = document.getElementById('add-customer-btn');
    const cancelModalBtn = document.getElementById('cancel-modal');
    const customerForm = document.getElementById('customer-form');
    const searchInput = document.getElementById('search-input');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    
    let currentCustomerId = null;

    // Inicializar notificaciones toast
    initToastNotifications();

    // Ocultar los modales al cargar la página
    if (customerModal) {
        customerModal.style.display = 'none';
    }
    
    if (deleteConfirmationModal) {
        deleteConfirmationModal.style.display = 'none';
    }

    // Manejar el botón de agregar cliente
    if (addCustomerBtn) {
        addCustomerBtn.addEventListener('click', function() {
            // Resetear el formulario
            customerForm.reset();
            document.getElementById('cliente_id').value = '';
            document.getElementById('modal-title').textContent = 'Nuevo Cliente';
            
            // Mostrar el modal
            customerModal.style.display = 'flex';
        });
    }

    // Manejar el botón de cancelar en el modal de cliente
    if (cancelModalBtn) {
        cancelModalBtn.addEventListener('click', function() {
            customerModal.style.display = 'none';
        });
    }

    // Manejar el botón de cancelar en el modal de confirmación de eliminación
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteConfirmationModal.style.display = 'none';
        });
    }

    // Manejar los botones de acción en cada fila
    setupActionButtons();

    // Botón de cerrar sesión
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
        if (confirm('¿Seguro que deseas cerrar sesión?')) {
            window.location.href = '../controllers/logout.php';
        }
    });
    }

    // Cerrar dropdowns cuando se hace clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-menu')) {
            document.querySelectorAll('.action-dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    // Manejar búsqueda en tiempo real
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#customers-table-body tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
            
            // Mostrar mensaje si no hay resultados
            const visibleRows = document.querySelectorAll('#customers-table-body tr:not([style="display: none;"])');
            const noDataRow = document.querySelector('.no-data-search');
            
            if (visibleRows.length === 0 && !noDataRow) {
                const tbody = document.getElementById('customers-table-body');
                const tr = document.createElement('tr');
                tr.className = 'no-data-search';
                tr.innerHTML = '<td colspan="6">No se encontraron clientes que coincidan con la búsqueda</td>';
                tbody.appendChild(tr);
            } else if (visibleRows.length > 0 && noDataRow) {
                noDataRow.remove();
            }
        });
    }

    // Confirmar eliminación
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (currentCustomerId) {
                window.location.href = `../controllers/cliente_controller.php?action=delete&id=${currentCustomerId}`;
            }
        });
    }

    // Configurar los botones de acción en la tabla
    function setupActionButtons() {
        // Botones de acción
        const actionBtns = document.querySelectorAll('.action-btn');
        actionBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Cerrar todos los dropdowns abiertos
                document.querySelectorAll('.action-dropdown.active').forEach(dropdown => {
                    if (dropdown !== this.nextElementSibling) {
                        dropdown.classList.remove('active');
                    }
                });
                
                // Abrir o cerrar el dropdown actual
                const dropdown = this.parentElement.querySelector('.action-dropdown');
                dropdown.classList.toggle('active');
            });
        });
        
        // Botones de editar
        const editBtns = document.querySelectorAll('.edit-customer');
        editBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const customerId = this.getAttribute('data-id');
                loadCustomerData(customerId);
            });
        });
        
        // Botones de eliminar
        const deleteBtns = document.querySelectorAll('.delete-customer');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const customerId = this.getAttribute('data-id');
                currentCustomerId = customerId;
                deleteConfirmationModal.style.display = 'flex';
            });
        });
    }

    // Función para cargar datos de un cliente para editar
    function loadCustomerData(customerId) {
        fetch(`../controllers/cliente_controller.php?action=get&id=${customerId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error de red: ${response.status}`);
                }
                return response.json();
            })
            .then(customer => {
                console.log('Datos del cliente recibidos:', customer); // Depuración
                
                // Llenar el formulario con los datos del cliente
                document.getElementById('cliente_id').value = customer.clienteID;
                document.getElementById('customer-name').value = `${customer.nombre} ${customer.apellidos || ''}`.trim();
                document.getElementById('customer-rfc').value = customer.rfc || '';
                document.getElementById('customer-phone').value = customer.telefono || '';
                document.getElementById('customer-email').value = customer.email || '';
                document.getElementById('customer-address').value = customer.direccion || '';
                document.getElementById('customer-status').value = customer.esRecurrente == 1 ? 'active' : 'inactive';
                
                // Cambiar título del modal
                document.getElementById('modal-title').textContent = 'Editar Cliente';
                
                // Mostrar modal
                customerModal.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error al cargar datos del cliente:', error);
                showToastNotification('Error al cargar los datos del cliente', 'error');
            });
    }

    // Inicializar notificaciones toast desde notificaciones tradicionales
    function initToastNotifications() {
        const notifications = document.querySelectorAll('.notification');
        
        notifications.forEach(notification => {
            const message = notification.querySelector('span').textContent;
            const type = notification.classList.contains('success') ? 'success' : 'error';
            
            // Mostrar como toast y ocultar la original
            showToastNotification(message, type);
            notification.style.display = 'none';
        });
        
        // Comprobar parámetros de URL para notificaciones
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('status')) {
            const status = urlParams.get('status');
            const message = urlParams.get('message');
            
            if (message) {
                showToastNotification(decodeURIComponent(message), status);
                
                // Limpiar los parámetros de URL sin recargar la página
                const url = new URL(window.location);
                url.searchParams.delete('status');
                url.searchParams.delete('message');
                window.history.replaceState({}, '', url);
            }
        }
    }
});

/**
 * Función para mostrar notificaciones tipo toast
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, info)
 * @param {number} duration - Duración en milisegundos (por defecto 4000)
 */
function showToastNotification(message, type = 'success', duration = 4000) {
    // Crear el elemento de notificación
    const notification = document.createElement('div');
    notification.className = `toast-notification ${type}`;
    
    // Elegir icono según el tipo
    let icon = '';
    if (type === 'success') {
        icon = '<i class="fas fa-check-circle"></i>';
    } else if (type === 'error') {
        icon = '<i class="fas fa-exclamation-circle"></i>';
    } else if (type === 'info') {
        icon = '<i class="fas fa-info-circle"></i>';
    }
    
    // Estructura interna
    notification.innerHTML = `
        <div class="notification-icon">${icon}</div>
        <div class="notification-content">${message}</div>
        <button class="close-notification">&times;</button>
        <div class="progress-bar"></div>
    `;
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar con animación después de un pequeño retraso
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Configurar cerrado automático
    const timeout = setTimeout(() => {
        closeNotification(notification);
    }, duration);
    
    // Configurar botón de cierre
    const closeBtn = notification.querySelector('.close-notification');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            clearTimeout(timeout);
            closeNotification(notification);
        });
    }
}

/**
 * Cierra la notificación con animación
 * @param {HTMLElement} notification - Elemento de notificación a cerrar
 */
function closeNotification(notification) {
    notification.classList.remove('show');
    
    // Eliminar del DOM después de la animación
    setTimeout(() => {
        if (notification.parentElement) {
            notification.parentElement.removeChild(notification);
        }
    }, 500);
}



