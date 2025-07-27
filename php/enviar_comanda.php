<?php
require_once "../inc/session_start.php";
require_once "main.php";

$orden_id = isset($_GET['orden_id']) ? intval($_GET['orden_id']) : 0;
if ($orden_id <= 0) {
    die("Orden inválida.");
}
$mesa_id_get = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
if ($mesa_id_get <= 0) {
    die("Mesa inválida.");
}

$conexion = conexion();

// Categorías por área
$categorias_comida = [1, 2, 4, 5, 6, 7, 8]; // Cocina
$categorias_bebida = [3];                  // Barra

// Obtener detalles no enviados
$sql = "
    SELECT do.id AS detalle_id, do.producto_id, do.cantidad, p.nombre AS producto, p.categoria_id,
           GROUP_CONCAT(dg.nombre SEPARATOR ', ') AS guarniciones
    FROM detalle_orden do
    JOIN productos p ON do.producto_id = p.id
    LEFT JOIN detalle_guarnicion dg ON dg.detalle_orden_id = do.id
    WHERE do.orden_id = ? AND do.enviado = 0
    GROUP BY do.id
";
$stmt = $conexion->prepare($sql);
$stmt->execute([$orden_id]);
$detalles = $stmt->fetchAll();

$para_cocina = [];
$para_barra = [];

foreach ($detalles as $detalle) {
    if (in_array($detalle['categoria_id'], $categorias_comida)) {
        $para_cocina[] = $detalle;
    } elseif (in_array($detalle['categoria_id'], $categorias_bebida)) {
        $para_barra[] = $detalle;
    }
}

// Marcar todos como enviados (cocina y barra)
$ids_enviar = array_merge(
    array_column($para_cocina, 'detalle_id'),
    array_column($para_barra, 'detalle_id')
);
if (!empty($ids_enviar)) {
    $in_query = implode(',', array_fill(0, count($ids_enviar), '?'));
    $update = $conexion->prepare("UPDATE detalle_orden SET enviado = 1 WHERE id IN ($in_query)");
    $update->execute($ids_enviar);
}

// Guardar en sesión para mostrar en la vista
$_SESSION['comanda_cocina'] = $para_cocina;
$_SESSION['comanda_barra'] = $para_barra;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../css/bulma.min.css">
<link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="container is-fluid">
        <h1 class="title">COMANDA GENERAL</h1>
        <h2 class="subtitle">Lista productos de las comandas</h2>
    </div>
    
    <div class="container pb-6 pt-6">  
        <?php
                        
            // Recoger datos de la sesión
            $orden_id = $_GET['orden_id'] ?? 0;
            $comida = $_SESSION['comanda_cocina'] ?? [];
            $bebida = $_SESSION['comanda_barra'] ?? [];
            
            // Definir variables para la vista
            $titulo = "Comanda enviada para orden #$orden_id";
            $hay_comida = !empty($comida);
            $hay_bebida = !empty($bebida);
            $vacio = empty($comida) && empty($bebida);
            ?>
               
               <div class="columns is-centered">
        <div class="column is-two-thirds">
            <?php if($hay_comida): ?>
                <div class="box">
                    <h2 class="subtitle is-4 has-text-primary">
                        <span class="icon"><i class="fas fa-utensils"></i></span>
                        <span>Cocina</span>
                    </h2>
                    <div class="content">
                        <ul>
                            <?php foreach($comida as $item): ?>
                                <li>
                                    <strong><?= $item['cantidad'] ?> × <?= htmlspecialchars($item['producto']) ?></strong>
                                    <?php if(!empty($item['guarniciones'])): ?>
                                        <br>
                                        <small class="has-text-grey">Guarniciones: <?= htmlspecialchars($item['guarniciones']) ?></small>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($hay_bebida): ?>
                            <div class="box">
                                <h2 class="subtitle is-4 has-text-info">
                                    <span class="icon"><i class="fas fa-glass-martini-alt"></i></span>
                                    <span>Barra</span>
                                </h2>
                                <div class="content">
                                    <ul>
                                        <?php foreach($bebida as $item): ?>
                                            <li><strong><?= $item['cantidad'] ?> × <?= htmlspecialchars($item['producto']) ?></strong></li>
                                            <?php endforeach; ?>
                                        </ul>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($vacio): ?>
                                <article class="message is-warning">
                                    <div class="message-body">
                                        No hay productos pendientes por enviar
                                    </div>
                                </article>
                                <?php endif; ?>
                                
                                <div class="has-text-centered mt-5">
                                    <a href="../index.php?vista=create_order&mesa_id=<?= $mesa_id_get; ?>" class="button is-link is-outlined">
                                        <span><--- Volver a la mesa</span>
                                    </a>
                                </div>
                            </div>
                        </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>