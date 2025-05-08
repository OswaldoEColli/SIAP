<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../views/login.php");
    exit;
}

// Incluir archivos necesarios
require_once __DIR__ . "/../config/db_config.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../models/Producto.php";

// Crear instancia del modelo Producto
$producto = new Producto($mysqli);

// Procesar las solicitudes según el método y acción
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        // Procesar la creación de un nuevo producto
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Obtener datos del formulario
            $nombre = isset($_POST['product_name']) ? $_POST['product_name'] : "";
            $codigo = isset($_POST['product_code']) ? $_POST['product_code'] : "";
            $descripcion = isset($_POST['product_description']) ? $_POST['product_description'] : "";
            
            // Campos específicos según la estructura de tu base de datos
            $proveedorID = isset($_POST['product_provider']) ? $_POST['product_provider'] : null;
            $precioCompra = isset($_POST['product_purchase_price']) ? (float)$_POST['product_purchase_price'] : 0;
            $precioVentaPlancha = isset($_POST['product_price']) ? (float)$_POST['product_price'] : 0;
            $precioVentaMediaPlancha = isset($_POST['product_price_half']) ? (float)$_POST['product_price_half'] : 0;
            $precioVentaUnitario = isset($_POST['product_price_unit']) ? (float)$_POST['product_price_unit'] : 0;
            $unidadesPorPlancha = isset($_POST['product_units']) ? (int)$_POST['product_units'] : 0;
            $imagen = isset($_POST['product_image']) ? $_POST['product_image'] : "";
            $activo = isset($_POST['product_status']) && $_POST['product_status'] === 'active' ? true : false;
            
            // Datos de inventario
            $stockPlanchas = isset($_POST['product_stock_planchas']) ? (int)$_POST['product_stock_planchas'] : 0;
            $stockUnidades = isset($_POST['product_stock_unidades']) ? (int)$_POST['product_stock_unidades'] : 0;
            
            // Validar datos
            $errors = array();
            
            if (empty($nombre)) {
                $errors[] = "El nombre del producto es obligatorio";
            }
            
            if (empty($codigo)) {
                $errors[] = "El código del producto es obligatorio";
            }
            
            if ($precioCompra < 0) {
                $errors[] = "El precio de compra no puede ser negativo";
            }
            
            if ($precioVentaPlancha <= 0) {
                $errors[] = "El precio de venta por plancha debe ser mayor que cero";
            }
            
            // Si no hay errores, guardar el producto
            if (empty($errors)) {
                if ($producto->create($nombre, $codigo, $descripcion, $proveedorID, $precioCompra, 
                                     $precioVentaPlancha, $precioVentaMediaPlancha, $precioVentaUnitario, 
                                     $unidadesPorPlancha, $imagen, $activo)) {
                    
                    // Obtener el ID del producto recién creado
                    $productoID = $mysqli->insert_id;
                    
                    // Actualizar el inventario
                    $producto->updateInventory($productoID, $stockPlanchas, $stockUnidades);
                    
                    $_SESSION['success_message'] = "Producto guardado exitosamente";
                } else {
                    $_SESSION['error_message'] = "Ocurrió un error al guardar el producto";
                }
                
                header("location: ../views/inventory.php");
                exit;
            } else {
                $_SESSION['error_message'] = implode(", ", $errors);
                header("location: ../views/inventory.php");
                exit;
            }
        }
        break;
        
    case 'get_all':
        // Obtener todos los productos
        $productos = $producto->getAllWithInventory(); // Cambiado para incluir datos de inventario
        
        header('Content-Type: application/json');
        echo json_encode($productos);
        exit;
        
    case 'get':
        // Obtener un producto específico con su inventario
        if (isset($_GET['id'])) {
            $producto_id = intval($_GET['id']);
            $producto_data = $producto->getById($producto_id);
            
            if ($producto_data) {
                // Obtener datos de inventario
                $query = "SELECT cantidadPlanchas, cantidadUnidades FROM Inventario WHERE productoID = ?";
                if($stmt = $mysqli->prepare($query)) {
                    $stmt->bind_param("i", $producto_id);
                    if($stmt->execute()) {
                        $result = $stmt->get_result();
                        if($result->num_rows > 0) {
                            $inventario = $result->fetch_assoc();
                            $producto_data['cantidadPlanchas'] = $inventario['cantidadPlanchas'];
                            $producto_data['cantidadUnidades'] = $inventario['cantidadUnidades'];
                        } else {
                            $producto_data['cantidadPlanchas'] = 0;
                            $producto_data['cantidadUnidades'] = 0;
                        }
                    }
                    $stmt->close();
                }
                
                header('Content-Type: application/json');
                echo json_encode($producto_data);
            } else {
                header('HTTP/1.1 404 Not Found');
                echo json_encode(array('error' => 'Producto no encontrado'));
            }
        } else {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(array('error' => 'ID de producto no proporcionado'));
        }
        exit;
        
    case 'update':
        // Actualizar un producto
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
            $producto_id = intval($_POST['product_id']);
            $nombre = isset($_POST['edit_product_name']) ? $_POST['edit_product_name'] : "";
            $codigo = isset($_POST['edit_product_code']) ? $_POST['edit_product_code'] : "";
            $descripcion = isset($_POST['edit_product_description']) ? $_POST['edit_product_description'] : "";
            
            // Campos específicos según la estructura de tu base de datos
            $proveedorID = isset($_POST['edit_product_provider']) ? $_POST['edit_product_provider'] : null;
            $precioCompra = isset($_POST['edit_product_purchase_price']) ? (float)$_POST['edit_product_purchase_price'] : 0;
            $precioVentaPlancha = isset($_POST['edit_product_price']) ? (float)$_POST['edit_product_price'] : 0;
            $precioVentaMediaPlancha = isset($_POST['edit_product_price_half']) ? (float)$_POST['edit_product_price_half'] : 0;
            $precioVentaUnitario = isset($_POST['edit_product_price_unit']) ? (float)$_POST['edit_product_price_unit'] : 0;
            $unidadesPorPlancha = isset($_POST['edit_product_units']) ? (int)$_POST['edit_product_units'] : 0;
            $imagen = isset($_POST['edit_product_image']) ? $_POST['edit_product_image'] : "";
            $activo = isset($_POST['edit_product_status']) && $_POST['edit_product_status'] === 'active' ? true : false;
            
            // Datos de inventario
            $stockPlanchas = isset($_POST['edit_product_stock_planchas']) ? (int)$_POST['edit_product_stock_planchas'] : 0;
            $stockUnidades = isset($_POST['edit_product_stock_unidades']) ? (int)$_POST['edit_product_stock_unidades'] : 0;
            
            // Validar datos
            $errors = array();
            
            if (empty($nombre)) {
                $errors[] = "El nombre del producto es obligatorio";
            }
            
            if (empty($codigo)) {
                $errors[] = "El código del producto es obligatorio";
            }
            
            if ($precioCompra < 0) {
                $errors[] = "El precio de compra no puede ser negativo";
            }
            
            if ($precioVentaPlancha <= 0) {
                $errors[] = "El precio de venta por plancha debe ser mayor que cero";
            }
            
            // Si no hay errores, actualizar el producto
            if (empty($errors)) {
                if ($producto->update($producto_id, $nombre, $codigo, $descripcion, $proveedorID, $precioCompra, 
                                     $precioVentaPlancha, $precioVentaMediaPlancha, $precioVentaUnitario, 
                                     $unidadesPorPlancha, $imagen, $activo)) {
                    
                    // Actualizar el inventario
                    $producto->updateInventory($producto_id, $stockPlanchas, $stockUnidades);
                    
                    $_SESSION['success_message'] = "Producto actualizado exitosamente";
                } else {
                    $_SESSION['error_message'] = "Ocurrió un error al actualizar el producto";
                }
                
                header("location: ../views/inventory.php");
                exit;
            } else {
                $_SESSION['error_message'] = implode(", ", $errors);
                header("location: ../views/inventory.php");
                exit;
            }
        }
        break;
        
    case 'delete':
        // Eliminar un producto
        if (isset($_GET['id'])) {
            $producto_id = intval($_GET['id']);
            
            if ($producto->delete($producto_id)) {
                // También eliminar el inventario asociado
                $query = "DELETE FROM Inventario WHERE productoID = ?";
                if($stmt = $mysqli->prepare($query)) {
                    $stmt->bind_param("i", $producto_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $_SESSION['success_message'] = "Producto eliminado exitosamente";
            } else {
                $_SESSION['error_message'] = "No se puede eliminar el producto porque tiene transacciones asociadas";
            }
            
            header("location: ../views/inventory.php");
            exit;
        }
        break;
        
    default:
        // Acción desconocida, redirigir a la página de inventario
        header("location: ../views/inventory.php");
        exit;
}
?>