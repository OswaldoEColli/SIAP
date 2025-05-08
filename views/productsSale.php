<?php

// Iniciar sesión solo si no existe una sesión activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener el ID del usuario logueado
$usuarioID = isset($_SESSION["usuario_id"]) ? $_SESSION["usuario_id"] : 0;

require_once '../config/db_config.php'; // Conexión a la base de datos

// Gestionar la creación de cliente mediante POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_customer'])) {
    // Cargar la clase Cliente
    require_once "../models/Cliente.php";
    
    // Obtener datos del formulario
    $nombre_completo = isset($_POST['customer_name']) ? $_POST['customer_name'] : "";
    $rfc = isset($_POST['customer_rfc']) ? $_POST['customer_rfc'] : "";
    $telefono = isset($_POST['customer_phone']) ? $_POST['customer_phone'] : "";
    $email = isset($_POST['customer_email']) ? $_POST['customer_email'] : "";
    $direccion = isset($_POST['customer_address']) ? $_POST['customer_address'] : "";
    $estado = isset($_POST['customer_status']) ? ($_POST['customer_status'] === 'active' ? 1 : 0) : 1;
    
    // Separar nombre y apellidos
    $nombre_parts = explode(' ', $nombre_completo, 2);
    $nombre = $nombre_parts[0];
    $apellidos = isset($nombre_parts[1]) ? $nombre_parts[1] : "";
    
    // Crear cliente
    $cliente = new Cliente($mysqli);
    $clienteID = $cliente->create($nombre, $apellidos, $telefono, $email, $direccion, $rfc, $estado);
    
    if ($clienteID) {
        // Almacenar en la sesión para usarlo después
        $_SESSION['new_customer_id'] = $clienteID;
        $_SESSION['new_customer_name'] = $nombre_completo;
        
        // Redirigir para evitar reenvío del formulario
        header("Location: " . $_SERVER['PHP_SELF'] . "?customer_added=true");
        exit;
    }
}




// Verificación de autenticación (se puede descomentar cuando esté implementado)
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

// Si no hay una caja abierta y se intenta acceder directamente, redirigir a sales.php
if (!isset($_SESSION['caja_actual_id'])) {
    // Verificar en la base de datos si hay alguna caja abierta
    $sql = "SELECT reporteID, montoInicial FROM ReporteCaja WHERE estado = 'Abierta' LIMIT 1";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['caja_actual_id'] = $row['reporteID'];
        $_SESSION['monto_inicial'] = $row['montoInicial'];
    } else {
        // Redireccionar si no hay una caja abierta
        header('Location: sales.php');
        exit;
    }
}

// Obtener información de la caja actual con verificación
$cajaID = isset($_SESSION['caja_actual_id']) ? $_SESSION['caja_actual_id'] : 0;
$montoInicial = isset($_SESSION['monto_inicial']) ? $_SESSION['monto_inicial'] : 0;
$notaCaja = isset($_SESSION['nota_caja']) ? $_SESSION['nota_caja'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema POS de Bebidas</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/productsSaleStyle.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            
        </div>

        <a href="home.php" class="menu-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>

        <a href="sales.php" class="menu-item active">
            <i class="fas fa-dollar-sign"></i>
            <span>Ventas</span>
        </a>

        <a href="customer.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span>Clientes</span>
        </a>

        <a href="reports.php" class="menu-item">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
        </a>

        <a href="inventory.php" class="menu-item">
            <i class="fas fa-boxes"></i>
            <span>Inventario</span>
        </a>

        <a href="branches.php" class="menu-item">
            <i class="fas fa-map-marker-alt"></i>
            <span>Sucursales</span>
        </a>

        <a href="settings.php" class="menu-item">
            <i class="fas fa-cog"></i>
            <span>Ajustes</span>
        </a>

        <button class="logout-btn" id="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Salir</span>
        </button>

        <!-- Botón para cerrar caja -->
        <a href="#" class="menu-item" id="btn-cerrar-caja" style="color: #d9534f;">
            <i class="fas fa-cash-register"></i>
            <span>Cerrar Caja</span>
        </a>
    </div>

    <!-- Logo flotante que se mueve con el sidebar -->
    <div class="floating-logo" id="floating-logo">
        <div class="logo">SIAP </div>
        <img src="../photos/logo 3.png" alt="Logo SIAP" class="logo-img">
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header">
                <div class="nav-buttons">
                    <div class="nav-button active">Registrar</div>
                    <div class="nav-button">Órdenes</div>
                </div>
                <img src="https://innovacraft.com/wp-content/uploads/2023/05/innovacraft-logo.png" alt="InnovaCraft" class="logo">
                <input type="text" class="search-bar" placeholder="Buscar producto" id="search-product">
            </div>
            
            <div class="content">
                <div class="cart-section">
                    <div class="cart-items">
                        <div class="cart-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                        <div class="cart-message">Agregue productos...</div>
                    </div>

                    <!-- Total above the divider line -->
                    <div class="cart-footer">
                        <div class="cart-total">
                            <span>Total: </span>
                            <span id="cart-total">0.00</span>
                        </div>
                    </div>

                    <!-- Buttons under the line -->
                    <div class="cart-buttons">
                        <button class="cart-button" id="cliente-btn">Cliente</button>
                        <button class="cart-button" id="nota-btn">Nota</button>
                        <button class="cart-button" id="btn-pagar" style="background-color: #273469; color: white; border-radius: 8px; padding: 10px 20px; border: none; cursor: pointer;">PAGAR</button>
                    </div>
                </div>
                
                <div class="products-grid">
                    <?php
                    // Consulta para obtener productos activos
                    $sql = "SELECT productoID, codigo, nombre, descripcion, 
                            precioVentaPlancha, precioVentaMediaPlancha, precioVentaUnitario, 
                            unidadesPorPlancha, imagen 
                            FROM Producto 
                            WHERE activo = 1 
                            ORDER BY nombre ASC";
                    
                    $result = $mysqli->query($sql);
                    
                    // Definir clases de fondo para alternar entre productos
                    $bgClasses = [
                        'pepsi-bg', 'pepsi-light-bg', 'manzanita-bg', 
                        'mirinda-bg', 'sangria-bg', 'seven-up-bg'
                    ];
                    $bgIndex = 0;
                    
                    if ($result && $result->num_rows > 0) {
                        // Generar tarjetas de productos dinámicamente desde la base de datos
                        while ($product = $result->fetch_assoc()) {
                            // Usar precios de unitario por defecto (puedes cambiarlo si prefieres otro)
                            $priceToShow = $product['precioVentaUnitario'];
                            
                            // Ruta de imagen predeterminada si no hay imagen en la base de datos
                            $imagePath = !empty($product['imagen']) ? $product['imagen'] : '../photos/default-product.png';
                            
                            // Asignar clases de fondo de manera cíclica
                            $bgClass = $bgClasses[$bgIndex % count($bgClasses)];
                            $bgIndex++;
                            
                            // Crear atributos de datos para precios y detalles del producto
                            $dataAttrs = 'data-id="'.$product['productoID'].'" ';
                            $dataAttrs .= 'data-price-unit="'.$product['precioVentaUnitario'].'" ';
                            $dataAttrs .= 'data-price-half="'.$product['precioVentaMediaPlancha'].'" ';
                            $dataAttrs .= 'data-price-full="'.$product['precioVentaPlancha'].'" ';
                            $dataAttrs .= 'data-price="'.$priceToShow.'" ';
                            $dataAttrs .= 'data-code="'.$product['codigo'].'" ';
                            
                            echo '<div class="product-card" '.$dataAttrs.'>';
                            echo '    <div class="product-image '.$bgClass.'">';
                            echo '        <img src="'.$imagePath.'" alt="'.$product['nombre'].'">';
                            echo '    </div>';
                            echo '    <div class="product-label">'.$product['nombre'].'</div>';
                            echo '    <div class="product-price">$'.number_format($priceToShow, 2).'</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-products">No hay productos disponibles en la base de datos.</div>';
                    }
                    ?>
                    
                    <!-- Add new product button -->
                    <div class="add-button">
                        <div class="add-icon">+</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar cliente -->
    <div class="customer-modal" id="customer-modal" style="display: none;">
        <div class="modal-content">
            <h2>Seleccionar Cliente</h2>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="customer-search" placeholder="Buscar cliente por nombre, RFC o teléfono...">
            </div>

            <div class="customers-table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Nombre</th>
                            <th><i class="fas fa-id-card"></i> RFC</th>
                            <th><i class="fas fa-phone"></i> Teléfono</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-circle-check"></i> Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Consultar clientes desde la base de datos
                        $sqlClientes = "SELECT clienteID, nombre, apellidos, telefono, email, esRecurrente, rfc 
                                      FROM Cliente 
                                      ORDER BY nombre ASC";
                        
                        $resultClientes = $mysqli->query($sqlClientes);
                        
                        if ($resultClientes && $resultClientes->num_rows > 0) {
                            // Generar filas de clientes dinámicamente desde la base de datos
                            while ($customer = $resultClientes->fetch_assoc()) {
                                $fullName = $customer['nombre'] . ' ' . $customer['apellidos'];
                                $status = $customer['esRecurrente'] ? 'Activo' : 'Inactivo';
                                $statusClass = $customer['esRecurrente'] ? 'status-active' : 'status-inactive';
                                
                                echo '<tr class="customer-row" data-id="'.$customer['clienteID'].'">';
                                echo '    <td>'.$fullName.'</td>';
                                echo '    <td>'.($customer['rfc'] ?? '-').'</td>';
                                echo '    <td>'.$customer['telefono'].'</td>';
                                echo '    <td>'.$customer['email'].'</td>';
                                echo '    <td><span class="status-badge '.$statusClass.'">'.$status.'</span></td>';
                                echo '</tr>';
                            }
                        } else {
                            // Si no hay clientes, mostrar algunos clientes por defecto
                            $customers = [
                                ['id' => 1, 'name' => 'María González', 'rfc' => 'GOGM8804056P8', 'phone' => '55 1234 5678', 'email' => 'maria@ejemplo.com', 'status' => 'Activo'],
                                ['id' => 2, 'name' => 'Juan Pérez', 'rfc' => 'PEPJ760812AB5', 'phone' => '55 8765 4321', 'email' => 'juan@ejemplo.com', 'status' => 'Activo'],
                                ['id' => 3, 'name' => 'Lucia Ramírez', 'rfc' => 'RAML9002233M7', 'phone' => '55 2233 4455', 'email' => 'lucia@ejemplo.com', 'status' => 'Inactivo'],
                                ['id' => 4, 'name' => 'Roberto Sánchez', 'rfc' => 'SAGR850615PS1', 'phone' => '55 6677 8899', 'email' => 'roberto@ejemplo.com', 'status' => 'Activo'],
                                ['id' => 5, 'name' => 'Ana López', 'rfc' => 'LOPA780930HR2', 'phone' => '55 1122 3344', 'email' => 'ana@ejemplo.com', 'status' => 'Activo']
                            ];

                            foreach($customers as $customer) {
                                $statusClass = ($customer['status'] === 'Activo') ? 'status-active' : 'status-inactive';
                                echo '<tr class="customer-row" data-id="'.$customer['id'].'">';
                                echo '    <td>'.$customer['name'].'</td>';
                                echo '    <td>'.$customer['rfc'].'</td>';
                                echo '    <td>'.$customer['phone'].'</td>';
                                echo '    <td>'.$customer['email'].'</td>';
                                echo '    <td><span class="status-badge '.$statusClass.'">'.$customer['status'].'</span></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="modal-footer">
                <button id="cancel-customer-modal">Cancelar</button>
                <button id="new-customer-btn">Nuevo Cliente</button>
            </div>
        </div>
    </div>

    <!-- Modal para añadir una nota -->
    <div class="note-modal" id="note-modal" style="display: none;">
        <div class="modal-content">
            <h2>Añadir Nota</h2>
            
            <div class="form-group">
                <label for="note-text">Nota para la venta:</label>
                <textarea id="note-text" placeholder="Escriba su nota aquí..." rows="5"></textarea>
            </div>
            
            <div class="modal-footer">
                <button id="cancel-note-modal">Cancelar</button>
                <button id="save-note">Guardar</button>
            </div>
        </div>
    </div>
    
    <div class="customer-add-modal" id="customer-add-modal" style="display: none;">
    <div class="modal-content">
        <h2>Nuevo Cliente</h2>
        
        <form method="post" action="" id="customer-form">
            <div class="form-group">
                <label for="customer-name">Nombre completo</label>
                <input type="text" id="customer-name" name="customer_name" placeholder="Nombre del cliente" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="customer-rfc">RFC</label>
                    <input type="text" id="customer-rfc" name="customer_rfc" placeholder="RFC">
                </div>
                <div class="form-group">
                    <label for="customer-phone">Teléfono</label>
                    <input type="text" id="customer-phone" name="customer_phone" placeholder="Teléfono" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="customer-email">Correo electrónico</label>
                <input type="email" id="customer-email" name="customer_email" placeholder="Correo electrónico">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="customer-address">Dirección</label>
                    <input type="text" id="customer-address" name="customer_address" placeholder="Dirección">
                </div>
                <div class="form-group">
                    <label for="customer-status">Estado</label>
                    <select id="customer-status" name="customer_status">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </div>
            </div>
            
            <input type="hidden" name="save_customer" value="1">
            
            <div class="modal-footer">
                <button type="button" id="cancel-add-customer-modal">Cancelar</button>
                <button type="submit" id="save-customer">Guardar</button>
            </div>
        </form>
    </div>
</div>

    <!-- Modal para seleccionar tipo de venta -->
    <div class="product-type-modal" id="product-type-modal" style="display:none;">
        <div class="modal-content">
            <h2>Seleccionar tipo de venta</h2>
            
            <div id="product-selected-name" style="margin-bottom: 15px; font-weight: bold;"></div>
            
            <div class="form-group">
                <label>Tipo de venta:</label>
                <select id="sale-type">
                    <option value="unitario">Unitario</option>
                    <option value="media">Media plancha</option>
                    <option value="plancha">Plancha completa</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Precio unitario: $<span id="price-per-unit">0.00</span></label>
            </div>
            
            <div class="form-group">
                <label>Cantidad:</label>
                <input type="number" id="product-quantity" value="1" min="1">
            </div>
            
            <div class="form-group">
                <label>Subtotal: $<span id="subtotal-amount">0.00</span></label>
            </div>
            
            <div class="modal-footer">
                <button id="cancel-product-type">Cancelar</button>
                <button id="confirm-product-type">Agregar</button>
            </div>
        </div>
    </div>

    <!-- Modal para cerrar caja -->
    <div class="customer-modal" id="cerrar-caja-modal" style="display: none;">
        <div class="modal-content">
            <h2><i class="fas fa-cash-register"></i> Cerrar Caja</h2>
            
            <div class="corte-caja-info">
                <div class="info-row">
                    <span>Monto Inicial:</span>
                    <span id="monto-inicial">$<?php echo number_format($montoInicial, 2); ?></span>
                </div>
                <div class="info-row">
                    <span>Total Ventas:</span>
                    <span id="total-ventas">$0.00</span>
                </div>
                <div class="info-row">
                    <span>Efectivo en Caja:</span>
                    <div class="input-with-icon">
                        <span>$</span>
                        <input type="number" id="efectivo-caja" step="0.01" min="0">
                    </div>
                </div>
                <div class="info-row total">
                    <span>Diferencia:</span>
                    <span id="diferencia-caja">$0.00</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="nota-cierre">Nota de cierre:</label>
                <textarea id="nota-cierre" placeholder="Observaciones sobre el cierre de caja..." rows="3"></textarea>
            </div>
            
            <div class="modal-footer">
                <button id="cancelar-cierre-caja">Cancelar</button>
                <button id="confirmar-cierre-caja" style="background-color: #d9534f; color: white;">Cerrar Caja</button>
            </div>
        </div>
    </div>

    <!-- Incluir archivo JavaScript externo -->
    <script src="../js/productsSaleScript.js"></script>

    <!-- Script adicional para manejar el evento de cierre de sesión y cierre de caja -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Evento para el botón de cierre de sesión
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                    // Limpiar almacenamiento local antes de cerrar sesión
                    sessionStorage.clear();
                    localStorage.clear();
                    
                    // Redirigir a la página de cierre de sesión
                    window.location.href = '../controllers/logout.php';
                }
            });
        }
        
        // Asegurar que el botón de pagar funcione correctamente
        const btnPagar = document.getElementById('btn-pagar');
        if (btnPagar) {
            btnPagar.addEventListener('click', function() {
                const cartProducts = window.cartProducts || {};
                
                if (Object.keys(cartProducts).length === 0) {
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
                    productos: Object.values(cartProducts)
                };
                
                console.log("Datos de venta a guardar:", ventaData);
                
                // Guardar datos en sessionStorage para usarlos en la página de resumen
                sessionStorage.setItem('venta_actual', JSON.stringify(ventaData));
                
                // Redirigir a la página de resumen y pago
                window.location.href = 'sumary.php';
            });
        }

        // Código para el modal de cierre de caja
        const btnCerrarCaja = document.getElementById('btn-cerrar-caja');
        const cerrarCajaModal = document.getElementById('cerrar-caja-modal');
        const cancelarCierreCaja = document.getElementById('cancelar-cierre-caja');
        const confirmarCierreCaja = document.getElementById('confirmar-cierre-caja');
        const efectivoCaja = document.getElementById('efectivo-caja');
        const diferenciaCaja = document.getElementById('diferencia-caja');
        const totalVentas = document.getElementById('total-ventas');
        
        // Verificar que los elementos existan
        if (btnCerrarCaja && cerrarCajaModal) {
            // Obtener total de ventas - En un escenario real, esto podría venir de la base de datos
            // Para este ejemplo, usaremos un valor calculado desde el total mostrado en cartTotal
            let totalVentasValue = 0;
            
            // Evento para mostrar el modal de cierre de caja
            btnCerrarCaja.addEventListener('click', function(e) {
                e.preventDefault();
                
                // En un entorno real, aquí harías una petición AJAX para obtener el total de ventas del día
                // Para este ejemplo, capturaremos el valor del total del carrito
                const cartTotalElement = document.getElementById('cart-total');
                if (cartTotalElement) {
                    totalVentasValue = parseFloat(cartTotalElement.textContent) || 0;
                    totalVentas.textContent = '$' + totalVentasValue.toFixed(2);
                }
                
                cerrarCajaModal.style.display = 'flex';
            });
            
            // Evento para cancelar el cierre de caja
            if (cancelarCierreCaja) {
                cancelarCierreCaja.addEventListener('click', function() {
                    cerrarCajaModal.style.display = 'none';
                });
            }
            
            // Evento para actualizar la diferencia al cambiar el efectivo
            if (efectivoCaja) {
                efectivoCaja.addEventListener('input', function() {
                    const efectivoValue = parseFloat(this.value) || 0;
                    const montoInicialValue = parseFloat(document.getElementById('monto-inicial').textContent.replace('$', '')) || 0;
                    
                    // Calcular diferencia (Efectivo final - (Monto inicial + Ventas del día))
                    const diferenciaValue = efectivoValue - (montoInicialValue + totalVentasValue);
                    
                    // Actualizar campo de diferencia
                    diferenciaCaja.textContent = '$' + diferenciaValue.toFixed(2);
                    
                    // Cambiar color según si hay faltante o sobrante
                    if (diferenciaValue < 0) {
                        diferenciaCaja.style.color = '#d9534f'; // Rojo para faltante
                    } else if (diferenciaValue > 0) {
                        diferenciaCaja.style.color = '#5cb85c'; // Verde para sobrante
                    } else {
                        diferenciaCaja.style.color = '#333'; // Color normal si es exacto
                    }
                });
            }
            
            // Evento para confirmar el cierre de caja
            if (confirmarCierreCaja) {
                confirmarCierreCaja.addEventListener('click', function() {
                    // Validar que se haya ingresado el efectivo
                    if (!efectivoCaja.value || isNaN(parseFloat(efectivoCaja.value))) {
                        alert('Por favor ingresa el monto de efectivo en caja');
                        return;
                    }
                    
                    // Recopilar datos para enviar
                    const notaCierre = document.getElementById('nota-cierre').value;
                    const efectivoFinal = parseFloat(efectivoCaja.value);
                    const diferencia = parseFloat(diferenciaCaja.textContent.replace('$', ''));
                    
                    // Crear FormData para enviar
                    const formData = new FormData();
                    formData.append('efectivo_final', efectivoFinal);
                    formData.append('total_ventas', totalVentasValue);
                    formData.append('diferencia', diferencia);
                    formData.append('nota_cierre', notaCierre);
                    
                    // Enviar datos a través de fetch
                    fetch('../controllers/cerrar_caja.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.text();
                    })
                    .then(data => {
                        console.log('Caja cerrada:', data);
                        
                        // Cerrar modal
                        cerrarCajaModal.style.display = 'none';
                        
                        // Mostrar mensaje de éxito
                        alert('Caja cerrada exitosamente');
                        
                        // Redirigir a sales.php
                        window.location.href = 'sales.php';
                    })
                    .catch(error => {
                        console.error('Error al cerrar caja:', error);
                        alert('Ocurrió un error al cerrar la caja: ' + error.message);
                    });
                });
            }
        }
        
        // Cerrar los modales al hacer click fuera de ellos
        window.addEventListener('click', function(event) {
            if (event.target === cerrarCajaModal) {
                cerrarCajaModal.style.display = 'none';
            }
            if (event.target === document.getElementById('customer-modal')) {
                document.getElementById('customer-modal').style.display = 'none';
            }
            if (event.target === document.getElementById('note-modal')) {
                document.getElementById('note-modal').style.display = 'none';
            }
            if (event.target === document.getElementById('customer-add-modal')) {
                document.getElementById('customer-add-modal').style.display = 'none';
            }
            if (event.target === document.getElementById('product-type-modal')) {
                document.getElementById('product-type-modal').style.display = 'none';
            }
        });
    });
    </script>

    <!-- Estilos CSS adicionales para el modal de cierre de caja -->
    <style>
    .corte-caja-info {
        margin: 15px 0;
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 15px;
        background-color: #f9f9f9;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .info-row.total {
        font-weight: bold;
        font-size: 1.
        .info-row.total {
        font-weight: bold;
        font-size: 1.1em;
        margin-top: 15px;
        border-top: 2px solid #ddd;
        border-bottom: none;
        padding-top: 10px;
    }

    .input-with-icon {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        background-color: white;
    }

    .input-with-icon span {
        padding: 0 10px;
        background-color: #f5f5f5;
        border-right: 1px solid #ddd;
    }

    .input-with-icon input {
        border: none;
        padding: 5px 10px;
        width: 120px;
        outline: none;
    }

    #cerrar-caja-modal .modal-content {
        width: 400px;
        max-width: 90%;
    }
    </style>
</body>
</html>