<?php
$conexion = conexion();

// Fecha de hoy
$hoy = date("Y-m-d");

// Totales por método de pago
$stmt = $conexion->prepare("
    SELECT metodo_pago, COUNT(*) AS cantidad, SUM(total_pago) AS total
    FROM ordenes
    WHERE DATE(fecha) = ? AND estado = 'cerrada'
    GROUP BY metodo_pago
");
$stmt->execute([$hoy]);
$totales_por_metodo = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total general
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total_ordenes, SUM(total_pago) AS total_general
    FROM ordenes
    WHERE DATE(fecha) = ? AND estado = 'cerrada'
");
$stmt->execute([$hoy]);
$resumen_general = $stmt->fetch(PDO::FETCH_ASSOC);

// Lista de órdenes cerradas
$stmt = $conexion->prepare("
    SELECT o.id, m.numero AS mesa, o.metodo_pago, o.total_orden, o.total_pago, o.cambio, TIME(o.fecha) AS hora
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    WHERE DATE(o.fecha) = ? AND o.estado = 'cerrada'
    ORDER BY o.fecha DESC
");
$stmt->execute([$hoy]);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Mostrar totales por método de pago -->
<div class="box">
    <h3 class="title is-5">Totales por método de pago</h3>
    <table class="table is-fullwidth is-striped">
        <thead>
            <tr>
                <th>Método</th>
                <th>Cantidad de Órdenes</th>
                <th>Total (C$)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($totales_por_metodo as $fila): ?>
            <tr>
                <td><?= ucfirst($fila['metodo_pago']) ?></td>
                <td><?= $fila['cantidad'] ?></td>
                <td><?= number_format($fila['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Mostrar resumen general -->
<div class="notification is-info">
    <strong>Resumen del día:</strong><br>
    Total de órdenes: <?= $resumen_general['total_ordenes'] ?? 0 ?><br>
    Total recaudado: C$ <?= number_format($resumen_general['total_general'] ?? 0, 2) ?>
</div>

<!-- Mostrar tabla de órdenes cerradas -->
<div class="box">
    <h3 class="title is-5">Órdenes cerradas hoy</h3>
    <table class="table is-fullwidth is-hoverable is-striped">
        <thead>
            <tr>
                <th>Orden ID</th>
                <th>Mesa</th>
                <th>Método</th>
                <th>Total Orden</th>
                <th>Pagado</th>
                <th>Cambio</th>
                <th>Hora</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ordenes as $orden): ?>
            <tr>
                <td>#<?= $orden['id'] ?></td>
                <td><?= htmlspecialchars($orden['mesa']) ?></td>
                <td><?= ucfirst($orden['metodo_pago']) ?></td>
                <td><?= number_format($orden['total_orden'], 2) ?></td>
                <td><?= number_format($orden['total_pago'], 2) ?></td>
                <td><?= number_format($orden['cambio'], 2) ?></td>
                <td><?= $orden['hora'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
