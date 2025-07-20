<div class="container is-fluid">
	<h1 class="title">MESAS</h1>
	<h2 class="subtitle">Actualizar mesa</h2>
</div>

<div class="container pb-6 pt-6">
	<?php

		require_once "./php/main.php";

		$id = (isset($_GET['mesa_id_up'])) ? $_GET['mesa_id_up'] : 0;
		$id=limpiar_cadena($id);

		/*== Verificando mesa ==*/
    	$check_producto=conexion();
    	$check_producto=$check_producto->query("SELECT * FROM mesas WHERE id='$id'");

        if($check_producto->rowCount()>0){
        	$datos=$check_producto->fetch();
	?>

	<div class="form-rest mb-6 mt-6"></div>
	
	<h2 class="title has-text-centered">MESA # <?php echo $datos['numero']; ?></h2>

	<form action="./php/mesa_actualizar.php" method="POST" class="FormularioAjax" autocomplete="off" >

		<input type="hidden" name="mesa_id" value="<?php echo $datos['id']; ?>" required>

		<div class="columns">
		  	
		  	<div class="column">
                  <label>Número</label>
		    	<div class="control">
				  	<input class="input" type="text" name="numero" pattern="[0-9]{1,70}" maxlength="70" required value="<?php echo $datos['numero']; ?>" >
				</div>
		  	</div>

		  	<div class="column">
                <label>Estado</label>
		    	<div class="control">
                    <div class="select">
                        <select name="estado">
                            <option value="libre" <?php if($datos['estado'] == 'libre') echo 'selected'; ?>>Libre</option>
                            <option value="ocupada" <?php if($datos['estado'] == 'ocupada') echo 'selected'; ?>>Ocupada</option>
                            <option value="esperando_cuenta" <?php if($datos['estado'] == 'esperando_cuenta') echo 'selected'; ?>>Esperando Cuenta</option>
                        </select>
                    </div>
				</div>
		  	</div>
		</div>
		<p class="has-text-centered">
			<button type="submit" class="button is-success is-rounded">Actualizar</button>
		</p>
	</form>
	<?php 
		}else{
			include "./inc/error_alert.php";
		}
		$check_producto=null;
	?>
</div>