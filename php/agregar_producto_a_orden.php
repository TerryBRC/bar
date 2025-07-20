<?php
// llamamos a main.php para incluir la conexión a la base de datos y otras funciones necesarias
require_once "./php/main.php";

$conexion = conexion(); // Get your PDO database connection

// Check if the request method is POST (meaning the form was submitted)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Retrieve and sanitize input data from the POST request
    $orden_id = isset($_POST['orden_id']) ? intval($_POST['orden_id']) : 0;
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0; // Get mesa_id from URL for redirect

    $producto_id = 0;
    // Loop through POST data to find the selected product ID
    // This handles cases where you have multiple product dropdowns (one per category)
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'producto_') === 0 && !empty($value)) {
            $producto_id = intval($value);
            break; // Found the selected product, no need to check further
        }
    }

    // Validate the received data
    if ($orden_id > 0 && $producto_id > 0 && $cantidad > 0 && $mesa_id > 0) {
        try {
            // Prepare and execute the stored procedure call
            $stmt = $conexion->prepare("CALL agregar_producto(?, ?, ?)");
            $stmt->execute([$orden_id, $producto_id, $cantidad]);

            // Redirect back to the order page for the same table with a success message
            header("Location: ../index.php?vista=create_order&mesa_id={$mesa_id}&status=product_added");
            exit(); // Always exit after a header redirect
        } catch (PDOException $e) {
            // Handle database errors (e.g., if the stored procedure signals an error)
            // Redirect back with an error message
            $error_message = urlencode("Error al agregar producto: " . $e->getMessage());
            header("Location: ../index.php?vista=create_order&mesa_id={$mesa_id}&status=error&message={$error_message}");
            exit();
        }
    } else {
        // Handle cases where input data is incomplete or invalid
        $error_message = urlencode("Datos incompletos o inválidos para agregar el producto.");
        header("Location: ../index.php?vista=create_order&mesa_id={$mesa_id}&status=error&message={$error_message}");
        exit();
    }
} else {
    // If not a POST request, redirect to the main tables view or an error page
    header("Location: ../index.php?status=invalid_request");
    exit();
}
?>