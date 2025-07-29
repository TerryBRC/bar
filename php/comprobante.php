<?php
// php/comprobante.php
require_once "../inc/session_start.php";
require_once "main.php";

$conexion = conexion();
$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;

if ($orden_id <= 0) {
    die("Orden inválida.");
}

// Obtener datos de orden
$stmt = $conexion->prepare("
    SELECT o.id, o.fecha, o.metodo_pago, o.total_pago, o.cambio, m.numero AS mesa
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    WHERE o.id = ?
");
$stmt->execute([$orden_id]);
$orden = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orden) die("Orden no encontrada.");

// Obtener productos
$stmt = $conexion->prepare("
    SELECT p.nombre, do.cantidad, p.precio
    FROM detalle_orden do
    JOIN productos p ON do.producto_id = p.id
    WHERE do.orden_id = ?
");
$stmt->execute([$orden_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Orden</title>
    <style>
        body {
            font-family: monospace;
            font-size: 12px;
            width: 250px;
            margin: 0 auto;
            padding: 10px;
        }

        h2, p {
            text-align: center;
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        td {
            padding: 2px 0;
        }

        .total {
            border-top: 1px dashed black;
            margin-top: 5px;
            padding-top: 5px;
            font-weight: bold;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <h2>🍽️ Restaurante RINCON CHINANDEGANO</h2>
    <p>Mesa: <?= htmlspecialchars($orden['mesa']) ?></p>
    <p>Orden #: <?= $orden['id'] ?></p>
    <p><?= date("d/m/Y H:i", strtotime($orden['fecha'])) ?></p>
    <hr>

    <table>
        <?php foreach ($detalles as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['nombre']) ?></td>
                <td>x<?= $item['cantidad'] ?></td>
                <td style="text-align:right;">C$ <?= number_format($item['precio'] * $item['cantidad'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p class="total">Total: C$ <?= number_format($orden['total_pago'], 2) ?></p>
    <p>Método: <?= ucfirst($orden['metodo_pago']) ?></p>
    <p>Cambio: C$ <?= number_format($orden['cambio'], 2) ?></p>

    <hr>
    <p>¡Gracias por su visita!</p>

    <script>
        window.print();
    </script>
</body>
</html>
