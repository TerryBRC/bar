<div class="container is-fluid">
    <h1 class="title">Mesas</h1>
    <h2 class="subtitle">Lista de Mesas</h2>
</div>
<div class="container pb-6 pt-6">
    <?php
        require_once "./php/main.php";

        # Eliminar mesa #
        /*if(isset($_GET['mesa_id_del'])){
            require_once "./php/mesa_eliminar.php";
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
        $url="index.php?vista=table_list&page="; 
        $registros=15;
        $busqueda="";*/

        # Paginador mesa #
        require_once "./php/mesa_lista.php";
    ?>
</div>