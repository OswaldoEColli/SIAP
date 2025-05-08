<?php
// controllers/procesar_venta.php
// Habilitar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db_config.php';

// Función para registrar un mensaje de log (para depuración)
function escribir_log($mensaje) {
    $archivo_log = "../logs/ventas_" . date("Y-m-d") . ".log";
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($archivo_log, "[$timestamp] $mensaje" . PHP_EOL, FILE_APPEND);
}

// Asegurarse de que el directorio de logs existe
if (!file_exists("../logs")) {
    mkdir("../logs", 0755, true);
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener los datos JSON del cuerpo de la petición
$json_data = file_get_contents('php://input');
escribir_log("Datos recibidos: " . $json_data);

// Verificar si hay datos
if (empty($json_data)) {
    escribir_log("ERROR: No se recibieron datos en la petición");
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
    exit;
}

// Intentar decodificar el JSON
$data = json_decode($json_data, true);

// Verificar si el JSON es válido
if ($data === null) {
    escribir_log("ERROR: JSON inválido: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'JSON inválido: ' . json_last_error_msg()]);
    exit;
}

// Verificar que los datos sean válidos
if (!isset($data['productos']) || empty($data['productos'])) {
    escribir_log("ERROR: Datos de venta inválidos - falta array de productos");
    echo json_encode(['success' => false, 'message' => 'Datos de venta inválidos - falta array de productos']);
    exit;
}

escribir_log("Iniciando procesamiento de venta con " . count($data['productos']) . " productos");

// Depuración de mysqli
escribir_log("Estado de conexión mysqli: " . ($mysqli->ping() ? "conectado" : "desconectado"));
if ($mysqli->connect_error) {
    escribir_log("ERROR de conexión: " . $mysqli->connect_error);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    // Iniciar una transacción
    $mysqli->begin_transaction();
    escribir_log("Transacción iniciada");
    
    // Obtener datos del usuario actual
    if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
        $usuarioID = $_SESSION['usuario_id'];
        escribir_log("Usuario ID obtenido de la sesión: $usuarioID");
    } else if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        $usuarioID = $_SESSION['user_id'];
        escribir_log("Usuario ID obtenido de la sesión (user_id): $usuarioID");
    } else {
        // Si no hay usuario en sesión, usar un valor por defecto
        $usuarioID = 1; // Usuario por defecto
        escribir_log("Usuario no encontrado en sesión, usando ID por defecto: $usuarioID");
    }
    
    // También verificar si viene el ID de usuario en los datos de la venta
    if (isset($data['usuario_id']) && $data['usuario_id'] > 0) {
        $usuarioID = $data['usuario_id'];
        escribir_log("Usuario ID obtenido de los datos de la venta: $usuarioID");
    }
    
    escribir_log("Usuario ID final para la venta: $usuarioID");
    
    // Resto del código igual...
    // ...
    // Obtener datos del cliente
    $clienteID = isset($data['cliente_id']) ? $data['cliente_id'] : 1;
    // Si el ID es 'new' o está vacío, usar el Cliente General
    if ($clienteID === 'new' || empty($clienteID) || $clienteID == 0) {
        $clienteID = 1; // Cliente General
    }
    escribir_log("Cliente ID: $clienteID");
    
    // Calcular totales
    $subtotal = isset($data['subtotal']) ? $data['subtotal'] : array_sum(array_column($data['productos'], 'subtotal'));
    $impuestos = isset($data['impuestos']) ? $data['impuestos'] : $subtotal * 0.16; // 16% de IVA por defecto
    $total = isset($data['total']) ? $data['total'] : $subtotal + $impuestos;
    
    escribir_log("Subtotal: $subtotal, Impuestos: $impuestos, Total: $total");
    
    // Determinar método de pago
    $metodoPago = isset($data['metodo_pago']) ? $data['metodo_pago'] : 'Efectivo';
    escribir_log("Método de pago: $metodoPago");
    
    // Insertar en la tabla Venta
    $sql_venta = "INSERT INTO Venta (usuarioID, clienteID, fechaVenta, subtotal, impuestos, total, metodoPago, estado) 
                 VALUES (?, ?, NOW(), ?, ?, ?, ?, 'Pagada')";
    
    escribir_log("SQL Venta: $sql_venta");
    
    // Preparar y verificar statement
    $stmt_venta = $mysqli->prepare($sql_venta);
    if (!$stmt_venta) {
        throw new Exception("Error preparando statement de venta: " . $mysqli->error);
    }
    
    // Ejecutar bind_param y verificar
    $bind_result = $stmt_venta->bind_param("iiddds", $usuarioID, $clienteID, $subtotal, $impuestos, $total, $metodoPago);
    if (!$bind_result) {
        throw new Exception("Error en bind_param para venta: " . $stmt_venta->error);
    }
    
    // Ejecutar statement y verificar
    $exec_result = $stmt_venta->execute();
    if (!$exec_result) {
        throw new Exception("Error al insertar la venta: " . $stmt_venta->error);
    }
    
    // Obtener el ID de la venta insertada
    $ventaID = $mysqli->insert_id;
    escribir_log("Venta creada con ID: $ventaID");
    
    // Cerrar el statement de venta
    $stmt_venta->close();
    
    // Insertar los detalles de la venta
    $sql_detalle = "INSERT INTO DetalleVenta (ventaID, productoID, cantidad, tipoVenta, precioUnitario, subtotal) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    
    escribir_log("SQL Detalle: $sql_detalle");
    
    // Preparar statement de detalle
    $stmt_detalle = $mysqli->prepare($sql_detalle);
    if (!$stmt_detalle) {
        throw new Exception("Error preparando statement de detalle: " . $mysqli->error);
    }
    
    foreach ($data['productos'] as $index => $producto) {
        $productoID = $producto['id'];
        $cantidad = $producto['quantity'];
        
        // Convertir el tipo de venta al formato de la base de datos
        $tipoVentaBD = 'Unitario'; // Por defecto
        if (isset($producto['saleType'])) {
            switch($producto['saleType']) {
                case 'plancha':
                    $tipoVentaBD = 'Plancha';
                    break;
                case 'media':
                    $tipoVentaBD = 'MediaPlancha';
                    break;
                default:
                    $tipoVentaBD = 'Unitario';
            }
        }
        
        $precioUnitario = $producto['price'];
        $subtotalProducto = $producto['subtotal'];
        
        escribir_log("Producto [$index]: ID=$productoID, Cantidad=$cantidad, Tipo=$tipoVentaBD, Precio=$precioUnitario, Subtotal=$subtotalProducto");
        
        // Bind de parámetros
        $bind_result = $stmt_detalle->bind_param("iisddd", $ventaID, $productoID, $cantidad, $tipoVentaBD, $precioUnitario, $subtotalProducto);
        if (!$bind_result) {
            throw new Exception("Error en bind_param para detalle: " . $stmt_detalle->error);
        }
        
        // Ejecutar statement
        $exec_result = $stmt_detalle->execute();
        if (!$exec_result) {
            throw new Exception("Error al insertar detalle de venta para producto $productoID: " . $stmt_detalle->error);
        }
        
        escribir_log("Detalle de venta agregado para producto $productoID");
        
        // Actualizar el inventario (reducir stock)
        // Nota: Para esto usamos el procedimiento almacenado
        try {
            // Convertir unidades según el tipo de venta
            $unidades_a_restar = $cantidad;
            if ($tipoVentaBD == 'Plancha') {
                // Obtener unidades por plancha
                $sql_unidades = "SELECT unidadesPorPlancha FROM Producto WHERE productoID = ?";
                $stmt_unidades = $mysqli->prepare($sql_unidades);
                if (!$stmt_unidades) {
                    throw new Exception("Error preparando consulta de unidades por plancha: " . $mysqli->error);
                }
                
                $stmt_unidades->bind_param("i", $productoID);
                $stmt_unidades->execute();
                $stmt_unidades->bind_result($unidadesPorPlancha);
                $stmt_unidades->fetch();
                $stmt_unidades->close();
                
                $unidades_a_restar = $cantidad * $unidadesPorPlancha;
            } else if ($tipoVentaBD == 'MediaPlancha') {
                // Obtener unidades por plancha y dividir por 2
                $sql_unidades = "SELECT unidadesPorPlancha FROM Producto WHERE productoID = ?";
                $stmt_unidades = $mysqli->prepare($sql_unidades);
                if (!$stmt_unidades) {
                    throw new Exception("Error preparando consulta de unidades por plancha: " . $mysqli->error);
                }
                
                $stmt_unidades->bind_param("i", $productoID);
                $stmt_unidades->execute();
                $stmt_unidades->bind_result($unidadesPorPlancha);
                $stmt_unidades->fetch();
                $stmt_unidades->close();
                
                $unidades_a_restar = $cantidad * ($unidadesPorPlancha / 2);
            }
            
            // Método alternativo: actualizar directamente el inventario sin usar procedimiento almacenado
            // (por si el procedimiento almacenado no está disponible)
            $sql_check_inventario = "SELECT cantidadPlanchas, cantidadUnidades FROM Inventario WHERE productoID = ?";
            $stmt_check = $mysqli->prepare($sql_check_inventario);
            if (!$stmt_check) {
                throw new Exception("Error preparando verificación de inventario: " . $mysqli->error);
            }
            
            $stmt_check->bind_param("i", $productoID);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                $stmt_check->bind_result($cantidadPlanchas, $cantidadUnidades);
                $stmt_check->fetch();
                
                // Actualizar el inventario
                $sql_update_inventario = "UPDATE Inventario SET cantidadUnidades = cantidadUnidades - ? WHERE productoID = ?";
                $stmt_update = $mysqli->prepare($sql_update_inventario);
                if (!$stmt_update) {
                    throw new Exception("Error preparando actualización de inventario: " . $mysqli->error);
                }
                
                $stmt_update->bind_param("ii", $unidades_a_restar, $productoID);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // No hay inventario, crearlo con valores negativos (deuda de inventario)
                $sql_insert_inventario = "INSERT INTO Inventario (productoID, cantidadPlanchas, cantidadUnidades) VALUES (?, 0, ?)";
                $stmt_insert = $mysqli->prepare($sql_insert_inventario);
                if (!$stmt_insert) {
                    throw new Exception("Error preparando inserción de inventario: " . $mysqli->error);
                }
                
                $unidades_negativas = -$unidades_a_restar;
                $stmt_insert->bind_param("ii", $productoID, $unidades_negativas);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            
            $stmt_check->close();
            
            escribir_log("Inventario actualizado para producto $productoID: -$unidades_a_restar unidades");
        } catch (Exception $e) {
            // Si hay un error al actualizar el inventario, lo registramos pero continuamos
            escribir_log("ERROR al actualizar inventario: " . $e->getMessage());
        }
    }
    
    // Cerrar statement de detalle
    $stmt_detalle->close();
    
    // Registrar el pago
    $sql_pago = "INSERT INTO Pago (ventaID, usuarioID, fechaPago, monto, tipoPago, referencia) 
                VALUES (?, ?, NOW(), ?, ?, ?)";
    
    $referencia = isset($data['referencia_pago']) ? $data['referencia_pago'] : '';
    
    escribir_log("SQL Pago: $sql_pago");
    
    // Preparar statement
    $stmt_pago = $mysqli->prepare($sql_pago);
    if (!$stmt_pago) {
        throw new Exception("Error preparando statement de pago: " . $mysqli->error);
    }
    
    // Bind de parámetros
    $bind_result = $stmt_pago->bind_param("iidss", $ventaID, $usuarioID, $total, $metodoPago, $referencia);
    if (!$bind_result) {
        throw new Exception("Error en bind_param para pago: " . $stmt_pago->error);
    }
    
    // Ejecutar statement
    $exec_result = $stmt_pago->execute();
    if (!$exec_result) {
        throw new Exception("Error al registrar el pago: " . $stmt_pago->error);
    }
    
    // Cerrar statement de pago
    $stmt_pago->close();
    
    escribir_log("Pago registrado para venta $ventaID: $total");
    
    // Si hay una nota, registrarla
    if (isset($data['nota_cierre']) && !empty($data['nota_cierre'])) {
        // Aquí podrías insertar la nota en alguna tabla de notas si existe
        escribir_log("Nota de cierre: " . $data['nota_cierre']);
    }
    
    // Si todo ha ido bien, confirmar la transacción
    $mysqli->commit();
    escribir_log("Transacción completada y confirmada");
    
    // Devolver el resultado exitoso
    echo json_encode([
        'success' => true, 
        'message' => 'Venta registrada correctamente',
        'ventaID' => $ventaID,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    // En caso de error, revertir la transacción
    $mysqli->rollback();
    
    escribir_log("ERROR: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    
    // Devolver el mensaje de error
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar la venta: ' . $e->getMessage()
    ]);
}
?>