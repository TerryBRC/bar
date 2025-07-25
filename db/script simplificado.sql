CREATE DATABASE IF NOT EXISTS restaurante;
USE restaurante;
-- ---
-- 1. Tablas
-- ---
-- Tabla de Mesas
CREATE TABLE IF NOT EXISTS mesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL UNIQUE,
    estado ENUM('libre', 'esperando_cuenta' ,'ocupada') DEFAULT 'libre'
);
-- tabla categoria
CREATE TABLE IF NOT EXISTS categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);
-- datos principales categoria
insert into categoria (nombre) values('Buffete'),('Carta'),('Bebida'),('Sopas');
-- Tabla de Productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id int,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categoria(id)
);

-- Tabla de Ordenes (una orden por mesa, abierta hasta que se pide la cuenta)
CREATE TABLE IF NOT EXISTS ordenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mesa_id INT NOT NULL,
    estado ENUM('abierta', 'enviada_a_cobro', 'cerrada') DEFAULT 'abierta',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mesa_id) REFERENCES mesas(id)
);

-- Tabla de Detalles de Orden (productos pedidos en cada orden)
-- 'estado' ahora gestiona el flujo de preparación y el destino
CREATE TABLE IF NOT EXISTS detalle_orden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Tablas para Empleados y Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);
INSERT INTO roles (nombre) VALUES ('Administrador'),('Mesero'),('Cajero');

CREATE TABLE IF NOT EXISTS empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL, -- Idealmente encriptada (bcrypt, Argon2, etc.)
    rol_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);
INSERT INTO `empleados` VALUES (1,'Elmer Laguna','admin','$2y$10$8f4sFyFlvXyAuGZMxXw88.PZoCl54KUAs9p.W7q7IX.XtoH/f6Dj2',1,1),(2,'Elena Guevara','mesero1','$2y$10$Q3mcUOgyboRu/uNqwQ/pneeC7cdKd2UJxXss/2hM2Uvo3sS//.Fvu',2,1),(3,'Erica Galindo','cajero1','$2y$10$7bThsg1tsW9OZl2HyEIniumJim8C4g4uA9s57RdfN.MAjTtUWMzp.',3,1);

---
-- 2. Procedimientos Almacenados
-- ---

-- Procedimiento para crear una nueva orden y ocupar la mesa
DROP PROCEDURE IF EXISTS crear_orden;
DELIMITER $$
CREATE PROCEDURE crear_orden(IN p_mesa_id INT)
BEGIN
    DECLARE mesa_estado VARCHAR(20);

    SELECT estado INTO mesa_estado FROM mesas WHERE id = p_mesa_id;

    IF mesa_estado = 'libre' THEN
        INSERT INTO ordenes (mesa_id) VALUES (p_mesa_id);
        UPDATE mesas SET estado = 'ocupada' WHERE id = p_mesa_id;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La mesa ya está ocupada o esperando cuenta.';
    END IF;
END $$
call crear_orden(1);
-- Procedimiento para agregar productos a una orden existente

DROP PROCEDURE IF EXISTS agregar_producto;
DELIMITER $$
CREATE PROCEDURE agregar_producto(
    IN p_orden_id INT,
    IN p_producto_id INT,
    IN p_cantidad INT
)
BEGIN
    -- Check if the order is open before adding products
    DECLARE orden_estado VARCHAR(20);
    SELECT estado INTO orden_estado FROM ordenes WHERE id = p_orden_id;

    IF orden_estado = 'abierta' THEN
        INSERT INTO detalle_orden (
            orden_id,        -- Corrected column name
            producto_id,     -- Corrected column name
            cantidad
        ) VALUES (
            p_orden_id,
            p_producto_id,
            p_cantidad
        );
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden agregar productos a una orden que no está abierta.';
    END IF;
END $$
DELIMITER ;

-- Procedimiento para pedir la cuenta de una mesa
DROP PROCEDURE IF EXISTS pedir_cuenta;
DELIMITER $$
CREATE PROCEDURE pedir_cuenta(IN p_mesa_id INT)
BEGIN
    DECLARE orden_abierta_id INT;

    SELECT id INTO orden_abierta_id
    FROM ordenes
    WHERE mesa_id = p_mesa_id AND estado = 'abierta'
    ORDER BY fecha DESC
    LIMIT 1;

    IF orden_abierta_id IS NOT NULL THEN
        UPDATE ordenes SET estado = 'enviada_a_cobro' WHERE id = orden_abierta_id;
        UPDATE mesas SET estado = 'esperando_cuenta' WHERE id = p_mesa_id;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay orden abierta para esta mesa.';
    END IF;
END $$

-- Procedimiento para cerrar una orden y liberar la mesa (después del pago)
DROP PROCEDURE IF EXISTS cerrar_orden_y_liberar_mesa;
DELIMITER $$
CREATE PROCEDURE cerrar_orden_y_liberar_mesa(IN p_orden_id INT)
BEGIN
    DECLARE v_mesa_id INT;

    SELECT mesa_id INTO v_mesa_id FROM ordenes WHERE id = p_orden_id;

    IF v_mesa_id IS NOT NULL THEN
        UPDATE ordenes SET estado = 'cerrada' WHERE id = p_orden_id;
        UPDATE mesas SET estado = 'libre' WHERE id = v_mesa_id;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Orden no encontrada.';
    END IF;
END $$

DELIMITER ;
DROP PROCEDURE IF EXISTS reporte_ventas_entre_fechas;
DELIMITER $$
CREATE PROCEDURE reporte_ventas_entre_fechas(
    IN p_fecha_inicio DATETIME,
    IN p_fecha_fin DATETIME
)
BEGIN
    SELECT
        o.id AS orden_id,
        m.numero AS numero_mesa,
        o.fecha AS fecha_orden,
        SUM(do.cantidad * p.precio) AS total_orden
    FROM
        ordenes o
    JOIN
        mesas m ON o.mesa_id = m.id
    JOIN
        detalle_orden do ON o.id = do.orden_id
    JOIN
        productos p ON do.producto_id = p.id
    WHERE
        o.estado = 'cerrada' AND o.fecha BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY
        o.id, m.numero, o.fecha
    ORDER BY
        o.fecha DESC;
END $$
DELIMITER ;

DROP PROCEDURE IF EXISTS reporte_ventas_del_dia;
DELIMITER $$
CREATE PROCEDURE reporte_ventas_del_dia()
BEGIN
    SELECT
        o.id AS orden_id,
        m.numero AS numero_mesa,
        o.fecha AS fecha_orden,
        SUM(do.cantidad * p.precio) AS total_orden
    FROM
        ordenes o
    JOIN
        mesas m ON o.mesa_id = m.id
    JOIN
        detalle_orden do ON o.id = do.orden_id
    JOIN
        productos p ON do.producto_id = p.id
    WHERE
        o.estado = 'cerrada' AND DATE(o.fecha) = CURDATE()
    GROUP BY
        o.id, m.numero, o.fecha
    ORDER BY
        o.fecha DESC;
END $$
DELIMITER ;

DROP VIEW IF EXISTS vista_cajero;

-- Vista para el Cajero (mostrar órdenes listas para cobrar)
CREATE VIEW vista_cajero AS
SELECT
    o.id AS orden_id,
    m.numero AS mesa,
    o.fecha,
    SUM(p.precio * do.cantidad) AS total
FROM ordenes o
JOIN mesas m ON o.mesa_id = m.id
JOIN detalle_orden do ON do.orden_id = o.id
JOIN productos p ON do.producto_id = p.id
WHERE o.estado = 'enviada_a_cobro'
GROUP BY o.id, m.numero, o.fecha
ORDER BY o.fecha DESC;