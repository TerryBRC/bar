<?php
require_once "../inc/session_start.php";
require_once "main.php";

$conexion = conexion();

$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;

if ($orden_id <= 0 || $mesa_id <= 0) {
    die("Orden o mesa inválida.");
}

// Obtener info mesa
$stmt = $conexion->prepare("SELECT numero FROM mesas WHERE id = ?");
$stmt->execute([$mesa_id]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
$mesa_numero = $mesa ? $mesa['numero'] : "Desconocida";

// Obtener detalles orden con productos
$stmt = $conexion->prepare("
    SELECT do.id AS detalle_id, p.nombre AS producto_nombre, p.categoria_id, p.precio, do.cantidad
    FROM detalle_orden do
    JOIN productos p ON do.producto_id = p.id
    WHERE do.orden_id = ?
");
$stmt->execute([$orden_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener guarniciones por detalle
$guarniciones_por_detalle = [];
$stmt = $conexion->prepare("SELECT detalle_orden_id, nombre FROM detalle_guarnicion WHERE detalle_orden_id IN (SELECT id FROM detalle_orden WHERE orden_id = ?)");
$stmt->execute([$orden_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $guarniciones_por_detalle[$row['detalle_orden_id']][] = $row['nombre'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Imprimir Orden #<?php echo $orden_id; ?></title>
<style>
  /*Estilo hoja A4*/
  /*body {
    font-family: Arial, sans-serif;
    margin: 1cm;
    color: #000;
  }
  h1, h2 {
    text-align: center;
    margin-bottom: 0.5em;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1em;
  }
  th, td {
    border: 1px solid #333;
    padding: 0.3em 0.5em;
    text-align: left;
  }
  .guarniciones {
    font-size: 0.85em;
    color: #555;
    margin-left: 1em;
  }
  .no-print {
    margin-bottom: 1em;
    text-align: center;
  }

  @media print {
    .no-print {
      display: none;
    }
  }*/
    /*Estilo Rollo */
  @media print {
  @page {
    size: 80mm auto; /* Ancho de ticket, altura automática */
    margin: 5mm 5mm 5mm 5mm; /* Márgenes reducidos */
  }
  body {
    width: 80mm;
    margin: 0;
    font-family: monospace, monospace;
    font-size: 12px;
    color: #000;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }
  th, td {
    border: none;
    padding: 2px 0;
    text-align: left;
  }
  h1, h2 {
    font-size: 14px;
    text-align: center;
    margin: 0 0 5px 0;
  }
  .guarniciones {
    font-size: 10px;
    color: #444;
    margin-left: 10px;
  }
  .no-print {
    display: none;
  }
}

</style>
    <link rel="stylesheet" href="../css/bulma.min.css">
<link rel="stylesheet" href="../css/estilos.css">
</head>
<body>

<div class="no-print">
  <button onclick="window.print()">Imprimir Orden</button>
</div>

<h1>Orden #<?php echo $orden_id; ?></h1>
<h2>Mesa <?php echo htmlspecialchars($mesa_numero); ?></h2>

<table>
  <thead>
    <tr>
      <th>Producto</th>
      <th>Cant</th>
      <th>Precio Unitario</th>
      <th>Subtotal</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $total = 0;
    foreach ($detalles as $detalle):
        $subtotal = $detalle['precio'] * $detalle['cantidad'];
        $total += $subtotal;
    ?>
    <tr>
      <td>
        <?php echo htmlspecialchars($detalle['producto_nombre']); ?>
        <?php if (isset($guarniciones_por_detalle[$detalle['detalle_id']])): ?>
          <div class="guarniciones">
            Guarniciones:
            <ul>
              <?php foreach ($guarniciones_por_detalle[$detalle['detalle_id']] as $g): ?>
                <li><?php echo htmlspecialchars($g); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </td>
      <td><?php echo $detalle['cantidad']; ?></td>
      <td>$<?php echo number_format($detalle['precio'], 2); ?></td>
      <td>$<?php echo number_format($subtotal, 2); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="3" style="text-align:right;"><strong>Total</strong></td>
      <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
    </tr>
  </tfoot>
</table>

<p>¡Gracias por su preferencia!</p>
<div class="no-print">
  <a href="../index.php?vista=create_order&mesa_id=<?= $mesa_id; ?>" class="button is-link is-outlined">
    <span><--- Volver a la mesa</span>
  </a>
</div>
</body>
</html>
