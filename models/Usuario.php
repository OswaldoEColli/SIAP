<?php
/**
 * Clase para manejo de usuarios
 */
class Usuario {
    private $conn;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
 * Autenticar usuario por email y contraseña
 */
public function login($email, $password) {
    // Preparar consulta
    $query = "SELECT usuarioID, nombre, apellidos, nombreUsuario, contraseña, 
              tipoUsuario, email, telefono, activo 
              FROM Usuario WHERE email = ?";
    
    if($stmt = $this->conn->prepare($query)) {
        // Vincular parámetros
        $stmt->bind_param("s", $param_email);
        $param_email = $email;
        
        // Ejecutar la consulta
        if($stmt->execute()) {
            // Almacenar resultado
            $stmt->store_result();
            
            // Verificar si el email existe
            if($stmt->num_rows == 1) {
                // Vincular variables de resultado
                $stmt->bind_result($id, $nombre, $apellidos, $username, $stored_password, 
                                   $tipo_usuario, $email, $telefono, $activo);
                
                if($stmt->fetch()) {
                    // Verificar si la cuenta está activa
                    if($activo) {
                        // Verificar la contraseña sin encriptar
                        if($password === $stored_password) {
                            // Contraseña correcta, almacenar datos en variables de sesión
                            // No iniciar sesión con session_start() aquí, ya está iniciada
                            
                            // Almacenar datos en variables de sesión
                            $_SESSION["loggedin"] = true;
                            $_SESSION["usuarioID"] = $id;
                            $_SESSION["nombre"] = $nombre;
                            $_SESSION["apellidos"] = $apellidos;
                            $_SESSION["nombreUsuario"] = $username;
                            $_SESSION["tipoUsuario"] = $tipo_usuario;
                            $_SESSION["email"] = $email;
                            
                            return true;
                        } else {
                            // Contraseña incorrecta
                            return false;
                        }
                    } else {
                        // Cuenta no activa
                        return false;
                    }
                }
            } else {
                // Email no existe
                return false;
            }
        } else {
            echo "Oops! Algo salió mal. Por favor, inténtalo más tarde.";
            return false;
        }
        
        // Cerrar declaración
        $stmt->close();
    }
    
    return false;
}
    
    /**
     * Registrar un nuevo usuario
     */
    public function register($nombre, $apellidos, $username, $email, $password, $tipo_usuario = 'Vendedor', $telefono = '') {
        // Verificar si el email ya existe
        if($this->emailExists($email)) {
            return false;
        }
        
        // Verificar si el nombre de usuario ya existe
        if($this->usernameExists($username)) {
            return false;
        }
        
        // Preparar la consulta de inserción
        $query = "INSERT INTO Usuario (nombre, apellidos, nombreUsuario, contraseña, 
                  tipoUsuario, email, telefono) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("sssssss", $param_nombre, $param_apellidos, $param_username, 
                             $param_password, $param_tipo_usuario, $param_email, $param_telefono);
            
            // Establecer parámetros
            $param_nombre = $nombre;
            $param_apellidos = $apellidos;
            $param_username = $username;
            $param_password = $password; // Sin hash
            $param_tipo_usuario = $tipo_usuario;
            $param_email = $email;
            $param_telefono = $telefono;
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                return true;
            } else {
                return false;
            }
            
            // Cerrar declaración
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Verificar si un email ya existe
     */
    public function emailExists($email) {
        $query = "SELECT usuarioID FROM Usuario WHERE email = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            if($stmt->execute()) {
                $stmt->store_result();
                
                if($stmt->num_rows > 0) {
                    $stmt->close();
                    return true;
                }
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Verificar si un nombre de usuario ya existe
     */
    private function usernameExists($username) {
        $query = "SELECT usuarioID FROM Usuario WHERE nombreUsuario = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if($stmt->execute()) {
                $stmt->store_result();
                
                if($stmt->num_rows > 0) {
                    $stmt->close();
                    return true;
                }
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Obtener datos de un usuario por ID
     */
    public function getUserById($id) {
        $query = "SELECT usuarioID, nombre, apellidos, nombreUsuario, 
                  tipoUsuario, email, telefono, fechaCreacion, activo 
                  FROM Usuario WHERE usuarioID = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("i", $param_id);
            $param_id = $id;
            
            if($stmt->execute()) {
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                return $user;
            }
            
            $stmt->close();
        }
        
        return null;
    }

    /**
     * Crear una solicitud de recuperación de contraseña
     */
    public function createPasswordRecovery($email, $token, $code, $expiry) {
        // Primero verificamos si existe una tabla para las solicitudes de recuperación
        $this->createRecoveryTableIfNotExists();
        
        // Eliminar solicitudes anteriores para este email
        $this->deleteOldRecoveryRequests($email);
        
        // Insertar nueva solicitud
        $query = "INSERT INTO PasswordRecovery (email, token, verification_code, expiry) 
                  VALUES (?, ?, ?, ?)";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("ssss", $param_email, $param_token, $param_code, $param_expiry);
            $param_email = $email;
            $param_token = $token;
            $param_code = $code;
            $param_expiry = $expiry;
            
            if($stmt->execute()) {
                $stmt->close();
                return true;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Verificar código de recuperación
     */
    public function verifyRecoveryCode($token, $code) {
        $query = "SELECT email FROM PasswordRecovery 
                  WHERE token = ? AND verification_code = ? AND expiry > NOW() AND used = 0";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("ss", $param_token, $param_code);
            $param_token = $token;
            $param_code = $code;
            
            if($stmt->execute()) {
                $stmt->store_result();
                
                if($stmt->num_rows > 0) {
                    $stmt->close();
                    return true;
                }
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Actualizar contraseña usando token de recuperación
     */
    public function updatePasswordByToken($token, $new_password) {
        // Obtener el email del token
        $query = "SELECT email FROM PasswordRecovery 
                  WHERE token = ? AND expiry > NOW() AND used = 0";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("s", $param_token);
            $param_token = $token;
            
            if($stmt->execute()) {
                $stmt->bind_result($email);
                
                if($stmt->fetch()) {
                    $stmt->close();
                    
                    // Actualizar la contraseña del usuario
                    $update_query = "UPDATE Usuario SET contraseña = ? WHERE email = ?";
                    
                    if($update_stmt = $this->conn->prepare($update_query)) {
                        $update_stmt->bind_param("ss", $param_password, $param_email);
                        $param_password = $new_password;
                        $param_email = $email;
                        
                        if($update_stmt->execute()) {
                            $update_stmt->close();
                            
                            // Marcar el token como usado
                            $this->markTokenAsUsed($token);
                            
                            return true;
                        }
                        
                        $update_stmt->close();
                    }
                }
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Marcar token como usado
     */
    private function markTokenAsUsed($token) {
        $query = "UPDATE PasswordRecovery SET used = 1 WHERE token = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("s", $param_token);
            $param_token = $token;
            
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Eliminar solicitudes de recuperación anteriores
     */
    private function deleteOldRecoveryRequests($email) {
        $query = "DELETE FROM PasswordRecovery WHERE email = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Crear tabla de recuperación de contraseña si no existe
     */
    private function createRecoveryTableIfNotExists() {
        $query = "CREATE TABLE IF NOT EXISTS PasswordRecovery (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100) NOT NULL,
                    token VARCHAR(100) NOT NULL,
                    verification_code VARCHAR(10) NOT NULL,
                    expiry DATETIME NOT NULL,
                    used TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
        
        $this->conn->query($query);
    }
}