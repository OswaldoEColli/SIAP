/**
 * Archivo JavaScript para productsSale.php
 * Sistema POS de Bebidas
 */

// Asegurarnos de que window.cartProducts sea siempre un objeto válido disponible globalmente
window.cartProducts = window.cartProducts || {};

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si se acaba de agregar un cliente
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('customer_added') === 'true') {
        // Obtener cliente de la sesión (estos valores serán establecidos por PHP)
        fetch('get_session_data.php?key=new_customer')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.customer_id) {
                    // Actualizar UI con el cliente recién creado
                    const clienteBtn = document.getElementById('cliente-btn');
                    if (clienteBtn) {
                        clienteBtn.textContent = data.customer_name || 'Cliente';
                        clienteBtn.setAttribute('data-customer-id', data.customer_id);
                        
                        // Mostrar toast de éxito
                        showToast('Cliente guardado exitosamente', 'success');
                    }
                }
            })
            .catch(error => console.error('Error al recuperar datos de sesión:', error));
    }

    // Elementos principales
    const floatingLogo = document.getElementById('floating-logo');
    const sidebar = document.getElementById('sidebar');
    
    // Toggle sidebar con el logo flotante
    if (floatingLogo) {
        floatingLogo.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Inicializar para dispositivos móviles
    if (window.innerWidth <= 768) {
        // En móviles, sidebar comienza oculto
        sidebar.classList.add('active');
    }

    const navButtons = document.querySelectorAll('.nav-button');
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            navButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Variables para almacenar producto seleccionado actualmente
    let selectedProduct = null;

    const productCards = document.querySelectorAll('.product-card');
    const productTypeModal = document.getElementById('product-type-modal');
    const saleTypeSelect = document.getElementById('sale-type');
    const productQuantityInput = document.getElementById('product-quantity');
    const pricePerUnitSpan = document.getElementById('price-per-unit');
    const subtotalAmountSpan = document.getElementById('subtotal-amount');
    const productSelectedName = document.getElementById('product-selected-name');
    
    const cartItems = document.querySelector('.cart-items');
    const cartTotal = document.getElementById('cart-total');
    let totalPrice = 0;
    
    // Cargar productos existentes en el carrito si existen
    if (Object.keys(window.cartProducts).length > 0) {
        // Si ya hay productos en el carrito, limpiar el contenedor
        cartItems.innerHTML = '';
        
        // Recrear los elementos visuales para cada producto
        Object.entries(window.cartProducts).forEach(([cartItemId, product]) => {
            createCartItem(
                cartItemId,
                product.id,
                product.name,
                product.price,
                product.saleType,
                product.quantity,
                product.subtotal
            );
            
            // Actualizar total
            totalPrice += product.subtotal;
        });
        
        // Actualizar el total mostrado
        cartTotal.textContent = totalPrice.toFixed(2);
    }

    // Búsqueda de productos
    const searchProduct = document.getElementById('search-product');
    if (searchProduct) {
        searchProduct.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            productCards.forEach(card => {
                const productName = card.querySelector('.product-label').textContent.toLowerCase();
                const productCode = card.getAttribute('data-code') ? card.getAttribute('data-code').toLowerCase() : '';
                
                if (productName.includes(searchTerm) || productCode.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Calcular subtotal en el modal de tipo de producto
    function updateSubtotal() {
        if (!selectedProduct) return;
        
        const saleType = saleTypeSelect.value;
        const quantity = parseInt(productQuantityInput.value) || 1;
        let pricePerUnit = 0;
        
        // Obtener precio según el tipo de venta
        switch (saleType) {
            case 'unitario':
                pricePerUnit = parseFloat(selectedProduct.getAttribute('data-price-unit'));
                break;
            case 'media':
                pricePerUnit = parseFloat(selectedProduct.getAttribute('data-price-half'));
                break;
            case 'plancha':
                pricePerUnit = parseFloat(selectedProduct.getAttribute('data-price-full'));
                break;
        }
        
        pricePerUnitSpan.textContent = pricePerUnit.toFixed(2);
        const subtotal = pricePerUnit * quantity;
        subtotalAmountSpan.textContent = subtotal.toFixed(2);
    }

    // Eventos para actualizar subtotal
    if (saleTypeSelect) {
        saleTypeSelect.addEventListener('change', updateSubtotal);
    }
    
    if (productQuantityInput) {
        productQuantityInput.addEventListener('input', updateSubtotal);
    }

    // Evento para mostrar modal de tipo de venta al seleccionar un producto
    productCards.forEach(card => {
        card.addEventListener('click', function() {
            selectedProduct = this;
            productSelectedName.textContent = this.querySelector('.product-label').textContent;
            
            // Seleccionar por defecto el tipo de venta unitario
            saleTypeSelect.value = 'unitario';
            productQuantityInput.value = 1;
            
            // Actualizar subtotal
            updateSubtotal();
            
            // Mostrar modal
            productTypeModal.style.display = 'flex';
        });
    });

    // Cancelar selección de tipo de venta
    const cancelProductTypeBtn = document.getElementById('cancel-product-type');
    if (cancelProductTypeBtn) {
        cancelProductTypeBtn.addEventListener('click', function() {
            productTypeModal.style.display = 'none';
            selectedProduct = null;
        });
    }

    // Confirmar selección de tipo de venta
    const confirmProductTypeBtn = document.getElementById('confirm-product-type');
    if (confirmProductTypeBtn) {
        confirmProductTypeBtn.addEventListener('click', function() {
            if (!selectedProduct) return;
            
            const productId = selectedProduct.getAttribute('data-id');
            const productName = selectedProduct.querySelector('.product-label').textContent;
            const saleType = saleTypeSelect.value;
            const quantity = parseInt(productQuantityInput.value) || 1;
            let pricePerUnit = 0;
            
            // Obtener precio según el tipo de venta seleccionado
            switch (saleType) {
                case 'unitario':
                    pricePerUnit = parseFloat(selectedProduct.getAttribute('data-price-unit'));
                    break;
                case 'media':
                    pricePerUnit = parseFloat(selectedProduct.getAttribute('data-price-half'));
                    break;
                case 'plancha':
                    pricePerUnit = parseFloat(selectedProduct.getAttribute('data-price-full'));
                    break;
            }
            
            const subtotal = pricePerUnit * quantity;
            
            // Limpiar mensaje de carrito vacío si es la primera vez
            if (cartItems.querySelector('.cart-icon')) {
                cartItems.innerHTML = '';
            }
            
            // Crear identificador único para el producto según tipo de venta
            const cartItemId = `${productId}-${saleType}`;
            
            // Revisar si el producto ya existe en el carrito
            if (window.cartProducts[cartItemId]) {
                // Incrementar cantidad
                const cartItem = document.querySelector(`[data-cart-id="${cartItemId}"]`);
                const quantityElem = cartItem.querySelector('.item-quantity');
                let newQty = parseInt(quantityElem.textContent) + quantity;
                quantityElem.textContent = newQty;
                
                // Actualizar subtotal del producto
                const productSubtotal = cartItem.querySelector('.product-subtotal');
                const newSubtotal = pricePerUnit * newQty;
                productSubtotal.textContent = `$${newSubtotal.toFixed(2)}`;
                
                // Actualizar objeto de productos
                window.cartProducts[cartItemId].quantity = newQty;
                window.cartProducts[cartItemId].subtotal = newSubtotal;
            } else {
                // Crear nuevo item para el carrito
                createCartItem(cartItemId, productId, productName, pricePerUnit, saleType, quantity, subtotal);
                
                // Agregar a objeto de productos
                window.cartProducts[cartItemId] = {
                    id: productId,
                    name: productName,
                    price: pricePerUnit,
                    saleType: saleType,
                    quantity: quantity,
                    subtotal: subtotal
                };
            }
            
            // Actualizar total general
            totalPrice = 0;
            Object.values(window.cartProducts).forEach(product => {
                totalPrice += product.subtotal;
            });
            cartTotal.textContent = totalPrice.toFixed(2);
            
            console.log("Productos en carrito:", window.cartProducts);
            
            // Cerrar modal
            productTypeModal.style.display = 'none';
            selectedProduct = null;
        });
    }

    function createCartItem(cartItemId, productId, productName, productPrice, saleType, quantity, subtotal) {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.setAttribute('data-cart-id', cartItemId);
        cartItem.setAttribute('data-product-id', productId);
        cartItem.style.display = 'flex';
        cartItem.style.justifyContent = 'space-between';
        cartItem.style.alignItems = 'center';
        cartItem.style.padding = '10px 5px';
        cartItem.style.borderBottom = '1px solid #eee';
        cartItem.style.width = '100%';

        // Texto para tipo de venta
        let saleTypeText = 'Unitario';
        if (saleType === 'media') saleTypeText = 'Media plancha';
        if (saleType === 'plancha') saleTypeText = 'Plancha completa';

        const productInfo = document.createElement('div');
        productInfo.className = 'product-info';
        productInfo.innerHTML = `<div>${productName}</div><small>${saleTypeText} - $${productPrice.toFixed(2)}</small>`;

        const rightSide = document.createElement('div');
        rightSide.style.display = 'flex';
        rightSide.style.alignItems = 'center';
        rightSide.style.gap = '10px';

        const controls = document.createElement('div');
        controls.className = 'quantity-controls';
        controls.style.display = 'flex';
        controls.style.alignItems = 'center';

        const decreaseBtn = document.createElement('button');
        decreaseBtn.textContent = '-';
        decreaseBtn.style.width = '25px';
        decreaseBtn.style.height = '25px';
        decreaseBtn.style.border = '1px solid #ddd';
        decreaseBtn.style.background = 'white';
        decreaseBtn.style.borderRadius = '4px';
        decreaseBtn.style.cursor = 'pointer';

        const quantityDisplay = document.createElement('span');
        quantityDisplay.className = 'item-quantity';
        quantityDisplay.textContent = quantity;
        quantityDisplay.style.margin = '0 5px';
        quantityDisplay.style.minWidth = '20px';
        quantityDisplay.style.textAlign = 'center';

        const increaseBtn = document.createElement('button');
        increaseBtn.textContent = '+';
        increaseBtn.style.width = '25px';
        increaseBtn.style.height = '25px';
        increaseBtn.style.border = '1px solid #ddd';
        increaseBtn.style.background = 'white';
        increaseBtn.style.borderRadius = '4px';
        increaseBtn.style.cursor = 'pointer';

        controls.appendChild(decreaseBtn);
        controls.appendChild(quantityDisplay);
        controls.appendChild(increaseBtn);

        const subtotalDisplay = document.createElement('div');
        subtotalDisplay.className = 'product-subtotal';
        subtotalDisplay.textContent = `$${subtotal.toFixed(2)}`;
        subtotalDisplay.style.minWidth = '60px';
        subtotalDisplay.style.textAlign = 'right';

        const removeBtn = document.createElement('button');
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.style.background = 'none';
        removeBtn.style.border = 'none';
        removeBtn.style.color = '#dc3545';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.padding = '0 5px';

        rightSide.appendChild(controls);
        rightSide.appendChild(subtotalDisplay);
        rightSide.appendChild(removeBtn);

        cartItem.appendChild(productInfo);
        cartItem.appendChild(rightSide);

        cartItems.appendChild(cartItem);

        // Evento para disminuir cantidad
        decreaseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            let qty = parseInt(quantityDisplay.textContent);
            qty--;
            
            if (qty <= 0) {
                // Eliminar producto del carrito
                cartItems.removeChild(cartItem);
                totalPrice -= window.cartProducts[cartItemId].subtotal;
                delete window.cartProducts[cartItemId];
                
                // Si no quedan productos, mostrar mensaje
                if (Object.keys(window.cartProducts).length === 0) {
                    cartItems.innerHTML = `
                        <div class="cart-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                        <div class="cart-message">Agregue productos...</div>
                    `;
                }
            } else {
                // Actualizar cantidad y subtotal
                quantityDisplay.textContent = qty;
                const newSubtotal = productPrice * qty;
                subtotalDisplay.textContent = `$${newSubtotal.toFixed(2)}`;
                
                // Actualizar objeto de productos y total
                window.cartProducts[cartItemId].quantity = qty;
                window.cartProducts[cartItemId].subtotal = newSubtotal;
                totalPrice -= (productPrice);
            }
            
            cartTotal.textContent = totalPrice.toFixed(2);
            console.log("Productos en carrito (después de disminuir):", window.cartProducts);
        });

        // Evento para aumentar cantidad
        increaseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            let qty = parseInt(quantityDisplay.textContent);
            qty++;
            
            // Actualizar cantidad y subtotal
            quantityDisplay.textContent = qty;
            const newSubtotal = productPrice * qty;
            subtotalDisplay.textContent = `$${newSubtotal.toFixed(2)}`;
            
            // Actualizar objeto de productos y total
            window.cartProducts[cartItemId].quantity = qty;
            window.cartProducts[cartItemId].subtotal = newSubtotal;
            totalPrice += productPrice;
            
            cartTotal.textContent = totalPrice.toFixed(2);
            console.log("Productos en carrito (después de aumentar):", window.cartProducts);
        });

        // Evento para eliminar producto
        removeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Eliminar producto del carrito
            cartItems.removeChild(cartItem);
            totalPrice -= window.cartProducts[cartItemId].subtotal;
            delete window.cartProducts[cartItemId];
            
            // Si no quedan productos, mostrar mensaje
            if (Object.keys(window.cartProducts).length === 0) {
                cartItems.innerHTML = `
                    <div class="cart-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    <div class="cart-message">Agregue productos...</div>
                `;
            }
            
            cartTotal.textContent = totalPrice.toFixed(2);
            console.log("Productos en carrito (después de eliminar):", window.cartProducts);
        });
    }

    // =====================================================================
    // Código para los modales de clientes (versión corregida)
    // =====================================================================
    
    // Elementos para el modal de cliente
    const clienteBtn = document.getElementById('cliente-btn');
    const customerModal = document.getElementById('customer-modal');
    const cancelCustomerModal = document.getElementById('cancel-customer-modal');
    const customerRows = document.querySelectorAll('.customer-row');
    
    // Verificar que los elementos existen antes de agregar los eventos
    if (clienteBtn && customerModal) {
        // Evento para mostrar el modal
        clienteBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevenir comportamiento por defecto
            console.log('Botón cliente clickeado'); // Depuración
            customerModal.style.display = 'flex';
        });
        
        // Evento para cerrar el modal
        if (cancelCustomerModal) {
            cancelCustomerModal.addEventListener('click', function() {
                customerModal.style.display = 'none';
            });
        }
        
        // Eventos para las filas de clientes
        if (customerRows.length > 0) {
            customerRows.forEach(row => {
                row.addEventListener('click', function() {
                    const customerName = this.cells[0].textContent;
                    const customerId = this.getAttribute('data-id');
                    clienteBtn.textContent = customerName;
                    clienteBtn.setAttribute('data-customer-id', customerId);
                    customerModal.style.display = 'none';
                });
            });
        }
    } else {
        console.error('No se encontraron los elementos del modal de clientes');
    }
    
    // Búsqueda de clientes
    const customerSearch = document.getElementById('customer-search');
    if (customerSearch) {
        customerSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            document.querySelectorAll('.customer-row').forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const rfc = row.cells[1].textContent.toLowerCase();
                const phone = row.cells[2].textContent.toLowerCase();
                
                if (name.includes(searchTerm) || rfc.includes(searchTerm) || phone.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // =====================================================================
    // Código para agregar nuevo cliente desde el modal
    // =====================================================================
    
    // Modal para nuevo cliente
    const newCustomerBtn = document.getElementById('new-customer-btn');
    const customerAddModal = document.getElementById('customer-add-modal');
    const cancelAddCustomerModal = document.getElementById('cancel-add-customer-modal');
    const saveCustomer = document.getElementById('save-customer');
    const customerForm = document.getElementById('customer-form');
    
    if (newCustomerBtn && customerAddModal) {
        // Evento para mostrar el modal de agregar cliente
        newCustomerBtn.addEventListener('click', function() {
            customerModal.style.display = 'none';
            customerAddModal.style.display = 'flex';
        });
        
        // Evento para cancelar y volver al modal de clientes
        if (cancelAddCustomerModal) {
            cancelAddCustomerModal.addEventListener('click', function() {
                customerAddModal.style.display = 'none';
                customerModal.style.display = 'flex';
            });
        }
        
        // El evento de guardar ahora usará el envío normal del formulario
        // Se ha modificado customerForm para usar action="" method="post"
        // y se ha añadido un campo hidden con name="save_customer"
    }
    
    // Modal para añadir una nota
    const notaBtn = document.getElementById('nota-btn');
    const noteModal = document.getElementById('note-modal');
    const cancelNoteModal = document.getElementById('cancel-note-modal');
    const saveNote = document.getElementById('save-note');
    const noteText = document.getElementById('note-text');
    
    if (notaBtn && noteModal) {
        notaBtn.addEventListener('click', function() {
            noteModal.style.display = 'flex';
        });
        
        if (cancelNoteModal) {
            cancelNoteModal.addEventListener('click', function() {
                noteModal.style.display = 'none';
            });
        }
        
        if (saveNote && noteText) {
            saveNote.addEventListener('click', function() {
                if (noteText.value.trim() !== '') {
                    notaBtn.innerHTML = '<i class="fas fa-sticky-note"></i> ' + noteText.value.substring(0, 10) + '...';
                    notaBtn.setAttribute('data-note', noteText.value);
                }
                noteModal.style.display = 'none';
            });
        }
    }

    // Botón de pagar - Preparar datos para el procesamiento de la venta
    const pagarBtn = document.getElementById('btn-pagar');
    if (pagarBtn) {
        pagarBtn.addEventListener('click', function() {
            console.log("Botón PAGAR clickeado");
            console.log("Estado del carrito:", window.cartProducts);
            console.log("Número de productos:", Object.keys(window.cartProducts).length);
            
            // Verificar si hay productos en el carrito
            if (Object.keys(window.cartProducts).length === 0) {
                alert('No hay productos en el carrito');
                return;
            }
            
            // Recopilar datos para procesar la venta
            const clienteBtn = document.getElementById('cliente-btn');
            const notaBtn = document.getElementById('nota-btn');
            const cartTotal = document.getElementById('cart-total');
            
            const ventaData = {
                cliente_id: clienteBtn.getAttribute('data-customer-id') || 0,
                cliente_nombre: clienteBtn.textContent !== 'Cliente' ? clienteBtn.textContent : 'Cliente General',
                nota: notaBtn.getAttribute('data-note') || '',
                total: parseFloat(cartTotal.textContent),
                productos: Object.values(window.cartProducts)
            };
            
            console.log("Datos de venta a guardar:", ventaData);
            
            // Guardar datos en sessionStorage para usarlos en la página de resumen
            sessionStorage.setItem('venta_actual', JSON.stringify(ventaData));
            console.log("Datos guardados en sessionStorage");
            
            // Redirigir a la página de resumen y pago
            window.location.href = 'sumary.php';
        });
    }

    // Cerrar los modales al hacer click fuera de ellos
    window.addEventListener('click', function(event) {
        if (event.target === customerModal) {
            customerModal.style.display = 'none';
        }
        if (event.target === noteModal) {
            noteModal.style.display = 'none';
        }
        if (event.target === customerAddModal) {
            customerAddModal.style.display = 'none';
        }
        if (event.target === productTypeModal) {
            productTypeModal.style.display = 'none';
            selectedProduct = null;
        }
    });

    // Función para mostrar notificaciones tipo toast
    function showToast(message, type = 'info') {
        // Crear elemento toast
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        
        // Contenido del toast según el tipo
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'times-circle';
        
        toast.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="notification-content">
                ${message}
            </div>
            <button class="close-notification">&times;</button>
            <div class="progress-bar"></div>
        `;
        
        // Agregar al body
        document.body.appendChild(toast);
        
        // Mostrar con animación
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Configurar cierre automático
        const timeout = setTimeout(() => {
            closeToast(toast);
        }, 4000);
        
        // Evento para cerrar manualmente
        const closeBtn = toast.querySelector('.close-notification');
        closeBtn.addEventListener('click', () => {
            clearTimeout(timeout);
            closeToast(toast);
        });
    }
    
    // Función para cerrar toast con animación
    function closeToast(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 500);
    }

    // Añadir estilos CSS para las notificaciones toast si no existen
    if (!document.getElementById('toast-styles')) {
        const toastStyles = document.createElement('style');
        toastStyles.id = 'toast-styles';
        toastStyles.innerHTML = `
            .toast-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                max-width: 350px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.5s ease;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .toast-notification.show {
                opacity: 1;
                transform: translateY(0);
            }

            .toast-notification.success {
                background-color: #28a745;
                border-left: 5px solid #1e7e34;
            }

            .toast-notification.error {
                background-color: #dc3545;
                border-left: 5px solid #bd2130;
            }

            .toast-notification.info {
                background-color: #17a2b8;
                border-left: 5px solid #138496;
            }

            .toast-notification .notification-icon {
                display: flex;
                align-items: center;
                margin-right: 15px;
                font-size: 20px;
            }

            .toast-notification .notification-content {
                flex: 1;
            }

            .toast-notification .close-notification {
                background: transparent;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.3s;
                padding: 0;
                margin-left: 10px;
            }

            .toast-notification .close-notification:hover {
                opacity: 1;
            }

            .toast-notification .progress-bar {
                position: absolute;
                left: 0;
                bottom: 0;
                height: 3px;
                background-color: rgba(255, 255, 255, 0.7);
                width: 100%;
                animation: progress-animation 4s linear;
            }

            @keyframes progress-animation {
                0% { width: 100%; }
                100% { width: 0%; }
            }
        `;
        document.head.appendChild(toastStyles);
    }
    
    // Debug para comprobar si el carrito ya está inicializado
    console.log("Script cargado. Estado inicial del carrito:", window.cartProducts);
});