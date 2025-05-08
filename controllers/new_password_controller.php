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

// Verificar si existe un token de recuperación verificado
if(!isset($_SESSION["code_verified"]) || $_SESSION["code_verified"] !== true || !isset($_SESSION["recovery_token"]) || !isset($_SESSION["recovery_email_confirmed"])) {
    header("location: ../views/passwordRecovery.php");
    exit;
}

// Procesar el formulario de nueva contraseña
$password = clean_input($_POST["password"]);
$confirm_password = clean_input($_POST["confirmPassword"]);
$password_err = "";
$confirm_password_err = "";
$general_err = "";
$token = $_SESSION["recovery_token"];
$email = $_SESSION["recovery_email_confirmed"];

// Validar contraseña
if (empty($password)) {
    $password_err = "Por favor ingrese una contraseña.";
} elseif (strlen($password) < 6) {
    $password_err = "La contraseña debe tener al menos 6 caracteres.";
}

// Validar confirmación de contraseña
if (empty($confirm_password)) {
    $confirm_password_err = "Por favor confirme la contraseña.";
} elseif ($password != $confirm_password) {
    $confirm_password_err = "Las contraseñas no coinciden.";
}

// Si no hay errores, actualizar la contraseña
if (empty($password_err) && empty($confirm_password_err)) {
    // Actualizar la contraseña en la base de datos
    $usuario = new Usuario($mysqli);
    $updated = $usuario->updatePasswordByToken($token, $password);
    
    if ($updated) {
        // Enviar correo de confirmación
        $emailHelper = new EmailHelper();
        $emailHelper->sendPasswordChangedNotification($email);
        
        // Limpiar variables de sesión
        unset($_SESSION["recovery_token"]);
        unset($_SESSION["code_verified"]);
        unset($_SESSION["recovery_email_confirmed"]);
        unset($_SESSION["verification_code_test"]);
        
        // Establecer mensaje de éxito
        $_SESSION["login_success"] = "Tu contraseña ha sido actualizada correctamente. Ahora puedes iniciar sesión.";
        
        // Redirigir a la página de login
        header("location: ../views/login.php");
        exit;
    } else {
        $general_err = "Ocurrió un error al actualizar la contraseña. Por favor intente nuevamente.";
    }
}

// Si hay errores, redirigir a la página de nueva contraseña con los mensajes
if (!empty($password_err) || !empty($confirm_password_err) || !empty($general_err)) {
    $_SESSION["new_password_err"] = $password_err;
    $_SESSION["confirm_password_err"] = $confirm_password_err;
    $_SESSION["general_err"] = $general_err;
    header("location: ../views/newPassword.php");
    exit;
}