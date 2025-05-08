<?php
/**
 * Funciones para obtener datos para los reportes
 */

/**
 * Obtener ventas totales en un periodo
 */
function obtenerVentasTotales($fechaInicio, $fechaFin) {
    global $mysqli;
    
    $sql = "SELECT SUM(total) as total_ventas 
            FROM Venta 
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['total_ventas'] ?: 0;
}

/**
 * Obtener ventas del periodo anterior para comparación
 */
function obtenerVentasPeriodoAnterior($fechaInicio, $fechaFin) {
    global $mysqli;
    
    // Calcular la duración del periodo en días
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $diferencia = $inicio->diff($fin);
    $dias = $diferencia->days + 1; // +1 porque incluimos ambos días
    
    // Calcular el periodo anterior
    $finPeriodoAnterior = date('Y-m-d', strtotime($fechaInicio . ' - 1 day'));
    $inicioPeriodoAnterior = date('Y-m-d', strtotime($finPeriodoAnterior . ' - ' . ($dias - 1) . ' days'));
    
    $sql = "SELECT SUM(total) as total_ventas 
            FROM Venta 
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $inicioPeriodoAnterior, $finPeriodoAnterior);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['total_ventas'] ?: 0;
}

/**
 * Obtener número de transacciones en un periodo
 */
function obtenerNumeroTransacciones($fechaInicio, $fechaFin) {
    global $mysqli;
    
    $sql = "SELECT COUNT(*) as total_transacciones 
            FROM Venta 
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['total_transacciones'] ?: 0;
}

/**
 * Obtener transacciones del periodo anterior
 */
function obtenerTransaccionesPeriodoAnterior($fechaInicio, $fechaFin) {
    global $mysqli;
    
    // Calcular la duración del periodo en días
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $diferencia = $inicio->diff($fin);
    $dias = $diferencia->days + 1;
    
    // Calcular el periodo anterior
    $finPeriodoAnterior = date('Y-m-d', strtotime($fechaInicio . ' - 1 day'));
    $inicioPeriodoAnterior = date('Y-m-d', strtotime($finPeriodoAnterior . ' - ' . ($dias - 1) . ' days'));
    
    $sql = "SELECT COUNT(*) as total_transacciones 
            FROM Venta 
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $inicioPeriodoAnterior, $finPeriodoAnterior);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['total_transacciones'] ?: 0;
}

/**
 * Obtener ticket promedio en un periodo
 */
function obtenerTicketPromedio($fechaInicio, $fechaFin) {
    global $mysqli;
    
    $sql = "SELECT AVG(total) as ticket_promedio 
            FROM Venta 
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['ticket_promedio'] ?: 0;
}

/**
 * Obtener ticket promedio del periodo anterior
 */
function obtenerTicketPromedioPeriodoAnterior($fechaInicio, $fechaFin) {
    global $mysqli;
    
    // Calcular la duración del periodo en días
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $diferencia = $inicio->diff($fin);
    $dias = $diferencia->days + 1;
    
    // Calcular el periodo anterior
    $finPeriodoAnterior = date('Y-m-d', strtotime($fechaInicio . ' - 1 day'));
    $inicioPeriodoAnterior = date('Y-m-d', strtotime($finPeriodoAnterior . ' - ' . ($dias - 1) . ' days'));
    
    $sql = "SELECT AVG(total) as ticket_promedio 
            FROM Venta 
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $inicioPeriodoAnterior, $finPeriodoAnterior);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['ticket_promedio'] ?: 0;
}

/**
 * Obtener total de artículos vendidos en un periodo
 */
function obtenerArticulosVendidos($fechaInicio, $fechaFin) {
    global $mysqli;
    
    $sql = "SELECT SUM(dv.cantidad) as total_articulos
            FROM DetalleVenta dv
            JOIN Venta v ON dv.ventaID = v.ventaID
            WHERE v.fechaVenta BETWEEN ? AND ?
            AND v.estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['total_articulos'] ?: 0;
}

/**
 * Obtener artículos vendidos del periodo anterior
 */
function obtenerArticulosVendidosPeriodoAnterior($fechaInicio, $fechaFin) {
    global $mysqli;
    
    // Calcular la duración del periodo en días
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $diferencia = $inicio->diff($fin);
    $dias = $diferencia->days + 1;
    
    // Calcular el periodo anterior
    $finPeriodoAnterior = date('Y-m-d', strtotime($fechaInicio . ' - 1 day'));
    $inicioPeriodoAnterior = date('Y-m-d', strtotime($finPeriodoAnterior . ' - ' . ($dias - 1) . ' days'));
    
    $sql = "SELECT SUM(dv.cantidad) as total_articulos
            FROM DetalleVenta dv
            JOIN Venta v ON dv.ventaID = v.ventaID
            WHERE v.fechaVenta BETWEEN ? AND ?
            AND v.estado = 'Pagada'";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $inicioPeriodoAnterior, $finPeriodoAnterior);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    
    return $fila['total_articulos'] ?: 0;
}

/**
 * Obtener ventas por periodo (día, semana, mes, etc.)
 */
function obtenerVentasPorPeriodo($fechaInicio, $fechaFin, $agrupacion = 'dia') {
    global $mysqli;
    
    switch ($agrupacion) {
        case 'dia':
            $formato = '%Y-%m-%d';
            $label = 'DATE(fechaVenta)';
            break;
        case 'semana':
            $formato = '%Y-%u';
            $label = "CONCAT(YEAR(fechaVenta), '-', WEEK(fechaVenta))";
            break;
        case 'mes':
            $formato = '%Y-%m';
            $label = "DATE_FORMAT(fechaVenta, '%Y-%m')";
            break;
        default:
            $formato = '%Y-%m-%d';
            $label = 'DATE(fechaVenta)';
    }
    
    $sql = "SELECT 
                DATE_FORMAT(fechaVenta, ?) as periodo,
                $label as label,
                SUM(total) as total_ventas
            FROM Venta
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'
            GROUP BY periodo
            ORDER BY fechaVenta";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sss", $formato, $fechaInicio, $fechaFin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $datos = array();
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
    
    return $datos;
}

/**
 * Obtener los productos más vendidos
 */
function obtenerProductosMasVendidos($fechaInicio, $fechaFin, $limite = 5) {
    global $mysqli;
    
    $sql = "SELECT 
                p.nombre, 
                SUM(dv.cantidad) as cantidad_vendida,
                SUM(dv.subtotal) as total_vendido
            FROM DetalleVenta dv
            JOIN Producto p ON dv.productoID = p.productoID
            JOIN Venta v ON dv.ventaID = v.ventaID
            WHERE v.fechaVenta BETWEEN ? AND ?
            AND v.estado = 'Pagada'
            GROUP BY p.productoID
            ORDER BY cantidad_vendida DESC
            LIMIT ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssi", $fechaInicio, $fechaFin, $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $datos = array();
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
    
    return $datos;
}

/**
 * Obtener ventas por método de pago
 */
function obtenerVentasPorMetodoPago($fechaInicio, $fechaFin) {
    global $mysqli;
    
    $sql = "SELECT 
                metodoPago, 
                COUNT(*) as cantidad_ventas,
                SUM(total) as total_ventas
            FROM Venta
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'
            GROUP BY metodoPago";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $datos = array();
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
    
    return $datos;
}

/**
 * Obtener ventas por hora del día
 */
function obtenerVentasPorHora($fechaInicio, $fechaFin) {
    global $mysqli;
    
    $sql = "SELECT 
                HOUR(fechaVenta) as hora, 
                COUNT(*) as cantidad_ventas,
                SUM(total) as total_ventas
            FROM Venta
            WHERE fechaVenta BETWEEN ? AND ?
            AND estado = 'Pagada'
            GROUP BY hora
            ORDER BY hora";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $datos = array();
    // Inicializar todas las horas con 0
    for ($i = 0; $i < 24; $i++) {
        $datos[$i] = [
            'hora' => $i,
            'cantidad_ventas' => 0,
            'total_ventas' => 0
        ];
    }
    
    // Actualizar con datos reales
    while ($fila = $resultado->fetch_assoc()) {
        $hora = intval($fila['hora']);
        $datos[$hora] = $fila;
    }
    
    return array_values($datos);
}

/**
 * Obtener fechas para el rango seleccionado
 */
function obtenerRangoFechas($rango = 'this-month') {
    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime('-1 day'));
    
    switch ($rango) {
        case 'today':
            return ['inicio' => $hoy, 'fin' => $hoy];
        case 'yesterday':
            return ['inicio' => $ayer, 'fin' => $ayer];
        case 'this-week':
            $inicio_semana = date('Y-m-d', strtotime('monday this week'));
            return ['inicio' => $inicio_semana, 'fin' => $hoy];
        case 'last-week':
            $inicio_semana_pasada = date('Y-m-d', strtotime('monday last week'));
            $fin_semana_pasada = date('Y-m-d', strtotime('sunday last week'));
            return ['inicio' => $inicio_semana_pasada, 'fin' => $fin_semana_pasada];
        case 'this-month':
            $inicio_mes = date('Y-m-01');
            return ['inicio' => $inicio_mes, 'fin' => $hoy];
        case 'last-month':
            $inicio_mes_pasado = date('Y-m-01', strtotime('first day of last month'));
            $fin_mes_pasado = date('Y-m-t', strtotime('last day of last month'));
            return ['inicio' => $inicio_mes_pasado, 'fin' => $fin_mes_pasado];
        case 'custom':
            // Para rangos personalizados, se deben proporcionar las fechas
            if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
                return [
                    'inicio' => $_POST['start_date'],
                    'fin' => $_POST['end_date']
                ];
            } else {
                // Si no hay fechas personalizadas, usar mes actual
                $inicio_mes = date('Y-m-01');
                return ['inicio' => $inicio_mes, 'fin' => $hoy];
            }
        default:
            // Por defecto, usar mes actual
            $inicio_mes = date('Y-m-01');
            return ['inicio' => $inicio_mes, 'fin' => $hoy];
    }
}