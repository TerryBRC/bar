<div class="container is-fluid">
    <h1 class="title">INTERFAZ DE COBROS</h1>
    <h2 class="subtitle">MESAS</h2>
</div>

<div class="container pb-6 pt-6">
    <?php
    //views/request_bill.php
        require_once "./php/main.php";
        $mesaid = $_GET['mesa_id'] ?? null;
        $ordenid = $_GET['orden_id'] ?? null;

        if ($mesaid != '' && $ordenid != '') {
            require_once "./php/cobrar_orden.php";
        } else {
            echo "<div class='notification is-danger'>Faltan datos de la mesa o la orden.</div>";
        }
    ?>
</div>
