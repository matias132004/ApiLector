<?php
include('conexionServer.php');

$obj = new ConexionServer();
$conexion = $obj->Conectar();
$consulta = "select id_umedida, nombre_umedida,nombre_corto from umedida";
$resultado = $conexion->prepare($consulta);

if ($resultado->execute()) {
    $array = array();
    while ($datos = $resultado->fetch(PDO::FETCH_ASSOC)) {
        $array[] = $datos;
    }
    $jsonData = json_encode(['umedidas' => $array], JSON_UNESCAPED_UNICODE);

    // Realizar la solicitud HTTP POST al archivo insertarFamilias.php
    $url = 'http://localhost/ApiLector/insertarUmedida.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Decodificar la respuesta
    $responseData = json_decode($response, true);

    // Verificar si la inserción fue exitosa
    if (isset($responseData['success']) && $responseData['success'] === true) {
        // Redirigir al usuario a la página deseada
        header('Location: http://localhost/ApiLector/obtenerProductos.php');
        exit;
    } else {
        // Manejar el caso de error si es necesario
        echo "Hubo un error durante la inserción de unidades de medida.";
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al obtener las unidades de medidas desde la base de datos alternativa.']);
}
?>
