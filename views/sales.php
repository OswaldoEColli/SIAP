<?php
// Iniciar sesión si es necesario
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener el ID del usuario logueado
$usuarioID = isset($_SESSION["usuario_id"]) ? $_SESSION["usuario_id"] : 0;


require_once '../config/db_config.php';

// Verificar si el usuario está autenticado
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

// Función para escribir en el log
$log_file = "../logs/sales_" . date("Y-m-d") . ".log";
function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Asegurarse de que el directorio de logs existe
if (!file_exists("../logs")) {
    mkdir("../logs", 0755, true);
}

// Registrar el inicio de la carga de la página
write_log("Cargando sales.php - Session ID: " . session_id());
write_log("Variables de sesión: " . print_r($_SESSION, true));

// Verificar si ya hay una caja abierta
$cajaAbierta = false;
$cajaActualID = null;

// Consulta para verificar si hay una caja abierta
$sql = "SELECT reporteID FROM ReporteCaja WHERE estado = 'Abierta' LIMIT 1";
$result = $mysqli->query($sql);

write_log("Ejecutando consulta para verificar cajas abiertas: $sql");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $cajaAbierta = true;
    $cajaActualID = $row['reporteID'];
    $_SESSION['caja_actual_id'] = $cajaActualID;
    write_log("Caja abierta encontrada. ID: $cajaActualID");
} else {
    // Si no hay caja abierta, nos aseguramos de que no haya restos de una sesión anterior
    write_log("No se encontró caja abierta. Limpiando variables de sesión relacionadas.");
    if (isset($_SESSION['caja_actual_id'])) {
        unset($_SESSION['caja_actual_id']);
    }
    if (isset($_SESSION['monto_inicial'])) {
        unset($_SESSION['monto_inicial']);
    }
    if (isset($_SESSION['nota_caja'])) {
        unset($_SESSION['nota_caja']);
    }
}

// Verificar si hay un mensaje de éxito o error de cierre de caja
$mensajeCierreCaja = '';
$tipoMensaje = '';

if (isset($_SESSION['cierre_caja_exitoso'])) {
    $mensajeCierreCaja = $_SESSION['cierre_caja_exitoso'];
    $tipoMensaje = 'success';
    unset($_SESSION['cierre_caja_exitoso']);
    write_log("Mensaje de éxito encontrado: $mensajeCierreCaja");
} elseif (isset($_SESSION['cierre_caja_error'])) {
    $mensajeCierreCaja = $_SESSION['cierre_caja_error'];
    $tipoMensaje = 'error';
    unset($_SESSION['cierre_caja_error']);
    write_log("Mensaje de error encontrado: $mensajeCierreCaja");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAP - Sistema de Ventas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/salesStyle.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <!-- Simplificado el header del sidebar -->
        </div>

        <a href="home.php" class="menu-item">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>

        <a href="#" class="menu-item active">
            <i class="fas fa-dollar-sign"></i>
            <span>Ventas</span>
        </a>

        <a href="customer.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span>Clientes</span>
        </a>

        <a href="reports.php" class="menu-item">
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
            <h1>¡Hola! Aquí podrás realizar tus ventas.</h1>
            
            <?php if (!empty($mensajeCierreCaja)): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
                <?php echo $mensajeCierreCaja; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-area">
            <div class="sales-container">
                <?php if ($cajaAbierta): ?>
                <div class="caja-abierta-info">
                    <p><strong>Ya hay una caja abierta.</strong></p>
                    <a href="productsSale.php" class="btn-continuar-venta">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Continuar con las ventas</span>
                    </a>
                </div>
                <?php else: ?>
                <button class="register-btn" id="open-register">
                    <i class="fas fa-cash-register"></i>
                    <span>Abrir caja registradora</span>
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Debug info (solo visible en desarrollo) -->
            <?php if (false): // Cambiar a true para depuración ?>
            <div class="debug-info">
                <h3>Información de depuración</h3>
                <p>Caja abierta: <?php echo $cajaAbierta ? 'Sí' : 'No'; ?></p>
                <p>ID de la caja: <?php echo $cajaActualID; ?></p>
                <p>Session ID: <?php echo session_id(); ?></p>
                <p>Variables de sesión:</p>
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="register-modal">
        <div class="modal-content">
            <h2>Abrir caja registradora</h2>
            <form method="post" action="abrir_caja.php" id="register-form">
                <label for="initial-amount">Monto inicial:</label>
                <input type="number" id="initial-amount" name="initial_amount" placeholder="Ej. 100.00" step="0.01" min="0" required>

                <label for="register-note">Nota (opcional):</label>
                <textarea id="register-note" name="register_note" rows="3" placeholder="Ej. Inicio de turno matutino..."></textarea>

                <div class="modal-footer">
                    <button type="button" id="cancel-modal">Cancelar</button>
                    <button type="submit" id="confirm-open">Abrir</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .alert {
        padding: 12px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        animation: fadeIn 0.5s ease;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    .alert i {
        margin-right: 10px;
        font-size: 18px;
    }
    
    .debug-info {
        margin-top: 20px;
        border: 1px solid #ddd;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    
    .debug-info pre {
        background-color: #eee;
        padding: 10px;
        border-radius: 3px;
        overflow: auto;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>

    <script>
        // Elementos principales
        const floatingLogo = document.getElementById('floating-logo');
        const sidebar = document.getElementById('sidebar');
        
        // Toggle sidebar con el logo flotante
        if (floatingLogo) {
            floatingLogo.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }

        // Código para el toggle de tema
        const themeToggle = document.querySelector('.theme-toggle');
        const body = document.body;

        if (themeToggle) {
            themeToggle.addEventListener('click', function(e) {
                // Evita que el clic en el botón de tema active el sidebar
                e.stopPropagation();
                
                body.classList.toggle('light-mode');
                themeToggle.innerHTML = body.classList.contains('light-mode')
                    ? '<i class="fas fa-moon"></i>'
                    : '<i class="fas fa-sun"></i>';
            });
        }

        // Código para el modal
        const openRegisterBtn = document.getElementById('open-register');
        const registerModal = document.getElementById('register-modal');
        const cancelModalBtn = document.getElementById('cancel-modal');
        const confirmOpenBtn = document.getElementById('confirm-open');
        
        if (openRegisterBtn && registerModal) {
            openRegisterBtn.addEventListener('click', function () {
                registerModal.style.display = 'flex';
            });
        }
        
        if (cancelModalBtn) {
            cancelModalBtn.addEventListener('click', function () {
                registerModal.style.display = 'none';
            });
        }
        
        if (confirmOpenBtn) {
            confirmOpenBtn.addEventListener('click', function (e) {
                const amount = document.getElementById('initial-amount').value.trim();
                
                if (!amount || isNaN(amount) || Number(amount) <= 0) {
                    alert('Por favor ingresa un monto válido.');
                    e.preventDefault(); // Detener el envío del formulario
                    return false;
                }
                
                // El formulario se enviará normalmente si todo está bien
                return true;
            });
        }

        // Manejo de menú activo
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function () {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                }
            });
        });

        
        
        // Inicializar para dispositivos móviles
        if (window.innerWidth <= 768) {
            // En móviles, sidebar comienza oculto (ya configurado en CSS)
            // No es necesario añadir la clase 'active' aquí
        }
        
        // Auto-ocultar alertas después de 7 segundos
        const alertElements = document.querySelectorAll('.alert');
        if (alertElements.length > 0) {
            setTimeout(function() {
                alertElements.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 7000);
        }
        
        // Cerrar el modal al hacer clic fuera de él
        window.addEventListener('click', function(event) {
            if (event.target === registerModal) {
                registerModal.style.display = 'none';
            }
        });

        // Agregar esto dentro del bloque <script> existente en sales.php
// Botón de cerrar sesión
const logoutBtn = document.querySelector('.logout-btn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', function() {
    if (confirm('¿Seguro que deseas cerrar sesión?')) {
      window.location.href = '../controllers/logout.php';
    }
  });
}
    </script>
</body>
</html>