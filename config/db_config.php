<?php
/**
 * Configuración de la conexión a la base de datos
 */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');  // Cambia según tu configuración
define('DB_PASSWORD', '');      // Cambia según tu configuración
define('DB_NAME', 'siap');      // Nombre de tu base de datos

/**
 * Intentar conexión a la base de datos MySQL
 */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if($mysqli === false){
    die("ERROR: No se pudo conectar a la base de datos. " . $mysqli->connect_error);
}

// Establecer el charset a utf8
$mysqli->set_charset("utf8");