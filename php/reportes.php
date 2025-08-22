<?php

// A more secure and robust way to handle the date input.
// htmlspecialchars() prevents potential Cross-Site Scripting (XSS).
$fecha = isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : date('Y-m-d');

// Assuming 'conexion()' is a function that returns a PDO connection.
$conn = conexion();

// Check if the database connection was successful.
if (!$conn) {
    die("Error: No se pudo conectar a la base de datos.");
}
// SQL query using a prepared statement for security.
$sql = "SELECT
    p.nombre AS producto,
    SUM(od.cantidad) AS cantidad_vendida,
    SUM(od.cantidad * p.precio) AS total_ventas
FROM
    detalle_orden od
JOIN
    productos p ON od.producto_id = p.id
JOIN
    ordenes o ON od.orden_id = o.id
WHERE
    DATE(o.fecha) = :fecha
GROUP BY
    p.id, p.nombre
ORDER BY
    cantidad_vendida DESC;";

// Prepare the statement.
$stmt = $conn->prepare($sql);

// Check if the statement was prepared successfully.
if ($stmt === false) {
    die("Error: Fallo en la preparación de la consulta: " . implode(" ", $conn->errorInfo()));
}

// Bind the parameter by name
$stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);

// Execute the statement.
$stmt->execute();

// Use fetchAll() to get all the results into an array.
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Reporte de Productos Vendidos</h2>
<form method="GET" action="">
    <input type="hidden" name="vista" value="reports">
    <label for="fecha">Selecciona la fecha:</label>
    <input type="date" id="fecha" name="fecha" value="<?php echo $fecha; ?>">
    <button class="button is-link" type="submit">Ver reporte</button>
</form>

<?php
$total_ventas_general = 0;
foreach ($resultados as $row) {
    $total_ventas_general += $row['total_ventas'];
}
?>

<?php if (count($resultados) > 0): ?>
    <table border='1' cellpadding='5' class="table" id="tabla-reporte">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad Vendida</th>
                <th>Total Ventas</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resultados as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['producto']); ?></td>
                    <td><?php echo htmlspecialchars($row['cantidad_vendida']); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($row['total_ventas'], 2)); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" style="text-align:right;"><strong>Suma Total de Ventas:</strong></td>
                <td><strong>$<?php echo htmlspecialchars(number_format($total_ventas_general, 2)); ?></strong></td>
            </tr>
        </tbody>
    </table>
    <button class="button is-primary" onclick="imprimirTabla()">Imprimir reporte</button>
    <script>
    function imprimirTabla() {
        var tabla = document.getElementById('tabla-reporte');
        if (!tabla) {
            alert('No se encontró la tabla para imprimir.');
            return;
        }
        var ventana = window.open('', '', 'height=600,width=800');
    ventana.document.write('<html><head><title>Reporte de Productos Vendidos</title>');
    ventana.document.write('<link rel="stylesheet" href="../css/bulma.min.css">');
    ventana.document.write('<style>');
    ventana.document.write('@media print {');
    ventana.document.write('body { width: 80mm; margin: 0; font-size: 12px; }');
    ventana.document.write('table { width: 100%; font-size: 12px; border-collapse: collapse; }');
    ventana.document.write('th, td { padding: 2px 4px; border: 1px solid #000; text-align: left; }');
    ventana.document.write('h2 { font-size: 14px; text-align: center; margin-bottom: 8px; }');
    ventana.document.write('}');
    ventana.document.write('</style>');
    ventana.document.write('</head><body>');
    ventana.document.write('<h2>Reporte de Productos Vendidos</h2>');
    ventana.document.write(tabla.outerHTML);
    ventana.document.write('</body></html>');
        ventana.document.close();
        ventana.focus();
        ventana.print();
        ventana.close();
    }
    </script>
<?php else: ?>
    <p>No hay ventas registradas para la fecha seleccionada.</p>
<?php endif; ?>

<?php
// No need to close the statement with PDO, the connection will be closed
// automatically at the end of the script.
$conn = null;
?>