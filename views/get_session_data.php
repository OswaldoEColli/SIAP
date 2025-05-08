<?php
session_start();
header('Content-Type: application/json');

// Solo permitir obtener ciertos datos específicos
if (isset($_GET['key']) && $_GET['key'] === 'new_customer') {
    $response = [
        'success' => true,
        'customer_id' => $_SESSION['new_customer_id'] ?? null,
        'customer_name' => $_SESSION['new_customer_name'] ?? null
    ];
    
    // Limpiar los datos de sesión después de usarlos
    unset($_SESSION['new_customer_id']);
    unset($_SESSION['new_customer_name']);
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud no válida']);
}
?>