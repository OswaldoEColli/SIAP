<?php
// Iniciar sesión
session_start();

// Si el usuario ya está logueado, redirigir a home.php
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../home.php");
    exit;
}

// Recuperar mensajes de error si existen
$nombre_err = isset($_SESSION["nombre_err"]) ? $_SESSION["nombre_err"] : "";
$apellido_err = isset($_SESSION["apellido_err"]) ? $_SESSION["apellido_err"] : "";
$telefono_err = isset($_SESSION["telefono_err"]) ? $_SESSION["telefono_err"] : "";
$email_err = isset($_SESSION["email_err"]) ? $_SESSION["email_err"] : "";
$password_err = isset($_SESSION["password_err"]) ? $_SESSION["password_err"] : "";
$confirm_password_err = isset($_SESSION["confirm_password_err"]) ? $_SESSION["confirm_password_err"] : "";
$general_err = isset($_SESSION["general_err"]) ? $_SESSION["general_err"] : "";

// Recuperar valores para volver a llenar el formulario
$nombre = isset($_SESSION["reg_nombre"]) ? $_SESSION["reg_nombre"] : "";
$apellido = isset($_SESSION["reg_apellido"]) ? $_SESSION["reg_apellido"] : "";
$telefono = isset($_SESSION["reg_telefono"]) ? $_SESSION["reg_telefono"] : "";
$email = isset($_SESSION["reg_email"]) ? $_SESSION["reg_email"] : "";

// Limpiar variables de sesión
unset($_SESSION["nombre_err"]);
unset($_SESSION["apellido_err"]);
unset($_SESSION["telefono_err"]);
unset($_SESSION["email_err"]);
unset($_SESSION["password_err"]);
unset($_SESSION["confirm_password_err"]);
unset($_SESSION["general_err"]);
unset($_SESSION["reg_nombre"]);
unset($_SESSION["reg_apellido"]);
unset($_SESSION["reg_telefono"]);
unset($_SESSION["reg_email"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta</title>
    <!-- Importando las fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/createAccount.css">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
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
    </style>
</head>
<body>
    <div class="form-container">
        <h1 class="form-title">Crea tu cuenta</h1>
        
        <?php if(!empty($general_err)): ?>
            <div class="alert alert-danger"><?php echo $general_err; ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION["register_success"]) && $_SESSION["register_success"] === true): ?>
            <div class="alert alert-success">Cuenta creada exitosamente. Ya puedes <a href="login.php">iniciar sesión</a>.</div>
            <?php unset($_SESSION["register_success"]); ?>
        <?php endif; ?>
        
        <form id="registerForm" method="post" action="../controllers/register_controller.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre (s)</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    <?php if(!empty($nombre_err)): ?>
                        <span class="error-message"><?php echo $nombre_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="apellido" class="form-label">Apellido (s)</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($apellido); ?>" required>
                    <?php if(!empty($apellido_err)): ?>
                        <span class="error-message"><?php echo $apellido_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono" class="form-label">Número de teléfono</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($telefono); ?>" required>
                    <?php if(!empty($telefono_err)): ?>
                        <span class="error-message"><?php echo $telefono_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    <?php if(!empty($email_err)): ?>
                        <span class="error-message"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <?php if(!empty($password_err)): ?>
                        <span class="error-message"><?php echo $password_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword" class="form-label">Confirma tu contraseña</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                    <?php if(!empty($confirm_password_err)): ?>
                        <span class="error-message"><?php echo $confirm_password_err; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="button-group">
                <button type="button" class="btn btn-cancel" id="btnCancel">Cancelar</button>
                <button type="submit" class="btn btn-confirm">Confirmar</button>
            </div>
            
            <div class="login-link">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a></p>
            </div>
        </form>
    </div>
    
    <script>
        // Basic form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            return true;
        });

        // Añadir funcionalidad al botón Cancelar para redirigir a la página de login
        document.getElementById('btnCancel').addEventListener('click', function() {
            // Redirigir a la página de login
            window.location.href = 'login.php';
        });
    </script>
</body>
</html>