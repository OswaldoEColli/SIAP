-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-05-2025 a las 15:35:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `siap`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_stock` (IN `p_producto_id` INT, IN `p_cantidad` INT, IN `p_tipo_movimiento` VARCHAR(10))   BEGIN
    DECLARE v_cantidadPlanchas INT;
    DECLARE v_cantidadUnidades INT;
    DECLARE v_unidades_por_plancha INT;
    DECLARE v_planchas_completas INT;
    DECLARE v_unidades_restantes INT;
    DECLARE v_existe INT DEFAULT 0;
    DECLARE v_unidades_disponibles INT;
    DECLARE v_unidades_faltantes INT;
    DECLARE v_planchas_necesarias INT;
    DECLARE v_unidades_adicionales INT;
    DECLARE v_unidades_sobrantes INT;
    
    -- Obtener información del inventario actual
    SELECT cantidadPlanchas, cantidadUnidades INTO v_cantidadPlanchas, v_cantidadUnidades 
    FROM Inventario WHERE productoID = p_producto_id;
    
    -- Verificar si existe el registro
    SELECT COUNT(*) INTO v_existe FROM Inventario WHERE productoID = p_producto_id;
    
    -- Obtener unidades por plancha
    SELECT unidadesPorPlancha INTO v_unidades_por_plancha FROM Producto WHERE productoID = p_producto_id;
    
    IF p_tipo_movimiento = 'Entrada' THEN
        -- Calcular planchas completas y unidades restantes
        SET v_planchas_completas = FLOOR(p_cantidad / v_unidades_por_plancha);
        SET v_unidades_restantes = p_cantidad % v_unidades_por_plancha;
        
        IF v_existe > 0 THEN
            -- Actualizar inventario existente
            UPDATE Inventario 
            SET cantidadPlanchas = cantidadPlanchas + v_planchas_completas,
                cantidadUnidades = cantidadUnidades + v_unidades_restantes,
                ultimaActualizacion = CURRENT_TIMESTAMP
            WHERE productoID = p_producto_id;
        ELSE
            -- Crear nuevo registro de inventario
            INSERT INTO Inventario (productoID, cantidadPlanchas, cantidadUnidades)
            VALUES (p_producto_id, v_planchas_completas, v_unidades_restantes);
        END IF;
        
    ELSEIF p_tipo_movimiento = 'Salida' THEN
        -- Implementar lógica para restar del inventario
        IF v_existe > 0 THEN
            IF v_cantidadUnidades >= p_cantidad THEN
                -- Si hay suficientes unidades sueltas
                UPDATE Inventario 
                SET cantidadUnidades = cantidadUnidades - p_cantidad,
                    ultimaActualizacion = CURRENT_TIMESTAMP
                WHERE productoID = p_producto_id;
            ELSE
                -- Calcular cuántas planchas debo convertir en unidades
                SET v_unidades_disponibles = v_cantidadUnidades;
                SET v_unidades_faltantes = p_cantidad - v_unidades_disponibles;
                SET v_planchas_necesarias = CEIL(v_unidades_faltantes / v_unidades_por_plancha);
                SET v_unidades_adicionales = v_planchas_necesarias * v_unidades_por_plancha;
                SET v_unidades_sobrantes = v_unidades_adicionales - v_unidades_faltantes;
                
                IF v_cantidadPlanchas >= v_planchas_necesarias THEN
                    UPDATE Inventario 
                    SET cantidadPlanchas = cantidadPlanchas - v_planchas_necesarias,
                        cantidadUnidades = v_unidades_sobrantes,
                        ultimaActualizacion = CURRENT_TIMESTAMP
                    WHERE productoID = p_producto_id;
                ELSE
                    SIGNAL SQLSTATE '45000' 
                    SET MESSAGE_TEXT = 'No hay suficiente stock para el producto';
                END IF;
            END IF;
        ELSE
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'No existe inventario para este producto';
        END IF;
        
    ELSEIF p_tipo_movimiento = 'Ajuste' THEN
        -- Para ajustes directos de inventario (positivos o negativos)
        SET v_planchas_completas = FLOOR(p_cantidad / v_unidades_por_plancha);
        SET v_unidades_restantes = p_cantidad % v_unidades_por_plancha;
        
        IF v_existe > 0 THEN
            UPDATE Inventario 
            SET cantidadPlanchas = v_planchas_completas,
                cantidadUnidades = v_unidades_restantes,
                ultimaActualizacion = CURRENT_TIMESTAMP
            WHERE productoID = p_producto_id;
        ELSE
            INSERT INTO Inventario (productoID, cantidadPlanchas, cantidadUnidades)
            VALUES (p_producto_id, v_planchas_completas, v_unidades_restantes);
        END IF;
    END IF;
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `calcular_precio` (`p_producto_id` INT, `p_tipo_venta` VARCHAR(20), `p_cantidad` INT) RETURNS DECIMAL(10,2)  BEGIN
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_precio_plancha DECIMAL(10,2);
    DECLARE v_precio_media_plancha DECIMAL(10,2);
    DECLARE v_precio_unitario DECIMAL(10,2);
    
    SELECT precioVentaPlancha, precioVentaMediaPlancha, precioVentaUnitario 
    INTO v_precio_plancha, v_precio_media_plancha, v_precio_unitario
    FROM Producto WHERE productoID = p_producto_id;
    
    IF p_tipo_venta = 'Plancha' THEN
        SET v_precio = v_precio_plancha * p_cantidad;
    ELSEIF p_tipo_venta = 'MediaPlancha' THEN
        SET v_precio = v_precio_media_plancha * p_cantidad;
    ELSEIF p_tipo_venta = 'Unitario' THEN
        SET v_precio = v_precio_unitario * p_cantidad;
    END IF;
    
    RETURN v_precio;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `verificar_disponibilidad` (`p_producto_id` INT, `p_cantidad` INT, `p_tipo_venta` VARCHAR(20)) RETURNS TINYINT(1)  BEGIN
    DECLARE v_cantidadPlanchas INT;
    DECLARE v_cantidadUnidades INT;
    DECLARE v_unidades_por_plancha INT;
    DECLARE v_total_unidades INT;
    DECLARE v_unidades_requeridas INT;
    
    -- Obtener información del inventario
    SELECT cantidadPlanchas, cantidadUnidades INTO v_cantidadPlanchas, v_cantidadUnidades
    FROM Inventario WHERE productoID = p_producto_id;
    
    -- Obtener unidades por plancha
    SELECT unidadesPorPlancha INTO v_unidades_por_plancha 
    FROM Producto WHERE productoID = p_producto_id;
    
    -- Calcular total de unidades disponibles
    SET v_total_unidades = (v_cantidadPlanchas * v_unidades_por_plancha) + v_cantidadUnidades;
    
    -- Calcular unidades requeridas según tipo de venta
    IF p_tipo_venta = 'Plancha' THEN
        SET v_unidades_requeridas = p_cantidad * v_unidades_por_plancha;
    ELSEIF p_tipo_venta = 'MediaPlancha' THEN
        SET v_unidades_requeridas = p_cantidad * (v_unidades_por_plancha / 2);
    ELSEIF p_tipo_venta = 'Unitario' THEN
        SET v_unidades_requeridas = p_cantidad;
    END IF;
    
    RETURN v_total_unidades >= v_unidades_requeridas;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `clienteID` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `esRecurrente` tinyint(1) DEFAULT 0,
  `saldoPendiente` decimal(10,2) DEFAULT 0.00,
  `fechaRegistro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`clienteID`, `nombre`, `apellidos`, `telefono`, `email`, `direccion`, `rfc`, `esRecurrente`, `saldoPendiente`, `fechaRegistro`) VALUES
(1, 'Cliente', 'General', '0000000000', 'cliente@general.com', 'Sin dirección', NULL, 0, 0.00, '2025-05-07 23:28:07'),
(3, 'Oswaldo', 'Colli', '44412', 'oswaldo@test.com', 'Fraccionamiento Viveros', 'asd', 1, 0.00, '2025-05-06 17:01:22'),
(4, 'emanuel', 'juarez', '9978263', 'emanuel@test.com', 'abc', '0', 1, 0.00, '2025-05-06 17:01:59'),
(5, 'Oswaldo', 'Colli', '123q', 'wd', 'Fraccionamiento Viveros', 'qwe1e4', 1, 0.00, '2025-05-07 04:02:20'),
(6, 'ricardo', 'puc', '874719', 'ricardo@test.com', 'abc', 'r123', 1, 0.00, '2025-05-07 17:34:45'),
(7, 'elizabeth', '', '5458612', 'e@test.com', 'uroepwj', 'e123', 1, 0.00, '2025-05-07 23:47:56'),
(8, 'pablo', '', '48561', 'nfmoew', 'dfsd', 'p123|', 1, 0.00, '2025-05-08 00:02:25'),
(9, 'lolo', '', '48596', 'fsbi', 'sfdn', 'l123', 1, 0.00, '2025-05-08 00:04:03'),
(10, 'qweqweq', '', '879156', 'qweqwe', 'qweqwe', 'qweqeq', 1, 0.00, '2025-05-08 00:09:15'),
(11, 'ureuweo', '', '748154', 'veiu', 'gsxda', 'ryu12o', 1, 0.00, '2025-05-08 00:10:25'),
(12, 'Oswaldo', 'Colli', '746', '', 'Fraccionamiento Viveros', ' tid6fd', 1, 0.00, '2025-05-08 02:28:57'),
(13, 'pepito', '', '1455', 'das', 'dfsafs', 'p123', 1, 0.00, '2025-05-08 02:52:30'),
(14, 'pepito', '', '1455', 'das', 'dfsafs', 'p123', 1, 0.00, '2025-05-08 02:52:41'),
(15, 'pepito', '', '4895', 'dfsbi', 'sdfafg', 'p123', 1, 0.00, '2025-05-08 02:55:44'),
(16, 'pepito', '', '4895', 'dfsbi', 'sdfafg', 'p123', 1, 0.00, '2025-05-08 02:57:47'),
(17, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:08'),
(18, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:13'),
(19, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:14'),
(20, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:15'),
(21, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:15'),
(22, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:15'),
(23, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:15'),
(24, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:15'),
(25, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:16'),
(26, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:17'),
(27, 'hgfse', '', '34', 'ghf', 'n', 'hrtt', 1, 0.00, '2025-05-08 02:58:32'),
(28, 'hgoehoir', '', '48596498', 'fiusdp@acosca', 'vnuiodspf', 'fiuospos', 1, 0.00, '2025-05-08 03:15:49'),
(29, 'berenice', 'de los angeles', '798156', 'aduasi@das', 'dsai', 'e8912', 1, 0.00, '2025-05-08 03:17:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallepedidoproveedor`
--

CREATE TABLE `detallepedidoproveedor` (
  `detallePedidoProveedorID` int(11) NOT NULL,
  `pedidoProveedorID` int(11) DEFAULT NULL,
  `productoID` int(11) DEFAULT NULL,
  `cantidadPlanchas` int(11) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `detallepedidoproveedor`
--
DELIMITER $$
CREATE TRIGGER `tr_actualizar_total_pedido` AFTER INSERT ON `detallepedidoproveedor` FOR EACH ROW BEGIN
    UPDATE PedidoProveedor
    SET totalPedido = (SELECT SUM(subtotal) FROM DetallePedidoProveedor WHERE pedidoProveedorID = NEW.pedidoProveedorID)
    WHERE pedidoProveedorID = NEW.pedidoProveedorID;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_actualizar_total_pedido_delete` AFTER DELETE ON `detallepedidoproveedor` FOR EACH ROW BEGIN
    UPDATE PedidoProveedor
    SET totalPedido = (SELECT IFNULL(SUM(subtotal), 0) FROM DetallePedidoProveedor WHERE pedidoProveedorID = OLD.pedidoProveedorID)
    WHERE pedidoProveedorID = OLD.pedidoProveedorID;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_actualizar_total_pedido_update` AFTER UPDATE ON `detallepedidoproveedor` FOR EACH ROW BEGIN
    UPDATE PedidoProveedor
    SET totalPedido = (SELECT SUM(subtotal) FROM DetallePedidoProveedor WHERE pedidoProveedorID = NEW.pedidoProveedorID)
    WHERE pedidoProveedorID = NEW.pedidoProveedorID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallerecepcion`
--

CREATE TABLE `detallerecepcion` (
  `detalleRecepcionID` int(11) NOT NULL,
  `recepcionID` int(11) DEFAULT NULL,
  `productoID` int(11) DEFAULT NULL,
  `cantidadRecibida` int(11) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `detallerecepcion`
--
DELIMITER $$
CREATE TRIGGER `tr_actualizar_total_recepcion` AFTER INSERT ON `detallerecepcion` FOR EACH ROW BEGIN
    UPDATE RecepcionMercancia
    SET totalFactura = (SELECT SUM(subtotal) FROM DetalleRecepcion WHERE recepcionID = NEW.recepcionID)
    WHERE recepcionID = NEW.recepcionID;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_actualizar_total_recepcion_delete` AFTER DELETE ON `detallerecepcion` FOR EACH ROW BEGIN
    UPDATE RecepcionMercancia
    SET totalFactura = (SELECT IFNULL(SUM(subtotal), 0) FROM DetalleRecepcion WHERE recepcionID = OLD.recepcionID)
    WHERE recepcionID = OLD.recepcionID;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_actualizar_total_recepcion_update` AFTER UPDATE ON `detallerecepcion` FOR EACH ROW BEGIN
    UPDATE RecepcionMercancia
    SET totalFactura = (SELECT SUM(subtotal) FROM DetalleRecepcion WHERE recepcionID = NEW.recepcionID)
    WHERE recepcionID = NEW.recepcionID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleventa`
--

CREATE TABLE `detalleventa` (
  `detalleVentaID` int(11) NOT NULL,
  `ventaID` int(11) DEFAULT NULL,
  `productoID` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `tipoVenta` enum('Plancha','MediaPlancha','Unitario') NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalleventa`
--

INSERT INTO `detalleventa` (`detalleVentaID`, `ventaID`, `productoID`, `cantidad`, `tipoVenta`, `precioUnitario`, `subtotal`) VALUES
(2, 3, 5, 1, '', 100.00, 100.00),
(3, 5, 4, 1, '', 12.00, 12.00),
(4, 13, 3, 1, '', 147.00, 147.00),
(5, 14, 5, 1, '', 123.00, 123.00),
(6, 14, 4, 1, '', 12.00, 12.00),
(7, 15, 4, 1, '', 12.00, 12.00),
(8, 16, 4, 1, '', 12.00, 12.00),
(9, 17, 4, 1, '', 12.00, 12.00),
(10, 18, 4, 100, '', 12.00, 1200.00),
(11, 19, 4, 1, '', 12.00, 12.00),
(12, 20, 4, 1, '', 12.00, 12.00),
(13, 20, 6, 1, '', 30.00, 30.00),
(14, 21, 5, 1, '', 123.00, 123.00),
(15, 22, 4, 1, '', 100.00, 100.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `inventarioID` int(11) NOT NULL,
  `productoID` int(11) DEFAULT NULL,
  `cantidadPlanchas` int(11) DEFAULT 0,
  `cantidadUnidades` int(11) DEFAULT 0,
  `ultimaActualizacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`inventarioID`, `productoID`, `cantidadPlanchas`, `cantidadUnidades`, `ultimaActualizacion`) VALUES
(1, 4, 0, -131, '2025-05-07 23:13:01'),
(2, 3, 0, -1, '2025-05-08 00:09:38'),
(3, 5, 0, 99, '2025-05-08 04:32:22'),
(4, 6, 0, -1, '2025-05-08 03:17:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `pagoID` int(11) NOT NULL,
  `ventaID` int(11) DEFAULT NULL,
  `usuarioID` int(11) DEFAULT NULL,
  `fechaPago` timestamp NOT NULL DEFAULT current_timestamp(),
  `monto` decimal(10,2) NOT NULL,
  `tipoPago` enum('Efectivo','Tarjeta','Transferencia','Crédito') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`pagoID`, `ventaID`, `usuarioID`, `fechaPago`, `monto`, `tipoPago`, `referencia`) VALUES
(1, 3, 1, '2025-05-07 23:07:46', 116.00, '', 'Prueba'),
(2, 5, 1, '2025-05-07 23:13:01', 13.92, 'Efectivo', 'Efectivo: $20, Cambio: $6.08'),
(3, 13, 1, '2025-05-08 00:09:38', 170.52, 'Efectivo', 'Efectivo: $500, Cambio: $329.48'),
(4, 14, 1, '2025-05-08 00:30:02', 156.60, 'Efectivo', 'Efectivo: $8000, Cambio: $7843.40'),
(5, 15, 1, '2025-05-08 00:31:53', 13.92, 'Efectivo', 'Efectivo: $20, Cambio: $6.08'),
(6, 16, 1, '2025-05-08 00:49:36', 13.92, 'Efectivo', 'Efectivo: $50, Cambio: $36.08'),
(7, 17, 1, '2025-05-08 00:58:48', 13.92, 'Efectivo', 'Efectivo: $50, Cambio: $36.08'),
(8, 18, 1, '2025-05-08 02:22:42', 1392.00, 'Tarjeta', 'Terminal: 1, Ref: 1234'),
(9, 19, 1, '2025-05-08 03:16:04', 13.92, 'Tarjeta', 'Terminal: 1, Ref: 7657'),
(10, 20, 1, '2025-05-08 03:17:59', 48.72, 'Tarjeta', 'Terminal: 1, Ref: 7894'),
(11, 21, 1, '2025-05-08 04:32:36', 142.68, 'Tarjeta', 'Terminal: 1, Ref: 1234'),
(12, 22, 1, '2025-05-08 05:12:28', 116.00, 'Efectivo', 'Efectivo: $200, Cambio: $84.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `passwordrecovery`
--

CREATE TABLE `passwordrecovery` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `verification_code` varchar(10) NOT NULL,
  `expiry` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `passwordrecovery`
--

INSERT INTO `passwordrecovery` (`id`, `email`, `token`, `verification_code`, `expiry`, `used`, `created_at`) VALUES
(13, 'collioswaldo@gmail.com', 'e98e335abdc8fa93b041b228c71dacb935f20148d1f869b42e683b302d9c4ba1', '820593', '2025-05-08 08:17:06', 0, '2025-05-08 05:17:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidoproveedor`
--

CREATE TABLE `pedidoproveedor` (
  `pedidoProveedorID` int(11) NOT NULL,
  `proveedorID` int(11) DEFAULT NULL,
  `usuarioID` int(11) DEFAULT NULL,
  `fechaPedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fechaEntregaEstimada` date DEFAULT NULL,
  `fechaEntregaReal` date DEFAULT NULL,
  `estado` enum('Creado','Enviado','Recibido','Cancelado') DEFAULT 'Creado',
  `totalPedido` decimal(10,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `productoID` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `proveedorID` int(11) DEFAULT NULL,
  `precioCompra` decimal(10,2) NOT NULL,
  `precioVentaPlancha` decimal(10,2) NOT NULL,
  `precioVentaMediaPlancha` decimal(10,2) NOT NULL,
  `precioVentaUnitario` decimal(10,2) NOT NULL,
  `unidadesPorPlancha` int(11) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`productoID`, `codigo`, `nombre`, `descripcion`, `proveedorID`, `precioCompra`, `precioVentaPlancha`, `precioVentaMediaPlancha`, `precioVentaUnitario`, `unidadesPorPlancha`, `imagen`, `activo`) VALUES
(3, '43', 'hola', '7hdw', NULL, 42356.00, 7561.00, 8564.00, 147.00, 12367, '', 1),
(4, '12341', 'pepsi', '', NULL, 50.00, 100.00, 80.00, 12.00, 24, 'https://i5.walmartimages.com.mx/gr/images/product-images/img_large/00750103131164L.jpg', 1),
(5, '1231', 'barritas', '123123', NULL, 2131.00, 123.00, 123.00, 123.00, 123, '', 1),
(6, '789456123', 'manzanita 1.5 L', '', NULL, 400.00, 600.00, 300.00, 30.00, 24, 'https://i5.walmartimages.com.mx/gr/images/product-images/img_large/00750103136004L.jpg', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `proveedorID` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `personaContacto` varchar(100) DEFAULT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepcionmercancia`
--

CREATE TABLE `recepcionmercancia` (
  `recepcionID` int(11) NOT NULL,
  `pedidoProveedorID` int(11) DEFAULT NULL,
  `usuarioID` int(11) DEFAULT NULL,
  `fechaRecepcion` timestamp NOT NULL DEFAULT current_timestamp(),
  `numeroFactura` varchar(50) DEFAULT NULL,
  `totalFactura` decimal(10,2) DEFAULT 0.00,
  `estado` enum('Pendiente','Parcial','Completa','Rechazada') DEFAULT 'Pendiente',
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacioncontrasena`
--

CREATE TABLE `recuperacioncontrasena` (
  `recuperacionID` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `expira` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportecaja`
--

CREATE TABLE `reportecaja` (
  `reporteID` int(11) NOT NULL,
  `usuarioID` int(11) DEFAULT NULL,
  `fechaApertura` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fechaCierre` timestamp NULL DEFAULT NULL,
  `montoInicial` decimal(10,2) NOT NULL,
  `montoFinal` decimal(10,2) DEFAULT NULL,
  `totalVentas` decimal(10,2) DEFAULT 0.00,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `estado` enum('Abierta','Cerrada') DEFAULT 'Abierta',
  `notaCierre` text DEFAULT NULL COMMENT 'Observaciones sobre el cierre de caja',
  `notaApertura` text DEFAULT NULL COMMENT 'Observaciones sobre la apertura de caja'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reportecaja`
--

INSERT INTO `reportecaja` (`reporteID`, `usuarioID`, `fechaApertura`, `fechaCierre`, `montoInicial`, `montoFinal`, `totalVentas`, `diferencia`, `estado`, `notaCierre`, `notaApertura`) VALUES
(1, 1, '2025-05-07 18:21:59', '2025-05-07 18:21:59', 123.00, 1.00, 0.00, 1.00, 'Cerrada', '', NULL),
(2, 1, '2025-05-07 18:25:44', '2025-05-07 18:25:44', 500.00, 200.00, 0.00, -300.00, 'Cerrada', '', NULL),
(3, 1, '2025-05-07 18:27:57', '2025-05-07 18:27:57', 1000.00, 500.00, 0.00, 499.00, 'Cerrada', 'hubo un robo', 'turno matutino'),
(4, 1, '2025-05-07 23:46:32', '2025-05-07 23:46:32', 800.00, 1.00, 0.00, 1.00, 'Cerrada', '', 'turno vespertino'),
(5, 1, '2025-05-08 00:31:11', '2025-05-08 00:31:11', 500.00, 5000.00, 0.00, 5000.00, 'Cerrada', '', 'turno de mañana'),
(6, 1, '2025-05-08 00:32:07', '2025-05-08 00:32:07', 123.00, 200.00, 0.00, 77.00, 'Cerrada', '', ''),
(7, 1, '2025-05-08 00:47:03', '2025-05-08 00:47:03', 12.00, 1.00, 0.00, -11.00, 'Cerrada', '', ''),
(8, 1, '2025-05-08 00:59:46', '2025-05-08 00:59:46', 600.00, 1000.00, 0.00, 400.00, 'Cerrada', '', ''),
(9, 1, '2025-05-08 02:23:53', '2025-05-08 02:23:53', 1000.00, 80000.00, 0.00, 79999.00, 'Cerrada', '', ''),
(10, 1, '2025-05-08 03:16:20', '2025-05-08 03:16:20', 800.00, 456.00, 0.00, 456.00, 'Cerrada', '', ''),
(11, 1, '2025-05-08 03:18:49', '2025-05-08 03:18:49', 500.00, 1000.00, 0.00, 500.00, 'Cerrada', '', 'mañana'),
(12, 1, '2025-05-08 04:32:46', '2025-05-08 04:32:46', 89.00, 233.00, 0.00, 144.00, 'Cerrada', '', ''),
(13, 1, '2025-05-08 05:12:37', '2025-05-08 05:12:37', 100.00, 10000.00, 0.00, 9900.00, 'Cerrada', '', 'hola');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `sucursalID` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` text NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `estado` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gerente` varchar(100) DEFAULT NULL,
  `horario` varchar(100) DEFAULT NULL,
  `fechaCreacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Activo','Inactivo') DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`sucursalID`, `nombre`, `direccion`, `ciudad`, `estado`, `telefono`, `email`, `gerente`, `horario`, `fechaCreacion`, `status`) VALUES
(1, 'Arian', 'Fraccionamiento Viveros', 'Campeche', 'Camp.', '123', 'admin@test.com', 'arian', '13123', '2025-05-08 02:21:50', 'Inactivo'),
(2, 'holaaa', 'Fraccionamiento Viveros', 'Campeche', 'Camp.', '123', 'admin@test.com', 'arian', '13123', '2025-05-08 02:37:12', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `usuarioID` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `nombreUsuario` varchar(50) NOT NULL,
  `contraseña` varchar(100) NOT NULL,
  `tipoUsuario` enum('Administrador','Vendedor','Encargado','Almacen') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fechaCreacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`usuarioID`, `nombre`, `apellidos`, `nombreUsuario`, `contraseña`, `tipoUsuario`, `email`, `telefono`, `fechaCreacion`, `activo`) VALUES
(1, 'Oswaldo', 'Colli', 'admin', 'hola', 'Administrador', 'admin@test.com', '1234567890', '2025-05-05 20:59:02', 1),
(2, 'Admin', 'Nuevo', 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin2@test.com', '1234567890', '2025-05-05 21:29:43', 1),
(3, 'emanuel', 'rosales', 'prueba', '123456', 'Vendedor', 'prueba@prueba.com', '123412', '2025-05-05 22:08:23', 1),
(4, 'prueba', 'prueba', 'collioswaldo', '123456', 'Vendedor', 'collioswaldo@gmail.com', '1234512312', '2025-05-05 23:11:13', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `usuarioID` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `nombreUsuario` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tipoUsuario` enum('Administrador','Vendedor','Inventario') NOT NULL,
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `fechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimoAcceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `ventaID` int(11) NOT NULL,
  `usuarioID` int(11) DEFAULT NULL,
  `clienteID` int(11) DEFAULT NULL,
  `fechaVenta` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `impuestos` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodoPago` enum('Efectivo','Tarjeta','Transferencia','Crédito') DEFAULT NULL,
  `estado` enum('Pendiente','Pagada','Cancelada') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`ventaID`, `usuarioID`, `clienteID`, `fechaVenta`, `subtotal`, `impuestos`, `total`, `metodoPago`, `estado`) VALUES
(3, 1, 6, '2025-05-07 23:07:46', 100.00, 16.00, 116.00, 'Efectivo', 'Pagada'),
(5, 1, 6, '2025-05-07 23:13:01', 12.00, 1.92, 13.92, 'Efectivo', 'Pagada'),
(13, 1, 1, '2025-05-08 00:09:38', 147.00, 23.52, 170.52, 'Efectivo', 'Pagada'),
(14, 1, 1, '2025-05-08 00:30:02', 135.00, 21.60, 156.60, 'Efectivo', 'Pagada'),
(15, 1, 1, '2025-05-08 00:31:53', 12.00, 1.92, 13.92, 'Efectivo', 'Pagada'),
(16, 1, 1, '2025-05-08 00:49:36', 12.00, 1.92, 13.92, 'Efectivo', 'Pagada'),
(17, 1, 1, '2025-05-08 00:58:48', 12.00, 1.92, 13.92, 'Efectivo', 'Pagada'),
(18, 1, 9, '2025-05-08 02:22:42', 1200.00, 192.00, 1392.00, 'Tarjeta', 'Pagada'),
(19, 1, 28, '2025-05-08 03:16:04', 12.00, 1.92, 13.92, 'Tarjeta', 'Pagada'),
(20, 1, 29, '2025-05-08 03:17:59', 42.00, 6.72, 48.72, 'Tarjeta', 'Pagada'),
(21, 1, 1, '2025-05-08 04:32:36', 123.00, 19.68, 142.68, 'Tarjeta', 'Pagada'),
(22, 1, 6, '2025-05-08 05:12:28', 100.00, 16.00, 116.00, 'Efectivo', 'Pagada');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`clienteID`);

--
-- Indices de la tabla `detallepedidoproveedor`
--
ALTER TABLE `detallepedidoproveedor`
  ADD PRIMARY KEY (`detallePedidoProveedorID`),
  ADD KEY `productoID` (`productoID`),
  ADD KEY `idx_detalle_pedido` (`pedidoProveedorID`);

--
-- Indices de la tabla `detallerecepcion`
--
ALTER TABLE `detallerecepcion`
  ADD PRIMARY KEY (`detalleRecepcionID`),
  ADD KEY `productoID` (`productoID`),
  ADD KEY `idx_detalle_recepcion` (`recepcionID`);

--
-- Indices de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD PRIMARY KEY (`detalleVentaID`),
  ADD KEY `productoID` (`productoID`),
  ADD KEY `idx_detalle_venta` (`ventaID`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`inventarioID`),
  ADD UNIQUE KEY `productoID` (`productoID`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`pagoID`),
  ADD KEY `usuarioID` (`usuarioID`),
  ADD KEY `idx_pago_venta` (`ventaID`);

--
-- Indices de la tabla `passwordrecovery`
--
ALTER TABLE `passwordrecovery`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedidoproveedor`
--
ALTER TABLE `pedidoproveedor`
  ADD PRIMARY KEY (`pedidoProveedorID`),
  ADD KEY `idx_pedido_proveedor` (`proveedorID`),
  ADD KEY `idx_pedido_usuario` (`usuarioID`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`productoID`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_producto_proveedor` (`proveedorID`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`proveedorID`);

--
-- Indices de la tabla `recepcionmercancia`
--
ALTER TABLE `recepcionmercancia`
  ADD PRIMARY KEY (`recepcionID`),
  ADD KEY `usuarioID` (`usuarioID`),
  ADD KEY `idx_recepcion_pedido` (`pedidoProveedorID`);

--
-- Indices de la tabla `recuperacioncontrasena`
--
ALTER TABLE `recuperacioncontrasena`
  ADD PRIMARY KEY (`recuperacionID`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`);

--
-- Indices de la tabla `reportecaja`
--
ALTER TABLE `reportecaja`
  ADD PRIMARY KEY (`reporteID`),
  ADD KEY `usuarioID` (`usuarioID`);

--
-- Indices de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD PRIMARY KEY (`sucursalID`),
  ADD KEY `idx_sucursal_nombre` (`nombre`),
  ADD KEY `idx_sucursal_ciudad` (`ciudad`),
  ADD KEY `idx_sucursal_estado` (`estado`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`usuarioID`),
  ADD UNIQUE KEY `nombreUsuario` (`nombreUsuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuarioID`),
  ADD UNIQUE KEY `nombreUsuario` (`nombreUsuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`ventaID`),
  ADD KEY `idx_venta_cliente` (`clienteID`),
  ADD KEY `idx_venta_usuario` (`usuarioID`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `clienteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `detallepedidoproveedor`
--
ALTER TABLE `detallepedidoproveedor`
  MODIFY `detallePedidoProveedorID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detallerecepcion`
--
ALTER TABLE `detallerecepcion`
  MODIFY `detalleRecepcionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  MODIFY `detalleVentaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `inventarioID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `pagoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `passwordrecovery`
--
ALTER TABLE `passwordrecovery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `pedidoproveedor`
--
ALTER TABLE `pedidoproveedor`
  MODIFY `pedidoProveedorID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `productoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `proveedorID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recepcionmercancia`
--
ALTER TABLE `recepcionmercancia`
  MODIFY `recepcionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recuperacioncontrasena`
--
ALTER TABLE `recuperacioncontrasena`
  MODIFY `recuperacionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportecaja`
--
ALTER TABLE `reportecaja`
  MODIFY `reporteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `sucursalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `usuarioID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `usuarioID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `ventaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detallepedidoproveedor`
--
ALTER TABLE `detallepedidoproveedor`
  ADD CONSTRAINT `detallepedidoproveedor_ibfk_1` FOREIGN KEY (`pedidoProveedorID`) REFERENCES `pedidoproveedor` (`pedidoProveedorID`),
  ADD CONSTRAINT `detallepedidoproveedor_ibfk_2` FOREIGN KEY (`productoID`) REFERENCES `producto` (`productoID`);

--
-- Filtros para la tabla `detallerecepcion`
--
ALTER TABLE `detallerecepcion`
  ADD CONSTRAINT `detallerecepcion_ibfk_1` FOREIGN KEY (`recepcionID`) REFERENCES `recepcionmercancia` (`recepcionID`),
  ADD CONSTRAINT `detallerecepcion_ibfk_2` FOREIGN KEY (`productoID`) REFERENCES `producto` (`productoID`);

--
-- Filtros para la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD CONSTRAINT `detalleventa_ibfk_1` FOREIGN KEY (`ventaID`) REFERENCES `venta` (`ventaID`),
  ADD CONSTRAINT `detalleventa_ibfk_2` FOREIGN KEY (`productoID`) REFERENCES `producto` (`productoID`);

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`productoID`) REFERENCES `producto` (`productoID`);

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`ventaID`) REFERENCES `venta` (`ventaID`),
  ADD CONSTRAINT `pago_ibfk_2` FOREIGN KEY (`usuarioID`) REFERENCES `usuario` (`usuarioID`);

--
-- Filtros para la tabla `pedidoproveedor`
--
ALTER TABLE `pedidoproveedor`
  ADD CONSTRAINT `pedidoproveedor_ibfk_1` FOREIGN KEY (`proveedorID`) REFERENCES `proveedor` (`proveedorID`),
  ADD CONSTRAINT `pedidoproveedor_ibfk_2` FOREIGN KEY (`usuarioID`) REFERENCES `usuario` (`usuarioID`);

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`proveedorID`) REFERENCES `proveedor` (`proveedorID`);

--
-- Filtros para la tabla `recepcionmercancia`
--
ALTER TABLE `recepcionmercancia`
  ADD CONSTRAINT `recepcionmercancia_ibfk_1` FOREIGN KEY (`pedidoProveedorID`) REFERENCES `pedidoproveedor` (`pedidoProveedorID`),
  ADD CONSTRAINT `recepcionmercancia_ibfk_2` FOREIGN KEY (`usuarioID`) REFERENCES `usuario` (`usuarioID`);

--
-- Filtros para la tabla `reportecaja`
--
ALTER TABLE `reportecaja`
  ADD CONSTRAINT `reportecaja_ibfk_1` FOREIGN KEY (`usuarioID`) REFERENCES `usuario` (`usuarioID`);

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`usuarioID`) REFERENCES `usuario` (`usuarioID`),
  ADD CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`clienteID`) REFERENCES `cliente` (`clienteID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
