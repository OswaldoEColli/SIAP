<?php
// Iniciar sesión
session_start();

// Si el usuario ya está logueado, redirigir a home.php
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../home.php");
    exit;
}

// Recuperar mensajes de error o éxito si existen
$email_err = isset($_SESSION["recovery_email_err"]) ? $_SESSION["recovery_email_err"] : "";
$success_msg = isset($_SESSION["recovery_success"]) ? $_SESSION["recovery_success"] : "";

// Recuperar el email para rellenar el formulario
$email = isset($_SESSION["recovery_email"]) ? $_SESSION["recovery_email"] : "";

// Limpiar variables de sesión
unset($_SESSION["recovery_email_err"]);
unset($_SESSION["recovery_success"]);
unset($_SESSION["recovery_email"]);

// Verificar si hay errores de conexión a la base de datos o a servicios de correo
$system_error = "";
$log_file = "../logs/email_" . date("Y-m-d") . ".log";
$db_error_file = "../logs/db_error_" . date("Y-m-d") . ".log";

// Verificar si hay archivos de log recientes
if (file_exists($log_file) && time() - filemtime($log_file) < 300) { // 5 minutos
    $system_error = "Hay problemas con el servicio de correo. Por favor, inténtelo más tarde.";
}

if (file_exists($db_error_file) && time() - filemtime($db_error_file) < 300) { // 5 minutos
    $system_error = "Hay problemas con la base de datos. Por favor, inténtelo más tarde.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupera tu contraseña</title>
    <!-- Importando las fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/passwordRecoveryStyle.css">
    <style>
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-send {
            background-color: #0056b3;
            transition: background-color 0.2s;
        }
        .btn-send:hover {
            background-color: #003d7e;
        }
        .btn-send:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        /* Animación de carga para el botón */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-top: -8px;
            margin-left: -8px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-top-color: #ffffff;
            animation: spin 1s infinite linear;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <h1 class="recovery-title">Recupera tu contraseña</h1>
        <p class="recovery-description">Te enviaremos un código a tu correo para que puedas recuperar tu cuenta</p>
        
        <?php if(!empty($system_error)): ?>
            <div class="alert alert-warning"><?php echo $system_error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($email_err)): ?>
            <div class="alert alert-danger"><?php echo $email_err; ?></div>
        <?php endif; ?>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <form id="recoveryForm" method="post" action="../controllers/password_recovery_controller.php">
            <div class="form-group">
                <label for="email" class="form-label">Correo</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="Ingresa el correo electrónico" 
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-send" id="btnSend" <?php echo !empty($system_error) ? 'disabled' : ''; ?>>
                    Enviar correo
                </button>
                <button type="button" class="btn btn-cancel" id="btnCancel">Cancelar</button>
            </div>
        </form>
        
        <div class="alternative-options">
            <p>¿Recuerdas tu contraseña? <a href="login.php">Inicia sesión</a></p>
        </div>
    </div>
    
    <script>
        // Añadir funcionalidad al botón Cancelar para redirigir a la página de login
        document.getElementById('btnCancel').addEventListener('click', function() {
            window.location.href = 'login.php';
        });
        
        // Mostrar animación de carga cuando se envía el formulario
        document.getElementById('recoveryForm').addEventListener('submit', function(e) {
            // Validar el email antes de enviar
            const emailInput = document.getElementById('email');
            const emailValue = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(emailValue)) {
                e.preventDefault();
                alert('Por favor, ingresa un correo electrónico válido.');
                emailInput.focus();
                return false;
            }
            
            // Si el email es válido, mostrar animación de carga
            const btnSend = document.getElementById('btnSend');
            btnSend.classList.add('btn-loading');
            btnSend.disabled = true;
            
            // Solo para demostración, simular un retraso antes de enviar
            // Quitar este setTimeout en producción
            /*
            e.preventDefault();
            setTimeout(() => {
                document.getElementById('recoveryForm').submit();
            }, 1500);
            */
        });
        
        // Auto-ocultar alertas después de 8 segundos
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
            }, 8000);
        }
    </script>
</body>
</html>