<?php
require_once "main.php";
$conexion = conexion();

// cerramos la mesa por si el cliente se va llamamos al procedimiento almacenado y le pasamos el id de la orden
if (isset($_GET['orden_id'])) {
    $orden_id = $_GET['orden_id'];
    $sql = "CALL cerrar_orden_y_liberar_mesa(?)";
    $stmt = $conexion->prepare($sql);
    if ($stmt->execute([$orden_id])) {
        header("Location: ../index.php?vista=table_list");
        exit();
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "<script>alert('Error al cerrar la mesa: " . $errorInfo[2] . "');</script>";
    }
}