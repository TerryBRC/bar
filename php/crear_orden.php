<?php
// crear_orden.php
require_once "main.php";

$conexion = conexion();

// Validar mesa_id
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
$current_order_id = 0;
$mesa_numero = '';
$categorias_con_productos = [];
$detalle_orden_actual = [];

if ($mesa_id > 0) {
    // Verificar existencia de la mesa
    $consulta_mesa = $conexion->prepare("SELECT id, numero, estado FROM mesas WHERE id = ?");
    $consulta_mesa->execute([$mesa_id]);
    $mesa = $consulta_mesa->fetch(PDO::FETCH_ASSOC);

    if (!$mesa) {
        die(json_encode(['error' => 'Mesa no encontrada']));
    }

    $mesa_numero = $mesa['numero'];

    // Buscar orden existente
    $consulta_orden = $conexion->prepare("SELECT id FROM ordenes WHERE mesa_id = ? AND estado = 'abierta' LIMIT 1");
    $consulta_orden->execute([$mesa_id]);
    $orden_existente = $consulta_orden->fetch(PDO::FETCH_ASSOC);

    if ($orden_existente) {
        $current_order_id = $orden_existente['id'];
    } else {
        // Crear nueva orden solo si la mesa está libre
        if ($mesa['estado'] === 'libre') {
            try {
                $conexion->beginTransaction();
                
                // Crear la orden
                $stmt = $conexion->prepare("INSERT INTO ordenes (mesa_id, estado, fecha) VALUES (?, 'abierta', NOW())");
                $stmt->execute([$mesa_id]);
                $current_order_id = $conexion->lastInsertId();
                
                // Actualizar estado de la mesa
                $stmt = $conexion->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?");
                $stmt->execute([$mesa_id]);
                
                $conexion->commit();
            } catch (PDOException $e) {
                $conexion->rollBack();
                die(json_encode(['error' => 'Error al crear orden: ' . $e->getMessage()]));
            }
        } else {
            die(json_encode(['error' => 'La mesa no está disponible para crear una nueva orden']));
        }
    }
} else {
    die(json_encode(['error' => 'ID de mesa no válido']));
}

// Obtener productos por categoría
$consulta_categorias = $conexion->query("
    SELECT c.id, c.nombre, 
           p.id AS producto_id, p.nombre AS producto_nombre, p.precio
    FROM categoria c
    LEFT JOIN productos p ON c.id = p.categoria_id
    ORDER BY c.nombre, p.nombre
");

while ($row = $consulta_categorias->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($categorias_con_productos[$row['id']])) {
        $categorias_con_productos[$row['id']] = [
            'nombre' => $row['nombre'],
            'productos' => []
        ];
    }
    
    if ($row['producto_id']) {
        $categorias_con_productos[$row['id']]['productos'][] = [
            'id' => $row['producto_id'],
            'nombre' => $row['producto_nombre'],
            'precio' => $row['precio']
        ];
    }
}

// Obtener detalles de la orden actual
if ($current_order_id > 0) {
    $consulta_detalle = $conexion->prepare("
        SELECT do.id AS detalle_id, p.nombre AS producto_nombre, 
               p.precio, do.cantidad
        FROM detalle_orden do
        JOIN productos p ON do.producto_id = p.id
        WHERE do.orden_id = ?
    ");
    $consulta_detalle->execute([$current_order_id]);
    $detalle_orden_actual = $consulta_detalle->fetchAll(PDO::FETCH_ASSOC);
}
?>
