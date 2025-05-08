<?php
// Iniciar sesión si es necesario
session_start();

// Verificar si el usuario está autenticado
//if (!isset($_SESSION['user_id'])) {
//    header('Location: login.php');
//    exit;
//}

// Incluir configuración de la base de datos
require_once '../config/db_config.php';
// Incluir funciones para reportes
require_once 'reportes_funciones.php';

// Obtener el rango de fechas (por defecto, mes actual)
$rango = isset($_GET['date_range']) ? $_GET['date_range'] : 'this-month';
$fechas = obtenerRangoFechas($rango);
$fechaInicio = $fechas['inicio'];
$fechaFin = $fechas['fin'];

// Si es un rango personalizado y se han proporcionado fechas
if ($rango === 'custom' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $fechaInicio = $_GET['start_date'];
    $fechaFin = $_GET['end_date'];
}

// Obtener datos para estadísticas
$ventasTotales = obtenerVentasTotales($fechaInicio, $fechaFin);
$ventasAnteriores = obtenerVentasPeriodoAnterior($fechaInicio, $fechaFin);
$cambioVentas = $ventasAnteriores > 0 ? (($ventasTotales - $ventasAnteriores) / $ventasAnteriores * 100) : 0;

$numTransacciones = obtenerNumeroTransacciones($fechaInicio, $fechaFin);
$transaccionesAnteriores = obtenerTransaccionesPeriodoAnterior($fechaInicio, $fechaFin);
$cambioTransacciones = $transaccionesAnteriores > 0 ? (($numTransacciones - $transaccionesAnteriores) / $transaccionesAnteriores * 100) : 0;

$ticketPromedio = obtenerTicketPromedio($fechaInicio, $fechaFin);
$ticketAnterior = obtenerTicketPromedioPeriodoAnterior($fechaInicio, $fechaFin);
$cambioTicket = $ticketAnterior > 0 ? (($ticketPromedio - $ticketAnterior) / $ticketAnterior * 100) : 0;

$articulosVendidos = obtenerArticulosVendidos($fechaInicio, $fechaFin);
$articulosAnteriores = obtenerArticulosVendidosPeriodoAnterior($fechaInicio, $fechaFin);
$cambioArticulos = $articulosAnteriores > 0 ? (($articulosVendidos - $articulosAnteriores) / $articulosAnteriores * 100) : 0;

// Obtener datos para los gráficos
$datosVentas = obtenerVentasPorPeriodo($fechaInicio, $fechaFin, 'dia');
$datosPeriodo = [];
$datosValores = [];

foreach ($datosVentas as $dato) {
    $datosPeriodo[] = $dato['periodo'];
    $datosValores[] = $dato['total_ventas'];
}

// Obtener productos más vendidos
$productosMasVendidos = obtenerProductosMasVendidos($fechaInicio, $fechaFin, 5);
$productosNombres = [];
$productosCantidades = [];

foreach ($productosMasVendidos as $producto) {
    $productosNombres[] = $producto['nombre'];
    $productosCantidades[] = $producto['cantidad_vendida'];
}

// Obtener ventas por método de pago
$ventasPorMetodo = obtenerVentasPorMetodoPago($fechaInicio, $fechaFin);
$metodosNombres = [];
$metodosValores = [];

foreach ($ventasPorMetodo as $metodo) {
    $metodosNombres[] = $metodo['metodoPago'];
    $metodosValores[] = $metodo['total_ventas'];
}

// Obtener ventas por hora
$ventasPorHora = obtenerVentasPorHora($fechaInicio, $fechaFin);
$horasEtiquetas = [];
$horasValores = [];

foreach ($ventasPorHora as $hora) {
    $horasEtiquetas[] = $hora['hora'] . ':00';
    $horasValores[] = $hora['total_ventas'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAP - Reportes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="../styles/reportsStyle.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            
        </div>

        <a href="home.php" class="menu-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>

        <a href="sales.php" class="menu-item">
            <i class="fas fa-dollar-sign"></i>
            <span>Ventas</span>
        </a>

        <a href="customer.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span>Clientes</span>
        </a>

        <a href="reports.php" class="menu-item active">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
        </a>

        <a href="inventory.php" class="menu-item">
            <i class="fas fa-boxes"></i>
            <span>Inventario</span>
        </a>

        <a href="branches.php" class="menu-item">
            <i class="fas fa-map-marker-alt"></i>
            <span>Sucursales</span>
        </a>

        <a href="settings.php" class="menu-item">
            <i class="fas fa-cog"></i>
            <span>Ajustes</span>
        </a>

        <button class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Salir</span>
        </button>
    </div>

    <!-- Logo flotante que se mueve con el sidebar -->
    <div class="floating-logo" id="floating-logo">
        <div class="logo">SIAP </div>
        <img src="../photos/logo 3.png" alt="Logo SIAP" class="logo-img">
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1>Reportes y Estadísticas</h1>
        </div>

        <div class="content-area">
            <div class="date-filters">
                <h2>Filtros de Fecha</h2>
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="date-range">Rango predefinido:</label>
                        <select id="date-range" name="date_range">
                            <option value="today" <?php echo $rango === 'today' ? 'selected' : ''; ?>>Hoy</option>
                            <option value="yesterday" <?php echo $rango === 'yesterday' ? 'selected' : ''; ?>>Ayer</option>
                            <option value="this-week" <?php echo $rango === 'this-week' ? 'selected' : ''; ?>>Esta semana</option>
                            <option value="last-week" <?php echo $rango === 'last-week' ? 'selected' : ''; ?>>Semana pasada</option>
                            <option value="this-month" <?php echo $rango === 'this-month' ? 'selected' : ''; ?>>Este mes</option>
                            <option value="last-month" <?php echo $rango === 'last-month' ? 'selected' : ''; ?>>Mes anterior</option>
                            <option value="custom" <?php echo $rango === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" id="custom-date-start" style="<?php echo $rango === 'custom' ? 'display:flex;' : 'display:none;'; ?>">
                        <label for="start-date">Fecha inicial:</label>
                        <input type="date" id="start-date" name="start_date" value="<?php echo $fechaInicio; ?>">
                    </div>
                    
                    <div class="filter-group" id="custom-date-end" style="<?php echo $rango === 'custom' ? 'display:flex;' : 'display:none;'; ?>">
                        <label for="end-date">Fecha final:</label>
                        <input type="date" id="end-date" name="end_date" value="<?php echo $fechaFin; ?>">
                    </div>
                    
                    <div class="filter-buttons">
                        <button class="apply-btn">Aplicar</button>
                        <button class="reset-btn">Reiniciar</button>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de estadísticas con datos reales -->
            <div class="stats-cards">
                <?php
                // Datos estadísticos desde la base de datos
                $statsData = [
                    [
                        'title' => 'Ventas Totales',
                        'value' => '$' . number_format($ventasTotales, 2),
                        'change' => round($cambioVentas, 1),
                        'positive' => $cambioVentas >= 0
                    ],
                    [
                        'title' => 'Transacciones',
                        'value' => number_format($numTransacciones),
                        'change' => round($cambioTransacciones, 1),
                        'positive' => $cambioTransacciones >= 0
                    ],
                    [
                        'title' => 'Ticket Promedio',
                        'value' => '$' . number_format($ticketPromedio, 2),
                        'change' => round($cambioTicket, 1),
                        'positive' => $cambioTicket >= 0
                    ],
                    [
                        'title' => 'Artículos Vendidos',
                        'value' => number_format($articulosVendidos),
                        'change' => round($cambioArticulos, 1),
                        'positive' => $cambioArticulos >= 0
                    ]
                ];

                // Generar las tarjetas dinámicamente
                foreach ($statsData as $stat) {
                    $changeClass = $stat['positive'] ? 'positive' : 'negative';
                    $changeIcon = $stat['positive'] ? 'fa-arrow-up' : 'fa-arrow-down';
                    
                    echo '<div class="stat-card">';
                    echo '    <div class="stat-card-title">'.$stat['title'].'</div>';
                    echo '    <div class="stat-card-value">'.$stat['value'].'</div>';
                    echo '    <div class="stat-card-change '.$changeClass.'">';
                    echo '        <i class="fas '.$changeIcon.'"></i> '.$stat['change'].'% vs periodo anterior';
                    echo '    </div>';
                    echo '</div>';
                }
                ?>
            </div>
            <!-- Gráfico principal -->
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Ventas por Período</h2>
                    <div class="chart-actions">
                        <button id="download-sales-chart"><i class="fas fa-download"></i></button>
                    </div>
                </div>
                <div id="chart-type-selector">
                    <button class="chart-type-btn active" data-type="line">Líneas</button>
                    <button class="chart-type-btn" data-type="bar">Barras</button>
                    <button class="chart-type-btn" data-type="area">Área</button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="sales-chart"></canvas>
                </div>
            </div>

            <!-- Grid de gráficos pequeños -->
            <div class="charts-grid">
                <?php
                // Configuración de gráficos pequeños
                $smallCharts = [
                    [
                        'id' => 'products-chart',
                        'title' => 'Productos Más Vendidos'
                    ],
                    [
                        'id' => 'categories-chart',
                        'title' => 'Ventas por Categoría'
                    ],
                    [
                        'id' => 'payment-chart',
                        'title' => 'Métodos de Pago'
                    ],
                    [
                        'id' => 'hours-chart',
                        'title' => 'Horarios de Venta'
                    ]
                ];

                // Generar los contenedores de gráficos pequeños
                foreach ($smallCharts as $chart) {
                    echo '<div class="small-chart-container">';
                    echo '    <div class="small-chart-header">';
                    echo '        <h3>'.$chart['title'].'</h3>';
                    echo '        <button class="download-chart-btn" data-chart="'.$chart['id'].'"><i class="fas fa-download"></i></button>';
                    echo '    </div>';
                    echo '    <div class="small-chart-wrapper">';
                    echo '        <canvas id="'.$chart['id'].'"></canvas>';
                    echo '    </div>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Tarjetas de reportes -->
            <div class="reports-container">
                <?php
                // Configuración de tarjetas de reportes
                $reportCards = [
                    [
                        'id' => 'sales-report',
                        'icon' => 'fas fa-file-invoice-dollar',
                        'title' => 'Reporte de Ventas',
                        'description' => 'Resumen de ventas, ingresos y artículos más vendidos.'
                    ],
                    [
                        'id' => 'inventory-report',
                        'icon' => 'fas fa-boxes',
                        'title' => 'Reporte de Inventario',
                        'description' => 'Estado actual del inventario, rotación y alertas de stock.'
                    ],
                    [
                        'id' => 'customer-report',
                        'icon' => 'fas fa-users',
                        'title' => 'Reporte de Clientes',
                        'description' => 'Análisis de clientes y comportamiento de compra.'
                    ],
                    [
                        'id' => 'cashflow-report',
                        'icon' => 'fas fa-money-bill-wave',
                        'title' => 'Flujo de Caja',
                        'description' => 'Reporte de ingresos y egresos diarios.'
                    ],
                    [
                        'id' => 'tax-report',
                        'icon' => 'fas fa-receipt',
                        'title' => 'Reporte Fiscal',
                        'description' => 'Resumen de impuestos y emisión de documentos fiscales.'
                    ],
                    [
                        'id' => 'custom-report',
                        'icon' => 'fas fa-sliders-h',
                        'title' => 'Reporte Personalizado',
                        'description' => 'Crea un reporte con los parámetros que necesites.'
                    ]
                ];

                // Generar las tarjetas de reportes dinámicamente
                foreach ($reportCards as $card) {
                    echo '<div class="report-card" id="'.$card['id'].'">';
                    echo '    <i class="'.$card['icon'].'"></i>';
                    echo '    <h3>'.$card['title'].'</h3>';
                    echo '    <p>'.$card['description'].'</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modales para reportes -->
    <?php
    // Configuración de modales de reportes
    $reportModals = [
        [
            'id' => 'sales-modal',
            'title' => 'Reporte de Ventas',
            'chartId' => 'modal-sales-chart'
        ],
        [
            'id' => 'inventory-modal',
            'title' => 'Reporte de Inventario',
            'chartId' => 'modal-inventory-chart'
        ],
        [
            'id' => 'customer-modal',
            'title' => 'Reporte de Clientes',
            'chartId' => 'modal-customer-chart'
        ],
        [
            'id' => 'cashflow-modal',
            'title' => 'Reporte de Flujo de Caja',
            'chartId' => 'modal-cashflow-chart'
        ],
        [
            'id' => 'tax-modal',
            'title' => 'Reporte Fiscal',
            'chartId' => 'modal-tax-chart'
        ]
    ];

    // Generar los modales dinámicamente
    foreach ($reportModals as $modal) {
        echo '<div id="'.$modal['id'].'" class="report-modal">';
        echo '    <div class="modal-content">';
        echo '        <div class="modal-header">';
        echo '            <h2>'.$modal['title'].'</h2>';
        echo '            <button class="close-modal"><i class="fas fa-times"></i></button>';
        echo '        </div>';
        echo '        <div class="modal-body">';
        echo '            <!-- Gráfico dentro del modal -->';
        echo '            <div class="chart-wrapper" style="margin-bottom: 20px;">';
        echo '                <canvas id="'.$modal['chartId'].'"></canvas>';
        echo '            </div>';
        
        // Aquí iría código específico para cada modal, como tablas de datos
        echo '            <div class="modal-data-placeholder">Cargando datos...</div>';
        
        echo '            <div class="summary-stats">';
        echo '                <h3>Resumen</h3>';
        echo '                <p><strong>Total:</strong> $'.number_format($ventasTotales, 2).'</p>';
        echo '            </div>';
        echo '        </div>';
        echo '        <div class="modal-footer">';
        echo '            <button class="print-btn"><i class="fas fa-print"></i> Imprimir</button>';
        echo '            <button class="export-btn"><i class="fas fa-file-export"></i> Exportar</button>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';
    }
    ?>

    <!-- Modal de reporte personalizado -->
    <div id="custom-modal" class="report-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reporte Personalizado</h2>
                <button class="close-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="date-filters" style="margin-bottom: 20px;">
                    <h3>Configura tu reporte</h3>
                    <form id="custom-report-form" method="post" action="generar_reporte.php">
                        <div class="filters-grid" style="margin-top: 15px;">
                            <div class="filter-group">
                                <label for="report-type">Tipo de reporte:</label>
                                <select id="report-type" name="report_type">
                                    <option value="sales">Ventas</option>
                                    <option value="inventory">Inventario</option>
                                    <option value="customers">Clientes</option>
                                    <option value="cashflow">Flujo de caja</option>
                                    <option value="tax">Fiscal</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="group-by">Agrupar por:</label>
                                <select id="group-by" name="group_by">
                                    <option value="dia">Día</option>
                                    <option value="semana">Semana</option>
                                    <option value="mes">Mes</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="custom-start-date">Fecha inicial:</label>
                                <input type="date" id="custom-start-date" name="custom_start_date" value="<?php echo date('Y-m-01'); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="custom-end-date">Fecha final:</label>
                                <input type="date" id="custom-end-date" name="custom_end_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <button type="button" class="apply-btn" id="generate-custom-report" style="margin-top: 15px;">Generar Reporte</button>
                    </form>
                </div>
                
                <div id="custom-report-content">
                    <p style="text-align: center; padding: 20px;">Configura los parámetros y presiona "Generar Reporte" para ver los resultados.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="print-btn"><i class="fas fa-print"></i> Imprimir</button>
                <button class="export-btn"><i class="fas fa-file-export"></i> Exportar</button>
            </div>
        </div>
    </div>
    <script>
        // Datos para los gráficos desde PHP
        const datosPeriodo = <?php echo json_encode($datosPeriodo); ?>;
        const datosValores = <?php echo json_encode($datosValores); ?>;
        const productosNombres = <?php echo json_encode($productosNombres); ?>;
        const productosCantidades = <?php echo json_encode($productosCantidades); ?>;
        const metodosNombres = <?php echo json_encode($metodosNombres); ?>;
        const metodosValores = <?php echo json_encode($metodosValores); ?>;
        const horasEtiquetas = <?php echo json_encode($horasEtiquetas); ?>;
        const horasValores = <?php echo json_encode($horasValores); ?>;

        // Elementos principales
        const floatingLogo = document.getElementById('floating-logo');
        const sidebar = document.getElementById('sidebar');
        // Toggle sidebar con el logo flotante
        if (floatingLogo) {
            floatingLogo.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }

        // Manejo del sidebar en móvil
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Ocultar/mostrar fechas personalizadas
        document.getElementById('date-range').addEventListener('change', function() {
            const customDateStart = document.getElementById('custom-date-start');
            const customDateEnd = document.getElementById('custom-date-end');
            
            if (this.value === 'custom') {
                customDateStart.style.display = 'flex';
                customDateEnd.style.display = 'flex';
            } else {
                customDateStart.style.display = 'none';
                customDateEnd.style.display = 'none';
            }
        });

        // Manejo de los modales
        const reportCards = document.querySelectorAll('.report-card');
        reportCards.forEach(card => {
            card.addEventListener('click', function() {
                const reportId = this.id;
                const modalId = reportId.replace('report', 'modal');
                const modal = document.getElementById(modalId);
                modal.style.display = 'flex';
            });
        });

        // Cerrar modales
        const closeButtons = document.querySelectorAll('.close-modal');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.report-modal');
                modal.style.display = 'none';
            });
        });

        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.report-modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Cambio de tipo de gráfico
        const chartTypeButtons = document.querySelectorAll('.chart-type-btn');
        chartTypeButtons.forEach(button => {
            button.addEventListener('click', function() {
                chartTypeButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                updateMainChart(this.getAttribute('data-type'));
            });
        });

        // Inicialización de gráficos
        let salesChart, productsChart, categoriesChart, paymentChart, hoursChart;
        let modalSalesChart, modalInventoryChart, modalCustomerChart, modalCashflowChart, modalTaxChart;

        function initCharts() {
            // Gráfico principal de ventas
            const salesCtx = document.getElementById('sales-chart').getContext('2d');
            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: datosPeriodo,
                    datasets: [{
                        label: 'Ventas ' + new Date().getFullYear(),
                        data: datosValores,
                        borderColor: '#2e3b7c',
                        backgroundColor: 'rgba(46, 59, 124, 0.1)',
                        tension: 0.3,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Gráfico de productos más vendidos
            const productsCtx = document.getElementById('products-chart').getContext('2d');
            productsChart = new Chart(productsCtx, {
                type: 'bar',
                data: {
                    labels: productosNombres,
                    datasets: [{
                        label: 'Unidades vendidas',
                        data: productosCantidades,
                        backgroundColor: [
                            'rgba(46, 59, 124, 0.8)',
                            'rgba(46, 59, 124, 0.6)',
                            'rgba(46, 59, 124, 0.4)',
                            'rgba(46, 59, 124, 0.3)',
                            'rgba(46, 59, 124, 0.2)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Gráfico de ventas por categoría (usando metodoPago como ejemplo)
            const categoriesCtx = document.getElementById('categories-chart').getContext('2d');
            categoriesChart = new Chart(categoriesCtx, {
                type: 'pie',
                data: {
                    labels: metodosNombres,
                    datasets: [{
                        data: metodosValores,
                        backgroundColor: [
                            '#2e3b7c',
                            '#4c5c9c',
                            '#6b7dbc',
                            '#8a9edc'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            // Gráfico de métodos de pago
            const paymentCtx = document.getElementById('payment-chart').getContext('2d');
            paymentChart = new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: metodosNombres,
                    datasets: [{
                        data: metodosValores,
                        backgroundColor: [
                            '#2e3b7c',
                            '#4c5c9c',
                            '#6b7dbc',
                            '#8a9edc'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            // Gráfico de horarios de venta
            const hoursCtx = document.getElementById('hours-chart').getContext('2d');
            hoursChart = new Chart(hoursCtx, {
                type: 'line',
                data: {
                    labels: horasEtiquetas,
                    datasets: [{
                        label: 'Ventas por hora',
                        data: horasValores,
                        borderColor: '#2e3b7c',
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Inicializar gráficos en modales
            initModalCharts();
        }

        function initModalCharts() {
            // Inicialización de gráficos en modales si existen los elementos
            const modalSalesCtx = document.getElementById('modal-sales-chart');
            if (modalSalesCtx) {
                modalSalesChart = new Chart(modalSalesCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: datosPeriodo,
                        datasets: [{
                            label: 'Ventas por período',
                            data: datosValores,
                            backgroundColor: 'rgba(46, 59, 124, 0.7)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Inicialización del resto de gráficos en modales (simplificado)
            const modalIds = ['modal-inventory-chart', 'modal-customer-chart', 'modal-cashflow-chart', 'modal-tax-chart'];
            
            modalIds.forEach(id => {
                const canvas = document.getElementById(id);
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                            datasets: [{
                                label: 'Datos de ejemplo',
                                data: [450, 580, 620, 490, 780, 950, 525],
                                backgroundColor: 'rgba(46, 59, 124, 0.7)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            });
        }

        function updateMainChart(chartType) {
            let type = chartType;
            let fill = false;
            
            if (chartType === 'area') {
                type = 'line';
                fill = true;
            }
            
            salesChart.config.type = type;
            salesChart.data.datasets[0].fill = fill;
            salesChart.update();
        }

        // Iniciar todos los gráficos cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', initCharts);

        // Manejo de botones de descarga (simulación)
        document.getElementById('download-sales-chart').addEventListener('click', function() {
            // Obtener el canvas como imagen
            const canvas = document.getElementById('sales-chart');
            const imageURL = canvas.toDataURL('image/png');
            
            // Crear un enlace temporal para descargar
            const link = document.createElement('a');
            link.href = imageURL;
            link.download = 'ventas_por_periodo.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        const downloadButtons = document.querySelectorAll('.download-chart-btn');
        downloadButtons.forEach(button => {
            button.addEventListener('click', function() {
                const chartId = this.getAttribute('data-chart');
                const canvas = document.getElementById(chartId);
                const imageURL = canvas.toDataURL('image/png');
                
                // Crear un enlace temporal para descargar
                const link = document.createElement('a');
                link.href = imageURL;
                link.download = chartId + '.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });

        // Botones de impresión y exportación en modales
        const printButtons = document.querySelectorAll('.print-btn');
        printButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modalContent = this.closest('.modal-content');
                
                // Crear estilos de impresión
                const printStyles = `
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .modal-header, .modal-footer { display: none; }
                        .chart-wrapper { max-width: 100%; height: auto; }
                    </style>
                `;
                
                // Crear ventana de impresión
                const printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Imprimir Reporte</title>');
                printWindow.document.write(printStyles);
                printWindow.document.write('</head><body>');
                printWindow.document.write('<div class="print-content">');
                printWindow.document.write(modalContent.querySelector('.modal-body').innerHTML);
                printWindow.document.write('</div></body></html>');
                
                printWindow.document.close();
                printWindow.focus();
                
                // Esperar a que se carguen las imágenes antes de imprimir
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 1000);
            });
        });

        const exportButtons = document.querySelectorAll('.export-btn');
        exportButtons.forEach(button => {
            button.addEventListener('click', function() {
                alert('Función de exportación a Excel pendiente de implementación.');
            });
        });
        
        // Filtros de fecha - Manejo de aplicación de filtros
        document.querySelector('.apply-btn').addEventListener('click', function() {
            const dateRange = document.getElementById('date-range').value;
            let url = 'reports.php?date_range=' + dateRange;
            
            // Si es rango personalizado, agregar fechas
            if (dateRange === 'custom') {
                const startDate = document.getElementById('start-date').value;
                const endDate = document.getElementById('end-date').value;
                
                if (startDate && endDate) {
                    url += '&start_date=' + startDate + '&end_date=' + endDate;
                }
            }
            
            window.location.href = url;
        });

        // Reiniciar filtros
        document.querySelector('.reset-btn').addEventListener('click', function() {
            window.location.href = 'reports.php';
        });
        
        // Generar reporte personalizado
        document.getElementById('generate-custom-report').addEventListener('click', function() {
            const reportType = document.getElementById('report-type').value;
            const groupBy = document.getElementById('group-by').value;
            const startDate = document.getElementById('custom-start-date').value;
            const endDate = document.getElementById('custom-end-date').value;
            
            // Validar fechas
            if (!startDate || !endDate) {
                alert('Por favor seleccione fechas de inicio y fin.');
                return;
            }
            
            const formData = new FormData();
            formData.append('report_type', reportType);
            formData.append('group_by', groupBy);
            formData.append('custom_start_date', startDate);
            formData.append('custom_end_date', endDate);
            
            // Mostrar cargando
            document.getElementById('custom-report-content').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <p>Generando reporte, por favor espere...</p>
                </div>
            `;
            
            // Enviar solicitud AJAX
            fetch('generar_reporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('custom-report-content').innerHTML = `
                        <div style="text-align: center; padding: 20px; color: #d9534f;">
                            <p>${data.error}</p>
                        </div>
                    `;
                    return;
                }
                
                // Crear tabla de resultados
                let tablaHTML = `
                    <h3>Reporte de ${getReportTypeName(reportType)} (${startDate} - ${endDate})</h3>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Total Ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                let totalGeneral = 0;
                
                if (data.length === 0) {
                    tablaHTML += `
                        <tr>
                            <td colspan="2" style="text-align: center;">No hay datos para mostrar en el período seleccionado.</td>
                        </tr>
                    `;
                } else {
                    data.forEach(item => {
                        const totalVentas = parseFloat(item.total_ventas) || 0;
                        tablaHTML += `
                            <tr>
                                <td>${item.periodo}</td>
                                <td>$${totalVentas.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `;
                        
                        totalGeneral += totalVentas;
                    });
                }
                
                tablaHTML += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total General:</th>
                                <th>$${totalGeneral.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</th>
                            </tr>
                        </tfoot>
                    </table>
                `;
                
                document.getElementById('custom-report-content').innerHTML = tablaHTML;
                
                // Crear un gráfico con los datos
                const chartContainer = document.createElement('div');
                chartContainer.className = 'chart-wrapper';
                chartContainer.style.marginTop = '20px';
                chartContainer.style.height = '300px';
                
                const canvas = document.createElement('canvas');
                canvas.id = 'custom-report-chart';
                chartContainer.appendChild(canvas);
                
                document.getElementById('custom-report-content').appendChild(chartContainer);
                
                const ctx = canvas.getContext('2d');
                const chartLabels = data.map(item => item.periodo);
                const chartData = data.map(item => parseFloat(item.total_ventas) || 0);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Total Ventas',
                            data: chartData,
                            backgroundColor: 'rgba(46, 59, 124, 0.7)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('custom-report-content').innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #d9534f;">
                        <p>Error al generar el reporte. Por favor intente nuevamente.</p>
                    </div>
                `;
            });
        });

        // Función auxiliar para obtener el nombre legible del tipo de reporte
        function getReportTypeName(type) {
            const types = {
                'sales': 'Ventas',
                'inventory': 'Inventario',
                'customers': 'Clientes',
                'cashflow': 'Flujo de Caja',
                'tax': 'Fiscal'
            };
            
            return types[type] || type;
        }
         // Script específico para el botón de logout
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Inicializando script de logout");
        
        const logoutBtn = document.querySelector('.logout-btn');
        console.log("Botón logout encontrado:", logoutBtn);
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                console.log("Botón logout clickeado");
                if (confirm('¿Seguro que deseas cerrar sesión?')) {
                    window.location.href = '../controllers/logout.php';
                }
            });
        }
    });
    </script>
</body>
</html>