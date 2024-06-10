<?php
include('conexionServer.php');
include('Rutas.php');

$obj = new ConexionServer();
$conexion = $obj->Conectar();
$consulta = "SELECT p.id_producto, p.id_umedida, p.nombre_producto, p.id_familia, p.cbarra, dp.total
             FROM producto AS p
             INNER JOIN detalle_lprecio AS dp ON p.id_producto = dp.id_producto
             WHERE venta_publico = 't'";
$resultado = $conexion->prepare($consulta);

if ($resultado->execute()) {
    $array = array();
    while ($datos = $resultado->fetch(PDO::FETCH_ASSOC)) {
        $array[] = $datos;
    }
    $jsonData = json_encode(['productos' => $array], JSON_UNESCAPED_UNICODE);

    $url = 'http://'.RUTA.'/ApiLectorResto/insertarProductos.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    error_log("Respuesta recibida del servidor: " . $response); // Agrega esta línea para registrar la respuesta
    curl_close($ch);

    $responseData = json_decode($response, true);

    // Verificar si 'success' está definido en la respuesta
    if (isset($responseData['success'])) {
        // Verificar si 'success' es true en la respuesta
        if ($responseData['success'] === true) {
            // Redirigir al usuario a la página deseada
            header('Location: http://'.RUTA.'/AdministradorLector/ControladorMenu/index');
            exit;
        } else {
            // Si 'success' está definido pero no es true, mostrar mensaje de error
            echo "Hubo un error durante la inserción de productos. Respuesta del servidor: " . $response;
        }
    } else {
        // Si 'success' no está definido en la respuesta, mostrar mensaje de error
        echo "Hubo un error durante la inserción de productos. 'success' no está definido en la respuesta.";
    }
} else {
    // Si la ejecución de la consulta no es exitosa, mostrar mensaje de error
    echo json_encode(['success' => false, 'message' => 'Error al obtener los productos desde la base de datos alternativa.']);
}
?>
