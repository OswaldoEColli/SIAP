<?php


// Agregar al comienzo del archivo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Registrar datos en un archivo de log
file_put_contents('debug_cliente.log', 
    date('Y-m-d H:i:s') . " - Petición recibida\n" . 
    "POST: " . print_r($_POST, true) . "\n\n", 
    FILE_APPEND);

// Resto del código...
// Este archivo procesa directamente los datos del formulario
// Iniciar sesión
session_start();

// Verificar si es una petición AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Incluir archivos necesarios
require_once "config/db_config.php";
require_once "includes/functions.php";
require_once "models/Cliente.php";

// Crear instancia del modelo Cliente
$cliente = new Cliente($mysqli);

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['cliente_id']) && !empty($_POST['cliente_id'])) {
        // Es una actualización
        $cliente_id = $_POST['cliente_id'];
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
        
        if ($cliente->update($cliente_id, $nombre, $apellidos, $telefono, $email, $direccion, $estado, $rfc)) {
            if ($isAjax) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cliente actualizado exitosamente',
                    'clienteID' => $cliente_id
                ]);
                exit;
            } else {
                $_SESSION['success_message'] = "Cliente actualizado exitosamente";
            }
        } else {
            if ($isAjax) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ocurrió un error al actualizar el cliente'
                ]);
                exit;
            } else {
                $_SESSION['error_message'] = "Ocurrió un error al actualizar el cliente";
            }
        }
    } else {
        // Es una creación
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
        
        // Validar datos
        $errors = array();
        
        if (empty($nombre)) {
            $errors[] = "El nombre es obligatorio";
        }
        
        // Si no hay errores, guardar el cliente
        if (empty($errors)) {
            $clienteID = $cliente->create($nombre, $apellidos, $telefono, $email, $direccion, $rfc, $estado);
            if ($clienteID) {
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cliente guardado exitosamente',
                        'clienteID' => $clienteID
                    ]);
                    exit;
                } else {
                    $_SESSION['success_message'] = "Cliente guardado exitosamente";
                }
            } else {
                if ($isAjax) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ocurrió un error al guardar el cliente'
                    ]);
                    exit;
                } else {
                    $_SESSION['error_message'] = "Ocurrió un error al guardar el cliente";
                }
            }
        } else {
            if ($isAjax) {
                echo json_encode([
                    'success' => false,
                    'message' => implode(", ", $errors)
                ]);
                exit;
            } else {
                $_SESSION['error_message'] = implode(", ", $errors);
            }
        }
    }

    // Solo redireccionar si no es una petición AJAX
    if (!$isAjax) {
        // Redireccionar a la página de clientes
        header("location: views/customer.php");
        exit;
    }
}
?>