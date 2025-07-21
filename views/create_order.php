<?php
// create_order.php
require_once "./php/main.php";
require_once "./php/crear_orden.php";
?>
<style>
/* Estilos para la notificación tipo Toast */
.notification.is-success,
.notification.is-danger{
    position: fixed; /* Hace que flote sobre el contenido */
    top: 55px;       /* Distancia desde la parte superior */
    right: 20px;     /* Distancia desde la parte derecha */
    z-index: 1000;   /* Asegura que esté por encima de otros elementos */
    width: 300px;    /* Ancho fijo para el toast */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra para que se vea flotante */
    border-radius: 4px; /* Bordes ligeramente redondeados */
    padding: 1rem 1.25rem; /* Ajusta el padding si es necesario */
}

/* Animación de desvanecimiento (ya la tienes, solo la confirmo) */
.fade-out {
    animation: fadeOut ease 2s; /* Duración de la animación */
    animation-fill-mode: forwards; /* Mantiene el estado final (oculto) */
}

@keyframes fadeOut {
    0% { opacity: 1; display: block; } /* Empieza visible */
    100% { opacity: 0; display: none; } /* Termina oculto y sin ocupar espacio */
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
            <form id="add-product-form" action="./php/agregar_producto_a_orden.php?mesa_id=<?php echo $mesa_id; ?>" method="POST">
                <input type="hidden" name="orden_id" value="<?php echo $current_order_id; ?>">

                <?php foreach ($categorias_con_productos as $categoria_id => $categoria): ?>
                    <div class="field">
                        <div class="control">
                            <button class="button is-fullwidth is-info" type="button" onclick="toggleProducts(<?php echo $categoria_id; ?>)">
                                <?php echo $categoria['nombre']; ?>
                            </button>
                        </div>
                        <div id="products-<?php echo $categoria_id; ?>" class="products-list" style="display: none;">
                            <div class="select is-fullwidth">
                                <select name="productos_<?php echo $categoria_id; ?>" class="product-select">
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
                                    <a href="./php/remove_item.php?detalle_id=<?php echo $detalle['detalle_id']; ?>&orden_id=<?php echo $current_order_id; ?>&mesa_id=<?php echo $mesa_id; ?>" class="button is-danger is-small">Eliminar</a>
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
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Selecciona todos los elementos con la clase 'notification'
    const notifications = document.querySelectorAll('.notification');

    notifications.forEach(notification => {
        // Solo nos interesan las notificaciones de éxito o peligro que estén visibles
        if (notification.classList.contains('is-success') || notification.classList.contains('is-danger')) {
            setTimeout(() => {
                // Añade la clase 'fade-out' para iniciar la animación
                notification.classList.add('fade-out');

                // Opcional: Elimina el elemento completamente del DOM después de que termine la animación
                // Esto asegura que no ocupe espacio invisiblemente
                notification.addEventListener('animationend', () => {
                    notification.remove();
                });
            }, 2000); // 2000 milisegundos = 2 segundos
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Lógica de Toast (ya la tienes, solo la reafirmo) ---
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

    // --- Lógica del Modal ---
    const productModal = document.getElementById('product-modal');
    const modalProductName = document.getElementById('modal-product-name');
    const modalProductId = document.getElementById('modal-product-id');
    const modalCantidad = document.getElementById('modal-cantidad');
    const modalPrecio = document.getElementById('modal-precio');
    const closeButtons = document.querySelectorAll('.modal-background, .modal-close, #close-modal-button');

    const productSelects = document.querySelectorAll('.product-select');
    
    // Iterar sobre cada select de producto
    productSelects.forEach(select => {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const productId = selectedOption.value;
            const productName = selectedOption.textContent.split(' ($')[0]; // Obtener solo el nombre
            const productPrice = parseFloat(selectedOption.getAttribute('data-price') || '0');

            if (productId) { // Si se seleccionó un producto válido (no la opción "Seleccione un producto")
                modalProductName.textContent = productName;
                modalProductId.value = productId;
                modalCantidad.value = 1; // Cantidad por defecto
                modalPrecio.value = productPrice.toFixed(2); // Precio con 2 decimales
                productModal.classList.add('is-active'); // Abre el modal
            }
        });
    });

    // Cerrar el modal al hacer click en los botones de cierre o en el fondo
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            productModal.classList.remove('is-active');
            // Opcional: Reiniciar el select a "Seleccione un producto" después de cerrar el modal
            productSelects.forEach(select => {
                select.value = ""; 
            });
        });
    });

    // --- Lógica de toggleProducts (ya la tienes, no la modificamos por ahora) ---
    // function toggleProducts(categoriaId) { ... }
});

function toggleProducts(categoriaId) {
    const productsList = document.getElementById(`products-${categoriaId}`);
    if (productsList.style.display === "none") {
        productsList.style.display = "block";
    } else {
        productsList.style.display = "none";
    }
}
    function toggleProducts(categoriaId) {
        const productsList = document.getElementById(`products-${categoriaId}`);
        if (productsList.style.display === "none") {
            productsList.style.display = "block";
        } else {
            productsList.style.display = "none";
        }
    }
</script>

