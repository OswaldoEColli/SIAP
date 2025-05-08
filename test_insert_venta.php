<?php
// Prueba simple para insertar una venta directamente
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'C:/xampp/htdocs/Servidor-SIAP/config/db_config.php';

echo "<h2>Prueba de inserción en tablas de venta</h2>";

// Verificar la conexión a la base de datos
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

echo "<p>Conexión a la base de datos exitosa.</p>";

// Datos de prueba
$usuarioID = 1; // Asegúrate de que este usuario exista
$clienteID = 6; // Asegúrate de que este cliente exista
$subtotal = 100.00;
$impuestos = 16.00;
$total = 116.00;
$metodoPago = 'Efectivo';

try {
    // Iniciar transacción
    $mysqli->begin_transaction();
    echo "<p>Transacción iniciada.</p>";
    
    // 1. Insertar en la tabla Venta
    $sql_venta = "INSERT INTO Venta (usuarioID, clienteID, fechaVenta, subtotal, impuestos, total, metodoPago, estado) 
                 VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Pagada')";
    
    $stmt_venta = $mysqli->prepare($sql_venta);
    if (!$stmt_venta) {
        throw new Exception("Error preparando statement de venta: " . $mysqli->error);
    }
    
    $stmt_venta->bind_param("iiddds", $usuarioID, $clienteID, $subtotal, $impuestos, $total, $metodoPago);
    
    $result_venta = $stmt_venta->execute();
    if (!$result_venta) {
        throw new Exception("Error ejecutando INSERT en Venta: " . $stmt_venta->error);
    }
    
    $ventaID = $mysqli->insert_id;
    echo "<p>Venta insertada con ID: $ventaID</p>";
    
    // 2. Insertar un detalle de venta de prueba
    $productoID = 5; // Asegúrate de que este producto exista
    $cantidad = 1;
    $tipoVenta = 'Unitario';
    $precioUnitario = 100.00;
    $subtotalDetalle = 100.00;
    
    $sql_detalle = "INSERT INTO DetalleVenta (ventaID, productoID, cantidad, tipoVenta, precioUnitario, subtotal) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt_detalle = $mysqli->prepare($sql_detalle);
    if (!$stmt_detalle) {
        throw new Exception("Error preparando statement de detalle: " . $mysqli->error);
    }
    
    $stmt_detalle->bind_param("iisddd", $ventaID, $productoID, $cantidad, $tipoVenta, $precioUnitario, $subtotalDetalle);
    
    $result_detalle = $stmt_detalle->execute();
    if (!$result_detalle) {
        throw new Exception("Error ejecutando INSERT en DetalleVenta: " . $stmt_detalle->error);
    }
    
    echo "<p>Detalle de venta insertado</p>";
    
    // 3. Insertar un registro de pago
    $sql_pago = "INSERT INTO Pago (ventaID, usuarioID, fechaPago, monto, tipoPago, referencia) 
                VALUES (?, ?, NOW(), ?, ?, 'Prueba')";
    
    $stmt_pago = $mysqli->prepare($sql_pago);
    if (!$stmt_pago) {
        throw new Exception("Error preparando statement de pago: " . $mysqli->error);
    }
    
    $stmt_pago->bind_param("iidd", $ventaID, $usuarioID, $total, $total);
    
    $result_pago = $stmt_pago->execute();
    if (!$result_pago) {
        throw new Exception("Error ejecutando INSERT en Pago: " . $stmt_pago->error);
    }
    
    echo "<p>Pago insertado</p>";
    
    // Confirmar la transacción
    $mysqli->commit();
    echo "<p>Transacción confirmada. La venta de prueba se ha registrado correctamente.</p>";
    
    // Link para ver los resultados
    echo "<p><a href='../views/sales.php'>Volver a ventas</a></p>";
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $mysqli->rollback();
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>La transacción ha sido revertida.</p>";
    
    // Información de depuración adicional
    echo "<h3>Información de depuración:</h3>";
    echo "<pre>";
    echo "Usuario ID: $usuarioID\n";
    echo "Cliente ID: $clienteID\n";
    echo "Método de pago: $metodoPago\n";
    echo "Host: " . DB_SERVER . "\n";
    echo "Base de datos: " . DB_NAME . "\n";
    echo "Usuario DB: " . DB_USERNAME . "\n";
    
    // Verificar si las tablas existen y sus columnas
    $result_tables = $mysqli->query("SHOW TABLES LIKE 'Venta'");
    echo "Tabla Venta existe: " . ($result_tables->num_rows > 0 ? "Sí" : "No") . "\n";
    
    if ($result_tables->num_rows > 0) {
        $result_columns = $mysqli->query("DESCRIBE Venta");
        echo "Columnas de Venta:\n";
        while ($row = $result_columns->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
    }
    
    echo "</pre>";
}
?>