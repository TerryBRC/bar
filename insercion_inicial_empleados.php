<?php

// --- Configuración de la base de datos ---
$servername = "localhost"; // O la IP/nombre de tu servidor de base de datos
$username = "root"; // Tu usuario de MySQL
$password = "root"; // Tu contraseña de MySQL
$dbname = "restaurante"; // El nombre de tu base de datos

// --- Conexión a la base de datos ---
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// --- Datos de los empleados a insertar ---
$empleados = [
    ['Elmer Laguna', 'admin', 'admin', 1, 1],
    ['Elena Guevara', 'mesero1', 'mesero', 2, 1],
    ['Erica Galindo', 'cajero1', 'cajero', 3, 1]
];

// --- Preparar la sentencia SQL para la inserción ---
// Usamos marcadores de posición (?) para seguridad (prepared statements)
$stmt = $conn->prepare("INSERT INTO empleados (nombre, usuario, clave, rol_id, activo) VALUES (?, ?, ?, ?, ?)");

// Verificar si la preparación de la sentencia fue exitosa
if ($stmt === false) {
    die("Error al preparar la sentencia: " . $conn->error);
}

// --- Insertar cada empleado ---
foreach ($empleados as $empleado) {
    $nombre = $empleado[0];
    $usuario = $empleado[1];
    $clave_sin_hash = $empleado[2];
    $rol_id = $empleado[3];
    $activo = $empleado[4];

    // Hashear la contraseña antes de almacenarla
    // PASSWORD_DEFAULT es el algoritmo de hashing recomendado y se actualiza automáticamente
    $clave_hasheada = password_hash($clave_sin_hash, PASSWORD_DEFAULT);

    // Vincular los parámetros y ejecutar la sentencia
    // 'sssis' indica el tipo de cada parámetro:
    // s = string, i = integer
    $stmt->bind_param("sssis", $nombre, $usuario, $clave_hasheada, $rol_id, $activo);

    if ($stmt->execute()) {
        echo "Empleado '" . $nombre . "' insertado/actualizado correctamente.<br>";
    } else {
        echo "Error al insertar/actualizar el empleado '" . $nombre . "': " . $stmt->error . "<br>";
    }
}

// --- Cerrar la sentencia y la conexión ---
$stmt->close();
$conn->close();

echo "<br>Proceso de inserción de empleados completado.";

?>