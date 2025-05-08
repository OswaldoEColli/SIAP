<?php
// ajax_cliente.php - Procesador AJAX para clientes
session_start();
header('Content-Type: application/json');

// Incluir archivos necesarios
require_once "config/db_config.php";

// Procesar la solicitud
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
        // Insertar cliente directamente (sin usar la clase Cliente)
        $query = "INSERT INTO Cliente (nombre, apellidos, telefono, email, direccion, rfc, esRecurrente, saldoPendiente, fechaRegistro) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)";
        
        $stmt = $mysqli->prepare($query);
        
        if($stmt) {
            // Vincular parámetros
            $esRecurrente = 1; // Por defecto es cliente recurrente
            $stmt->bind_param("ssssssi", $nombre, $apellidos, $telefono, $email, $direccion, $rfc, $esRecurrente);
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                // Obtener el ID del cliente insertado
                $clienteID = $mysqli->insert_id;
                $stmt->close();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente guardado exitosamente',
                    'clienteID' => $clienteID
                ]);
                exit;
            } else {
                $errors[] = "Error al ejecutar la consulta: " . $stmt->error;
                $stmt->close();
            }
        } else {
            $errors[] = "Error al preparar la consulta: " . $mysqli->error;
        }
    }
    
    // Si llegamos aquí, hubo errores
    echo json_encode([
        'success' => false,
        'message' => implode(", ", $errors)
    ]);
    
} else {
    // Si no es una petición POST
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>