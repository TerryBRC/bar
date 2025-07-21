<?php
// print_item.php
require_once "main.php"; // Asegúrate de que main.php contenga la función conexion()

$conexion = conexion();

// Validar y obtener parámetros
$detalle_id = isset($_GET['detalle_id']) ? intval($_GET['detalle_id']) : 0;
$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;

if ($detalle_id <= 0 || $orden_id <= 0 || $mesa_id <= 0) {
    echo "Parámetros inválidos para imprimir el ítem.";
    exit();
}

// Obtener los datos del detalle de la orden, el producto y la mesa
try {
    $consulta_detalle = $conexion->prepare("
        SELECT 
            do.cantidad,
            p.nombre AS producto_nombre,
            p.precio AS producto_precio,
            o.fecha_hora_creacion,
            m.numero AS mesa_numero
        FROM 
            detalle_orden do
        JOIN 
            productos p ON do.producto_id = p.id
        JOIN 
            ordenes o ON do.orden_id = o.id
        JOIN 
            mesas m ON o.mesa_id = m.id
        WHERE 
            do.id = ? AND do.orden_id = ? AND o.mesa_id = ?
    ");
    $consulta_detalle->execute([$detalle_id, $orden_id, $mesa_id]);
    $detalle = $consulta_detalle->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        echo "No se encontró el detalle del ítem para imprimir.";
        exit();
    }

    // Calcular el subtotal usando el precio del producto
    $subtotal = $detalle['cantidad'] * $detalle['producto_precio'];

} catch (PDOException $e) {
    error_log("Error al obtener datos para imprimir ítem: " . $e->getMessage());
    echo "Error interno al procesar la solicitud de impresión.";
    exit();
}

// --- Generar el HTML de la "facturita" ---
?>
    <style>
        body {
            font-family: 'Consolas', 'Courier New', monospace; /* Fuente monoespaciada para estilo de recibo */
            font-size: 12px; /* Tamaño de fuente para impresoras térmicas */
            width: 80mm; /* Ancho típico de un recibo térmico (ajusta según tu impresora) */
            margin: 0 auto; /* Centrar el contenido si el ancho es menor que la página */
            padding: 5mm;
            box-sizing: border-box;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 10px;
        }
        .item-details {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            margin-bottom: 10px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
        }
        .item-name {
            flex-grow: 1;
            padding-right: 5px;
        }
        .item-qty, .item-price, .item-subtotal {
            text-align: right;
            width: 25%; /* Ajusta el ancho para alinear columnas */
        }
        .item-qty { width: 15%; }
        .item-price { width: 25%; }
        .item-subtotal { width: 35%; }


        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        .info-line {
            text-align: left;
            margin-bottom: 3px;
        }
        @media print {
            body {
                width: auto; /* Permite que la impresora controle el ancho */
                margin: 0;
                padding: 0;
            }
        }
    </style>
<body onload="window.print()">
    <div class="header">
        <strong>BAR/RESTAURANTE EL CHOMBO</strong><br>
        Dirección: LEÓN, NICARAGUA<br>
        Tel: 1234-5678<br>
        <br>
        --- ORDEN DE COCINA / BAR ---
    </div>

    <div class="info-line">
        <strong>Orden #<?php echo htmlspecialchars($orden_id); ?></strong>
    </div>
    <div class="info-line">
        <strong>Mesa: <?php echo htmlspecialchars($detalle['mesa_numero']); ?></strong>
    </div>
    <div class="info-line">
        Fecha: <?php echo date('d/m/Y H:i', strtotime($detalle['fecha_hora_creacion'])); ?>
    </div>
    <br>

    <div class="item-details">
        <div class="item-row">
            <span class="item-name"><strong>Producto</strong></span>
            <span class="item-qty"><strong>Cant.</strong></span>
            <span class="item-price"><strong>P.Unit.</strong></span>
            <span class="item-subtotal"><strong>Subtotal</strong></span>
        </div>
        <div class="item-row">
            <span class="item-name"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></span>
            <span class="item-qty"><?php echo htmlspecialchars($detalle['cantidad']); ?></span>
            <span class="item-price">$<?php echo number_format($detalle['producto_precio'], 2); ?></span>
            <span class="item-subtotal">$<?php echo number_format($subtotal, 2); ?></span>
        </div>
    </div>

    <div class="total-row">
        <span>TOTAL ÍTEM:</span>
        <span>$<?php echo number_format($subtotal, 2); ?></span>
    </div>

    <div class="footer">
        <p>Gracias por su preferencia!</p>
        <p>****** FIN ÍTEM ******</p>
    </div>

    <script>
        // Imprime automáticamente la página cuando carga y luego la cierra (opcional)
        // setTimeout(() => {
        //     window.close(); 
        // }, 1000); // Pequeño retraso para asegurar que la impresión se inicie
    </script>
