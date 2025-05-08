/**
 * Archivo JavaScript para sumary.php
 * Sistema POS de Bebidas - Resumen de Venta
 */

document.addEventListener('DOMContentLoaded', function() {
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

    // Cargar datos de la venta desde sessionStorage
    const ventaData = JSON.parse(sessionStorage.getItem('venta_actual') || '{}');
    console.log('Datos de venta cargados:', ventaData);
    
    // Referencias a elementos del DOM
    const cartItemsContainer = document.getElementById('cart-items-container');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryTax = document.getElementById('summary-tax');
    const summaryTotal = document.getElementById('summary-total');
    const customerInfo = document.getElementById('customer-info');
    const saleNotes = document.getElementById('sale-notes');
    
    // Referencias a elementos de pago
    const paymentMethods = document.querySelectorAll('input[name="payment-method"]');
    const cashPaymentDetails = document.getElementById('cash-payment-details');
    const cardPaymentDetails = document.getElementById('card-payment-details');
    const transferPaymentDetails = document.getElementById('transfer-payment-details');
    const cashAmount = document.getElementById('cash-amount');
    const cashChange = document.getElementById('cash-change');
    
    // Referencias a botones y modales
    const btnBack = document.getElementById('btn-back');
    const btnCancelSale = document.getElementById('btn-cancel-sale');
    const btnCompleteSale = document.getElementById('btn-complete-sale');
    const confirmModal = document.getElementById('confirm-modal');
    const btnModalCancel = document.getElementById('btn-modal-cancel');
    const btnModalConfirm = document.getElementById('btn-modal-confirm');
    const successModal = document.getElementById('success-modal');
    const saleNumber = document.getElementById('sale-number');
    const saleTotal = document.getElementById('sale-total');
    const btnPrintTicket = document.getElementById('btn-print-ticket');
    const btnNewSale = document.getElementById('btn-new-sale');
    
    // Variables para guardar los totales calculados
    let subtotalVenta = 0;
    let impuestosVenta = 0;
    let totalVenta = 0;
    
    // Inicializar UI con datos de la venta
    function initializeUI() {
        if (!ventaData || !ventaData.productos || ventaData.productos.length === 0) {
            showToast('No hay datos de venta disponibles', 'error');
            setTimeout(() => {
                window.location.href = 'productsSale.php';
            }, 2000);
            return;
        }
        
        // Mostrar productos en el carrito
        renderCartItems();
        
        // Mostrar información del cliente
        renderCustomerInfo();
        
        // Inicializar eventos
        initializeEvents();
        
        // Establecer nota si existe
        if (ventaData.nota) {
            saleNotes.value = ventaData.nota;
        }
    }

    // Renderizar productos en el carrito
    function renderCartItems() {
        cartItemsContainer.innerHTML = '';
        
        subtotalVenta = 0;
        
        ventaData.productos.forEach(producto => {
            // Crear elemento para el producto
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            
            // Convertir tipo de venta a texto legible
            let saleTypeText = 'Unitario';
            if (producto.saleType === 'media') saleTypeText = 'Media plancha';
            if (producto.saleType === 'plancha') saleTypeText = 'Plancha completa';
            
            // Estructura del item
            cartItem.innerHTML = `
                <div class="item-details">
                    <div class="item-name">${producto.name}</div>
                    <div class="item-type">${saleTypeText} - $${producto.price.toFixed(2)}</div>
                </div>
                <div class="item-quantity">${producto.quantity}</div>
                <div class="item-price">$${producto.subtotal.toFixed(2)}</div>
            `;
            
            cartItemsContainer.appendChild(cartItem);
            subtotalVenta += producto.subtotal;
        });
        
        // Calcular el IVA y total
        impuestosVenta = subtotalVenta * 0.16; // 16% de IVA
        totalVenta = subtotalVenta + impuestosVenta;
        
        // Actualizar los totales en la UI
        summarySubtotal.textContent = `$${subtotalVenta.toFixed(2)}`;
        summaryTax.textContent = `$${impuestosVenta.toFixed(2)}`;
        summaryTotal.textContent = `$${totalVenta.toFixed(2)}`;
        
        // Actualizar monto total para cálculos de cambio
        cashAmount.setAttribute('data-total', totalVenta.toFixed(2));
        cashAmount.setAttribute('min', totalVenta.toFixed(2));
        
        // Mostrar subtotal en el modal de éxito también
        saleTotal.textContent = `$${totalVenta.toFixed(2)}`;
    }

    // Renderizar información del cliente
    function renderCustomerInfo() {
        customerInfo.innerHTML = '';
        
        let customerName = ventaData.cliente_nombre || 'Cliente General';
        let customerId = ventaData.cliente_id || '0';
        
        // Crear elemento para la información del cliente
        const customerDetails = document.createElement('div');
        customerDetails.className = 'customer-details';
        
        // Estructura de la información del cliente
        customerDetails.innerHTML = `
            <div class="customer-detail">
                <i class="fas fa-user"></i>
                <span>${customerName}</span>
            </div>
            <div class="customer-detail">
                <i class="fas fa-id-card"></i>
                <span>ID: ${customerId}</span>
            </div>
        `;
        
        customerInfo.appendChild(customerDetails);
    }

    // Inicializar eventos
    function initializeEvents() {
        // Eventos para los métodos de pago
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                // Ocultar todos los paneles de detalles de pago
                cashPaymentDetails.style.display = 'none';
                cardPaymentDetails.style.display = 'none';
                transferPaymentDetails.style.display = 'none';
                
                // Mostrar el panel correspondiente al método seleccionado
                if (this.value === 'cash') {
                    cashPaymentDetails.style.display = 'block';
                } else if (this.value === 'card') {
                    cardPaymentDetails.style.display = 'block';
                } else if (this.value === 'transfer') {
                    transferPaymentDetails.style.display = 'block';
                }
            });
        });
        
        // Evento para calcular el cambio
        cashAmount.addEventListener('input', function() {
            const receivedAmount = parseFloat(this.value) || 0;
            const totalAmount = parseFloat(this.getAttribute('data-total')) || 0;
            
            const change = receivedAmount - totalAmount;
            cashChange.value = change > 0 ? change.toFixed(2) : '0.00';
        });
        
        // Evento para botón de volver
        if (btnBack) {
            btnBack.addEventListener('click', function() {
                window.location.href = 'productsSale.php';
            });
        }
        
        // Evento para botón de cancelar venta
        if (btnCancelSale) {
            btnCancelSale.addEventListener('click', function() {
                if (confirm('¿Estás seguro de que deseas cancelar esta venta?')) {
                    // Limpiar datos de la venta
                    sessionStorage.removeItem('venta_actual');
                    window.location.href = 'productsSale.php';
                }
            });
        }
        
        // Evento para botón de completar venta
        if (btnCompleteSale) {
            btnCompleteSale.addEventListener('click', function() {
                // Validar que se haya seleccionado un método de pago
                const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
                
                if (paymentMethod === 'cash') {
                    const receivedAmount = parseFloat(cashAmount.value) || 0;
                    const totalAmount = parseFloat(cashAmount.getAttribute('data-total')) || 0;
                    
                    if (receivedAmount < totalAmount) {
                        showToast('El monto recibido es menor al total de la venta', 'error');
                        return;
                    }
                    
                    if (receivedAmount === 0) {
                        showToast('Ingresa el monto recibido', 'error');
                        return;
                    }
                } else if (paymentMethod === 'card') {
                    const reference = document.getElementById('card-reference').value.trim();
                    if (!reference) {
                        showToast('Ingresa la referencia de la tarjeta', 'error');
                        return;
                    }
                } else if (paymentMethod === 'transfer') {
                    const reference = document.getElementById('transfer-reference').value.trim();
                    if (!reference) {
                        showToast('Ingresa la referencia de la transferencia', 'error');
                        return;
                    }
                }
                
                // Mostrar modal de confirmación
                confirmModal.style.display = 'flex';
            });
        }
        
        // Eventos para los botones del modal de confirmación
        if (btnModalCancel) {
            btnModalCancel.addEventListener('click', function() {
                confirmModal.style.display = 'none';
            });
        }
        
        if (btnModalConfirm) {
            btnModalConfirm.addEventListener('click', function() {
                // Cerrar modal de confirmación
                confirmModal.style.display = 'none';
                
                // Recopilar datos de la venta
                const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
                
                // Mapear los valores del formulario al formato esperado por el servidor
                let metodoFormatoDB = 'Efectivo'; // Valor por defecto
                if (paymentMethod === 'card') metodoFormatoDB = 'Tarjeta';
                if (paymentMethod === 'transfer') metodoFormatoDB = 'Transferencia';
                
                let referencia = '';
                if (paymentMethod === 'cash') {
                    referencia = `Efectivo: $${cashAmount.value}, Cambio: $${cashChange.value}`;
                } else if (paymentMethod === 'card') {
                    referencia = `Terminal: ${document.getElementById('card-terminal').value}, Ref: ${document.getElementById('card-reference').value}`;
                } else if (paymentMethod === 'transfer') {
                    referencia = document.getElementById('transfer-reference').value;
                }
                
                // Preparar datos para enviar al procesar la venta
                const datosVenta = {
                    cliente_id: parseInt(ventaData.cliente_id) || 1, // Convertir a entero y usar 1 (Cliente General) si no es válido
                    cliente_nombre: ventaData.cliente_nombre || 'Cliente General',
                    productos: ventaData.productos,
                    subtotal: subtotalVenta,
                    impuestos: impuestosVenta,
                    total: totalVenta,
                    metodo_pago: metodoFormatoDB,
                    referencia_pago: referencia,
                    nota_cierre: saleNotes.value || ventaData.nota || '',
                    // Añadir el ID del usuario de la sesión si está disponible
                    usuario_id: ventaData.usuario_id || 0
                };
                
                // Mostrar un toast de procesamiento
                showToast('Procesando venta...', 'info');
                
                console.log("Enviando datos al servidor:", datosVenta);
                
                // Enviar datos al controlador mediante fetch API
                fetch('../controllers/procesar_venta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datosVenta)
                })
                .then(response => {
                    console.log('Status de la respuesta:', response.status);
                    return response.text().then(text => {
                        try {
                            // Intentar parsear como JSON
                            console.log('Texto de respuesta completo:', text);
                            return JSON.parse(text);
                        } catch (error) {
                            // Si no es un JSON válido, muestra el texto completo
                            console.error('La respuesta no es un JSON válido:', text);
                            throw new Error('La respuesta del servidor no es un JSON válido');
                        }
                    });
                })
                .then(data => {
                    console.log('Datos parseados de la respuesta:', data);
                    
                    if (data.success) {
                        // Actualizar información en el modal de éxito
                        saleNumber.textContent = `V-${data.ventaID ? data.ventaID.toString().padStart(4, '0') : '0000'}`;
                        saleTotal.textContent = `$${data.total ? data.total.toFixed(2) : totalVenta.toFixed(2)}`;
                        
                        // Mostrar modal de éxito
                        successModal.style.display = 'flex';
                        showToast('Venta registrada exitosamente', 'success');
                    } else {
                        showToast(`Error: ${data.message || 'Error desconocido'}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    showToast(`Error: ${error.message}`, 'error');
                });
            });
        }
        
        // Eventos para el modal de éxito
        if (btnPrintTicket) {
            btnPrintTicket.addEventListener('click', function() {
                printTicket();
            });
        }
        
        if (btnNewSale) {
            btnNewSale.addEventListener('click', function() {
                // Limpiar datos de la venta actual
                sessionStorage.removeItem('venta_actual');
                window.location.href = 'productsSale.php';
            });
        }
        
        // Cerrar modales si se hace clic fuera de ellos
        window.addEventListener('click', function(event) {
            if (event.target === confirmModal) {
                confirmModal.style.display = 'none';
            }
            if (event.target === successModal) {
                successModal.style.display = 'none';
            }
        });
    }
    
    // Imprimir ticket
    function printTicket() {
        // Crear contenido HTML para el ticket
        let ticketContent = `
            <html>
            <head>
                <title>Ticket de Venta</title>
                <style>
                    body {
                        font-family: monospace;
                        width: 80mm;
                        margin: 0 auto;
                        padding: 5mm;
                        font-size: 12px;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 10px;
                    }
                    .logo {
                        font-size: 20px;
                        font-weight: bold;
                    }
                    .divider {
                        border-top: 1px dashed #000;
                        margin: 5px 0;
                    }
                    .item {
                        display: flex;
                        justify-content: space-between;
                    }
                    .total {
                        font-weight: bold;
                        margin-top: 10px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        font-size: 10px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="logo">SIAP</div>
                    <div>Sistema de Inventario Asosiación Pepsi</div>
                    <div>${new Date().toLocaleString()}</div>
                </div>
                <div class="divider"></div>
                <div>
                    <p>Cliente: ${ventaData.cliente_nombre || 'Cliente General'}</p>
                    <p>Ticket: ${saleNumber.textContent}</p>
                </div>
                <div class="divider"></div>
        `;
        
        // Agregar productos
        ventaData.productos.forEach(producto => {
            // Convertir tipo de venta a texto legible
            let saleTypeText = 'Unitario';
            if (producto.saleType === 'media') saleTypeText = 'Media plancha';
            if (producto.saleType === 'plancha') saleTypeText = 'Plancha completa';
            
            ticketContent += `
                <div class="item">
                    <div>${producto.quantity} x ${producto.name} (${saleTypeText})</div>
                    <div>$${producto.subtotal.toFixed(2)}</div>
                </div>
            `;
        });
        
        // Agregar totales
        ticketContent += `
                <div class="divider"></div>
                <div class="item">
                    <div>Subtotal:</div>
                    <div>$${subtotalVenta.toFixed(2)}</div>
                </div>
                <div class="item">
                    <div>IVA (16%):</div>
                    <div>$${impuestosVenta.toFixed(2)}</div>
                </div>
                <div class="item total">
                    <div>TOTAL:</div>
                    <div>$${totalVenta.toFixed(2)}</div>
                </div>
                <div class="divider"></div>
                <div>
                    <p>Método de pago: ${document.querySelector('input[name="payment-method"]:checked').value === 'cash' ? 'Efectivo' : 
                                      document.querySelector('input[name="payment-method"]:checked').value === 'card' ? 'Tarjeta' : 'Transferencia'}</p>
                </div>
        `;
        
        // Si hay notas, agregarlas
        if (saleNotes.value.trim()) {
            ticketContent += `
                <div class="divider"></div>
                <div>
                    <p>Notas: ${saleNotes.value}</p>
                </div>
            `;
        }
        
        // Pie de página
        ticketContent += `
                <div class="divider"></div>
                <div class="footer">
                    <p>¡Gracias por su compra!</p>
                </div>
            </body>
            </html>
        `;
        
        // Abrir ventana de impresión
        const printWindow = window.open('', 'PRINT', 'height=600,width=800');
        printWindow.document.write(ticketContent);
        printWindow.document.close();
        printWindow.focus();
        
        // Esperar a que la ventana se cargue completamente antes de imprimir
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
    
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
    
    // Añadir estilos para las notificaciones toast si no existen
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
    
    // Inicializar la página
    initializeUI();
});