<?php
$conexion = conexion();

// Check if the connection was successful.
if (!$conexion) {
    die("Error: No se pudo conectar a la base de datos.");
}
// SQL query to get the total sales for the day.
$sql = "SELECT
    SUM(od.cantidad * p.precio) AS total_ventas,
    COUNT(DISTINCT o.id) AS total_ordenes
FROM
    detalle_orden od
JOIN
    productos p ON od.producto_id = p.id
JOIN
    ordenes o ON od.orden_id = o.id
WHERE
    DATE(o.fecha) = CURDATE();";
// Prepare the statement.
$stmt = $conexion->prepare($sql);
// Check if the statement was prepared successfully.
if ($stmt === false) {
    die("Error: Fallo en la preparación de la consulta: " . implode(" ", $conexion->errorInfo()));
}
// Execute the statement.
$stmt->execute();
// Fetch the result.
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<h2>Dashboard de Ventas del Día</h2>
<p>Total Ventas: <?php echo number_format($resultado['total_ventas'], 2); ?> C$</p>
<p>Total Órdenes: <?php echo $resultado['total_ordenes']; ?></p