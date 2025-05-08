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
require_once "../models/Usuario.php";

// Si el método no es POST, redirigir a la página de verificación
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: ../views/verificationCode.php");
    exit;
}

// Verificar si existe un token de recuperación
if(!isset($_SESSION["recovery_token"]) || empty($_SESSION["recovery_token"])) {
    header("location: ../views/passwordRecovery.php");
    exit;
}

// Procesar el formulario de verificación de código
$code = clean_input($_POST["code"]);
$code_err = "";
$token = $_SESSION["recovery_token"];

// Validar el código
if (empty($code)) {
    $code_err = "Por favor ingrese el código de verificación.";
} elseif (strlen($code) != 6 || !is_numeric($code)) {
    $code_err = "El código debe ser numérico de 6 dígitos.";
}

// Si no hay errores, verificar el código
if (empty($code_err)) {
    // Verificar el código en la base de datos
    $usuario = new Usuario($mysqli);
    $code_valid = $usuario->verifyRecoveryCode($token, $code);
    
    if ($code_valid) {
        // Marcar código como verificado
        $_SESSION["code_verified"] = true;
        
        // Redirigir a la página para establecer nueva contraseña
        header("location: ../views/newPassword.php");
        exit;
    } else {
        $code_err = "El código de verificación es inválido o ha expirado.";
    }
}

// Si hay errores, redirigir a la página de verificación con los mensajes
if (!empty($code_err)) {
    $_SESSION["verification_code_err"] = $code_err;
    header("location: ../views/verificationCode.php");
    exit;
}