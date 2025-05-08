<?php
// Iniciar sesión si es necesario
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir configuración de la base de datos
require_once 'db_config.php';
// Incluir funciones para reportes
require_once 'reportes_funciones.php';

// Procesar solicitud AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? 'sales';
    $groupBy = $_POST['group_by'] ?? 'dia';
    $startDate = $_POST['custom_start_date'] ?? date('Y-m-01');
    $endDate = $_POST['custom_end_date'] ?? date('Y-m-d');
    
    // Validar fechas
    if (empty($startDate) || empty($endDate)) {
        echo json_encode(['error' => 'Las fechas de inicio y fin son requeridas']);
        exit;
    }
    
    if (strtotime($startDate) > strtotime($endDate)) {
        echo json_encode(['error' => 'La fecha de inicio debe ser anterior a la fecha final']);
        exit;
    }
    
    $datos = [];
    
    try {
        switch ($reportType) {
            case 'sales':
                $datos = obtenerVentasPorPeriodo($startDate, $endDate, $groupBy);
                break;
            case 'inventory':
                // Implementar función para reporte de inventario
                // Por ahora devolvemos datos de ejemplo
                $datos = [
                    ['periodo' => 'Producto A', 'total_ventas' => 1200],
                    ['periodo' => 'Producto B', 'total_ventas' => 980],
                    ['periodo' => 'Producto C', 'total_ventas' => 750],
                    ['periodo' => 'Producto D', 'total_ventas' => 620]
                ];
                break;
            case 'customers':
                // Implementar función para reporte de clientes
                // Por ahora devolvemos datos de ejemplo
                $datos = [
                    ['periodo' => 'Cliente Frecuente', 'total_ventas' => 2500],
                    ['periodo' => 'Cliente Regular', 'total_ventas' => 1800],
                    ['periodo' => 'Cliente Ocasional', 'total_ventas' => 950],
                    ['periodo' => 'Cliente Nuevo', 'total_ventas' => 520]
                ];
                break;
            case 'cashflow':
                // Implementar función para reporte de flujo de caja
                // Por ahora devolvemos datos de ejemplo
                $datos = [
                    ['periodo' => 'Ingresos', 'total_ventas' => 15000],
                    ['periodo' => 'Egresos', 'total_ventas' => 8500],
                    ['periodo' => 'Devoluciones', 'total_ventas' => 1200],
                    ['periodo' => 'Balance', 'total_ventas' => 5300]
                ];
                break;
            case 'tax':
                // Implementar función para reporte fiscal
                // Por ahora devolvemos datos de ejemplo
                $datos = [
                    ['periodo' => 'IVA Cobrado', 'total_ventas' => 2400],
                    ['periodo' => 'IVA Pagado', 'total_ventas' => 1350],
                    ['periodo' => 'ISR', 'total_ventas' => 1800],
                    ['periodo' => 'Otros Impuestos', 'total_ventas' => 650]
                ];
                break;
            default:
                $datos = obtenerVentasPorPeriodo($startDate, $endDate, $groupBy);
        }
        
        // Devolver datos como JSON
        header('Content-Type: application/json');
        echo json_encode($datos);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al generar el reporte: ' . $e->getMessage()]);
    }
    
    exit;
}

// Si no es una solicitud POST, redirigir a la página de reportes
header('Location: reports.php');
exit;
?>