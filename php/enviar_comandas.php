<?php
require_once 'main.php';
require_once "../inc/session_start.php";
// Categorías clasificadas manualmente
$categorias_comida = [1, 2, 4,7,8];   // Cambia según tu base
$categorias_bebida = [3];      // Cambia según tu base

$orden_id = $_GET['orden_id'] ?? null;
if (!$orden_id) {
    die("Orden no especificada.");
}

$conexion = conexion();

// Obtener detalles con producto y categoría
$stmt = $conexion->prepare("
    SELECT do.id AS detalle_id, do.cantidad, p.nombre AS producto, p.categoria_id
    FROM detalle_orden do
    INNER JOIN productos p ON p.id = do.producto_id
    WHERE do.orden_id = ?
");
$stmt->execute([$orden_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$comanda_cocina = [];
$comanda_barra = [];

foreach ($detalles as $detalle) {
    $linea = $detalle['cantidad'] . 'x ' . $detalle['producto'];

    // Agregar guarniciones si existen en sesión
    if (isset($_SESSION['guarniciones_por_detalle'][$detalle['detalle_id']])) {
        $guarniciones = $_SESSION['guarniciones_por_detalle'][$detalle['detalle_id']];
        foreach ($guarniciones as $g) {
            $linea .= " - $g";
        }
    }

    if (in_array($detalle['categoria_id'], $categorias_comida)) {
        $comanda_cocina[] = $linea;
    } elseif (in_array($detalle['categoria_id'], $categorias_bebida)) {
        $comanda_barra[] = $linea;
    }
}
// aqui tiene que capturar las impresoras de cada puesto y mandarlas a imprimir sin ninguna vista!
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comandas - Orden <?php echo $orden_id; ?></title>
    <link rel="stylesheet" href="../css/bulma.min.css">
</head>
<body class="p-5">

    <h1 class="title is-4">🧾 Comandas - Orden #<?php echo $orden_id; ?></h1>
    <h2>Id: <?php echo $_SESSION['id']; ?></h2><br>
    <h2>Nombre: <?php echo $_SESSION['nombre']; ?></h2>

    <?php if (!empty($comanda_cocina)) : ?>
    <section class="box">
        <h2 class="title is-5">👨‍🍳 Cocina</h2>
        <ul>
            <?php foreach ($comanda_cocina as $item): ?>
                <li><?php echo htmlspecialchars($item); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if (!empty($comanda_barra)) : ?>
    <section class="box mt-4">
        <h2 class="title is-5">🍹 Barra</h2>
        <ul>
            <?php foreach ($comanda_barra as $item): ?>
                <li><?php echo htmlspecialchars($item); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <a href="../index.php?vista=table_list" class="button is-link mt-5">← Volver a MESAS</a>

</body>
</html>
