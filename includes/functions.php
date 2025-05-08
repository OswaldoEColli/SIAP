<?php
/**
 * Funciones reutilizables para la aplicación
 */

/**
 * Función para limpiar y validar datos de entrada
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Función para verificar si una sesión está activa
 */
function is_logged_in() {
    session_start();
    return isset($_SESSION['usuarioID']);
}

/**
 * Redireccionar a una página específica
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Función para mostrar mensajes de error o éxito
 */
function show_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}