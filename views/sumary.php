<?php
// Iniciar sesión solo si no existe una sesión activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener el ID del usuario logueado
$usuarioID = isset($_SESSION["usuario_id"]) ? $_SESSION["usuario_id"] : 0;


require_once '../config/db_config.php'; // Conexión a la base de datos

// Verificar si el usuario está autenticado
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
    <title>Resumen de Venta - SIAP</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../styles/sumaryStyle.css">
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

        <button class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Salir</span>
        </button>
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
                    <div class="nav-button active">Resumen</div>
                    <div class="nav-button" id="btn-back">Volver</div>
                </div>
                <img src="https://innovacraft.com/wp-content/uploads/2023/05/innovacraft-logo.png" alt="InnovaCraft" class="logo">
                <div class="search-bar">Resumen de Venta</div>
            </div>
            
            <div class="content">
                <div class="sale-summary-section">
                    <!-- Panel izquierdo para productos y total -->
                    <div class="cart-section">
                        <div class="summary-header">
                            <h2><i class="fas fa-shopping-cart"></i> Detalle de Compra</h2>
                        </div>
                        <div class="cart-items" id="cart-items-container">
                            <!-- Aquí se cargarán dinámicamente los items del carrito -->
                        </div>
                        <div class="cart-footer">
                            <div class="cart-total">
                                <div class="summary-line">
                                    <span>Subtotal:</span>
                                    <span id="summary-subtotal">$0.00</span>
                                </div>
                                <div class="summary-line">
                                    <span>IVA (16%):</span>
                                    <span id="summary-tax">$0.00</span>
                                </div>
                                <div class="summary-line total">
                                    <span>Total:</span>
                                    <span id="summary-total">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel derecho para detalles y método de pago -->
                    <div class="payment-section">
                        <div class="summary-section">
                            <h2><i class="fas fa-user"></i> Información del Cliente</h2>
                            <div class="customer-info" id="customer-info">
                                <!-- Información del cliente se cargará dinámicamente -->
                            </div>
                        </div>
                        
                        <div class="summary-section">
                            <h2><i class="fas fa-credit-card"></i> Método de Pago</h2>
                            <div class="payment-options">
                                <div class="payment-method-selector">
                                    <div class="payment-option">
                                        <input type="radio" id="payment-cash" name="payment-method" value="cash" checked>
                                        <label for="payment-cash">
                                            <i class="fas fa-money-bill-wave"></i> Efectivo
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" id="payment-card" name="payment-method" value="card">
                                        <label for="payment-card">
                                            <i class="fas fa-credit-card"></i> Tarjeta
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" id="payment-transfer" name="payment-method" value="transfer">
                                        <label for="payment-transfer">
                                            <i class="fas fa-exchange-alt"></i> Transferencia
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="cash-payment-details" class="payment-details">
                                    <div class="form-group">
                                        <label for="cash-amount">Cantidad recibida:</label>
                                        <div class="input-with-icon">
                                            
                                            <input type="number" id="cash-amount" placeholder="0.00" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="cash-change">Cambio:</label>
                                        <div class="input-with-icon">
                                            
                                            <input type="text" id="cash-change" placeholder="0.00" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="card-payment-details" class="payment-details" style="display: none;">
                                    <div class="form-group">
                                        <label for="card-terminal">Terminal:</label>
                                        <select id="card-terminal">
                                            <option value="1">Terminal 1</option>
                                            <option value="2">Terminal 2</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="card-reference">Referencia:</label>
                                        <input type="text" id="card-reference" placeholder="Últimos 4 dígitos">
                                    </div>
                                </div>
                                
                                <div id="transfer-payment-details" class="payment-details" style="display: none;">
                                    <div class="form-group">
                                        <label for="transfer-reference">Referencia de transferencia:</label>
                                        <input type="text" id="transfer-reference" placeholder="Número de referencia">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="summary-section">
                            <h2><i class="fas fa-sticky-note"></i> Notas</h2>
                            <div class="form-group">
                                <textarea id="sale-notes" placeholder="Agregar notas a la venta..." rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="summary-actions">
                            <button id="btn-cancel-sale" class="cart-button">
                                <i class="fas fa-times"></i> Cancelar Venta
                            </button>
                            <button id="btn-complete-sale" class="cart-button" style="background-color: #273469; color: white;">
                                <i class="fas fa-check"></i> Completar Venta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para procesar venta -->
    <div class="customer-modal" id="confirm-modal">
        <div class="modal-content">
            <h2><i class="fas fa-check-circle"></i> Confirmar Venta</h2>
            <p>¿Estás seguro de que deseas completar esta venta?</p>
            
            <div class="modal-footer">
                <button id="btn-modal-cancel">Cancelar</button>
                <button id="btn-modal-confirm" style="background-color: #273469; color: white;">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal de venta completada -->
    <div class="customer-modal" id="success-modal">
        <div class="modal-content">
            <h2><i class="fas fa-check-circle"></i> ¡Venta Completada!</h2>
            <p>La venta se ha procesado correctamente.</p>
            <div class="sale-info">
                <div class="sale-info-item">
                    <span>Número de venta:</span>
                    <span id="sale-number">V-0001</span>
                </div>
                <div class="sale-info-item">
                    <span>Total:</span>
                    <span id="sale-total">$0.00</span>
                </div>
            </div>
            
            <div class="modal-footer">
                <button id="btn-print-ticket">
                    <i class="fas fa-print"></i> Imprimir Ticket
                </button>
                <button id="btn-new-sale" style="background-color: #273469; color: white;">
                    <i class="fas fa-plus"></i> Nueva Venta
                </button>
            </div>
        </div>
    </div>

    <!-- Script para la funcionalidad de la página -->
    <script src="../js/sumaryScript.js"></script>
</body>
</html>