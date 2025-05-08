<?php
// Mostrar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once "../config/db_config.php";
require_once "../includes/functions.php";
require_once "../includes/email_helper.php";
require_once "../models/Usuario.php";

// Si el método no es POST, redirigir a la página de recuperación
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: ../views/passwordRecovery.php");
    exit;
}

// Procesar el formulario de recuperación de contraseña
$email = clean_input($_POST["email"]);
$email_err = "";

// Validar el email
if (empty($email)) {
    $email_err = "Por favor ingrese su correo electrónico.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email_err = "Por favor ingrese un correo electrónico válido.";
}

// Si no hay errores, procesar la solicitud
if (empty($email_err)) {
    // Verificar si el email existe en la base de datos
    $usuario = new Usuario($mysqli);
    $user_exists = $usuario->emailExists($email);
    
    if ($user_exists) {
        // Generar token de recuperación y código de verificación
        $token = bin2hex(random_bytes(32));
        $verification_code = sprintf("%06d", mt_rand(100000, 999999)); // Código de 6 dígitos
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora
        
        // Guardar el token y el código en la base de datos
        $recovery_saved = $usuario->createPasswordRecovery($email, $token, $verification_code, $expiry);
        
        if ($recovery_saved) {
            // Crear una instancia de EmailHelper
            $emailHelper = new EmailHelper();
            
            // Enviar email con código de verificación
            $sent = $emailHelper->sendVerificationCode($email, $verification_code);
            
            if ($sent) {
                // Guardar token en la sesión para la verificación
                $_SESSION["recovery_token"] = $token;
                $_SESSION["recovery_email_confirmed"] = $email;
                
                // Guardar código en sesión para desarrollo (opcional, quitar en producción)
                $_SESSION["verification_code_test"] = $verification_code;
                
                // Redirigir a la página de verificación
                header("location: ../views/verificationCode.php");
                exit;
            } else {
                $email_err = "No se pudo enviar el correo de verificación. Por favor intente más tarde.";
            }
        } else {
            $email_err = "Ocurrió un error al procesar la solicitud. Por favor intente más tarde.";
        }
    } else {
        // Por seguridad, no indicar si el email existe o no
        $_SESSION["recovery_success"] = "Si tu correo está registrado, recibirás un código de verificación.";
        $_SESSION["recovery_email"] = $email;
        header("location: ../views/passwordRecovery.php");
        exit;
    }
}

// Si hay errores, redirigir a la página de recuperación con los mensajes
if (!empty($email_err)) {
    $_SESSION["recovery_email_err"] = $email_err;
    $_SESSION["recovery_email"] = $email;
    header("location: ../views/passwordRecovery.php");
    exit;
}