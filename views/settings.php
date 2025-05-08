<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once "../config/db_config.php";

// Obtener información del usuario actual
$usuarioID = $_SESSION["usuarioID"];
$nombre = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";
$apellidos = isset($_SESSION["apellidos"]) ? $_SESSION["apellidos"] : "";
$nombreUsuario = isset($_SESSION["nombreUsuario"]) ? $_SESSION["nombreUsuario"] : "";
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "";
$tipoUsuario = isset($_SESSION["tipoUsuario"]) ? $_SESSION["tipoUsuario"] : "";

// Recuperar mensajes de sesión
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Limpiar mensajes de sesión
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Procesar cambio de contraseña si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'user') {
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];
    
    // Validar datos
    $errors = array();
    
    if (empty($currentPassword)) {
        $errors[] = "Debes ingresar tu contraseña actual";
    }
    
    if (empty($newPassword)) {
        $errors[] = "Debes ingresar una nueva contraseña";
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    // Si no hay errores, verificar contraseña actual y actualizar
    if (empty($errors)) {
        // Verificar contraseña actual
        $query = "SELECT contraseña FROM Usuario WHERE usuarioID = ?";
        
        if ($stmt = $mysqli->prepare($query)) {
            $stmt->bind_param("i", $usuarioID);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $stored_password = $row['contraseña'];
                    
                    // Verificar si la contraseña actual es correcta
                    if ($currentPassword === $stored_password) { // En producción, usar password_verify()
                        // Cerrar el resultado primero antes de continuar con otra consulta
                        $stmt->close();
                        
                        // Ahora hacemos la actualización
                        $update_query = "UPDATE Usuario SET contraseña = ? WHERE usuarioID = ?";
                        
                        if ($update_stmt = $mysqli->prepare($update_query)) {
                            $update_stmt->bind_param("si", $newPassword, $usuarioID);
                            
                            if ($update_stmt->execute()) {
                                $_SESSION['success_message'] = "Contraseña actualizada correctamente";
                            } else {
                                $_SESSION['error_message'] = "Error al actualizar la contraseña: " . $mysqli->error;
                            }
                            
                            $update_stmt->close();
                        }
                    } else {
                        $_SESSION['error_message'] = "La contraseña actual es incorrecta";
                        $stmt->close();
                    }
                } else {
                    $_SESSION['error_message'] = "Usuario no encontrado";
                    $stmt->close();
                }
            } else {
                $_SESSION['error_message'] = "Error en la consulta: " . $mysqli->error;
                $stmt->close();
            }
        } else {
            $_SESSION['error_message'] = "Error al preparar la consulta: " . $mysqli->error;
        }
        
        // Redirigir para evitar reenvío del formulario
        header("Location: settings.php");
        exit;
    } else {
        $_SESSION['error_message'] = implode(", ", $errors);
        header("Location: settings.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAP - Ajustes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/settingsStyle.css">
    <style>
        /* Estilos para las notificaciones */
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .notification .close-btn {
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>
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

        <a href="settings.php" class="menu-item active">
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
        <div class="content-area">
            <div class="settings-header">
                <h1>Ajustes del Sistema</h1>
            </div>
            
            <!-- Mostrar notificaciones -->
            <?php if (!empty($success_message)): ?>
                <div class="notification success">
                    <span><?php echo $success_message; ?></span>
                    <button class="close-btn">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="notification error">
                    <span><?php echo $error_message; ?></span>
                    <button class="close-btn">&times;</button>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Ajustes de Negocio -->
                <div class="settings-card">
                    <h2><i class="fas fa-store"></i> Información del Negocio</h2>
                    
                    <form method="post" action="settings.php" id="business-form">
                        <input type="hidden" name="form_type" value="business">
                        <div class="form-group">
                            <label for="business-name">Nombre del Negocio</label>
                            <input type="text" id="business-name" name="business_name" value="Distribuidora Pepsi Palizada">
                        </div>
                        
                        <div class="form-group">
                            <label for="business-rfc">RFC</label>
                            <input type="text" id="business-rfc" name="business_rfc" value="XAXX010101000">
                        </div>
                        
                        <div class="form-group">
                            <label for="business-address">Dirección</label>
                            <textarea id="business-address" name="business_address" rows="2">Av. Ejemplo #123, Col. Centro</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="business-phone">Teléfono</label>
                            <input type="text" id="business-phone" name="business_phone" value="55 1234 5678">
                        </div>
                        
                        <button type="submit" class="save-btn" data-form="business-form">Guardar Cambios</button>
                    </form>
                </div>
                
                <!-- Ajustes de Usuario -->
                <div class="settings-card">
                    <h2><i class="fas fa-user-cog"></i> Usuario y Seguridad</h2>
                    
                    <form method="post" action="settings.php" id="user-form">
                        <input type="hidden" name="form_type" value="user">
                        <div class="form-group">
                            <label for="username">Nombre de Usuario</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($nombreUsuario); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="user-email">Correo Electrónico</label>
                            <input type="email" id="user-email" name="user_email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="user-type">Tipo de Usuario</label>
                            <input type="text" id="user-type" name="user_type" value="<?php echo htmlspecialchars($tipoUsuario); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="current-password">Contraseña Actual</label>
                            <input type="password" id="current-password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new-password">Nueva Contraseña</label>
                            <input type="password" id="new-password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm-password">Confirmar Contraseña</label>
                            <input type="password" id="confirm-password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="save-btn" data-form="user-form">Actualizar Contraseña</button>
                    </form>
                </div>
                
                <!-- Ajustes del Sistema -->
                <div class="settings-card">
                    <h2><i class="fas fa-sliders-h"></i> Sistema</h2>
                    
                    <form method="post" action="settings.php" id="system-form">
                        <input type="hidden" name="form_type" value="system">
                        <div class="form-group">
                            <label for="language">Idioma</label>
                            <select id="language" name="language">
                                <option value="es" selected>Español</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Zona Horaria</label>
                            <select id="timezone" name="timezone">
                                <option value="America/Mexico_City" selected>Ciudad de México (GMT-6)</option>
                                <option value="America/New_York">Nueva York (GMT-5)</option>
                                <option value="Europe/Madrid">Madrid (GMT+1)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date-format">Formato de Fecha</label>
                            <select id="date-format" name="date_format">
                                <option value="dd/mm/yyyy" selected>DD/MM/AAAA</option>
                                <option value="mm/dd/yyyy">MM/DD/AAAA</option>
                                <option value="yyyy-mm-dd">AAAA-MM-DD</option>
                            </select>
                        </div>
                        
                        <div class="toggle-group">
                            <span class="toggle-label">Modo Oscuro</span>
                            <label class="toggle-switch">
                                <input type="checkbox" name="dark_mode" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <button type="submit" class="save-btn" data-form="system-form">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

        // Inicializar para dispositivos móviles
        if (window.innerWidth <= 768) {
            // En móviles, sidebar comienza oculto (ya configurado en CSS)
            sidebar.classList.add('active');
        }

        // Cerrando notificaciones
        const closeButtons = document.querySelectorAll('.notification .close-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.notification').style.display = 'none';
            });
        });
        
        // Validación de formulario de usuario
        document.getElementById('user-form').addEventListener('submit', function(event) {
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const currentPassword = document.getElementById('current-password').value;
            
            if (!currentPassword) {
                alert('Debes ingresar tu contraseña actual');
                event.preventDefault();
                return;
            }
            
            if (!newPassword) {
                alert('Debes ingresar una nueva contraseña');
                event.preventDefault();
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                event.preventDefault();
                return;
            }
        });

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

        document.querySelector('.logout-btn').addEventListener('click', function () {
            if (confirm('¿Seguro que deseas cerrar sesión?')) {
                window.location.href = '../controllers/logout.php';
            }
        });
    </script>
</body>
</html>