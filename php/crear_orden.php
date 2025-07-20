<?php
$conexion = conexion();

$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
$current_order_id = 0; // Initialize order ID
$mesa_numero = ''; // Initialize mesa number to be used in the title

// --- Step 1: Validate Mesa ID and Determine/Set current_order_id ---
if ($mesa_id > 0) {
    // Fetch mesa details
    $consulta_mesa = "SELECT id, numero, estado FROM mesas WHERE id = ?";
    $stmt_mesa = $conexion->prepare($consulta_mesa);
    $stmt_mesa->execute([$mesa_id]);
    $mesa = $stmt_mesa->fetch(PDO::FETCH_ASSOC);

    if (!$mesa) {
        // If mesa not found, display error and exit
        echo "<p class='notification is-danger'>Error: Mesa con ID {$mesa_id} no encontrada.</p>";
        exit();
    }

    $mesa_numero = $mesa['numero']; // Set mesa number for display

    // Attempt to find an existing OPEN order for this table
    $consulta_orden_abierta = "SELECT id FROM ordenes WHERE mesa_id = ? AND estado = 'abierta' ORDER BY fecha DESC LIMIT 1";
    $stmt_orden_abierta = $conexion->prepare($consulta_orden_abierta);
    $stmt_orden_abierta->execute([$mesa_id]);
    $existing_order = $stmt_orden_abierta->fetch(PDO::FETCH_ASSOC);

    if ($mesa['estado'] == 'libre' && !$existing_order) {
        // Scenario: Table is FREE and has NO existing open order. Create a new one.
        try {
            // Call the stored procedure to create a new order
            $stmt_crear_orden_sp = $conexion->prepare("CALL crear_orden(?)");
            $stmt_crear_orden_sp->execute([$mesa_id]);

            // Get the ID of the newly created order
            $current_order_id = $conexion->lastInsertId();

            // Refresh mesa state as it should now be 'ocupada' after the SP call
            $stmt_mesa->execute([$mesa_id]);
            $mesa = $stmt_mesa->fetch(PDO::FETCH_ASSOC); // Update $mesa variable with new state

            echo "<p class='notification is-success'>Orden #{$current_order_id} creada exitosamente para la Mesa {$mesa_numero}.</p>";

        } catch (PDOException $e) {
            // Handle error from stored procedure (e.g., if the SP detected an issue)
            echo "<p class='notification is-danger'>Error al crear la orden: " . $e->getMessage() . "</p>";
            exit(); // Stop execution if order creation fails
        }
    } elseif (($mesa['estado'] == 'ocupada' || $mesa['estado'] == 'esperando_cuenta') && $existing_order) {
        // Scenario: Table is OCCUPIED or WAITING FOR BILL and has an EXISTING open order. Use it.
        $current_order_id = $existing_order['id'];
        // echo "<p class='notification is-info'>Gestionando orden #{$current_order_id} para la Mesa {$mesa_numero}.</p>";
    } else {
        // Scenario: Mesa is in a state where no order can be created or modified, or data is inconsistent.
        echo "<p class='notification is-warning'>La Mesa {$mesa_numero} no está disponible para agregar productos en este momento (Estado: " . ucfirst($mesa['estado']) . ").</p>";
        exit(); // Stop execution as action is not allowed
    }
} else {
    // Scenario: No valid mesa ID provided
    echo "<p class='notification is-danger'>Error: ID de mesa no proporcionado o inválido.</p>";
    exit(); // Stop execution
}

// --- Step 2: Fetch Data for the View (This runs after current_order_id is set) ---

// Fetch all categories and their products
$categorias_con_productos = [];
$consulta_categorias_productos = "
    SELECT c.id AS categoria_id, c.nombre AS categoria_nombre,
           p.id AS producto_id, p.nombre AS producto_nombre, p.precio
    FROM categoria c
    LEFT JOIN productos p ON c.id = p.categoria_id
    ORDER BY c.nombre, p.nombre;
";
$stmt_cat_prod = $conexion->query($consulta_categorias_productos);

while ($row = $stmt_cat_prod->fetch(PDO::FETCH_ASSOC)) {
    $cat_id = $row['categoria_id'];
    if (!isset($categorias_con_productos[$cat_id])) {
        $categorias_con_productos[$cat_id] = [
            'nombre' => $row['categoria_nombre'],
            'productos' => []
        ];
    }
    // Only add product if it exists (i.e., not a category with no products)
    if ($row['producto_id']) {
        $categorias_con_productos[$cat_id]['productos'][] = [
            'id' => $row['producto_id'],
            'nombre' => $row['producto_nombre'],
            'precio' => $row['precio']
        ];
    }
}

// Fetch current order details IF an order ID was successfully determined
$detalle_orden_actual = [];
if ($current_order_id > 0) {
    $consulta_detalle = "SELECT do.id AS detalle_id, p.nombre AS producto_nombre, p.precio, do.cantidad
                         FROM detalle_orden do
                         JOIN productos p ON do.producto_id = p.id
                         WHERE do.orden_id = ?";
    $stmt_detalle = $conexion->prepare($consulta_detalle);
    $stmt_detalle->execute([$current_order_id]);
    $detalle_orden_actual = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);
}
?>
<h1 class="title is-2 has-text-centered">Orden para Mesa <?php echo $mesa_numero; ?></h1>

<div class="columns">
    <div class="column is-half">
        <h2 class="title is-4">Añadir Productos</h2>
        <form id="add-product-form" action="index.php?vista=add_products_to_order.php?mesa_id=<?php echo $mesa_id; ?>" method="POST">
            <input type="hidden" name="orden_id" value="<?php echo $current_order_id; ?>">

            <?php foreach ($categorias_con_productos as $categoria_id => $categoria): ?>
                <div class="field">
                    <label class="label"><?php echo $categoria['nombre']; ?></label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="producto_<?php echo $categoria_id; ?>" onchange="updatePrice(this)">
                                <option value="" data-price="0">Seleccione un producto</option>
                                <?php foreach ($categoria['productos'] as $producto): ?>
                                    <option value="<?php echo $producto['id']; ?>" data-price="<?php echo $producto['precio']; ?>">
                                        <?php echo $producto['nombre']; ?> ($<?php echo number_format($producto['precio'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="field">
                <label class="label">Cantidad</label>
                <div class="control">
                    <input class="input" type="number" name="cantidad" value="1" min="1" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Precio Unitario Seleccionado</label>
                <div class="control">
                    <input class="input" type="text" id="selected-price" value="$0.00" readonly>
                </div>
            </div>

            <div class="field">
                <div class="control">
                    <button type="submit" class="button is-primary is-fullwidth">Agregar a la Orden</button>
                </div>
            </div>
        </form>
    </div>

    <div class="column is-half">
        <h2 class="title is-4">Detalle de la Orden #<?php echo $current_order_id; ?></h2>
        <?php if (!empty($detalle_orden_actual)): ?>
            <table class="table is-striped is-fullwidth">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total_orden = 0; ?>
                    <?php foreach ($detalle_orden_actual as $detalle): ?>
                        <?php $subtotal = $detalle['cantidad'] * $detalle['precio']; ?>
                        <tr>
                            <td><?php echo $detalle['producto_nombre']; ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                            <td>$<?php echo number_format($detalle['precio'], 2); ?></td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <a href="controllers/remove_item.php?detalle_id=<?php echo $detalle['detalle_id']; ?>&orden_id=<?php echo $current_order_id; ?>&mesa_id=<?php echo $mesa_id; ?>" class="button is-danger is-small">Eliminar</a>
                            </td>
                        </tr>
                        <?php $total_orden += $subtotal; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="has-text-right has-text-weight-bold">Total:</td>
                        <td class="has-text-weight-bold">$<?php echo number_format($total_orden, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <div class="buttons is-right">
                <a href="controllers/request_bill_action.php?mesa_id=<?php echo $mesa_id; ?>&orden_id=<?php echo $current_order_id; ?>" class="button is-warning is-large">
                    Pedir Cuenta
                </a>
            </div>
        <?php else: ?>
            <p class="notification is-info">Aún no hay productos en esta orden. ¡Agrega algunos!</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Updates the 'Precio Unitario Seleccionado' field when a product is chosen
    function updatePrice(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        document.getElementById('selected-price').value = `$${parseFloat(price).toFixed(2)}`;
    }

    // Optional: You might want to pre-select the first product or set the price on page load
    // For example, trigger `updatePrice` for each select element on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.select select').forEach(select => {
            if (select.value !== "") { // If a product is pre-selected
                updatePrice(select);
            }
        });
    });
</script>