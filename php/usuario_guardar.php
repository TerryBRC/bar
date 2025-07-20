<?php
    
    require_once "main.php";

    /*== Almacenando datos ==*/
    $nombre=limpiar_cadena($_POST['usuario_nombre']);
    $rol=limpiar_cadena($_POST['usuario_rol']);
    $usuario=limpiar_cadena($_POST['usuario_usuario']);

    $clave_1=limpiar_cadena($_POST['usuario_clave_1']);
    $clave_2=limpiar_cadena($_POST['usuario_clave_2']);


    /*== Verificando campos obligatorios ==*/
    if($nombre=="" || $rol == '' || $usuario=="" || $clave_1=="" || $clave_2==""){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                No has llenado todos los campos que son obligatorios
            </div>
        ';
        exit();
    }


    /*== Verificando integridad de los datos ==*/
    if(verificar_datos("[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{3,100}",$nombre)){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                El NOMBRE no coincide con el formato solicitado
            </div>
        ';
        exit();
    }

    if(verificar_datos("[a-zA-Z0-9]{4,20}",$usuario)){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                El USUARIO no coincide con el formato solicitado
            </div>
        ';
        exit();
    }

    if(verificar_datos("[a-zA-Z0-9$@.-]{4,100}",$clave_1) || verificar_datos("[a-zA-Z0-9$@.-]{4,100}",$clave_2)){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                Las CLAVES no coinciden con el formato solicitado
            </div>
        ';
        exit();
    }

    /*== Verificando el rol ==*/
    if(verificar_datos("[0-9]{1,11}",$rol)){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                El ROL no coincide con el formato solicitado
            </div>
        ';
        exit();
    }


    /*== Verificando usuario ==*/
    $check_usuario=conexion();
    $check_usuario=$check_usuario->query("SELECT usuario FROM empleados WHERE usuario='$usuario'");
    if($check_usuario->rowCount()>0){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                El USUARIO ingresado ya se encuentra registrado, por favor elija otro
            </div>
        ';
        exit();
    }
    $check_usuario=null;


    /*== Verificando claves ==*/
    if($clave_1!=$clave_2){
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                Las CLAVES que ha ingresado no coinciden
            </div>
        ';
        exit();
    }else{
        $clave=password_hash($clave_1,PASSWORD_BCRYPT,["cost"=>10]);
    }


    /*== Guardando datos ==*/
    $guardar_usuario=conexion();
    $guardar_usuario=$guardar_usuario->prepare("INSERT INTO empleados(nombre,usuario,clave,rol_id) VALUES(:nombre,:usuario,:clave,:rol)");

    $marcadores=[
        ":nombre"=>$nombre,
        ":usuario"=>$usuario,
        ":clave"=>$clave,
        ":rol"=>$rol
    ];

    $guardar_usuario->execute($marcadores);

    if($guardar_usuario->rowCount()==1){
        echo '
            <div class="notification is-info is-light">
                <strong>¡USUARIO REGISTRADO!</strong><br>
                El usuario se registro con exito
            </div>
        ';
    }else{
        echo '
            <div class="notification is-danger is-light">
                <strong>¡Ocurrio un error inesperado!</strong><br>
                No se pudo registrar el usuario, por favor intente nuevamente
            </div>
        ';
    }
    $guardar_usuario=null;