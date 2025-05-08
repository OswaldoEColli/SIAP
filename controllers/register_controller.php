<?php
// Mostrar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

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
$nombre = $apellido = $telefono = $email = $password = $confirmPassword = "";
$nombre_err = $apellido_err = $telefono_err = $email_err = $password_err = $confirm_password_err = $general_err = "";

// Procesar datos del formulario cuando se envía el formulario
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar nombre
    if(empty(trim($_POST["nombre"]))) {
        $nombre_err = "Por favor ingrese su nombre.";
    } else {
        $nombre = clean_input($_POST["nombre"]);
    }
    
    // Validar apellido
    if(empty(trim($_POST["apellido"]))) {
        $apellido_err = "Por favor ingrese su apellido.";
    } else {
        $apellido = clean_input($_POST["apellido"]);
    }
    
    // Validar teléfono
    if(empty(trim($_POST["telefono"]))) {
        $telefono_err = "Por favor ingrese su número de teléfono.";
    } else {
        $telefono = clean_input($_POST["telefono"]);
    }
    
    // Validar email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Por favor ingrese su correo electrónico.";
    } else {
        $email = clean_input($_POST["email"]);
        
        // Verificar si el email es válido
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Por favor ingrese un correo electrónico válido.";
        }
    }
    
    // Validar contraseña
    if(empty(trim($_POST["password"]))) {
        $password_err = "Por favor ingrese una contraseña.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $password = clean_input($_POST["password"]);
    }
    
    // Validar confirmación de contraseña
    if(empty(trim($_POST["confirmPassword"]))) {
        $confirm_password_err = "Por favor confirme la contraseña.";     
    } else {
        $confirmPassword = clean_input($_POST["confirmPassword"]);
        if(empty($password_err) && ($password != $confirmPassword)) {
            $confirm_password_err = "Las contraseñas no coinciden.";
        }
    }
    
    // Verificar errores de entrada antes de insertar en la base de datos
    if(empty($nombre_err) && empty($apellido_err) && empty($telefono_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Crear instancia del modelo de Usuario
        $usuario = new Usuario($mysqli);
        
        // Generar un nombreUsuario a partir del email
        $nombreUsuario = explode('@', $email)[0];
        
        // Intentar registrar usuario
        if($usuario->register($nombre, $apellido, $nombreUsuario, $email, $password, 'Vendedor', $telefono)) {
            // Registro exitoso
            $_SESSION["register_success"] = true;
            header("location: ../views/login.php");
            exit;
        } else {
            // Error en el registro
            $general_err = "Ocurrió un error al intentar registrar la cuenta. Es posible que el correo electrónico ya esté en uso.";
        }
    }
    
    // Si llegamos aquí sin redirigir, hubo algún error
    // Devolver a la página de registro con los errores
    $_SESSION["nombre_err"] = $nombre_err;
    $_SESSION["apellido_err"] = $apellido_err;
    $_SESSION["telefono_err"] = $telefono_err;
    $_SESSION["email_err"] = $email_err;
    $_SESSION["password_err"] = $password_err;
    $_SESSION["confirm_password_err"] = $confirm_password_err;
    $_SESSION["general_err"] = $general_err;
    
    // Guardar los valores para rellenar el formulario
    $_SESSION["reg_nombre"] = $nombre;
    $_SESSION["reg_apellido"] = $apellido;
    $_SESSION["reg_telefono"] = $telefono;
    $_SESSION["reg_email"] = $email;
    
    header("location: ../views/createAccount.php");
    exit;
}

// Cerrar conexión
$mysqli->close();

// Si se accede directamente a este archivo sin POST, redirigir a la página de registro
header("location: ../views/createAccount.php");
exit;
?>