<?php
// php/cobrar_orden.php

require_once "./inc/session_start.php";
require_once "main.php";

$conexion = conexion();

// VALIDACIÓN Y PROCESAMIENTO DEL COBRO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orden_id = intval($_POST['orden_id']);
    $mesa_id = intval($_POST['mesa_id']);
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $monto_pagado = floatval($_POST['monto_pagado']);
    $total_orden = floatval($_POST['total_orden']);

    if ($orden_id <= 0 || $mesa_id <= 0 || $total_orden <= 0 || $monto_pagado < 0 || empty($metodo_pago)) {
        $error = "⚠️ Datos inválidos. Verifica los campos.";
    } elseif ($metodo_pago === 'efectivo' && $monto_pagado < $total_orden) {
        $error = "💵 El monto pagado no cubre el total de la orden.";
    } else {
        $cambio = 0;
        if ($metodo_pago === 'efectivo') {
            $cambio = $monto_pagado - $total_orden;
        } else {
            $monto_pagado = $total_orden;
        }

        try {
            $conexion->beginTransaction();

            // ✅ Actualizar orden con pago
            $stmt = $conexion->prepare("
                UPDATE ordenes 
                SET metodo_pago = ?, total_orden = ?, total_pago = ?, cambio = ?, estado = 'cerrada' 
                WHERE id = ?
            ");
            $stmt->execute([$metodo_pago, $total_orden, $monto_pagado, $cambio, $orden_id]);

            // ✅ Llamar procedimiento para liberar mesa
            $conexion->exec("CALL cerrar_orden_y_liberar_mesa($orden_id)");

            $conexion->commit();
            $mensaje_exito = "✅ Orden cobrada exitosamente. Cambio: C$ " . number_format($cambio, 2);
        } catch (Exception $e) {
            $conexion->rollBack();
            $error = "❌ Error al procesar el cobro: " . $e->getMessage();
        }
    }
}

// CARGA DE INFORMACIÓN PARA MOSTRAR DETALLES DE LA ORDEN
$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : ($_POST['orden_id'] ?? 0);
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : ($_POST['mesa_id'] ?? 0);

if ($orden_id <= 0 || $mesa_id <= 0) {
    die("⚠️ Orden o mesa no válidas.");
}

// Obtener número de mesa
$stmt = $conexion->prepare("SELECT numero FROM mesas WHERE id = ?");
$stmt->execute([$mesa_id]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
$mesa_numero = $mesa ? $mesa['numero'] : "Desconocida";

// Obtener detalles de productos
$stmt = $conexion->prepare("
    SELECT do.id AS detalle_id, p.nombre AS producto_nombre, p.precio, do.cantidad
    FROM detalle_orden do
    JOIN productos p ON do.producto_id = p.id
    WHERE do.orden_id = ?
");
$stmt->execute([$orden_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total
$total = 0;
foreach ($detalles as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>

<p><strong>Mesa:</strong> <?= htmlspecialchars($mesa_numero) ?></p>
<p><strong>Orden ID:</strong> <?= $orden_id ?></p>
<p style="font-size: 1.5rem; font-weight: bold;">💵 Total a Pagar: C$ <?= number_format($total, 2) ?></p>

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
        <?php 
        $total = 0; // ✅ Inicializar total
        foreach ($detalles as $item): 
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

<!-- Botón para abrir modal -->
<button class="button is-primary" id="btn-abrir-modal">💰 Cobrar</button>
<a href="index.php?vista=table_list" class="button is-light">⬅️ Volver a mesas</a>
<!-- Modal -->
<div class="modal" id="modal-cobro">
  <div class="modal-background"></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title">Registrar Cobro</p>
      <button class="delete" aria-label="close" id="cerrar-modal"></button>
    </header>
    <section class="modal-card-body">
      <form id="form-cobro" method="POST">
        <input type="hidden" name="orden_id" value="<?= $orden_id ?>">
        <input type="hidden" name="mesa_id" value="<?= $mesa_id ?>">
        <input type="hidden" name="total_orden" id="total_orden" value="<?= $total ?>">
        <!-- Mostrar total dentro del modal -->
        <div class="notification is-primary is-light has-text-centered mb-3">
            <strong>Total a Pagar:</strong> C$ <?= number_format($total, 2) ?>
        </div>


        <div class="field">
          <label class="label">Método de Pago</label>
          <div class="control">
            <div class="select">
              <select name="metodo_pago" id="metodo_pago" required>
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="cortesia">Cortesía</option>
              </select>
            </div>
          </div>
        </div>

        <div class="field" id="campo-monto">
          <label class="label">Monto Recibido</label>
          <div class="control">
            <input class="input" type="number" id="monto_pagado" name="monto_pagado" step="0.01" min="0">
          </div>
        </div>

        <div class="notification is-info is-light mt-2" id="mensaje-cambio" style="display: none;"></div>

        <footer class="modal-card-foot">
          <button type="submit" class="button is-success">✅ Confirmar Cobro</button>
          <button type="button" class="button" id="cancelar-cobro">Cancelar</button>
        </footer>
      </form>
    </section>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-cobro');
    const btnAbrir = document.getElementById('btn-abrir-modal');
    const btnCerrar = document.getElementById('cerrar-modal');
    const btnCancelar = document.getElementById('cancelar-cobro');
    const metodo = document.getElementById('metodo_pago');
    const monto = document.getElementById('monto_pagado');
    const total = parseFloat(document.getElementById('total_orden').value);
    const campoMonto = document.getElementById('campo-monto');
    const mensajeCambio = document.getElementById('mensaje-cambio');

    function abrirModal() {
        modal.classList.add('is-active');
        metodo.value = 'efectivo';
        monto.value = '';
        mensajeCambio.style.display = 'none';
        campoMonto.style.display = 'block';
        monto.readOnly = false;
    }

    function cerrarModal() {
        modal.classList.remove('is-active');
    }

    function actualizarCampoMonto() {
        const tipo = metodo.value;
        if (tipo === 'cortesia' /*|| tipo === 'tarjeta'*/) {
            campoMonto.style.display = 'none';
            monto.value = total.toFixed(2);
            mensajeCambio.style.display = 'none';
        } else {
            campoMonto.style.display = 'block';
            monto.readOnly = false;
        }
    }

    monto.addEventListener('input', () => {
        const pagado = parseFloat(monto.value);
        const cambio = pagado - total;
        if (!isNaN(cambio) && cambio >= 0) {
            mensajeCambio.style.display = 'block';
            mensajeCambio.textContent = `Cambio a entregar: C$ ${cambio.toFixed(2)}`;
        } else {
            mensajeCambio.style.display = 'none';
        }
    });

    metodo.addEventListener('change', actualizarCampoMonto);
    btnAbrir.addEventListener('click', abrirModal);
    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
});
</script>

