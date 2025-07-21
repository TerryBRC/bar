
<?php
require_once "main.php";
$conexion = conexion();

// Validar parámetros recibidos
$detalle_id = isset($_GET['detalle_id']) ? intval($_GET['detalle_id']) : 0;
$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;

if ($detalle_id <= 0 || $orden_id <= 0 || $mesa_id <= 0) {
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=Parámetros%20inválidos");
    exit();
}

try {
    // Verificar que el detalle pertenece a la orden
    $verificar_detalle = $conexion->prepare("
        SELECT id FROM detalle_orden 
        WHERE id = ? AND orden_id = ?
    ");
    $verificar_detalle->execute([$detalle_id, $orden_id]);
    
    if (!$verificar_detalle->fetch()) {
        throw new Exception("El ítem no pertenece a esta orden");
    }

    // Eliminar el ítem
    $eliminar = $conexion->prepare("
        DELETE FROM detalle_orden 
        WHERE id = ?
    ");
    $eliminar->execute([$detalle_id]);

    // Verificar si la orden queda vacía
    $contar_items = $conexion->prepare("
        SELECT COUNT(*) as total FROM detalle_orden 
        WHERE orden_id = ?
    ");
    $contar_items->execute([$orden_id]);
    $total_items = $contar_items->fetch()['total'];

    // Redirigir con mensaje de éxito
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=item_removed");
    exit();

} catch (PDOException $e) {
    error_log("Error al eliminar ítem: " . $e->getMessage());
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=Error%20al%20eliminar%20el%20ítem");
    exit();
} catch (Exception $e) {
    header("Location: ../index.php?vista=create_order&mesa_id=$mesa_id&status=error&message=" . urlencode($e->getMessage()));
    exit();
}
?>
