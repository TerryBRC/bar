<?php
require_once "main.php";

$pdo = conexion();

// Procesar fecha
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');
$fecha_filtro = limpiar_cadena($fecha_filtro);

// Paginación
$pagina = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$pagina = max($pagina, 1);
$registros = 15;
$inicio = ($pagina - 1) * $registros;

// Contar total de órdenes
$consulta_total = $pdo->prepare("SELECT COUNT(*) FROM ordenes WHERE DATE(fecha) = ?");
$consulta_total->execute([$fecha_filtro]);
$total_registros = $consulta_total->fetchColumn();
$Npaginas = ceil($total_registros / $registros);

// Obtener órdenes paginadas
$consulta = $pdo->prepare("
    SELECT o.id, o.fecha, o.total_orden, o.estado, m.numero AS mesa
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    WHERE DATE(o.fecha) = ?
    ORDER BY o.fecha DESC
    LIMIT $inicio, $registros
");
$consulta->execute([$fecha_filtro]);
$ordenes = $consulta->fetchAll();

?>

<!-- Filtro de fecha -->
<form method="get" action="index.php" class="mb-4">
    <input type="hidden" name="vista" value="order_list">
    <div class="field has-addons">
        <div class="control">
            <input class="input" type="date" name="fecha" value="<?= $fecha_filtro ?>">
        </div>
        <div class="control">
            <button type="submit" class="button is-info">Filtrar</button>
        </div>
        <div class="control ml-auto">
            <a href="index.php?vista=closeout&fecha=<?= $fecha_filtro ?>" class="button is-danger">
                <strong>Cierre de Caja</strong>
            </a>
        </div>
    </div>
</form>

<!-- Tabla de órdenes -->
<div class="table-container">
    <table class="table is-striped is-hoverable is-fullwidth">
        <thead>
            <tr>
                <th># Orden</th>
                <th>Fecha y Hora</th>
                <th>Mesa</th>
                <th>Total</th>
                
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if(count($ordenes) > 0): ?>
            <?php foreach($ordenes as $orden): ?>
                <tr>
                    <td><?= $orden['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?></td>
                    <td><?= htmlspecialchars($orden['mesa']) ?></td>
                    <td>C$ <?= number_format($orden['total_orden'], 2) ?></td>
                    
                    <td><?= ucfirst($orden['estado']) ?></td>
                    <td>
                        <a href="php/imprimir_comprobante.php?orden_id=<?= $orden['id'] ?>" class="button is-small is-link" target="_blank">
                            Reimprimir
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="has-text-centered">No hay órdenes registradas en esta fecha.</td>
            </tr>
        <?php endif ?>
        </tbody>
    </table>
</div>

<!-- Paginación -->
<?php
echo paginador_tablas($pagina, $Npaginas, "index.php?vista=order_list&fecha=$fecha_filtro&page=", 5);
?>
