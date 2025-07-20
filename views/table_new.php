<div class="container is-fluid">
	<h1 class="title">MESAS</h1>
	<h2 class="subtitle">Nueva Mesa</h2>
</div>

<div class="container pb-6 pt-6">
    <?php
        require_once "./php/main.php";
    ?>

    <div class="form-rest mb-6 mt-6"></div>

    <form action="./php/mesa_guardar.php" method="POST" class="FormularioAjax" autocomplete="off" >
        <div class="columns">
          	
          	<div class="column">
            	<div class="control">
                    <label>Número</label>
                  	<input class="input" type="text" name="numero" pattern="[0-9]{1,70}" maxlength="70" required >
                </div>
          	</div>

          	<div class="column">
                  <label>Estado</label>
            	<div class="control">
                    <div class="select">
                      	<select name="estado" required>
                        	<option value="" selected="">Seleccione una opción</option>
                        	<option value="libre">Libre</option>
                        	<option value="ocupada">Ocupada</option>
                        	<option value="esperando_cuenta">Esperando Cuenta</option>
                      	</select>
                    </div>
                </div>
          	</div>
        </div>

        <p class="has-text-centered">
            <button type="submit" class="button is-success is-rounded">Guardar</button>
        </p>
    </form>