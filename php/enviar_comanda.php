<?php
require_once "../inc/session_start.php";
require_once "main.php";

$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
if ($orden_id <= 0) {
    die("Orden inválida.");
}

$conexion = conexion();

// Categorías por área
$categorias_comida = [1, 2, 4, 5, 6, 7, 8]; // Cocina
$categorias_bebida = [3];                  // Barra

// Obtener detalles no enviados
$sql = "
    SELECT do.id AS detalle_id, do.producto_id, do.cantidad, p.nombre AS producto, p.categoria_id,
           GROUP_CONCAT(dg.nombre SEPARATOR ', ') AS guarniciones
    FROM detalle_orden do
    JOIN productos p ON do.producto_id = p.id
    LEFT JOIN detalle_guarnicion dg ON dg.detalle_orden_id = do.id
    WHERE do.orden_id = ? AND do.enviado = 0
    GROUP BY do.id
";
$stmt = $conexion->prepare($sql);
$stmt->execute([$orden_id]);
$detalles = $stmt->fetchAll();

$para_cocina = [];
$para_barra = [];

foreach ($detalles as $detalle) {
    if (in_array($detalle['categoria_id'], $categorias_comida)) {
        $para_cocina[] = $detalle;
    } elseif (in_array($detalle['categoria_id'], $categorias_bebida)) {
        $para_barra[] = $detalle;
    }
}

// Marcar todos como enviados (cocina y barra)
$ids_enviar = array_merge(
    array_column($para_cocina, 'detalle_id'),
    array_column($para_barra, 'detalle_id')
);
if (!empty($ids_enviar)) {
    $in_query = implode(',', array_fill(0, count($ids_enviar), '?'));
    $update = $conexion->prepare("UPDATE detalle_orden SET enviado = 1 WHERE id IN ($in_query)");
    $update->execute($ids_enviar);
}

// Guardar en sesión para mostrar en la vista
$_SESSION['comanda_cocina'] = $para_cocina;
$_SESSION['comanda_barra'] = $para_barra;

header("Location: ../views/comanda_general.php?orden_id=$orden_id");
exit();
