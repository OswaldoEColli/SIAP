<?php
/**
 * Clase para manejar las operaciones CRUD de Sucursales
 */
class Sucursal {
    private $db;
    
    /**
     * Constructor
     * @param mysqli $db Conexión a la base de datos
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Verifica si la tabla existe y la crea si no
     * @return bool Éxito de la operación
     */
    public function verificarTabla() {
        $tableExists = false;
        $checkTable = $this->db->query("SHOW TABLES LIKE 'Sucursal'");
        
        if ($checkTable && $checkTable->num_rows > 0) {
            $tableExists = true;
        }
        
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
            
            return $this->db->query($createTable);
        }
        
        return true;
    }
    
    /**
     * Obtiene todas las sucursales activas
     * @return array Arreglo de sucursales
     */
    public function getAll() {
        $sucursales = [];
        
        if ($this->verificarTabla()) {
            $query = "SELECT * FROM Sucursal WHERE status = 'Activo' ORDER BY nombre";
            $result = $this->db->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $sucursales[] = $row;
                }
            }
        }
        
        return $sucursales;
    }
    
    /**
     * Crea una nueva sucursal
     * @param string $nombre Nombre de la sucursal
     * @param string $direccion Dirección completa
     * @param string $ciudad Ciudad
     * @param string $estado Estado
     * @param string $telefono Teléfono
     * @param string $email Correo electrónico
     * @param string $gerente Nombre del gerente
     * @param string $horario Horario de atención
     * @param string $status Estado (Activo/Inactivo)
     * @return int|bool ID de la nueva sucursal o false en caso de error
     */
    public function create($nombre, $direccion, $ciudad, $estado, $telefono = '', $email = '', $gerente = '', $horario = 'Lun-Vie 9:00-18:00', $status = 'Activo') {
        if (!$this->verificarTabla()) {
            return false;
        }
        
        $query = "INSERT INTO Sucursal (nombre, direccion, ciudad, estado, telefono, email, gerente, horario, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sssssssss", $nombre, $direccion, $ciudad, $estado, $telefono, $email, $gerente, $horario, $status);
        
        if ($stmt->execute()) {
            $id = $this->db->insert_id;
            $stmt->close();
            return $id;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Obtiene una sucursal por su ID
     * @param int $id ID de la sucursal
     * @return array|bool Datos de la sucursal o false si no existe
     */
    public function getById($id) {
        if (!$this->verificarTabla()) {
            return false;
        }
        
        $query = "SELECT * FROM Sucursal WHERE sucursalID = ?";
        $stmt = $this->db->prepare($query);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $sucursal = $result->fetch_assoc();
            $stmt->close();
            return $sucursal;
        }
        
        $stmt->close();
        return false;
    }
}