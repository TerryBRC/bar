<?php
// php/agregar_producto_a_orden.php
require_once "../inc/session_start.php";
require_once "main.php";

$conexion = conexion();

// Validar método y parámetros
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['error' => 'Método no permitido']));
}

$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
$orden_id = isset($_POST['orden_id']) ? intval($_POST['orden_id']) : 0;
$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
$precio_unitario_modal = isset($_POST['precio_unitario']) ? floatval($_POST['precio_unitario']) : 0.00;
$guarniciones = $_POST['guarniciones'] ?? [];

if ($mesa_id <= 0 || $orden_id <= 0 || $producto_id <= 0 || $cantidad <= 0 || $precio_unitario_modal < 0) {
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=Datos%20inv%C3%A1lidos");
    exit();
}

// Verificar que la orden pertenezca a la mesa
$verificar_orden = $conexion->prepare("SELECT id FROM ordenes WHERE id = ? AND mesa_id = ?");
$verificar_orden->execute([$orden_id, $mesa_id]);
if (!$verificar_orden->fetch()) {
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=Orden%20inv%C3%A1lida%20para%20mesa");
    exit();
}

try {
    $conexion->beginTransaction();

    // Verificar si ya existe el producto en la orden
    $existe_producto = $conexion->prepare("
        SELECT id, cantidad FROM detalle_orden 
        WHERE orden_id = ? AND producto_id = ?
    ");
    $existe_producto->execute([$orden_id, $producto_id]);
    $detalle_existente = $existe_producto->fetch(PDO::FETCH_ASSOC);

    if ($detalle_existente) {
        // Actualizar cantidad
        $nueva_cantidad = $detalle_existente['cantidad'] + $cantidad;
        $detalle_id = $detalle_existente['id'];

        $actualizar = $conexion->prepare("UPDATE detalle_orden SET cantidad = ? WHERE id = ?");
        $actualizar->execute([$nueva_cantidad, $detalle_id]);

        // Eliminar guarniciones anteriores (por si acaso)
        $borrar_guarniciones = $conexion->prepare("DELETE FROM detalle_guarnicion WHERE detalle_orden_id = ?");
        $borrar_guarniciones->execute([$detalle_id]);
    } else {
        // Insertar nuevo detalle
        $insertar = $conexion->prepare("
            INSERT INTO detalle_orden (orden_id, producto_id, cantidad)
            VALUES (?, ?, ?)
        ");
        $insertar->execute([$orden_id, $producto_id, $cantidad]);
        $detalle_id = $conexion->lastInsertId();
    }

    // Guardar guarniciones si las hay (máx 3, ya validado en cliente)
    if (!empty($guarniciones)) {
        $insert_guarnicion = $conexion->prepare("
            INSERT INTO detalle_guarnicion (detalle_orden_id, nombre)
            VALUES (?, ?)
        ");

        foreach ($guarniciones as $guarnicion) {
            $insert_guarnicion->execute([$detalle_id, $guarnicion]);
        }

        // También almacenar en $_SESSION para mostrar en vista
        $_SESSION['guarniciones_por_detalle'][$detalle_id] = $guarniciones;
    }

    $conexion->commit();

    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=product_added");
    exit();

} catch (PDOException $e) {
    $conexion->rollBack();
    error_log("Error al agregar producto: " . $e->getMessage());
    die("Error en la base de datos: " . $e->getMessage());
}
