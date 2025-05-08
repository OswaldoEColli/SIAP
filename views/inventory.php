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
require_once "../models/Producto.php";

// Crear instancia del modelo Producto
$productoModel = new Producto($mysqli);

// Obtener todos los productos desde la base de datos con información de inventario
$products = $productoModel->getAllWithInventory();

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
    <title>SIAP - Sistema de Inventario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/inventoryStyle.css">
    <style>
        /* Estilos para las notificaciones toast */
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
        
        /* Estilo para la barra de búsqueda */
        .search-box {
            display: flex;
            align-items: center;
            background-color: #fff;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .search-box i {
            color: #6c757d;
            margin-right: 10px;
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 16px;
            color: #495057;
        }
        
        /* Estilos para notificaciones tradicionales */
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

        /* Estilos para el inventario */
        .stock-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .stock-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .stock-normal {
            background-color: #d4edda;
            color: #155724;
        }

        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }

        .stock-critical {
            background-color: #f8d7da;
            color: #721c24;
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

        <a href="customer.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span>Clientes</span>
        </a>

        <a href="reports.php" class="menu-item">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
        </a>

        <a href="inventory.php" class="menu-item active">
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
            
            <div class="products-header">
                <h2>Inventario de Productos</h2>
                <button class="add-product-btn" id="add-product-btn">
                    <i class="fas fa-plus"></i>
                    <span>Añadir Producto</span>
                </button>
            </div>
            
            <!-- Barra de búsqueda -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Buscar producto por nombre, código o precio...">
            </div>
            
            <div class="products-table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Nombre</th>
                            <th><i class="fas fa-barcode"></i> Código</th>
                            <th><i class="fas fa-dollar-sign"></i> Precio</th>
                            <th><i class="fas fa-boxes"></i> Unid/Plancha</th>
                            <th><i class="fas fa-warehouse"></i> Inventario</th>
                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (is_array($products) && count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <?php 
                                    // Calcular el stock total en unidades
                                    $planchas = isset($product['cantidadPlanchas']) ? (int)$product['cantidadPlanchas'] : 0;
                                    $unidades = isset($product['cantidadUnidades']) ? (int)$product['cantidadUnidades'] : 0;
                                    $unidadesPorPlancha = (int)$product['unidadesPorPlancha'];
                                    $totalUnidades = ($planchas * $unidadesPorPlancha) + $unidades;
                                    
                                    // Determinar el estado del stock
                                    $stockClass = 'stock-normal';
                                    if ($totalUnidades == 0) {
                                        $stockClass = 'stock-critical';
                                    } elseif ($totalUnidades < 10) {
                                        $stockClass = 'stock-low';
                                    }
                                ?>
                                <tr data-id="<?php echo $product['productoID']; ?>">
                                    <td><?php echo htmlspecialchars($product['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($product['codigo']); ?></td>
                                    <td>$<?php echo htmlspecialchars($product['precioVentaUnitario']); ?></td>
                                    <td><?php echo htmlspecialchars($product['unidadesPorPlancha']); ?></td>
                                    <td>
                                        <div class="stock-info">
                                            <?php if ($planchas > 0): ?>
                                                <span><?php echo $planchas; ?> plancha(s)</span>
                                            <?php endif; ?>
                                            <?php if ($unidades > 0): ?>
                                                <span><?php echo $unidades; ?> unidad(es)</span>
                                            <?php endif; ?>
                                            <span class="stock-badge <?php echo $stockClass; ?>">
                                                Total: <?php echo $totalUnidades; ?> unidades
                                            </span>
                                        </div>
                                    </td>
                                    <td class="action-cell">
                                        <button class="action-btn" data-id="<?php echo $product['productoID']; ?>">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">No hay productos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para añadir producto con campos de inventario -->
    <div class="modal" id="add-product-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Añadir Nuevo Producto</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-product-form" action="../controllers/producto_controller.php?action=create" method="post">
                    <!-- Información básica en la misma fila -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="product-name">Nombre</label>
                            <input type="text" class="form-input" id="product-name" name="product_name" placeholder="Nombre producto" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-code">Código</label>
                            <input type="text" class="form-input" id="product-code" name="product_code" placeholder="Código barras" required>
                        </div>
                    </div>
                    
                    <!-- Precio de compra -->
                    <div class="form-group">
                        <label class="form-label" for="product-purchase-price">Precio de compra</label>
                        <input type="number" class="form-input" id="product-purchase-price" name="product_purchase_price" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    
                    <!-- Precios de venta -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="product-price">P. plancha</label>
                            <input type="number" class="form-input" id="product-price" name="product_price" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-price-half">P. media</label>
                            <input type="number" class="form-input" id="product-price-half" name="product_price_half" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="product-price-unit">P. unitario</label>
                            <input type="number" class="form-input" id="product-price-unit" name="product_price_unit" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-units">Unid/plancha</label>
                            <input type="number" class="form-input" id="product-units" name="product_units" placeholder="0" min="0" required>
                        </div>
                    </div>
                    
                    <!-- Stock inicial -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="product-stock-planchas">Stock Planchas</label>
                            <input type="number" class="form-input" id="product-stock-planchas" name="product_stock_planchas" placeholder="0" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-stock-unidades">Stock Unidades</label>
                            <input type="number" class="form-input" id="product-stock-unidades" name="product_stock_unidades" placeholder="0" min="0" value="0">
                        </div>
                    </div>
                    
                    <!-- Estado e imagen en una fila -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="product-status">Estado</label>
                            <select class="form-input" id="product-status" name="product_status">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-image">Imagen</label>
                            <input type="text" class="form-input" id="product-image" name="product_image" placeholder="URL imagen">
                        </div>
                    </div>
                    
                    <!-- Descripción al final -->
                    <div class="form-group">
                        <label class="form-label" for="product-description">Descripción</label>
                        <textarea class="form-input" id="product-description" name="product_description" placeholder="Descripción breve..." rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-add">Cancelar</button>
                <button class="btn btn-primary" id="save-add">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal para editar producto con campos de inventario -->
    <div class="modal" id="edit-product-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Producto</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="edit-product-form" action="../controllers/producto_controller.php?action=update" method="post">
                    <input type="hidden" name="product_id" id="edit-product-id">
                    
                    <!-- Información básica -->
                    <div class="form-group">
                        <label class="form-label" for="edit-product-name">Nombre del producto</label>
                        <input type="text" class="form-input" id="edit-product-name" name="edit_product_name" placeholder="Nombre del producto" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-product-code">Código de barras</label>
                        <input type="text" class="form-input" id="edit-product-code" name="edit_product_code" placeholder="Código de barras" required>
                    </div>
                    
                    <!-- Precio de compra -->
                    <div class="form-group">
                        <label class="form-label" for="edit-product-purchase-price">Precio de compra</label>
                        <input type="number" class="form-input" id="edit-product-purchase-price" name="edit_product_purchase_price" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    
                    <!-- Precios de venta en dos filas -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="edit-product-price">Precio plancha</label>
                            <input type="number" class="form-input" id="edit-product-price" name="edit_product_price" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit-product-price-half">Precio media</label>
                            <input type="number" class="form-input" id="edit-product-price-half" name="edit_product_price_half" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="edit-product-price-unit">Precio unitario</label>
                            <input type="number" class="form-input" id="edit-product-price-unit" name="edit_product_price_unit" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit-product-units">Unid/plancha</label>
                            <input type="number" class="form-input" id="edit-product-units" name="edit_product_units" placeholder="0" min="0" required>
                        </div>
                    </div>
                    
                    <!-- Stock actual -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="edit-product-stock-planchas">Stock Planchas</label>
                            <input type="number" class="form-input" id="edit-product-stock-planchas" name="edit_product_stock_planchas" placeholder="0" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit-product-stock-unidades">Stock Unidades</label>
                            <input type="number" class="form-input" id="edit-product-stock-unidades" name="edit_product_stock_unidades" placeholder="0" min="0" value="0">
                        </div>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="form-group">
                        <label class="form-label" for="edit-product-description">Descripción</label>
                        <textarea class="form-input" id="edit-product-description" name="edit_product_description" placeholder="Descripción del producto" rows="2"></textarea>
                    </div>
                    
                    <!-- Estado e imagen en una fila -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="edit-product-status">Estado</label>
                            <select class="form-input" id="edit-product-status" name="edit_product_status">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit-product-image">Imagen (URL)</label>
                            <input type="text" class="form-input" id="edit-product-image" name="edit_product_image" placeholder="URL de la imagen">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" id="delete-product">Eliminar</button>
                <button class="btn btn-secondary" id="cancel-edit">Cancelar</button>
                <button class="btn btn-primary" id="save-edit">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Menu de acciones -->
    <div class="action-menu" id="action-menu">
        <div class="action-menu-item" id="edit-product-option">
            <i class="fas fa-edit"></i>
            <span>Editar</span>
        </div>
        <div class="action-menu-item" id="delete-product-option">
            <i class="fas fa-trash"></i>
            <span>Eliminar</span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/inventoryScript.js"></script>
</body>
</html>