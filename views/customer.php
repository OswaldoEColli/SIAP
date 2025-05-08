<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once "../config/db_config.php";
require_once "../includes/functions.php";
require_once "../models/Cliente.php";

// Crear instancia del modelo Cliente
$clienteModel = new Cliente($mysqli);

// Obtener todos los clientes desde la base de datos
$customers = $clienteModel->getAll();

// Recuperar mensajes de sesión
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Limpiar mensajes de sesión
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAP - Clientes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/customerStyle.css">
    <style>
        /* Estilos para las notificaciones tradicionales */
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification .close-btn {
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
        }
        
        .action-dropdown {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 10;
            width: 150px;
        }
        
        .action-dropdown.active {
            display: block;
        }
        
        .action-dropdown a {
            display: block;
            padding: 8px 15px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.3s;
        }
        
        .action-dropdown a:hover {
            background-color: #f5f5f5;
        }
        
        .action-cell {
            position: relative;
        }
        
        .action-menu {
            position: relative;
        }
        
        /* Estilos para las notificaciones animadas */
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
        
        /* Estilos adicionales para los modales */
        .customer-modal, .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .confirmation-modal .modal-content {
            max-width: 400px;
            text-align: center;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .modal-footer button {
            padding: 8px 16px;
            margin-left: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .modal-footer button:first-child {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #495057;
        }
        
        .modal-footer button:last-child {
            background-color: #007bff;
            border: 1px solid #007bff;
            color: white;
        }
        
        #confirm-delete {
            background-color: #dc3545 !important;
            border: 1px solid #dc3545 !important;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            
        </div>

        <a href="home.php" class="menu-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>

        <a href="sales.php" class="menu-item">
            <i class="fas fa-dollar-sign"></i>
            <span>Ventas</span>
        </a>

        <a href="customer.php" class="menu-item active">
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

        <button class="logout-btn" onclick="if(confirm('¿Seguro que deseas cerrar sesión?')) window.location.href='../controllers/logout.php';">
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
        <div class="content-area">
            <!-- Mostrar notificaciones tradicionales (se convertirán en toast) -->
            <?php if (!empty($success_message)): ?>
                <div class="notification success">
                    <span><?php echo $success_message; ?></span>
                    <button class="close-btn">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="notification error">
                    <span><?php echo $error_message; ?></span>
                    <button class="close-btn">&times;</button>
                </div>
            <?php endif; ?>
            
            <div class="customers-header">
                <h1>Clientes</h1>
                <button class="add-customer-btn" id="add-customer-btn">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Cliente</span>
                </button>
            </div>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Buscar cliente por nombre, RFC o teléfono...">
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
                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="customers-table-body">
                        <?php
                        // Generar filas de la tabla con los datos de la base de datos
                        foreach($customers as $customer) {
                            $statusClass = ($customer['esRecurrente'] == 1) ? 'status-active' : 'status-inactive';
                            $statusText = ($customer['esRecurrente'] == 1) ? 'Activo' : 'Inactivo';
                            
                            echo '<tr data-id="'.$customer['clienteID'].'">';
                            echo '    <td>'.$customer['nombre'].' '.$customer['apellidos'].'</td>';
                            echo '    <td>'.(!empty($customer['rfc']) ? $customer['rfc'] : 'N/A').'</td>';
                            echo '    <td>'.$customer['telefono'].'</td>';
                            echo '    <td>'.$customer['email'].'</td>';
                            echo '    <td><span class="status-badge '.$statusClass.'">'.$statusText.'</span></td>';
                            echo '    <td class="action-cell">';
                            echo '        <div class="action-menu">';
                            echo '            <button class="action-btn">';
                            echo '                <i class="fas fa-ellipsis-h"></i>';
                            echo '            </button>';
                            echo '            <div class="action-dropdown">';
                            echo '                <a href="#" class="edit-customer" data-id="'.$customer['clienteID'].'"><i class="fas fa-edit"></i> Editar</a>';
                            echo '                <a href="#" class="delete-customer" data-id="'.$customer['clienteID'].'"><i class="fas fa-trash"></i> Eliminar</a>';
                            echo '            </div>';
                            echo '        </div>';
                            echo '    </td>';
                            echo '</tr>';
                        }
                        
                        // Si no hay clientes, mostrar mensaje
                        if (empty($customers)) {
                            echo '<tr><td colspan="6" class="no-data">No hay clientes registrados</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para agregar/editar cliente -->
    <div class="customer-modal" id="customer-modal">
        <div class="modal-content">
            <h2 id="modal-title">Nuevo Cliente</h2>
            
            <form method="post" action="../procesar_cliente.php" id="customer-form">
                <input type="hidden" id="cliente_id" name="cliente_id" value="">
                
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
                
                <div class="modal-footer">
                    <button type="button" id="cancel-modal">Cancelar</button>
                    <button type="submit" id="save-customer">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="confirmation-modal" id="delete-confirmation-modal">
        <div class="modal-content">
            <h2>Confirmar eliminación</h2>
            <p>¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.</p>
            
            <div class="modal-footer">
                <button type="button" id="cancel-delete">Cancelar</button>
                <button type="button" id="confirm-delete">Eliminar</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/customerScript.js"></script>
</body>
</html>