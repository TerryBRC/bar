<?php
//php/agregar_producto_a_orden.php
require_once "inc/session_start.php";
require_once "main.php";

$conexion = conexion();
if (!empty($_POST['guarniciones'])) {
    $_SESSION['guarniciones_por_detalle'][$detalle_id] = $_POST['guarniciones'];
}
// Validar método y parámetros
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 400 Bad Request");
    die(json_encode(['error' => 'Método no permitido']));
}

$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
$orden_id = isset($_POST['orden_id']) ? intval($_POST['orden_id']) : 0;
$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
// No usaremos $precio_unitario para almacenar en DB, pero lo validamos si es necesario
$precio_unitario_modal = isset($_POST['precio_unitario']) ? floatval($_POST['precio_unitario']) : 0.00;

// Validar datos básicos
// Ajustamos la validación para que $precio_unitario_modal no impida la ejecución si es 0,
// solo si es negativo o no numérico (floatval lo convierte a 0 si no es numérico)
if ($mesa_id <= 0 || $orden_id <= 0 || $producto_id <= 0 || $cantidad <= 0 || $precio_unitario_modal < 0) {
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=Datos%20incompletos%20o%20inválidos");
    exit();
}


// Verificar que la orden pertenece a la mesa
$verificar_orden = $conexion->prepare("SELECT id FROM ordenes WHERE id = ? AND mesa_id = ?");
$verificar_orden->execute([$orden_id, $mesa_id]);
if (!$verificar_orden->fetch()) {
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=Orden%20no%20válida%20para%20esta%20mesa");
    exit();
}

try {
    // Verificar si el producto ya está en la orden
    $existe_producto = $conexion->prepare("
        SELECT id, cantidad FROM detalle_orden 
        WHERE orden_id = ? AND producto_id = ?
    ");
    $existe_producto->execute([$orden_id, $producto_id]);
    $detalle_existente = $existe_producto->fetch();

    if ($detalle_existente) {
        // Actualizar cantidad si ya existe
        $nueva_cantidad = $detalle_existente['cantidad'] + $cantidad;
        $actualizar = $conexion->prepare("
            UPDATE detalle_orden SET cantidad = ? 
            WHERE id = ?
        ");
        // ¡IMPORTANTE!: Quitamos 'precio = ?' de aquí
        $actualizar->execute([$nueva_cantidad, $detalle_existente['id']]);
    } else {
        // Insertar nuevo registro
        $insertar = $conexion->prepare("
            INSERT INTO detalle_orden (orden_id, producto_id, cantidad)
            VALUES (?, ?, ?)
        ");
        // ¡IMPORTANTE!: Quitamos 'precio' de la lista de columnas y el '?' correspondiente,
        // y también el $precio_unitario_modal del execute
        $insertar->execute([$orden_id, $producto_id, $cantidad]);
    }

    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=product_added");
    exit();

} catch (PDOException $e) {
    error_log("Error al agregar producto: " . $e->getMessage());
    // Muestra el error de PDO directamente para depuración, luego quita esto
    die("Error de base de datos: " . $e->getMessage() . "<br>Consulta SQL para INSERT: " . ($insertar->queryString ?? 'N/A') . "<br>Consulta SQL para UPDATE: " . ($actualizar->queryString ?? 'N/A'));
    // header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=Error%20al%20procesar%20la%20solicitud");
    // exit();
} catch (Exception $e) {
    // Muestra el error de aplicación directamente para depuración, luego quita esto
    die("Error de aplicación: " . $e->getMessage());
    // header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=" . urlencode($e->getMessage()));
    // exit();
}
?>