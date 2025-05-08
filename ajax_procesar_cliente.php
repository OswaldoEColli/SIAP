<?php
// ajax_procesar_cliente.php - Procesador AJAX para clientes
// Iniciar sesión
session_start();

header('Content-Type: application/json');

// Incluir archivos necesarios
require_once "config/db_config.php";
require_once "models/Cliente.php";

// Crear instancia del modelo Cliente
$cliente = new Cliente($mysqli);

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        // Modificar el método create para que devuelva el ID
        // Es posible que esto ya exista en tu clase Cliente
        $clienteID = $cliente->create($nombre, $apellidos, $telefono, $email, $direccion, $rfc, $estado);
        
        if ($clienteID) {
            echo json_encode([
                'success' => true,
                'message' => 'Cliente guardado exitosamente',
                'clienteID' => $clienteID
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Ocurrió un error al guardar el cliente'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => implode(", ", $errors)
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>