<?php
require_once "./php/main.php";
require_once "./inc/session_start.php";

$conexion = conexion();

$current_order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
$mesa_numero = '';
$categorias_con_productos = [];
$detalle_orden_actual = [];

if ($mesa_id > 0) {
    $consulta_mesa = $conexion->prepare("SELECT id, numero, estado FROM mesas WHERE id = ?");
    $consulta_mesa->execute([$mesa_id]);
    $mesa = $consulta_mesa->fetch(PDO::FETCH_ASSOC);

    if (!$mesa) {
        header("Location: index.php?status=error&message=" . urlencode("Mesa no encontrada."));
        exit();
    }

    $mesa_numero = $mesa['numero'];

    // Buscar o crear orden abierta
    if ($current_order_id === 0) {
        $consulta_orden = $conexion->prepare("SELECT id FROM ordenes WHERE mesa_id = ? AND estado = 'abierta' LIMIT 1");
        $consulta_orden->execute([$mesa_id]);
        $orden_existente = $consulta_orden->fetch(PDO::FETCH_ASSOC);

        if ($orden_existente) {
            $current_order_id = $orden_existente['id'];
        } else {
            if ($mesa['estado'] === 'libre') {
                try {
                    $conexion->beginTransaction();

                    $stmt = $conexion->prepare("INSERT INTO ordenes (mesa_id, estado, fecha) VALUES (?, 'abierta', NOW())");
                    $stmt->execute([$mesa_id]);
                    $current_order_id = $conexion->lastInsertId();

                    $stmt = $conexion->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?");
                    $stmt->execute([$mesa_id]);

                    $conexion->commit();
                } catch (PDOException $e) {
                    $conexion->rollBack();
                    header("Location: index.php?status=error&message=" . urlencode("Error al crear orden: " . $e->getMessage()));
                    exit();
                }
            } else {
                header("Location: index.php?status=error&message=" . urlencode("La mesa no está disponible para crear una nueva orden."));
                exit();
            }
        }
    }
} else {
    header("Location: index.php?status=error&message=" . urlencode("ID de mesa no válido."));
    exit();
}

// Consultar productos y categorías
$consulta_categorias = $conexion->query("
    SELECT c.id, c.nombre, 
           p.id AS producto_id, p.nombre AS producto_nombre, p.precio
    FROM categoria c
    LEFT JOIN productos p ON c.id = p.categoria_id
    ORDER BY c.nombre, p.nombre
");

while ($row = $consulta_categorias->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($categorias_con_productos[$row['id']])) {
        $categorias_con_productos[$row['id']] = [
            'nombre' => $row['nombre'],
            'productos' => []
        ];
    }

    if ($row['producto_id']) {
        $categorias_con_productos[$row['id']]['productos'][] = [
            'id' => $row['producto_id'],
            'nombre' => $row['producto_nombre'],
            'precio' => $row['precio']
        ];
    }
}

// Detalle de orden actual
if ($current_order_id > 0) {
    $consulta_detalle = $conexion->prepare("
        SELECT do.id AS detalle_id, p.id AS producto_id, p.nombre AS producto_nombre, 
               p.precio, do.cantidad,do.enviado
        FROM detalle_orden do
        JOIN productos p ON do.producto_id = p.id
        WHERE do.orden_id = ?
    ");
    $consulta_detalle->execute([$current_order_id]);
    $detalle_orden_actual = $consulta_detalle->fetchAll(PDO::FETCH_ASSOC);
}
?>


<style>
    .notification.is-success,
    .notification.is-danger {
        position: fixed;
        top: 55px;
        right: 20px;
        z-index: 1000;
        width: 300px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        border-radius: 4px;
        padding: 1rem 1.25rem;
    }

    .fade-out {
        animation: fadeOut ease 2s;
        animation-fill-mode: forwards;
    }

    @keyframes fadeOut {
        0% { opacity: 1; display: block; }
        100% { opacity: 0; display: none; }
    }

    .guarniciones-list {
        font-size: 0.8rem;
        color: #666;
        margin-top: 4px;
    }
</style>

<div class="container is-fluid">
    <h1 class="title">Orden de Mesa</h1>
    <h2 class="subtitle">Agregar productos a la orden</h2>
</div>

<div class="container pb-6 pt-6">
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'product_added'): ?>
            <div class="notification is-success">
                Producto agregado exitosamente a la orden
            </div>
        <?php elseif ($_GET['status'] === 'item_removed'): ?>
            <div class="notification is-danger">
                Producto eliminado exitosamente de la orden
            </div>
        <?php elseif ($_GET['status'] === 'error' && isset($_GET['message'])): ?>
            <div class="notification is-danger">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <h1 class="title is-2 has-text-centered">Orden para Mesa <?php echo $mesa_numero; ?></h1>

    <div class="columns">
        <div class="column is-half">
            <h2 class="title is-4">Añadir Productos</h2>
            <?php foreach ($categorias_con_productos as $categoria_id => $categoria): ?>
                <div class="field">
                    <div class="control">
                        <button class="button is-fullwidth is-info" type="button" onclick="toggleProducts(<?php echo $categoria_id; ?>)">
                            <?php echo $categoria['nombre']; ?>
                        </button>
                    </div>
                    <div id="products-<?php echo $categoria_id; ?>" class="products-list" style="display: none;">
                        <?php foreach ($categoria['productos'] as $producto): ?>
                            <div class="field has-addons product-item-display">
                                <div class="control is-expanded">
                                    <input type="text" class="input is-static" value="<?php echo $producto['nombre']; ?> ($<?php echo number_format($producto['precio'], 2); ?>)" readonly>
                                </div>
                                <div class="control">
                                    <button type="button" class="button is-info add-product-btn"
                                            data-product-id="<?php echo $producto['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                            data-product-price="<?php echo $producto['precio']; ?>"
                                            data-categoria-id="<?php echo $categoria_id; ?>">
                                        Agregar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div id="product-modal" class="modal">
                <div class="modal-background"></div>
                <div class="modal-content">
                    <div class="box">
                        <h3 class="title is-4">Añadir <span id="modal-product-name"></span></h3>
                        <form id="modal-add-product-form" action="./php/agregar_producto_a_orden.php?mesa_id=<?php echo $mesa_id; ?>" method="POST">
                            <input type="hidden" name="orden_id" value="<?php echo $current_order_id; ?>">
                            <input type="hidden" name="producto_id" id="modal-product-id">

                            <div class="field">
                                <label class="label">Cantidad</label>
                                <div class="control">
                                    <input class="input" type="number" name="cantidad" id="modal-cantidad" value="1" min="1" required>
                                </div>
                            </div>

                            <div class="field">
                                <label class="label">Precio Unitario</label>
                                <div class="control">
                                    <input class="input" type="number" name="precio_unitario" id="modal-precio" step="0.01" min="0" readonly required>
                                </div>
                            </div>

                            <div id="guarniciones-section" class="field" style="display: none;">
                                <label class="label">Selecciona 3 guarniciones (obligatorio)</label>
                                <div class="control" id="guarniciones-checkboxes">
                                    <label class="checkbox">
                                        <input type="checkbox" name="guarniciones[]" value="Arroz">
                                        Arroz
                                    </label><br>
                                    <label class="checkbox">
                                        <input type="checkbox" name="guarniciones[]" value="Gallo Pinto">
                                        Gallo Pinto
                                    </label><br>
                                    <label class="checkbox">
                                        <input type="checkbox" name="guarniciones[]" value="Tostones">
                                        Tostones
                                    </label><br>
                                    <label class="checkbox">
                                        <input type="checkbox" name="guarniciones[]" value="Papas Fritas">
                                        Papas Fritas
                                    </label><br>
                                    <label class="checkbox">
                                        <input type="checkbox" name="guarniciones[]" value="Vegetales">
                                        Vegetales
                                    </label><br>
                                    <label class="checkbox">
                                        <input type="checkbox" name="guarniciones[]" value="Ensalada">
                                        Ensalada
                                    </label><br>
                                </div>
                                <p class="help is-danger" id="guarniciones-error" style="display: none;">Debes seleccionar exactamente 3 guarniciones.</p>
                            </div>

                            <div class="field is-grouped">
                                <div class="control">
                                    <button type="submit" class="button is-success">Confirmar Añadir</button>
                                </div>
                                <div class="control">
                                    <button type="button" class="button is-light" id="close-modal-button">Cancelar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <button class="modal-close is-large" aria-label="close"></button>
            </div>
        </div>

        <div class="column is-half">
            <div style="display: flex;justify-content: center;gap: 50px;align-items: center;">
                <div>

                    <h2 class="title is-4">Detalle de la Orden #<?php echo $current_order_id; ?></h2>
                </div>
                <div>

                    <a href="./php/enviar_comanda.php?orden_id=<?= $current_order_id ?>" class="button is-info">📨 Enviar Comanda</a>
                </div>

            </div>
            <?php if (!empty($detalle_orden_actual)): ?>
                <table class="table is-striped is-fullwidth">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                            <th>Enviado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total_orden = 0; ?>
                        <?php foreach ($detalle_orden_actual as $detalle): ?>
                            <?php $subtotal = $detalle['cantidad'] * $detalle['precio']; ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($detalle['producto_nombre']); ?>
                                    <?php if (isset($_SESSION['guarniciones_por_detalle'][$detalle['detalle_id']])): ?>
                                        <div class="guarniciones-list">
                                            <?php foreach ($_SESSION['guarniciones_por_detalle'][$detalle['detalle_id']] as $guarnicion): ?>
                                                <div>↳ <?php echo htmlspecialchars($guarnicion); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td>$<?php echo number_format($detalle['precio'], 2); ?></td>
                                <td>$<?php echo number_format($subtotal, 2); ?></td>
                                <td>
                                    <?php if($detalle['enviado']==1): ?>
                                       <p>Si</p>
                                    <?php else: ?>
                                        <p>No</p>
                                    <?php endif; ?>


                                </td>
                                <td>
                                    <a href="./php/remove_item.php?detalle_id=<?php echo $detalle['detalle_id']; ?>&orden_id=<?php echo $current_order_id; ?>&mesa_id=<?php echo $mesa_id; ?>" 
                                       class="button is-danger is-small">
                                        Eliminar
                                    </a>
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
                    <a href="controllers/request_bill_action.php?orden_id=<?php echo $current_order_id; ?>" 
                       class="button is-warning is-large">
                        COBRAR
                    </a>
                    <a href="./php/imprimir_orden.php?mesa_id=<?php echo $mesa_id; ?>&orden_id=<?php echo $current_order_id; ?>" 
                       class="button is-success is-large">
                        PRE-FACTURA
                    </a>
                </div>
            <?php else: ?>
                <p class="notification is-info">Aún no hay productos en esta orden. ¡Agrega algunos!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Toast notifications
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        if (notification.classList.contains('is-success') || notification.classList.contains('is-danger')) {
            setTimeout(() => {
                notification.classList.add('fade-out');
                notification.addEventListener('animationend', () => {
                    notification.remove();
                });
            }, 2000);
        }
    });

    // Modal functionality
    const productModal = document.getElementById('product-modal');
    const modalProductName = document.getElementById('modal-product-name');
    const modalProductId = document.getElementById('modal-product-id');
    const modalCantidad = document.getElementById('modal-cantidad');
    const modalPrecio = document.getElementById('modal-precio');
    const closeButtons = document.querySelectorAll('.modal-background, .modal-close, #close-modal-button');
    const guarnicionesSection = document.getElementById('guarniciones-section');
    const guarnicionesCheckboxes = document.querySelectorAll('#guarniciones-checkboxes input[type="checkbox"]');
    const guarnicionesError = document.getElementById('guarniciones-error');
    const modalAddProductForm = document.getElementById('modal-add-product-form');

    // Product buttons functionality
    document.querySelectorAll('.add-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = this.getAttribute('data-product-price');
            const categoriaId = parseInt(this.getAttribute('data-categoria-id'));

            modalProductName.textContent = productName;
            modalProductId.value = productId;
            modalCantidad.value = 1;
            modalPrecio.value = parseFloat(productPrice).toFixed(2);

            // Show garnishes section only for category 2
            if (categoriaId === 2) {
                guarnicionesSection.style.display = 'block';
                // Uncheck all checkboxes when opening modal
                guarnicionesCheckboxes.forEach(cb => cb.checked = false);
                guarnicionesError.style.display = 'none';
            } else {
                guarnicionesSection.style.display = 'none';
            }

            productModal.classList.add('is-active');
        });
    });

    // Form submission validation
    modalAddProductForm.addEventListener('submit', function(event) {
        if (guarnicionesSection.style.display === 'block') {
            const selectedGuarniciones = Array.from(guarnicionesCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            if (selectedGuarniciones.length !== 3) {
                guarnicionesError.style.display = 'block';
                event.preventDefault();
                return false;
            }
        }
        return true;
    });

    // Close modal functionality
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            productModal.classList.remove('is-active');
        });
    });
});

// Toggle products visibility
function toggleProducts(categoriaId) {
    const productsList = document.getElementById(`products-${categoriaId}`);
    productsList.style.display = productsList.style.display === 'none' ? 'block' : 'none';
}
</script>
