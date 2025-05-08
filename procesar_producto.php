<?php
// Este archivo procesa directamente los datos del formulario
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: views/login.php");
    exit;
}

// Incluir archivos necesarios
require_once "config/db_config.php";
require_once "includes/functions.php";
require_once "models/Producto.php";

// Crear instancia del modelo Producto
$producto = new Producto($mysqli);

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        // Es una actualización (edición)
        $producto_id = intval($_POST['product_id']);
        $nombre = isset($_POST['edit_product_name']) ? $_POST['edit_product_name'] : "";
        $codigo = isset($_POST['edit_product_code']) ? $_POST['edit_product_code'] : "";
        $descripcion = isset($_POST['edit_product_description']) ? $_POST['edit_product_description'] : "";
        
        // Campos específicos según la estructura de la base de datos
        // Removido proveedorID
        $precioCompra = isset($_POST['edit_product_purchase_price']) ? (float)$_POST['edit_product_purchase_price'] : 0;
        $precioVentaPlancha = isset($_POST['edit_product_price']) ? (float)$_POST['edit_product_price'] : 0;
        $precioVentaMediaPlancha = isset($_POST['edit_product_price_half']) ? (float)$_POST['edit_product_price_half'] : 0;
        $precioVentaUnitario = isset($_POST['edit_product_price_unit']) ? (float)$_POST['edit_product_price_unit'] : 0;
        $unidadesPorPlancha = isset($_POST['edit_product_units']) ? (int)$_POST['edit_product_units'] : 0;
        $imagen = isset($_POST['edit_product_image']) ? $_POST['edit_product_image'] : "";
        $activo = isset($_POST['edit_product_status']) && $_POST['edit_product_status'] === 'active' ? true : false;
        
        try {
            if ($producto->update($producto_id, $nombre, $codigo, $descripcion, null, $precioCompra, 
                                $precioVentaPlancha, $precioVentaMediaPlancha, $precioVentaUnitario, 
                                $unidadesPorPlancha, $imagen, $activo)) {
                $_SESSION['success_message'] = "Producto actualizado exitosamente";
            } else {
                $_SESSION['error_message'] = "Ocurrió un error al actualizar el producto";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        // Es una creación
        $nombre = isset($_POST['product_name']) ? $_POST['product_name'] : "";
        $codigo = isset($_POST['product_code']) ? $_POST['product_code'] : "";
        $descripcion = isset($_POST['product_description']) ? $_POST['product_description'] : "";
        
        // Removido proveedorID
        $precioCompra = isset($_POST['product_purchase_price']) ? (float)$_POST['product_purchase_price'] : 0;
        $precioVentaPlancha = isset($_POST['product_price']) ? (float)$_POST['product_price'] : 0;
        $precioVentaMediaPlancha = isset($_POST['product_price_half']) ? (float)$_POST['product_price_half'] : 0;
        $precioVentaUnitario = isset($_POST['product_price_unit']) ? (float)$_POST['product_price_unit'] : 0;
        $unidadesPorPlancha = isset($_POST['product_units']) ? (int)$_POST['product_units'] : 0;
        $imagen = isset($_POST['product_image']) ? $_POST['product_image'] : "";
        $activo = isset($_POST['product_status']) && $_POST['product_status'] === 'active' ? true : false;
        
        // Validar datos
        $errors = array();
        
        if (empty($nombre)) {
            $errors[] = "El nombre del producto es obligatorio";
        }
        
        if (empty($codigo)) {
            $errors[] = "El código del producto es obligatorio";
        }
        
        // Si no hay errores, guardar el producto
        if (empty($errors)) {
            try {
                if ($producto->create($nombre, $codigo, $descripcion, null, $precioCompra, 
                                    $precioVentaPlancha, $precioVentaMediaPlancha, $precioVentaUnitario, 
                                    $unidadesPorPlancha, $imagen, $activo)) {
                    $_SESSION['success_message'] = "Producto guardado exitosamente";
                } else {
                    $_SESSION['error_message'] = "Ocurrió un error al guardar el producto";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error en la base de datos: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = implode(", ", $errors);
        }
    }
}

// Redireccionar a la página de inventario
header("location: views/inventory.php");
exit;
?>