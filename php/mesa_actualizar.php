<?php
	require_once "main.php";

	/*== Almacenando id ==*/
    $id=limpiar_cadena($_POST['mesa_id']);


    /*== Verificando producto ==*/
	$check_mesa=conexion();
	$check_mesa=$check_mesa->query("SELECT * FROM mesas WHERE id='$id'");

    if($check_mesa->rowCount()<=0){
    	echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                La mesa no existe en el sistema
            </div>
        ';
        exit();
    }else{
    	$datos=$check_mesa->fetch();
    }
    $check_mesa=null;


    /*== Almacenando datos ==*/
	$numero=limpiar_cadena($_POST['numero']);

	$estado=limpiar_cadena($_POST['estado']);


	/*== Verificando campos obligatorios ==*/
    if($numero=="" || $estado==""){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                No has llenado todos los campos que son obligatorios
            </div>
        ';
        exit();
    }


    if(verificar_datos("[0-9]{1,70}",$numero)){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                El número no coincide con el formato solicitado
            </div>
        ';
        exit();
    }

    if(verificar_datos("[a-zA-Z_]{1,25}",$estado)){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                El PRECIO no coincide con el formato solicitado
            </div>
        ';
        exit();
    }

    /*== Verificando numero ==*/
    if($numero!=$datos['numero']){
	    $check_mesa=conexion();
	    $check_mesa=$check_mesa->query("SELECT numero FROM mesas WHERE numero='$numero'");
	    if($check_mesa->rowCount()>0){
	        echo '
	            <div class="notification is-danger is-light">
	                <strong>¡Ocurrio un error inesperado!</strong><br>
	                El numero ingresado ya se encuentra registrado, por favor elija otro
	            </div>
	        ';
	        exit();
	    }
	    $check_mesa=null;
    }

    /*== Actualizando datos ==*/
    $actualizar_mesa=conexion();
    $actualizar_mesa=$actualizar_mesa->prepare("UPDATE mesas SET numero=:numero,estado=:estado WHERE id=:id");

    $marcadores=[
        ":numero"=>$numero,
        ":estado"=>$estado,
        ":id"=>$id
    ];


    if($actualizar_mesa->execute($marcadores)){
        echo '
            <div class="notification is-info is-light">
                <strong>¡Mesa ACTUALIZADA!</strong><br>
                El mesa se actualizo con exito
            </div>
        ';
    }else{
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                No se pudo actualizar la mesa, por favor intente nuevamente
            </div>
        ';
    }
    $actualizar_mesa=null;