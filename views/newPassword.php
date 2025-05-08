<?php
// Iniciar sesión
session_start();

// Si el usuario ya está logueado, redirigir a home.php
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../home.php");
    exit;
}

// Verificar si existe un token de recuperación verificado
if(!isset($_SESSION["code_verified"]) || $_SESSION["code_verified"] !== true) {
    header("location: passwordRecovery.php");
    exit;
}

// Recuperar mensajes de error o éxito si existen
$password_err = isset($_SESSION["new_password_err"]) ? $_SESSION["new_password_err"] : "";
$confirm_password_err = isset($_SESSION["confirm_password_err"]) ? $_SESSION["confirm_password_err"] : "";
$general_err = isset($_SESSION["general_err"]) ? $_SESSION["general_err"] : "";

// Limpiar variables de sesión de errores
unset($_SESSION["new_password_err"]);
unset($_SESSION["confirm_password_err"]);
unset($_SESSION["general_err"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña</title>
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
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <h1 class="recovery-title">Establece tu nueva contraseña</h1>
        <p class="recovery-description">Crea una contraseña segura para tu cuenta</p>
        
        <?php if(!empty($general_err)): ?>
            <div class="alert alert-danger"><?php echo $general_err; ?></div>
        <?php endif; ?>
        
        <form id="newPasswordForm" method="post" action="../controllers/new_password_controller.php">
            <div class="form-group">
                <label for="password" class="form-label">Nueva contraseña</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Ingresa tu nueva contraseña" required>
                <?php if(!empty($password_err)): ?>
                    <span class="error-message"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirmPassword" class="form-label">Confirma tu contraseña</label>
                <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" placeholder="Confirma tu nueva contraseña" required>
                <?php if(!empty($confirm_password_err)): ?>
                    <span class="error-message"><?php echo $confirm_password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-send">Guardar contraseña</button>
                <button type="button" class="btn btn-cancel" id="btnCancel">Cancelar</button>
            </div>
        </form>
    </div>
    
    <script>
        // Validación de contraseñas
        document.getElementById('newPasswordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return true;
        });

        // Botón de cancelar
        document.getElementById('btnCancel').addEventListener('click', function() {
            if(confirm('¿Está seguro que desea cancelar el proceso?')) {
                window.location.href = 'login.php';
            }
        });
    </script>
</body>
</html>