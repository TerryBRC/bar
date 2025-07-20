<?php

//listar las mesas
$conexion = conexion();
$consulta_datos_mesas = "SELECT * FROM mesas ORDER BY numero ASC";
$datos_mesas = $conexion->query($consulta_datos_mesas);
?>
<!-- Mostramos todas las mesas con su numero y su estado con formato como de card -->
<div class="columns is-multiline">
    <?php foreach ($datos_mesas as $mesa): ?>
        <div class="column is-one-fifth">
            <div class="box has-text-centered">
                <h3 class="title is-4 has-text-weight-bold">Mesa <?php echo $mesa['numero']; ?></h3>
                <p class="subtitle is-6">
                    Estado:
                    <span class="<?php echo
                        $mesa['estado'] == 'libre' ? 'has-text-success' :
                        ($mesa['estado'] == 'esperando_cuenta' ? 'has-text-warning' : 'has-text-danger'); ?> subtitle has-text-weight-bold">
                        <?php echo ucfirst($mesa['estado']); ?>
                    </span>
                </p>
                <div class="buttons is-centered">
                    <?php if ($mesa['estado'] == 'libre'): ?>
                        <a href="index.php?vista=create_order&mesa_id=<?php echo $mesa['id']; ?>" class="button is-primary is-rounded">
                            Crear Orden
                        </a>
                    <?php elseif ($mesa['estado'] == 'ocupada'): ?>
                        <a href="index.php?vista=create_order&mesa_id=<?php echo $mesa['id']; ?>" class="button is-info is-rounded">
                            Agregar Productos
                        </a>
                        <a href="index.php?vista=request_bill&mesa_id=<?php echo $mesa['id']; ?>" class="button is-warning is-rounded">
                            Pedir Cuenta
                        </a>
                        

                    <?php elseif ($mesa['estado'] == 'esperando_cuenta'): ?>
                        <!-- Botón para procesar pago, solo se mostrara si el rol del usuario es id 2 $_SESSION['rol_id'] -->
                         <?php if ($_SESSION['rol_id'] == 1 || $_SESSION['rol_id'] == 2): ?>
                            <a href="index.php?vista=process_payment&mesa_id=<?php echo $mesa['id']; ?>" class="button is-danger is-rounded">
                                Procesar Pago
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <!-- Botón para actualizar la mesa, solo se mostrara si el rol del usuario es id 1 $_SESSION['rol_id'] -->
                    <?php if ($_SESSION['rol_id'] == 1): ?>
                        <a href="index.php?vista=update_table&mesa_id=<?php echo $mesa['id']; ?>" class="button is-link is-rounded">
                            Actualizar Mesa
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>