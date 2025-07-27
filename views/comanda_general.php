<?php
            require_once "../inc/session_start.php"; ?>
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
                                    <a href="../index.php?vista=table_list" class="button is-link is-outlined">
                                        <span class="icon"><i class="fas fa-arrow-left"></i></span>
                                        <span>Volver a la lista de mesas</span>
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