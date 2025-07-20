<?php
/*
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
*/
$conexion = conexion();
// verificamos si la mesa esta libre y no tiene una orden abierta
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
$current_order_id = 0;


// Obtener las categorías y productos
$consulta_categorias = "SELECT * FROM categoria";
$stmt_categorias = $conexion->prepare($consulta_categorias);
$stmt_categorias->execute();
$categorias_result = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);


if ($mesa_id > 0) {
    $consulta_mesa = "SELECT * FROM mesas WHERE id = ?";
    $stmt = $conexion->prepare($consulta_mesa);
    $stmt->execute([$mesa_id]);
    $mesa = $stmt->fetch();

    if ($mesa && $mesa['estado'] == 'libre') {
        // Crear una nueva orden para la mesa llamando al procedimiento
        $crear_orden = "INSERT INTO ordenes (mesa_id) VALUES (?)";
        $stmt = $conexion->prepare($crear_orden);
        if ($stmt->execute([$mesa_id])) {
            // Actualizar el estado de la mesa a 'ocupada'
            $current_order_id = $conexion->lastInsertId();
            $detalle_orden_actual = []; // Inicializar el detalle de la orden actual
            $actualizar_mesa = "UPDATE mesas SET estado = 'ocupada' WHERE id = ?";
            $stmt = $conexion->prepare($actualizar_mesa);
            $stmt->execute([$mesa_id]);
            header("Location: index.php?vista=create_order&mesa_id=$mesa_id");
        } else {
            echo "Error al crear la orden.";
        }
    } else {
?>    
<h1 class="title is-2 has-text-centered">Orden para Mesa <?php echo $mesa['numero']; ?></h1>

<div class="columns">
    <div class="column is-half">
        <h2 class="title is-4">Añadir Productos</h2>
        <form id="add-product-form" action="controllers/add_product_to_order.php" method="POST">
            <input type="hidden" name="orden_id" value="<?php echo $current_order_id; ?>">

            <?php foreach ($categorias_result as $categoria_id => $categoria): ?>
                <div class="field">
                    <label class="label"><?php echo $categoria['nombre']; ?></label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="producto_<?php echo $categoria_id; ?>" onchange="updatePrice(this)">
                                <option value="" data-price="0">Seleccione un producto</option>
                                <?php foreach ($categoria['productos'] as $producto): ?>
                                    <option value="<?php echo $producto['id']; ?>" data-price="<?php echo $producto['precio']; ?>">
                                        <?php echo $producto['nombre']; ?> ($<?php echo number_format($producto['precio'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="field">
                <label class="label">Cantidad</label>
                <div class="control">
                    <input class="input" type="number" name="cantidad" value="1" min="1" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Precio Unitario Seleccionado</label>
                <div class="control">
                    <input class="input" type="text" id="selected-price" value="$0.00" readonly>
                </div>
            </div>

            <div class="field">
                <div class="control">
                    <button type="submit" class="button is-primary is-fullwidth">Agregar a la Orden</button>
                </div>
            </div>
        </form>
    </div>

    <div class="column is-half">
        <h2 class="title is-4">Detalle de la Orden #<?php echo $current_order_id; ?></h2>
        <?php if (!empty($detalle_orden_actual)): ?>
            <table class="table is-striped is-fullwidth">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total_orden = 0; ?>
                    <?php foreach ($detalle_orden_actual as $detalle): ?>
                        <?php $subtotal = $detalle['cantidad'] * $detalle['precio']; ?>
                        <tr>
                            <td><?php echo $detalle['producto_nombre']; ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                            <td>$<?php echo number_format($detalle['precio'], 2); ?></td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <a href="controllers/remove_item.php?detalle_id=<?php echo $detalle['detalle_id']; ?>" class="button is-danger is-small">Eliminar</a>
                                </td>
                        </tr>
                        <?php $total_orden += $subtotal; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="has-text-right has-text-weight-bold">Total:</td>
                        <td class="has-text-weight-bold">$<?php echo number_format($total_orden, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <div class="buttons is-right">
                <a href="controllers/request_bill_action.php?mesa_id=<?php echo $mesa_id; ?>" class="button is-warning is-large">
                    Pedir Cuenta
                </a>
            </div>
        <?php else: ?>
            <p class="notification is-info">Aún no hay productos en esta orden.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function updatePrice(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        document.getElementById('selected-price').value = `$${parseFloat(price).toFixed(2)}`;
    }

    // You might want to handle form submission with AJAX for a smoother experience
    // For simplicity, this example uses a standard form submission.
</script>
<?php
    }
} else {
    echo "ID de mesa inválido.";
}
?>