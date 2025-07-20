<div class="container is-fluid">
    <h1 class="title">Ordenes</h1>
    <h2 class="subtitle">Lista de Ordenes</h2>
</div>
<div class="container pb-6 pt-6">
    <?php
        require_once "./php/main.php";

        # Eliminar orden #
        /*if(isset($_GET['orden_id_del'])){
            require_once "./php/orden_eliminar.php";
        }

        if(!isset($_GET['page'])){
            $pagina=1;
        }else{
            $pagina=(int) $_GET['page'];
            if($pagina<=1){
                $pagina=1;
            }
        }

        $pagina=limpiar_cadena($pagina);
        $url="index.php?vista=order_list&page="; 
        $registros=15;
        $busqueda="";*/

        # Paginador orden #
        require_once "./php/orden_lista.php";
    ?>