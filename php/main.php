<?php
    # Conexion a la base de datos
    function conexion() {

        $pdo = new PDO ('mysql:host=localhost;dbname=restaurante',
        'root',
        'root');
        return $pdo;
    }
    #Verificar datos
    function verificar_datos($filtro,$cadena){
        #si la cadena coincide con el filtro, retorna false
        #si no coincide, retorna true proque no es un dato valido
        if (preg_match("/^".$filtro."$/",$cadena)) {
            return false;
        } else {
            return true;
        }
    }
    # Limpiar cadenas de texto #
	function limpiar_cadena($cadena){
		$cadena=trim($cadena);// Elimina espacios al inicio y al final
		$cadena=stripslashes($cadena);// Elimina las barras invertidas
		$cadena=str_ireplace("<script>", "", $cadena);// Elimina las etiquetas script
		$cadena=str_ireplace("</script>", "", $cadena);// Elimina las etiquetas script
		$cadena=str_ireplace("<script src", "", $cadena);// Elimina las etiquetas script
		$cadena=str_ireplace("<script type=", "", $cadena);// Elimina las etiquetas script
		$cadena=str_ireplace("SELECT * FROM", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("DELETE FROM", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("INSERT INTO", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("DROP TABLE", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("DROP DATABASE", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("TRUNCATE TABLE", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("SHOW TABLES;", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("SHOW DATABASES;", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("<?php", "", $cadena);// Elimina las etiquetas PHP
		$cadena=str_ireplace("?>", "", $cadena);// Elimina las etiquetas PHP
		$cadena=str_ireplace("--", "", $cadena);// Elimina los comentarios SQL
		$cadena=str_ireplace("^", "", $cadena);// Elimina los caracteres especiales
		$cadena=str_ireplace("<", "", $cadena);// Elimina los caracteres especiales
        $cadena=str_ireplace(">", "", $cadena);// Elimina los caracteres especiales
		$cadena=str_ireplace("[", "", $cadena);// Elimina los caracteres especiales
		$cadena=str_ireplace("]", "", $cadena);// Elimina los caracteres especiales
        $cadena=str_ireplace("(", "", $cadena);// Elimina los caracteres especiales
        $cadena=str_ireplace(")", "", $cadena);// Elimina los caracteres especiales
        $cadena=str_ireplace(" OR ", "", $cadena);// Elimina las consultas SQL
		$cadena=str_ireplace("==", "", $cadena);// Elimina los operadores de comparación
		$cadena=str_ireplace(";", "", $cadena); // Elimina los puntos y comas
		$cadena=str_ireplace("::", "", $cadena);    // Elimina los operadores de resolución de ámbito
        /*$cadena=str_ireplace("=", "", $cadena); // Elimina los signos de igual
        $cadena=str_ireplace("'", "", $cadena); // Elimina las comillas simples
        $cadena=str_ireplace('"', "", $cadena); // Elimina las comillas dobles
        $cadena=str_ireplace("´", "", $cadena); // Elimina las comillas acentuadas
        $cadena=str_ireplace("`", "", $cadena); // Elimina las comillas invertidas
        $cadena=str_ireplace("!", "", $cadena); // Elimina los signos de exclamación
        $cadena=str_ireplace("?", "", $cadena); // Elimina los signos de interrogación
        $cadena=str_ireplace("~", "", $cadena); // Elimina los caracteres especiales
        $cadena=str_ireplace(":", "", $cadena); // Elimina los dos puntos
        $cadena=str_ireplace(",", "", $cadena); // Elimina las comas*/
		$cadena=trim($cadena);
		$cadena=stripslashes($cadena);
		return $cadena;
	}

    # Funcion paginador de tablas #
	function paginador_tablas($pagina,$Npaginas,$url,$botones){
		$tabla='<nav class="pagination is-centered is-rounded" role="navigation" aria-label="pagination">';

		if($pagina<=1){
			$tabla.='
			<a class="pagination-previous is-disabled" disabled >Anterior</a>
			<ul class="pagination-list">';
		}else{
			$tabla.='
			<a class="pagination-previous" href="'.$url.($pagina-1).'" >Anterior</a>
			<ul class="pagination-list">
				<li><a class="pagination-link" href="'.$url.'1">1</a></li>
				<li><span class="pagination-ellipsis">&hellip;</span></li>
			';
		}

		$ci=0;
		for($i=$pagina; $i<=$Npaginas; $i++){
			if($ci>=$botones){
				break;
			}
			if($pagina==$i){
				$tabla.='<li><a class="pagination-link is-current" href="'.$url.$i.'">'.$i.'</a></li>';
			}else{
				$tabla.='<li><a class="pagination-link" href="'.$url.$i.'">'.$i.'</a></li>';
			}
			$ci++;
		}

		if($pagina==$Npaginas){
			$tabla.='
			</ul>
			<a class="pagination-next is-disabled" disabled >Siguiente</a>
			';
		}else{
			$tabla.='
				<li><span class="pagination-ellipsis">&hellip;</span></li>
				<li><a class="pagination-link" href="'.$url.$Npaginas.'">'.$Npaginas.'</a></li>
			</ul>
			<a class="pagination-next" href="'.$url.($pagina+1).'" >Siguiente</a>
			';
		}

		$tabla.='</nav>';
		return $tabla;
	}