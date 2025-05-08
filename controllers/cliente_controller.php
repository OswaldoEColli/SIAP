<?php
// Activar reporte de errores para depuración en entorno de desarrollo
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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
require_once __DIR__ . "/../models/Cliente.php";

// Crear instancia del modelo Cliente
$cliente = new Cliente($mysqli);

// Procesar las solicitudes según el método y acción
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        // Procesar la creación de un nuevo cliente
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
            
            // Validar datos
            $errors = array();
            
            if (empty($nombre)) {
                $errors[] = "El nombre es obligatorio";
            }
            
            // Si no hay errores, guardar el cliente
            if (empty($errors)) {
                if ($cliente->create($nombre, $apellidos, $telefono, $email, $direccion, $rfc, $estado)) {
                    $_SESSION['success_message'] = "Cliente guardado exitosamente";
                } else {
                    $_SESSION['error_message'] = "Ocurrió un error al guardar el cliente";
                }
                header("location: ../views/customer.php");
                exit;
            } else {
                $_SESSION['error_message'] = implode(", ", $errors);
                header("location: ../views/customer.php");
                exit;
            }
        }
        break;
        
    case 'get_all':
        // Obtener todos los clientes
        $clientes = $cliente->getAll();
        header('Content-Type: application/json');
        echo json_encode($clientes);
        exit;
        
    case 'get':
        // Obtener un cliente específico
        if (isset($_GET['id'])) {
            $cliente_id = intval($_GET['id']);
            $cliente_data = $cliente->getById($cliente_id);
            
            if ($cliente_data) {
                header('Content-Type: application/json');
                echo json_encode($cliente_data);
            } else {
                header('HTTP/1.1 404 Not Found');
                echo json_encode(array('error' => 'Cliente no encontrado'));
            }
        } else {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(array('error' => 'ID de cliente no proporcionado'));
        }
        exit;
        
    case 'update':
        // Actualizar un cliente
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cliente_id'])) {
            $cliente_id = intval($_POST['cliente_id']);
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
                $_SESSION['success_message'] = "Cliente actualizado exitosamente";
            } else {
                $_SESSION['error_message'] = "Ocurrió un error al actualizar el cliente";
            }
            
            header("location: ../views/customer.php");
            exit;
        }
        break;
        
    case 'delete':
        // Eliminar un cliente
        if (isset($_GET['id'])) {
            $cliente_id = intval($_GET['id']);
            
            if ($cliente->delete($cliente_id)) {
                $_SESSION['success_message'] = "Cliente eliminado exitosamente";
            } else {
                $_SESSION['error_message'] = "No se puede eliminar el cliente porque tiene ventas asociadas";
            }
            
            header("location: ../views/customer.php");
            exit;
        }
        break;
        
    default:
        // Acción desconocida, redirigir a la página de clientes
        header("location: ../views/customer.php");
        exit;
}
?>