<?php
include('conexionServer.php');

$obj = new ConexionServer();
$conexion = $obj->Conectar();
$consulta = "SELECT id_familia, nombre_familia FROM familia";
$resultado = $conexion->prepare($consulta);

if ($resultado->execute()) {
    $array = array();
    while ($datos = $resultado->fetch(PDO::FETCH_ASSOC)) {
        $array[] = $datos;
    }
    $jsonData = json_encode(['familias' => $array], JSON_UNESCAPED_UNICODE);

    // Realizar la solicitud HTTP POST al archivo insertarFamilias.php
    $url = 'http://localhost/ApiLector/insertarFamilias.php';
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
        header('Location:http://192.168.1.190/ApiLector/obtenerUmedida.php');
        exit;
    } else {
        // Manejar el caso de error si es necesario
        echo "Hubo un error durante la inserción de familias.";
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al obtener las familias desde la base de datos alternativa.']);
}
?>
