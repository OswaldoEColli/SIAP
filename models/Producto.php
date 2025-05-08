<?php
/**
 * Clase para manejar operaciones relacionadas con productos
 */
class Producto {
    private $conn;
    
    /**
     * Constructor
     * @param mysqli $db Conexión a la base de datos
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crear un nuevo producto
     * 
     * @param string $nombre Nombre del producto
     * @param string $codigo Código de barras o código del producto
     * @param string $descripcion Descripción del producto (opcional)
     * @param int $proveedorID ID del proveedor (opcional)
     * @param float $precioCompra Precio de compra
     * @param float $precioVentaPlancha Precio de venta por plancha
     * @param float $precioVentaMediaPlancha Precio de venta por media plancha
     * @param float $precioVentaUnitario Precio de venta unitario
     * @param int $unidadesPorPlancha Unidades por plancha
     * @param string $imagen Ruta de la imagen (opcional)
     * @param bool $activo Estado del producto (por defecto activo)
     * @return bool True si se creó correctamente, False en caso contrario
     */
    public function create($nombre, $codigo, $descripcion = '', $proveedorID = null, $precioCompra = 0, 
                          $precioVentaPlancha = 0, $precioVentaMediaPlancha = 0, $precioVentaUnitario = 0, 
                          $unidadesPorPlancha = 0, $imagen = '', $activo = true) {
        // Preparar consulta
        $query = "INSERT INTO Producto (nombre, codigo, descripcion, proveedorID, precioCompra, 
                                     precioVentaPlancha, precioVentaMediaPlancha, precioVentaUnitario, 
                                     unidadesPorPlancha, imagen, activo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("sssiddddisd", $param_nombre, $param_codigo, $param_descripcion, $param_proveedorID, 
                            $param_precioCompra, $param_precioVentaPlancha, $param_precioVentaMediaPlancha, 
                            $param_precioVentaUnitario, $param_unidadesPorPlancha, $param_imagen, $param_activo);
            
            // Establecer parámetros
            $param_nombre = $nombre;
            $param_codigo = $codigo;
            $param_descripcion = $descripcion;
            $param_proveedorID = $proveedorID;
            $param_precioCompra = $precioCompra;
            $param_precioVentaPlancha = $precioVentaPlancha;
            $param_precioVentaMediaPlancha = $precioVentaMediaPlancha;
            $param_precioVentaUnitario = $precioVentaUnitario;
            $param_unidadesPorPlancha = $unidadesPorPlancha;
            $param_imagen = $imagen;
            $param_activo = $activo ? 1 : 0;
            
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
     * Obtener todos los productos
     * 
     * @return array Lista de productos
     */
    public function getAll() {
        $productos = [];
        
        // Preparar consulta
        $query = "SELECT productoID, codigo, nombre, descripcion, proveedorID, precioCompra, 
                         precioVentaPlancha, precioVentaMediaPlancha, precioVentaUnitario, 
                         unidadesPorPlancha, imagen, activo 
                  FROM Producto 
                  ORDER BY nombre ASC";
        
        if($stmt = $this->conn->prepare($query)) {
            // Ejecutar la consulta
            if($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Obtener los resultados
                while($row = $result->fetch_assoc()) {
                    $productos[] = $row;
                }
            }
            
            $stmt->close();
        }
        
        return $productos;
    }
    
    /**
     * Obtener todos los productos junto con su información de inventario
     * 
     * @return array Lista de productos con datos de inventario
     */
    public function getAllWithInventory() {
        $productos = [];
        
        // Preparar consulta que une productos con su inventario
        $query = "SELECT p.productoID, p.codigo, p.nombre, p.descripcion, p.proveedorID, 
                         p.precioCompra, p.precioVentaPlancha, p.precioVentaMediaPlancha, 
                         p.precioVentaUnitario, p.unidadesPorPlancha, p.imagen, p.activo,
                         i.cantidadPlanchas, i.cantidadUnidades 
                  FROM Producto p 
                  LEFT JOIN Inventario i ON p.productoID = i.productoID 
                  ORDER BY p.nombre ASC";
        
        if($result = $this->conn->query($query)) {
            // Obtener los resultados
            while($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
        }
        
        return $productos;
    }
    
    /**
     * Obtener un producto por su ID
     * 
     * @param int $id ID del producto
     * @return array|null Datos del producto o null si no existe
     */
    public function getById($id) {
        // Preparar consulta
        $query = "SELECT productoID, codigo, nombre, descripcion, proveedorID, precioCompra, 
                         precioVentaPlancha, precioVentaMediaPlancha, precioVentaUnitario, 
                         unidadesPorPlancha, imagen, activo 
                  FROM Producto 
                  WHERE productoID = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("i", $param_id);
            $param_id = $id;
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $result = $stmt->get_result();
                
                if($result->num_rows == 1) {
                    $producto = $result->fetch_assoc();
                    $stmt->close();
                    return $producto;
                }
            }
            
            $stmt->close();
        }
        
        return null;
    }
    
    /**
     * Actualizar un producto
     * 
     * @param int $id ID del producto
     * @param string $nombre Nombre del producto
     * @param string $codigo Código de barras o código del producto
     * @param string $descripcion Descripción del producto (opcional)
     * @param int $proveedorID ID del proveedor (opcional)
     * @param float $precioCompra Precio de compra
     * @param float $precioVentaPlancha Precio de venta por plancha
     * @param float $precioVentaMediaPlancha Precio de venta por media plancha
     * @param float $precioVentaUnitario Precio de venta unitario
     * @param int $unidadesPorPlancha Unidades por plancha
     * @param string $imagen Ruta de la imagen (opcional)
     * @param bool $activo Estado del producto
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function update($id, $nombre, $codigo, $descripcion = '', $proveedorID = null, $precioCompra = 0, 
                          $precioVentaPlancha = 0, $precioVentaMediaPlancha = 0, $precioVentaUnitario = 0, 
                          $unidadesPorPlancha = 0, $imagen = '', $activo = true) {
        // Preparar consulta
        $query = "UPDATE Producto 
                  SET nombre = ?, 
                      codigo = ?, 
                      descripcion = ?, 
                      proveedorID = ?, 
                      precioCompra = ?, 
                      precioVentaPlancha = ?, 
                      precioVentaMediaPlancha = ?, 
                      precioVentaUnitario = ?, 
                      unidadesPorPlancha = ?, 
                      imagen = ?, 
                      activo = ?
                  WHERE productoID = ?";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $stmt->bind_param("sssiddddisdi", $param_nombre, $param_codigo, $param_descripcion, $param_proveedorID, 
                             $param_precioCompra, $param_precioVentaPlancha, $param_precioVentaMediaPlancha, 
                             $param_precioVentaUnitario, $param_unidadesPorPlancha, $param_imagen, $param_activo, $param_id);
            
            // Establecer parámetros
            $param_nombre = $nombre;
            $param_codigo = $codigo;
            $param_descripcion = $descripcion;
            $param_proveedorID = $proveedorID;
            $param_precioCompra = $precioCompra;
            $param_precioVentaPlancha = $precioVentaPlancha;
            $param_precioVentaMediaPlancha = $precioVentaMediaPlancha;
            $param_precioVentaUnitario = $precioVentaUnitario;
            $param_unidadesPorPlancha = $unidadesPorPlancha;
            $param_imagen = $imagen;
            $param_activo = $activo ? 1 : 0;
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
     * Eliminar un producto
     * 
     * @param int $id ID del producto
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function delete($id) {
        // Verificar si el producto tiene ventas asociadas
        if($this->productoHasTransactions($id)) {
            return false; // No permitir eliminar productos con transacciones
        }
        
        // Preparar consulta
        $query = "DELETE FROM Producto WHERE productoID = ?";
        
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
     * Verificar si un producto tiene transacciones asociadas
     * 
     * @param int $id ID del producto
     * @return bool True si tiene transacciones, False en caso contrario
     */
    private function productoHasTransactions($id) {
        // Preparar consulta para verificar si hay detalles de venta asociados
        $query = "SELECT COUNT(*) as count FROM DetalleVenta WHERE productoID = ?";
        
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
     * Buscar productos por nombre o código
     * 
     * @param string $term Término de búsqueda
     * @return array Lista de productos que coinciden con la búsqueda
     */
    public function search($term) {
        $productos = [];
        
        // Preparar consulta
        $query = "SELECT productoID, codigo, nombre, descripcion, proveedorID, precioCompra, 
                         precioVentaPlancha, precioVentaMediaPlancha, precioVentaUnitario, 
                         unidadesPorPlancha, imagen, activo 
                  FROM Producto 
                  WHERE nombre LIKE ? OR codigo LIKE ? 
                  ORDER BY nombre ASC";
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            $search_term = "%" . $term . "%";
            $stmt->bind_param("ss", $search_term, $search_term);
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Obtener los resultados
                while($row = $result->fetch_assoc()) {
                    $productos[] = $row;
                }
            }
            
            $stmt->close();
        }
        
        return $productos;
    }

    /**
     * Actualizar o crear inventario de un producto
     * 
     * @param int $productoID ID del producto
     * @param int $cantidadPlanchas Cantidad de planchas
     * @param int $cantidadUnidades Cantidad de unidades sueltas
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    public function updateInventory($productoID, $cantidadPlanchas, $cantidadUnidades) {
        // Verificar si existe ya un registro de inventario para este producto
        $checkQuery = "SELECT inventarioID FROM Inventario WHERE productoID = ?";
        $inventarioExists = false;
        
        if($stmt = $this->conn->prepare($checkQuery)) {
            $stmt->bind_param("i", $productoID);
            
            if($stmt->execute()) {
                $result = $stmt->get_result();
                $inventarioExists = ($result->num_rows > 0);
            }
            
            $stmt->close();
        }
        
        // Preparar consulta de actualización o inserción
        if($inventarioExists) {
            $query = "UPDATE Inventario 
                      SET cantidadPlanchas = ?, cantidadUnidades = ?, ultimaActualizacion = CURRENT_TIMESTAMP
                      WHERE productoID = ?";
        } else {
            $query = "INSERT INTO Inventario (productoID, cantidadPlanchas, cantidadUnidades) 
                      VALUES (?, ?, ?)";
        }
        
        if($stmt = $this->conn->prepare($query)) {
            // Vincular parámetros
            if($inventarioExists) {
                $stmt->bind_param("iii", $cantidadPlanchas, $cantidadUnidades, $productoID);
            } else {
                $stmt->bind_param("iii", $productoID, $cantidadPlanchas, $cantidadUnidades);
            }
            
            // Ejecutar la consulta
            if($stmt->execute()) {
                $stmt->close();
                return true;
            }
            
            $stmt->close();
        }
        
        return false;
    }
}