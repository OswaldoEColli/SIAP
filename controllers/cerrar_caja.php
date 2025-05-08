<?php
// controllers/cerrar_caja.php
session_start();
require_once '../config/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $efectivoFinal = $_POST['efectivo_final'];
    $totalVentas = $_POST['total_ventas'];
    $diferencia = $_POST['diferencia'];
    $notaCierre = $_POST['nota_cierre']; // Ahora sí guardaremos la nota de cierre
    
    // Obtener ID de la caja actual de la sesión
    $cajaID = isset($_SESSION['caja_actual_id']) ? $_SESSION['caja_actual_id'] : 0;
    
    if ($cajaID <= 0) {
        echo "Error: No hay una caja abierta para cerrar";
        exit;
    }
    
    // Actualizar el registro de la caja en la base de datos incluyendo notaCierre
    $sql = "UPDATE ReporteCaja SET 
            fechaCierre = NOW(), 
            montoFinal = ?, 
            totalVentas = ?, 
            diferencia = ?, 
            notaCierre = ?,
            estado = 'Cerrada' 
            WHERE reporteID = ? AND estado = 'Abierta'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("dddsi", $efectivoFinal, $totalVentas, $diferencia, $notaCierre, $cajaID);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Limpiar las variables de sesión relacionadas con la caja
        unset($_SESSION['caja_actual_id']);
        unset($_SESSION['monto_inicial']);
        unset($_SESSION['nota_caja']);
        
        // Establecer mensaje de éxito en la sesión
        $_SESSION['cierre_caja_exitoso'] = "La caja ha sido cerrada exitosamente";
        
        echo "success";
    } else {
        $_SESSION['cierre_caja_error'] = "Error al cerrar la caja: " . ($stmt->error ? $stmt->error : "No se encontró la caja o ya está cerrada");
        echo "Error al cerrar la caja: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    // Si alguien intenta acceder directamente a este archivo sin enviar el formulario
    echo "Acceso no autorizado";
}
?>