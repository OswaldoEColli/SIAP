<?php
// Iniciar sesión
session_start();

// Verificar si el usuario ya está logueado
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../home.php");
    exit;
}

// Recuperar mensajes de error si existen
$login_err = isset($_SESSION["login_err"]) ? $_SESSION["login_err"] : "";
$email_err = isset($_SESSION["email_err"]) ? $_SESSION["email_err"] : "";
$password_err = isset($_SESSION["password_err"]) ? $_SESSION["password_err"] : "";
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "";

// Limpiar variables de sesión de errores
unset($_SESSION["login_err"]);
unset($_SESSION["email_err"]);
unset($_SESSION["password_err"]);
unset($_SESSION["email"]);

// Incluir archivos necesarios
require_once "../includes/functions.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <!-- Importando las fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/loginStyle.css">
    <style>
        /* Estilo adicional para mensajes de error */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .error {
            color: #dc3545;
            font-size: 0.8em;
            margin-top: 5px;
            display: block;
        }
        
        /* Estilos para la animación de carga */
        #login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            display: none; /* Inicialmente oculto */
            justify-content: center;
            align-items: center;
        }
        
        .login-spinner-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 200px;
        }
        
        .login-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #1e3a8a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        .login-spinner-text {
            font-family: 'Raleway', sans-serif;
            color: #1e3a8a;
            font-size: 18px;
            margin-top: 10px;
        }
        
        .login-pepsi-logo {
            margin: 0 auto 15px;
            width: 60px;
            height: 60px;
        }
        
        .login-pepsi-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Overlay de carga para la animación de inicio de sesión -->
    <div id="login-overlay">
        <div class="login-spinner-container">
            <div class="login-pepsi-logo">
                <img src="../photos/Pepsi-Logo.jpg" alt="Pepsi Logo">
            </div>
            <div class="login-spinner"></div>
            <div class="login-spinner-text">Iniciando sesión...</div>
        </div>
    </div>
    
    <div class="login-container">
        <div class="logo-container">
            <img src="../photos/Pepsi-Logo.jpg" alt="Logo de Pepsi" class="logo">
        </div>
        
        <div class="form-container">
            <h1>Iniciar sesión</h1>
            
            <?php
            // Mostrar mensajes de error si existen
            if(!empty($login_err)){
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            ?>
            
            <form id="loginForm" action="../controllers/login_controller.php" method="post">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <div class="input-container">
                        <span class="icon icon-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#777" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <?php if(!empty($email_err)){ echo '<span class="error">' . $email_err . '</span>'; } ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-container">
                        <span class="icon icon-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#777" viewBox="0 0 16 16">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password" required>
                        <span class="icon icon-right" id="togglePassword">
                            
                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                            </svg>
                        </span>
                    </div>
                    <?php if(!empty($password_err)){ echo '<span class="error">' . $password_err . '</span>'; } ?>
                </div>
                
                <div class="forgot-password">
                    <a href="passwordRecovery.php">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">Iniciar sesión</button>
                
                <div class="register">
                    <p>¿No tienes una cuenta? <a href="createAccount.php">Regístrate aquí</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const loginForm = document.getElementById('loginForm');
            const loginOverlay = document.getElementById('login-overlay');
            
            // Función para alternar la visibilidad de la contraseña
            togglePassword.addEventListener('click', function() {
                // Toggle type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Cambiamos el icono para indicar el estado
                if (type === 'password') {
                    togglePassword.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#777" viewBox="0 0 16 16">
                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                        </svg>
                    `;
                } else {
                    togglePassword.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#777" viewBox="0 0 16 16" style="opacity: 0.7;">
                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                        </svg>
                    `;
                }
            });
            
            // Mostrar animación al enviar el formulario
            loginForm.addEventListener('submit', function(event) {
                // Validar formulario
                const emailValue = document.getElementById('email').value.trim();
                const passwordValue = document.getElementById('password').value.trim();
                
                if (emailValue === '' || passwordValue === '') {
                    return; // No mostrar animación si hay campos vacíos
                }
                
                // Mostrar la animación de carga
                loginOverlay.style.display = 'flex';
            });
        });
    </script>
</body>
</html>