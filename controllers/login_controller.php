<?php
// Desactivar la visualización de errores para este archivo
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sesión si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ya está logueado
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../home.php");
    exit;
}

// Incluir archivos de configuración y modelos
require_once "../config/db_config.php";
require_once "../includes/functions.php";
require_once "../models/Usuario.php";

// Definir variables e inicializar con valores vacíos
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Procesar datos del formulario cuando se envía el formulario
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Por favor ingrese su correo electrónico.";
    } else {
        $email = clean_input($_POST["email"]);
    }
    
    // Validar contraseña
    if(empty(trim($_POST["password"]))) {
        $password_err = "Por favor ingrese su contraseña.";
    } else {
        $password = clean_input($_POST["password"]);
    }
    
    // Validar credenciales
    if(empty($email_err) && empty($password_err)) {
        // Crear instancia del modelo de Usuario
        $usuario = new Usuario($mysqli);
        
        // Intentar login
        if($usuario->login($email, $password)) {
            // Login exitoso - Mostrar pantalla de carga
            
            // Mostrar solo la animación de carga (sin errores o mensajes PHP)
            echo '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Iniciando sesión...</title>
                <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
                <style>
                    body {
                        background-color: #30343F;
                        font-family: "Work Sans", sans-serif;
                        margin: 0;
                        padding: 0;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                    }
                    
                    .loading-container {
                        background-color: white;
                        padding: 30px;
                        border-radius: 10px;
                        text-align: center;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
                        width: 250px;
                    }
                    
                    .logo-container {
                        margin-bottom: 20px;
                        display: flex;
                        justify-content: center;
                    }
                    
                    .pepsi-logo {
                        width: 80px;
                        height: 80px;
                        object-fit: contain;
                        animation: pulse 1.5s ease-in-out infinite;
                    }
                    
                    .spinner {
                        border: 5px solid #f3f3f3;
                        border-top: 5px solid #1e3a8a;
                        border-radius: 50%;
                        width: 50px;
                        height: 50px;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 15px auto;
                    }
                    
                    .message {
                        color: #1e3a8a;
                        font-family: "Raleway", sans-serif;
                        font-size: 18px;
                        margin-top: 15px;
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
                <div class="loading-container">
                    <div class="logo-container">
                        <img src="../photos/logo 3.png" alt="SIAP Logo" class="pepsi-logo">
                    </div>
                    <div class="spinner"></div>
                    <div class="message">Iniciando sesión...</div>
                </div>
                
                <script>
                    // Redirigir después de 2.5 segundos
                    setTimeout(function() {
                        window.location.href = "../views/home.php";
                    }, 2500);
                </script>
            </body>
            </html>';
            exit;
        } else {
            // Mostrar un mensaje de error si las credenciales no son válidas
            $login_err = "Correo electrónico o contraseña incorrectos.";
        }
    }
    
    // Si llegamos aquí sin redirigir, hubo algún error
    // Devolver a la página de login con los errores
    $_SESSION["login_err"] = $login_err;
    $_SESSION["email_err"] = $email_err;
    $_SESSION["password_err"] = $password_err;
    $_SESSION["email"] = $email; // Para rellenar el campo de email y que el usuario no tenga que escribirlo de nuevo
    
    header("location: ../views/login.php");
    exit;
}

// Cerrar conexión si existe
if (isset($mysqli)) {
    $mysqli->close();
}

// Si se accede directamente a este archivo sin POST, redirigir a la página de login
header("location: ../views/login.php");
exit;
?>