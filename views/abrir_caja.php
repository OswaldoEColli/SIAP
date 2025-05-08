<?php
// abrir_caja.php
session_start();
require_once '../config/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $montoInicial = $_POST['initial_amount'];
    $notaApertura = $_POST['register_note']; // Obtener la nota de apertura del formulario
    
    // Obtener el ID del usuario de la sesión (asumiendo que está almacenado)
    // Si no tienes esto configurado aún, puedes usar un valor por defecto para pruebas
    $usuarioID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    
    // Verificar si ya hay una caja abierta
    $verificacion = "SELECT reporteID FROM ReporteCaja WHERE estado = 'Abierta' LIMIT 1";
    $resultVerificacion = $mysqli->query($verificacion);
    
    if ($resultVerificacion && $resultVerificacion->num_rows > 0) {
        // Ya hay una caja abierta, redirigir a productsSale.php
        $row = $resultVerificacion->fetch_assoc();
        $_SESSION['caja_actual_id'] = $row['reporteID'];
        header("Location: productsSale.php");
        exit();
    }
    
    // Insertar registro de apertura de caja (ahora incluyendo notaApertura)
    $sql = "INSERT INTO ReporteCaja (usuarioID, fechaApertura, montoInicial, notaApertura, estado) 
            VALUES (?, NOW(), ?, ?, 'Abierta')";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ids", $usuarioID, $montoInicial, $notaApertura);
    
    if ($stmt->execute()) {
        // Obtener el ID del reporte creado
        $reporteID = $mysqli->insert_id;
        
        // Guardar el ID del reporte en la sesión para usarlo en productsSale.php
        $_SESSION['caja_actual_id'] = $reporteID;
        $_SESSION['monto_inicial'] = $montoInicial;
        $_SESSION['nota_apertura'] = $notaApertura; // Guardar también la nota en la sesión
        
        // Redireccionar a la página de ventas
        header("Location: productsSale.php");
        exit();
    } else {
        // En caso de error, mostrar mensaje y redirigir
        $_SESSION['error_message'] = "Error al abrir la caja: " . $stmt->error;
        header("Location: sales.php");
        exit();
    }
    
    $stmt->close();
} else {
    // Si alguien intenta acceder directamente a este archivo sin enviar el formulario
    header("Location: sales.php");
    exit();
}
?>