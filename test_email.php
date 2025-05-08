<?php
// Mostrar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define la ruta base - ajusta esto según la estructura real de tu proyecto
$base_dir = __DIR__;

// Imprimir información de ruta para depuración
echo "<h3>Información de rutas:</h3>";
echo "<p>Directorio actual: " . $base_dir . "</p>";
echo "<p>Ruta include: " . $base_dir . "/includes/email_helper.php</p>";

// Verificar si el archivo existe antes de incluirlo
$include_path = $base_dir . "/includes/email_helper.php";
if (file_exists($include_path)) {
    echo "<p style='color:green'>✓ El archivo email_helper.php existe.</p>";
} else {
    echo "<p style='color:red'>✗ El archivo email_helper.php no existe en la ruta: " . $include_path . "</p>";
    
    // Intentar buscar en ubicaciones alternativas
    $alt_paths = [
        dirname($base_dir) . "/includes/email_helper.php",
        dirname(dirname($base_dir)) . "/includes/email_helper.php",
        $base_dir . "/../includes/email_helper.php",
        "./includes/email_helper.php",
        "../includes/email_helper.php"
    ];
    
    echo "<p>Buscando en ubicaciones alternativas:</p>";
    echo "<ul>";
    foreach ($alt_paths as $path) {
        if (file_exists($path)) {
            echo "<li style='color:green'>✓ Encontrado en: " . $path . "</li>";
            $include_path = $path;
            break;
        } else {
            echo "<li style='color:red'>✗ No encontrado en: " . $path . "</li>";
        }
    }
    echo "</ul>";
}

// Intentar incluir el archivo si existe
if (file_exists($include_path)) {
    try {
        require_once $include_path;
        echo "<p style='color:green'>✓ El archivo email_helper.php se ha incluido correctamente.</p>";
        
        // Intentar crear una instancia de EmailHelper
        try {
            $emailHelper = new EmailHelper();
            echo "<p style='color:green'>✓ Se ha creado una instancia de EmailHelper correctamente.</p>";
            
            // Configurar datos de prueba
            $test_email = "test@example.com";
            $test_code = "123456";
            
            // Probar el envío de código de verificación
            echo "<h2>Probando envío de código de verificación</h2>";
            $result = $emailHelper->sendVerificationCode($test_email, $test_code);
            
            if ($result) {
                echo "<p style='color:green'>✓ Correo simulado enviado correctamente.</p>";
            } else {
                echo "<p style='color:red'>✗ Error al enviar el correo.</p>";
            }
            
            // Probar el envío de notificación de cambio de contraseña
            echo "<h2>Probando envío de notificación de cambio de contraseña</h2>";
            $result = $emailHelper->sendPasswordChangedNotification($test_email);
            
            if ($result) {
                echo "<p style='color:green'>✓ Notificación simulada enviada correctamente.</p>";
            } else {
                echo "<p style='color:red'>✗ Error al enviar la notificación.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Error al crear instancia de EmailHelper: " . $e->getMessage() . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Error al incluir el archivo: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2 style='color:red'>No se pudo encontrar el archivo email_helper.php</h2>";
    echo "<p>Por favor, asegúrese de que el archivo existe en su servidor y que está en la ubicación correcta.</p>";
    
    // Instrucciones para solucionar el problema
    echo "<h3>Pasos para solucionar este problema:</h3>";
    echo "<ol>";
    echo "<li>Verifica que el archivo 'email_helper.php' existe en la carpeta 'includes'</li>";
    echo "<li>Asegúrate de que el archivo tiene los permisos correctos (generalmente 644)</li>";
    echo "<li>Comprueba que no hay errores de sintaxis en el archivo 'email_helper.php'</li>";
    echo "<li>Verifica la estructura de directorios de tu proyecto</li>";
    echo "</ol>";
    
    // Mostrar la estructura del directorio actual para ayudar en la depuración
    echo "<h3>Estructura del directorio actual:</h3>";
    echo "<pre>";
    
    function listDirectoryContents($dir, $indent = 0) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            $path = $dir . '/' . $file;
            echo str_repeat('  ', $indent) . ($indent > 0 ? '└─ ' : '') . $file;
            
            if (is_dir($path)) {
                echo " (directorio)";
            }
            
            echo "\n";
            
            if (is_dir($path) && $indent < 3) { // Limitar la profundidad para evitar resultados demasiado grandes
                listDirectoryContents($path, $indent + 1);
            }
        }
    }
    
    try {
        listDirectoryContents($base_dir);
    } catch (Exception $e) {
        echo "Error al mostrar la estructura de directorios: " . $e->getMessage();
    }
    
    echo "</pre>";
}
?>