<?php
require_once "main.php";
/*== Almacenando datos ==*/
$numero = limpiar_cadena($_POST['numero']);
$estado = limpiar_cadena($_POST['estado']);
/*== Verificando campos obligatorios ==*/
if ($numero == "" || $estado == "") {
    echo '
        <div class="notification is-danger is-light">
            <strong>¡Ocurrio un error inesperado!</strong><br>
            No has llenado todos los campos que son obligatorios
        </div>
    ';
    exit();
}
if (verificar_datos("[0-9]{1,70}", $numero)) {
    echo '
        <div class="notification is-danger is-light">
            <strong>¡Ocurrio un error inesperado!</strong><br>
            El NÚMERO no coincide con el formato solicitado
        </div>
    ';
    exit();
}
/*== Verificando si la mesa ya existe ==*/
$check_mesa = conexion();
$check_mesa = $check_mesa->query("SELECT * FROM mesas WHERE numero='$numero'");
if ($check_mesa->rowCount() > 0) {
    echo '
        <div class="notification is-danger is-light">
            <strong>¡Ocurrio un error inesperado!</strong><br>
            La MESA ya se encuentra registrada en el sistema
        </div>
    ';
    exit();
}
/*== Guardando datos ==*/
$guardar_mesa = conexion();
$guardar_mesa = $guardar_mesa->prepare("INSERT INTO mesas(numero, estado) VALUES(:numero, :estado)");
$guardar_mesa->bindParam(":numero", $numero);
$guardar_mesa->bindParam(":estado", $estado);
if ($guardar_mesa->execute()) {
    echo '
        <div class="notification is-success is-light">
            <strong>¡Mesa registrada exitosamente!</strong><br>
            La mesa ha sido registrada correctamente en el sistema
        </div>
    ';
} else {
    echo '
        <div class="notification is-danger is-light">
            <strong>¡Ocurrio un error inesperado!</strong><br>
            No hemos podido registrar la mesa, por favor intente nuevamente
        </div>
    ';
}
$guardar_mesa = null;
$check_mesa = null;