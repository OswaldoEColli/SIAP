document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const addProductBtn = document.getElementById('add-product-btn');
    const addProductModal = document.getElementById('add-product-modal');
    const editProductModal = document.getElementById('edit-product-modal');
    const cancelAddBtn = document.getElementById('cancel-add');
    const saveAddBtn = document.getElementById('save-add');
    const cancelEditBtn = document.getElementById('cancel-edit');
    const saveEditBtn = document.getElementById('save-edit');
    const deleteProductBtn = document.getElementById('delete-product');
    const actionMenu = document.getElementById('action-menu');
    const searchInput = document.getElementById('search-input');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    
    // Estado actual
    let currentProductId = null;
    
    // Convertir notificaciones tradicionales a toast
    convertToToastNotifications();
    
    // Agregar eventos para agregar producto
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            addProductModal.style.display = 'flex';
        });
    }
    
    // Cerrar modales con botones de cancelar
    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', function() {
            addProductModal.style.display = 'none';
        });
    }
    
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            editProductModal.style.display = 'none';
        });
    }
    
    // Cerrar modales con el botón X
    closeModalBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            addProductModal.style.display = 'none';
            editProductModal.style.display = 'none';
        });
    });
    
    // Guardar al agregar producto
    if (saveAddBtn) {
        saveAddBtn.addEventListener('click', function() {
            document.getElementById('add-product-form').submit();
        });
    }
    
    // Guardar al editar producto
    if (saveEditBtn) {
        saveEditBtn.addEventListener('click', function() {
            document.getElementById('edit-product-form').submit();
        });
    }
    
    // Eliminar producto
    if (deleteProductBtn) {
        deleteProductBtn.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
                window.location.href = '../controllers/producto_controller.php?action=delete&id=' + currentProductId;
            }
        });
    }
    
    // Evento para los botones de acción en cada fila
    const actionBtns = document.querySelectorAll('.action-btn');
    actionBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Obtener la posición del botón para posicionar el menú
            const btnRect = btn.getBoundingClientRect();
            actionMenu.style.top = btnRect.bottom + window.scrollY + 'px';
            actionMenu.style.left = btnRect.left - actionMenu.offsetWidth + btnRect.width + window.scrollX + 'px';
            
            // Mostrar el menú
            actionMenu.style.display = 'block';
            
            // Guardar el ID del producto actual
            currentProductId = btn.getAttribute('data-id');
        });
    });
    
    // Ocultar el menú de acciones al hacer clic en cualquier parte
    document.addEventListener('click', function() {
        actionMenu.style.display = 'none';
    });
    
    // Prevenir que el clic dentro del menú lo cierre
    actionMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Evento para editar producto
    const editProductOption = document.getElementById('edit-product-option');
    if (editProductOption) {
        editProductOption.addEventListener('click', function() {
            // Ocultar el menú de acciones
            actionMenu.style.display = 'none';
            
            // Cargar datos del producto
            fetch('../controllers/producto_controller.php?action=get&id=' + currentProductId)
                .then(response => response.json())
                .then(data => {
                    // Llenar el formulario con los datos
                    document.getElementById('edit-product-id').value = data.productoID;
                    document.getElementById('edit-product-name').value = data.nombre;
                    document.getElementById('edit-product-code').value = data.codigo;
                    document.getElementById('edit-product-description').value = data.descripcion || '';
                    document.getElementById('edit-product-purchase-price').value = data.precioCompra;
                    document.getElementById('edit-product-price').value = data.precioVentaPlancha;
                    document.getElementById('edit-product-price-half').value = data.precioVentaMediaPlancha;
                    document.getElementById('edit-product-price-unit').value = data.precioVentaUnitario;
                    document.getElementById('edit-product-units').value = data.unidadesPorPlancha;
                    document.getElementById('edit-product-image').value = data.imagen || '';
                    
                    // Cargar datos de inventario si existen
                    if (data.cantidadPlanchas !== undefined) {
                        document.getElementById('edit-product-stock-planchas').value = data.cantidadPlanchas;
                    } else {
                        document.getElementById('edit-product-stock-planchas').value = 0;
                    }
                    
                    if (data.cantidadUnidades !== undefined) {
                        document.getElementById('edit-product-stock-unidades').value = data.cantidadUnidades;
                    } else {
                        document.getElementById('edit-product-stock-unidades').value = 0;
                    }
                    
                    // Seleccionar el proveedor
                    if (data.proveedorID) {
                        const providerSelect = document.getElementById('edit-product-provider');
                        if (providerSelect) {
                            const options = providerSelect.options;
                            for (let i = 0; i < options.length; i++) {
                                if (options[i].value == data.proveedorID) {
                                    options[i].selected = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Estado activo/inactivo
                    document.getElementById('edit-product-status').value = data.activo ? 'active' : 'inactive';
                    
                    // Mostrar el modal
                    editProductModal.style.display = 'flex';
                })
                .catch(error => console.error('Error:', error));
        });
    }
    
    // Evento para eliminar producto
    const deleteProductOption = document.getElementById('delete-product-option');
    if (deleteProductOption) {
        deleteProductOption.addEventListener('click', function() {
            // Ocultar el menú de acciones
            actionMenu.style.display = 'none';
            
            // Confirmar eliminación
            if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
                window.location.href = '../controllers/producto_controller.php?action=delete&id=' + currentProductId;
            }
        });
    }
    
    // Búsqueda en tiempo real
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                if (text.indexOf(searchTerm) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Función para convertir notificaciones tradicionales a toast
    function convertToToastNotifications() {
        const notifications = document.querySelectorAll('.notification');
        
        notifications.forEach(notification => {
            const message = notification.querySelector('span').textContent;
            let type = 'info';
            
            if (notification.classList.contains('success')) {
                type = 'success';
            } else if (notification.classList.contains('error')) {
                type = 'error';
            }
            
            // Eliminar la notificación tradicional
            notification.remove();
            
            // Crear la notificación toast
            showToastNotification(message, type);
        });
    }
    
    // Función para mostrar notificaciones toast
    function showToastNotification(message, type = 'info') {
        // Crear el elemento de notificación
        const notification = document.createElement('div');
        notification.className = `toast-notification ${type}`;
        
        // Icono según el tipo
        let icon = '';
        if (type === 'success') {
            icon = '<i class="fas fa-check-circle"></i>';
        } else if (type === 'error') {
            icon = '<i class="fas fa-exclamation-circle"></i>';
        } else {
            icon = '<i class="fas fa-info-circle"></i>';
        }
        
        // Contenido de la notificación
        notification.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">${message}</div>
            <button class="close-notification">&times;</button>
            <div class="progress-bar"></div>
        `;
        
        // Agregar al cuerpo del documento
        document.body.appendChild(notification);
        
        // Mostrar con animación
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Configurar el botón de cierre
        const closeBtn = notification.querySelector('.close-notification');
        closeBtn.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 500);
        });
        
        // Auto-cerrar después de 4 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 4000);
    }

     // Script específico para el botón de logout
     document.addEventListener('DOMContentLoaded', function() {
        console.log("Inicializando script de logout");
        
        const logoutBtn = document.querySelector('.logout-btn');
        console.log("Botón logout encontrado:", logoutBtn);
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                console.log("Botón logout clickeado");
                if (confirm('¿Seguro que deseas cerrar sesión?')) {
                    window.location.href = '../controllers/logout.php';
                }
            });
        }
    });
});