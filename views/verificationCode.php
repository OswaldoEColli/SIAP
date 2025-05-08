<?php
// Iniciar sesión
session_start();

// Si el usuario ya está logueado, redirigir a home.php
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../home.php");
    exit;
}

// Verificar si existe un token de recuperación
if(!isset($_SESSION["recovery_token"]) || empty($_SESSION["recovery_token"])) {
    header("location: passwordRecovery.php");
    exit;
}

// Recuperar mensajes de error o éxito si existen
$code_err = isset($_SESSION["verification_code_err"]) ? $_SESSION["verification_code_err"] : "";
$email = isset($_SESSION["recovery_email_confirmed"]) ? $_SESSION["recovery_email_confirmed"] : "";

// Limpiar variables de sesión de errores
unset($_SESSION["verification_code_err"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso de Código de Verificación</title>
    <!-- Importando las fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/verificationCodeStyle.css">
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
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h1 class="verification-title">Recupera tu contraseña</h1>
        <p class="verification-description">Hemos enviado un código a tu correo <?php echo !empty($email) ? htmlspecialchars($email) : ""; ?>. Ingresa el código para continuar con la recuperación de tu cuenta.</p>
        
        <?php if(!empty($code_err)): ?>
            <div class="alert alert-danger"><?php echo $code_err; ?></div>
        <?php endif; ?>
        
        <form id="verificationForm" method="post" action="../controllers/verification_code_controller.php">
            <div class="form-group">
                <label for="code" class="form-label">Código</label>
                <input type="text" id="code" name="code" class="form-control" placeholder="Ingresa el código enviado" required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-send">Verificar</button>
                <button type="button" class="btn btn-cancel" id="btnCancel">Cancelar</button>
            </div>
        </form>
    </div>
    
    <script>
        document.getElementById('btnCancel').addEventListener('click', function() {
            if(confirm('¿Está seguro que desea cancelar el proceso de recuperación?')) {
                window.location.href = 'login.php';
            }
        });
    </script>
</body>
</html>