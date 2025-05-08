<?php
/**
 * Clase para manejar operaciones relacionadas con clientes
 */
class Cliente {
    private $conn;
    
    /**
     * Constructor
     * @param mysqli $db Conexión a la base de datos
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear un nuevo cliente
     * 
     * @param string $nombre Nombre del cliente
     * @param string $apellidos Apellidos del cliente
     * @param string $telefono Teléfono del cliente
     * @param string $email Email del cliente
     * @param string $direccion Dirección del cliente
     * @param string $rfc RFC del cliente
     * @param bool $esRecurrente Si es cliente recurrente (opcional)
     * @return int|false ID del cliente creado o false en caso de error
     */
    public function create($nombre, $apellidos, $telefono, $email, $direccion, $rfc = '', $esRecurrente = 1) {
        // Preparar consulta
        $query = "INSERT INTO Cliente (nombre, apellidos, telefono, email, direccion, rfc, esRecurrente, saldoPendiente, fechaRegistro) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("ssssssi", $param_nombre, $param_apellidos, $param_telefono, $param_email, $param_direccion, $param_rfc, $param_esRecurrente);
            
            // Establecer parámetros
            $param_nombre = $nombre;
            $param_apellidos = $apellidos;
            $param_telefono = $telefono;
            $param_email = $email;
            $param_direccion = $direccion;
            $param_rfc = $rfc;
            $param_esRecurrente = $esRecurrente ? 1 : 0;
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                // Obtener el ID del cliente insertado
                $clienteID = $this->conn->insert_id;
                $stmt->close();
                return $clienteID;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Obtener todos los clientes
     * 
     * @return array Lista de clientes
     */
    public function getAll() {
        $clientes = [];
        
        // Preparar consulta
        $query = "SELECT clienteID, nombre, apellidos, telefono, email, direccion, rfc, esRecurrente, saldoPendiente, fechaRegistro 
                  FROM Cliente 
                  ORDER BY nombre ASC";
        
        if($stmt = $this->conn->prepare($query)) {
            // Ejecutar la consulta
            if($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Obtener los resultados
                while($row = $result->fetch_assoc()) {
                    $clientes[] = $row;
                }
            }
            
            $stmt->close();
        }
        
        return $clientes;
    }
    
    /**
     * Obtener un cliente por su ID
     * 
     * @param int $id ID del cliente
     * @return array|null Datos del cliente o null si no existe
     */
    public function getById($id) {
        // Preparar consulta
        $query = "SELECT clienteID, nombre, apellidos, telefono, email, direccion, rfc, esRecurrente, saldoPendiente, fechaRegistro 
                  FROM Cliente 
                  WHERE clienteID = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("i", $param_id);
            $param_id = $id;
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $result = $stmt->get_result();
                
                if($result->num_rows == 1) {
                    $cliente = $result->fetch_assoc();
                    $stmt->close();
                    return $cliente;
                }
            }
            
            $stmt->close();
        }
        
        return null;
    }
    
    /**
     * Actualizar un cliente
     * 
     * @param int $id ID del cliente
     * @param string $nombre Nombre del cliente
     * @param string $apellidos Apellidos del cliente
     * @param string $telefono Teléfono del cliente
     * @param string $email Email del cliente
     * @param string $direccion Dirección del cliente
     * @param bool $esRecurrente Si es cliente recurrente
     * @param string $rfc RFC del cliente
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function update($id, $nombre, $apellidos, $telefono, $email, $direccion, $esRecurrente, $rfc = '') {
        // Preparar consulta
        $query = "UPDATE Cliente 
                  SET nombre = ?, 
                      apellidos = ?, 
                      telefono = ?, 
                      email = ?, 
                      direccion = ?, 
                      rfc = ?,
                      esRecurrente = ? 
                  WHERE clienteID = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("sssssiii", $param_nombre, $param_apellidos, $param_telefono, $param_email, $param_direccion, $param_rfc, $param_esRecurrente, $param_id);
            
            // Establecer parámetros
            $param_nombre = $nombre;
            $param_apellidos = $apellidos;
            $param_telefono = $telefono;
            $param_email = $email;
            $param_direccion = $direccion;
            $param_rfc = $rfc;
            $param_esRecurrente = $esRecurrente;
            $param_id = $id;
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $stmt->close();
                return true;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Eliminar un cliente
     * 
     * @param int $id ID del cliente
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function delete($id) {
        // Verificar si el cliente tiene ventas asociadas
        if($this->clienteHasTransactions($id)) {
            return false; // No permitir eliminar clientes con transacciones
        }
        
        // Preparar consulta
        $query = "DELETE FROM Cliente WHERE clienteID = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("i", $param_id);
            $param_id = $id;
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $stmt->close();
                return true;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Verificar si un cliente tiene transacciones asociadas
     * 
     * @param int $id ID del cliente
     * @return bool True si tiene transacciones, False en caso contrario
     */
    private function clienteHasTransactions($id) {
        // Preparar consulta
        $query = "SELECT COUNT(*) as count FROM Venta WHERE clienteID = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("i", $param_id);
            $param_id = $id;
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if($row['count'] > 0) {
                    $stmt->close();
                    return true;
                }
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Buscar clientes por nombre, RFC o teléfono
     * 
     * @param string $term Término de búsqueda
     * @return array Lista de clientes que coinciden con la búsqueda
     */
    public function search($term) {
        $clientes = [];
        
        // Preparar consulta
        $query = "SELECT clienteID, nombre, apellidos, telefono, email, direccion, rfc, esRecurrente, saldoPendiente, fechaRegistro 
                  FROM Cliente 
                  WHERE nombre LIKE ? OR apellidos LIKE ? OR telefono LIKE ? OR rfc LIKE ?
                  ORDER BY nombre ASC";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $search_term = "%" . $term . "%";
            $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Obtener los resultados
                while($row = $result->fetch_assoc()) {
                    $clientes[] = $row;
                }
            }
            
            $stmt->close();
        }
        
        return $clientes;
    }
}