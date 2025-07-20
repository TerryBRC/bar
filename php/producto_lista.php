<?php
	$inicio = ($pagina>0) ? (($pagina * $registros)-$registros) : 0;
	$tabla="";

	$campos="productos.id,productos.nombre as Producto,productos.precio,productos.categoria_id,categoria.nombre as Categoria";

	if(isset($busqueda) && $busqueda!=""){

		$consulta_datos="SELECT $campos FROM productos INNER JOIN categoria ON productos.categoria_id=categoria.id WHERE productos.nombre LIKE '%$busqueda%' ORDER BY productos.nombre ASC LIMIT $inicio,$registros";

		$consulta_total="SELECT COUNT(id) FROM productos WHERE nombre LIKE '%$busqueda%'";

	}elseif($categoria_id>0){

		$consulta_datos="SELECT $campos FROM productos INNER JOIN categoria ON productos.categoria_id=categoria.id WHERE productos.categoria_id='$categoria_id' ORDER BY productos.nombre ASC LIMIT $inicio,$registros";

		$consulta_total="SELECT COUNT(id) FROM productos WHERE categoria_id='$categoria_id'";

	}else{

		$consulta_datos="SELECT $campos FROM productos INNER JOIN categoria ON productos.categoria_id=categoria.id ORDER BY productos.nombre ASC LIMIT $inicio,$registros";

		$consulta_total="SELECT COUNT(id) FROM productos";

	}

	$conexion=conexion();

	$datos = $conexion->query($consulta_datos);
	$datos = $datos->fetchAll();

	$total = $conexion->query($consulta_total);
	$total = (int) $total->fetchColumn();

	$Npaginas =ceil($total/$registros);

	$tabla.='
	<div class="table-container">
        <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
            <thead>
                <tr class="has-text-centered">
                	<th>#</th>
					<th>Producto</th>
					<th>Precio</th>
					<th>Categoría</th>
					<th colspan="2">Opciones</th>
                </tr>
            </thead>
            <tbody>
	';

	if($total>=1 && $pagina<=$Npaginas){
		$contador=$inicio+1;
		$pag_inicio=$inicio+1;
		foreach($datos as $rows){
			$tabla.='
				<tr class="has-text-centered" >
					<td>'.$contador.'</td>
					<td>'.$rows['Producto'].'</td>
					<td>'.$rows['precio'].'</td>
					<td>'.$rows['Categoria'].'</td>
					<td>
						<a href="index.php?vista=product_update&product_id_up='.$rows['id'].'" class="button is-success is-rounded is-small">Actualizar</a>
					</td>
				</tr>
            ';
            $contador++;
		}
		$pag_final=$contador-1;
	}else{
		if($total>=1){
			$tabla.='
				<tr class="has-text-centered" >
					<td colspan="5">
						<a href="'.$url.'1" class="button is-link is-rounded is-small mt-4 mb-4">
							Haga clic acá para recargar el listado
						</a>
					</td>
				</tr>
			';
		}else{
			$tabla.='
				<tr class="has-text-centered" >
					<td colspan="5">
						No hay registros en el sistema
					</td>
				</tr>
			';
		}
	}
	$tabla.='</tbody></table></div>';

	if($total>0 && $pagina<=$Npaginas){
		$tabla.='<p class="has-text-right">Mostrando productos <strong>'.$pag_inicio.'</strong> al <strong>'.$pag_final.'</strong> de un <strong>total de '.$total.'</strong></p>';
	}

	$conexion=null;
	echo $tabla;

	if($total>=1 && $pagina<=$Npaginas){
		echo paginador_tablas($pagina,$Npaginas,$url,7);
	}