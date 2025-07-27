<?php
require_once 'main.php';
require __DIR__ . '\..\vendor\autoload.php'; // Adjust path if needed
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
$conexion = conexion();

$current_order_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
if ($current_order_id <= 0) {
    die("Orden inválida.");
}
$current_mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
if ($current_mesa_id <= 0) {
    die("Mesa inválida.");
}
// Get current order details
if ($current_order_id > 0) {
    $consulta_detalle = $conexion->prepare("
        SELECT do.id AS detalle_id, p.id AS producto_id, p.nombre AS producto_nombre, 
               p.precio, do.cantidad
        FROM detalle_orden do
        JOIN productos p ON do.producto_id = p.id
        WHERE do.orden_id = ?
    ");
    $consulta_detalle->execute([$current_order_id]);
    $detalle_orden_actual = $consulta_detalle->fetchAll(PDO::FETCH_ASSOC);
}

$printer = [
    'printer_name' => 'PrinterName', // Replace with your printer name
    'printer_ip' => ''];
if ($printer) {
    // 1. Fetch the actual order details for printing (items, quantities, prices)
    $order_details_query = $conexion->prepare("
        SELECT do.cantidad, p.precio, p.nombre AS producto_nombre
        FROM detalle_orden do
        JOIN productos p ON do.producto_id = p.id
        WHERE do.orden_id = ?
    ");
    $order_details_query->execute([$current_order_id]);
    $order_items = $order_details_query->fetchAll(PDO::FETCH_ASSOC);

    // Get prefactura data if not already fetched for the total
    if (!isset($prefactura_data)) {
        $prefactura = $conexion->prepare("
            SELECT o.id AS orden_id, m.numero AS mesa_numero, 
                   SUM(do.cantidad * p.precio) AS total
            FROM ordenes o
            JOIN mesas m ON o.mesa_id = m.id
            JOIN detalle_orden do ON o.id = do.orden_id
            join productos p ON do.producto_id = p.id
            WHERE o.id = ?
            GROUP BY o.id, m.numero
        ");
        $prefactura->execute([$current_order_id]);
        $prefactura_data = $prefactura->fetch(PDO::FETCH_ASSOC);
        try {
            $connector = new NetworkPrintConnector($printer['printer_ip'], 9100); // 9100 is common for raw TCP
            $printer_obj = new Printer($connector);

            // --- Construct the Receipt ---
            $printer_obj->setJustification(Printer::JUSTIFY_CENTER);
            $printer_obj->text("RINCON CHINANDEGANO\n");
            $printer_obj->text("--- Pre-factura ---\n");
            $printer_obj->text("Mesa: " . $prefactura_data['mesa_numero'] . "\n");
            $printer_obj->text("Fecha: " . date('d/m/Y H:i') . "\n");
            $printer_obj->feed(2);

            $printer_obj->setJustification(Printer::JUSTIFY_LEFT);
            $printer_obj->text("--------------------------------\n");
            $printer_obj->text(sprintf("%-6s %-18s %7s\n", "CANT", "PRODUCTO", "SUBTOTAL"));
            $printer_obj->text("--------------------------------\n");

            foreach ($order_items as $item) {
                $subtotal_item = $item['cantidad'] * $item['precio'];
                $printer_obj->text(sprintf("%-6s %-18s %7.2f\n", 
                                          $item['cantidad'], 
                                          $item['producto_nombre'], 
                                          $subtotal_item));
            }

            $printer_obj->text("--------------------------------\n");
            $printer_obj->setJustification(Printer::JUSTIFY_RIGHT);
            $printer_obj->setTextSize(2, 2); // Larger text for total
            $printer_obj->text("TOTAL: $" . number_format($prefactura_data['total'], 2) . "\n");
            $printer_obj->setTextSize(1, 1); // Reset text size
            $printer_obj->feed(3);
            $printer_obj->setJustification(Printer::JUSTIFY_CENTER);
            $printer_obj->text("¡Gracias por su visita!\n");
            $printer_obj->feed(2);
            $printer_obj->cut(); // Cut the paper

            $printer_obj->close();
            header("../index.php?vista=create_order&mesa_id=<?= $mesa_id_get; ?>");
            //echo "<p>Impresión enviada a {$printer['printer_name']} ({$printer['printer_ip']}).</p>";

        } catch (Exception $e) {
            echo "<p>Error al imprimir: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>No se encontraron detalles de la orden para imprimir.</p>";
    }
} else {
    echo "<p>No se encontró una impresora activa o el ID de la orden es inválido.</p>";
} 
?>