<?php
include('conexionServer.php');
include('Rutas.php');

$obj = new ConexionServer();
$conexion = $obj->Conectar();
$consulta = "select id_precio_volumen,id_producto,desde,hasta,precio_bruto from precio_volumen";
$resultado = $conexion->prepare($consulta);

if ($resultado->execute()) {
    $array = array();
    while ($datos = $resultado->fetch(PDO::FETCH_ASSOC)) {
        $array[] = $datos;
    }
    $jsonData = json_encode(['PrecioVolumen' => $array], JSON_UNESCAPED_UNICODE);

    // Realizar la solicitud HTTP POST al archivo insertarFamilias.php
    $url = 'http://'.RUTA.'/ApiLector/insertarPrecioVolumen.php';
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
        header('Location: http://'.RUTA.'/AdministradorLector/ControladorMenu/index');
        exit;
    } else {
        // Manejar el caso de error si es necesario
        echo "Hubo un error durante la inserción de Venta por Volumen.";
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al obtener las ventas por volumen desde la base de datos alternativa.']);
}
?>
