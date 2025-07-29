<?php
//php/cobrar_orden.php
require_once "./inc/session_start.php";
require_once "main.php";

$conexion = conexion();

// VALIDACIÓN Y PROCESAMIENTO SI SE ENVÍA EL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orden_id = intval($_POST['orden_id']);
    $mesa_id = intval($_POST['mesa_id']);
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $monto_pagado = floatval($_POST['monto_pagado']);
    $total_orden = floatval($_POST['total_orden']);

    if ($orden_id <= 0 || $mesa_id <= 0 || $total_orden <= 0 || $monto_pagado < 0 || empty($metodo_pago)) {
        $error = "Datos inválidos.";
    } elseif ($metodo_pago === 'efectivo' && $monto_pagado < $total_orden) {
        $error = "El monto pagado no cubre el total.";
    } else {
        $cambio = 0;
        if ($metodo_pago === 'efectivo') {
            $cambio = $monto_pagado - $total_orden;
        } else {
            $monto_pagado = $total_orden;
        }

        try {
            $conexion->beginTransaction();

            // Actualizar orden
            $stmt = $conexion->prepare("
                UPDATE ordenes 
                SET metodo_pago = ?, total_pago = ?, cambio = ?, estado = 'cerrada' 
                WHERE id = ?
            ");
            $stmt->execute([$metodo_pago, $monto_pagado, $cambio, $orden_id]);

            // Llamar procedimiento
            $conexion->exec("CALL cerrar_orden_y_liberar_mesa($orden_id)");

            $conexion->commit();
            $mensaje_exito = "✔️ Orden cobrada correctamente. Cambio: C$ " . number_format($cambio, 2);
            

        } catch (Exception $e) {
            $conexion->rollBack();
            $error = "❌ Error al procesar el cobro: " . $e->getMessage();
        }
    }
}

// CARGAR DETALLES DE LA ORDEN
$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : ($_POST['orden_id'] ?? 0);
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : ($_POST['mesa_id'] ?? 0);

if ($orden_id <= 0 || $mesa_id <= 0) {
    die("Orden o mesa inválida.");
}

$stmt = $conexion->prepare("SELECT numero FROM mesas WHERE id = ?");
$stmt->execute([$mesa_id]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
$mesa_numero = $mesa ? $mesa['numero'] : "Desconocida";

$stmt = $conexion->prepare("
    SELECT do.id AS detalle_id, p.nombre AS producto_nombre, p.precio, do.cantidad
    FROM detalle_orden do
    JOIN productos p ON do.producto_id = p.id
    WHERE do.orden_id = ?
");
$stmt->execute([$orden_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
?>
    <p><strong>Mesa:</strong> <?= htmlspecialchars($mesa_numero) ?></p>
    <p><strong>Orden ID:</strong> <?= $orden_id ?></p>

    <?php if (isset($error)): ?>
        <div class="notification is-danger"><?= $error ?></div>
    <?php elseif (isset($mensaje_exito)): ?>
        <div class="notification is-success"><?= $mensaje_exito ?></div>
    <?php endif; ?>

    <table class="table is-striped is-fullwidth">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $item): 
                $subtotal = $item['cantidad'] * $item['precio'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['producto_nombre']) ?></td>
                <td><?= $item['cantidad'] ?></td>
                <td><?= number_format($item['precio'], 2) ?></td>
                <td><?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 class="title is-4">Total a pagar: C$ <?= number_format($total, 2) ?></h3>

    <?php if (!isset($mensaje_exito)): ?>
    <form method="POST">
        <input type="hidden" name="orden_id" value="<?= $orden_id ?>">
        <input type="hidden" name="mesa_id" value="<?= $mesa_id ?>">
        <input type="hidden" name="total_orden" value="<?= $total ?>">

        <div class="field">
            <label class="label">Método de pago</label>
            <div class="control">
                <div class="select">
                    <select name="metodo_pago" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="cortesia">Cortesía</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="field">
            <label class="label">Monto pagado</label>
            <div class="control">
                <input class="input" type="number" name="monto_pagado" step="0.01" min="0" required>
            </div>
        </div>

        <div class="field">
            <div class="control">
                <button type="submit" class="button is-primary">💰 Cobrar</button>
            </div>
        </div>
    </form>
    <?php elseif (isset($mensaje_exito)): ?>
    <div class="notification is-success"><?= $mensaje_exito ?></div>

    <div class="box">
        <p>¿Desea imprimir el comprobante?</p>
        <a href="comprobante.php?orden_id=<?= $orden_id ?>" target="_blank" class="button is-info">🖨️ Sí, imprimir</a>
        <a href="index.php?vista=table_list" class="button is-light">❌ No, volver</a>
    </div>
<?php endif; ?>


    <a href="index.php?vista=table_list" class="button is-light mt-4">⬅️ Volver a mesas</a>
