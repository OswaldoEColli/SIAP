<?php
// Iniciar sesión
session_start();

// Deshabilitar la salida de errores PHP en producción
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Función para registrar errores en un archivo de log
function logError($message) {
    $logFile = '../logs/sucursal_errors.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Verificar que existe el archivo de configuración de la base de datos
if (!file_exists('../config/db_config.php')) {
    logError("Archivo de configuración db_config.php no encontrado");
    echo json_encode(['success' => false, 'message' => 'Error de configuración del servidor']);
    exit;
}

// Incluir configuración de base de datos
require_once '../config/db_config.php';

// Función para sanitizar entradas
function sanitize($conn, $data) {
    if (!$conn) {
        return trim($data);
    }
    return $conn->real_escape_string(trim($data));
}

// Verificar el tipo de solicitud
$response = array('success' => false, 'message' => '', 'data' => null);

try {
    // Verificar conexión a base de datos
    if (!isset($mysqli) || $mysqli->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . ($mysqli ? $mysqli->connect_error : "No se pudo conectar"));
    }
    
    // Detectar la acción a realizar
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Verificar si la tabla existe
        $tableExists = false;
        $checkTable = $mysqli->query("SHOW TABLES LIKE 'Sucursal'");
        if ($checkTable && $checkTable->num_rows > 0) {
            $tableExists = true;
        }
        
        // Si la tabla no existe y la acción no es crear, crear la tabla
        if (!$tableExists && $action != 'create') {
            $createTable = "CREATE TABLE Sucursal (
                sucursalID INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                direccion TEXT NOT NULL,
                ciudad VARCHAR(100) NOT NULL,
                estado VARCHAR(100) NOT NULL,
                telefono VARCHAR(20),
                email VARCHAR(100),
                gerente VARCHAR(100),
                horario VARCHAR(100),
                fechaCreacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(20) DEFAULT 'Activo'
            )";
            
            if (!$mysqli->query($createTable)) {
                throw new Exception('Error al crear la tabla: ' . $mysqli->error);
            }
            $tableExists = true;
        }
        
        switch ($action) {
            case 'create':
                // Obtener datos del formulario
                $nombre = sanitize($mysqli, $_POST['nombre']);
                $ciudad = sanitize($mysqli, $_POST['ciudad']);
                $estado = sanitize($mysqli, $_POST['estado']);
                $direccion = sanitize($mysqli, $_POST['direccion']);
                $telefono = isset($_POST['telefono']) ? sanitize($mysqli, $_POST['telefono']) : '';
                $email = isset($_POST['email']) ? sanitize($mysqli, $_POST['email']) : '';
                $gerente = isset($_POST['gerente']) ? sanitize($mysqli, $_POST['gerente']) : '';
                $status = isset($_POST['status']) ? sanitize($mysqli, $_POST['status']) : 'Activo';
                $horario = isset($_POST['horario']) ? sanitize($mysqli, $_POST['horario']) : 'Lun-Vie 9:00-18:00';
                
                // Validar datos requeridos
                if (empty($nombre) || empty($direccion) || empty($ciudad) || empty($estado)) {
                    throw new Exception('Por favor complete todos los campos obligatorios');
                }
                
                // Si la tabla no existe, crearla
                if (!$tableExists) {
                    $createTable = "CREATE TABLE Sucursal (
                        sucursalID INT AUTO_INCREMENT PRIMARY KEY,
                        nombre VARCHAR(100) NOT NULL,
                        direccion TEXT NOT NULL,
                        ciudad VARCHAR(100) NOT NULL,
                        estado VARCHAR(100) NOT NULL,
                        telefono VARCHAR(20),
                        email VARCHAR(100),
                        gerente VARCHAR(100),
                        horario VARCHAR(100),
                        fechaCreacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        status VARCHAR(20) DEFAULT 'Activo'
                    )";
                    
                    if (!$mysqli->query($createTable)) {
                        throw new Exception('Error al crear la tabla: ' . $mysqli->error);
                    }
                }
                
                // Insertar en la base de datos
                $query = "INSERT INTO Sucursal (nombre, direccion, ciudad, estado, telefono, email, gerente, horario, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                $stmt = $mysqli->prepare($query);
                if (!$stmt) {
                    throw new Exception('Error al preparar la consulta: ' . $mysqli->error);
                }
                
                $stmt->bind_param("sssssssss", $nombre, $direccion, $ciudad, $estado, $telefono, $email, $gerente, $horario, $status);
                
                if ($stmt->execute()) {
                    $sucursalID = $mysqli->insert_id;
                    $response['success'] = true;
                    $response['message'] = 'Sucursal agregada correctamente';
                    $response['data'] = array(
                        'id' => $sucursalID,
                        'nombre' => $nombre,
                        'direccion' => $direccion,
                        'ciudad' => $ciudad,
                        'estado' => $estado,
                        'telefono' => $telefono,
                        'email' => $email,
                        'gerente' => $gerente,
                        'horario' => $horario,
                        'status' => $status
                    );
                } else {
                    throw new Exception('Error al guardar la sucursal: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'update':
                // Obtener ID de la sucursal a actualizar
                $sucursalID = isset($_POST['sucursalID']) ? intval($_POST['sucursalID']) : 0;
                if ($sucursalID <= 0) {
                    throw new Exception('ID de sucursal no válido');
                }
                
                // Obtener datos del formulario
                $nombre = sanitize($mysqli, $_POST['nombre']);
                $ciudad = sanitize($mysqli, $_POST['ciudad']);
                $estado = sanitize($mysqli, $_POST['estado']);
                $direccion = sanitize($mysqli, $_POST['direccion']);
                $telefono = isset($_POST['telefono']) ? sanitize($mysqli, $_POST['telefono']) : '';
                $email = isset($_POST['email']) ? sanitize($mysqli, $_POST['email']) : '';
                $gerente = isset($_POST['gerente']) ? sanitize($mysqli, $_POST['gerente']) : '';
                $status = isset($_POST['status']) ? sanitize($mysqli, $_POST['status']) : 'Activo';
                $horario = isset($_POST['horario']) ? sanitize($mysqli, $_POST['horario']) : 'Lun-Vie 9:00-18:00';
                
                // Validar datos requeridos
                if (empty($nombre) || empty($direccion) || empty($ciudad) || empty($estado)) {
                    throw new Exception('Por favor complete todos los campos obligatorios');
                }
                
                // Actualizar en la base de datos
                $query = "UPDATE Sucursal SET 
                        nombre = ?, 
                        direccion = ?, 
                        ciudad = ?, 
                        estado = ?, 
                        telefono = ?, 
                        email = ?, 
                        gerente = ?, 
                        horario = ?, 
                        status = ? 
                        WHERE sucursalID = ?";
                        
                $stmt = $mysqli->prepare($query);
                if (!$stmt) {
                    throw new Exception('Error al preparar la consulta: ' . $mysqli->error);
                }
                
                $stmt->bind_param("sssssssssi", $nombre, $direccion, $ciudad, $estado, $telefono, $email, $gerente, $horario, $status, $sucursalID);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Sucursal actualizada correctamente';
                    $response['data'] = array(
                        'id' => $sucursalID,
                        'nombre' => $nombre,
                        'direccion' => $direccion,
                        'ciudad' => $ciudad,
                        'estado' => $estado,
                        'telefono' => $telefono,
                        'email' => $email,
                        'gerente' => $gerente,
                        'horario' => $horario,
                        'status' => $status
                    );
                } else {
                    throw new Exception('Error al actualizar la sucursal: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'delete':
                // Obtener ID de la sucursal a eliminar
                $sucursalID = isset($_POST['sucursalID']) ? intval($_POST['sucursalID']) : 0;
                if ($sucursalID <= 0) {
                    throw new Exception('ID de sucursal no válido');
                }
                
                // Eliminar de la base de datos (o marcar como inactiva)
                // Opción 1: Eliminación real
                // $query = "DELETE FROM Sucursal WHERE sucursalID = ?";
                
                // Opción 2: Marcar como inactiva (recomendado)
                $query = "UPDATE Sucursal SET status = 'Inactivo' WHERE sucursalID = ?";
                
                $stmt = $mysqli->prepare($query);
                if (!$stmt) {
                    throw new Exception('Error al preparar la consulta: ' . $mysqli->error);
                }
                
                $stmt->bind_param("i", $sucursalID);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Sucursal eliminada correctamente';
                } else {
                    throw new Exception('Error al eliminar la sucursal: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'get':
                // Obtener ID de la sucursal 
                $sucursalID = isset($_POST['sucursalID']) ? intval($_POST['sucursalID']) : 0;
                if ($sucursalID <= 0) {
                    throw new Exception('ID de sucursal no válido');
                }
                
                // Obtener datos de la sucursal
                $query = "SELECT * FROM Sucursal WHERE sucursalID = ?";
                $stmt = $mysqli->prepare($query);
                if (!$stmt) {
                    throw new Exception('Error al preparar la consulta: ' . $mysqli->error);
                }
                
                $stmt->bind_param("i", $sucursalID);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $response['success'] = true;
                    $response['data'] = $result->fetch_assoc();
                } else {
                    throw new Exception('Sucursal no encontrada');
                }
                $stmt->close();
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    } else {
        throw new Exception('No se especificó ninguna acción');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    
    // Registrar el error en el archivo de log
    logError($e->getMessage() . ' - ' . $e->getTraceAsString());
}

// Limpiar cualquier salida anterior
if (ob_get_length()) ob_clean();

// Establecer el Content-Type correcto
header('Content-Type: application/json');

// Devolver respuesta en formato JSON
echo json_encode($response);
exit;
?>