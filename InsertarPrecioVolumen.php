<?php

include('conexionLector.php');
$obj = new ConexionLector();
$conexion = $obj->Conectar();

$jsonData = file_get_contents('php://input');

// Imprimir el JSON recibido en la consola
error_log('JSON recibido: ' . $jsonData);

// Decodificar los datos JSON a un array asociativo
$data = json_decode($jsonData, true);

// Verificar si se pudo decodificar correctamente
if ($data !== null && isset($data['PrecioVolumen'])) {
    // Obtener los IDs de los precios de volumen enviados en el JSON
    $PrecioVolumen_json_ids = array_column($data['PrecioVolumen'], 'id_precio_volumen');

    // Obtener los IDs de los precios de volumen almacenados en la base de datos del lector
    $query_lector = "SELECT id_precio_volumen FROM precio_volumen";
    $statement_lector = $conexion->prepare($query_lector);
    $statement_lector->execute();
    $PrecioVolumen_db_ids = $statement_lector->fetchAll(PDO::FETCH_COLUMN);

    // Obtener los IDs de los precios de volumen que están en el lector pero no en el JSON
    $PrecioVolumen_a_eliminar = array_diff($PrecioVolumen_db_ids, $PrecioVolumen_json_ids);

    // Eliminar los precios de volumen no encontrados en el JSON
    foreach ($PrecioVolumen_a_eliminar as $id_precio_volumen) {
        $query_delete = "DELETE FROM precio_volumen WHERE id_precio_volumen = :id_precio_volumen";
        $statement_delete = $conexion->prepare($query_delete);
        $statement_delete->bindParam(':id_precio_volumen', $id_precio_volumen);
        $statement_delete->execute();
    }

    // Iterar sobre los precios de volumen y realizar la inserción o actualización en la base de datos del lector
    foreach ($data['PrecioVolumen'] as $PrecioVolumen) {
        $id_precio_volumen = $PrecioVolumen['id_precio_volumen'];
        $id_producto = $PrecioVolumen['id_producto'];
        $desde = $PrecioVolumen['desde'];
        $hasta = $PrecioVolumen['hasta'];
        $precio = $PrecioVolumen['precio_bruto'];

        // Verificar si el precio de volumen ya existe en la base de datos
        $query_check = "SELECT id_precio_volumen FROM precio_volumen WHERE id_precio_volumen = :id_precio_volumen";
        $statement_check = $conexion->prepare($query_check);
        $statement_check->bindParam(':id_precio_volumen', $id_precio_volumen);
        $statement_check->execute();
        $precio_volumen_existente = $statement_check->fetch(PDO::FETCH_ASSOC);

        if ($precio_volumen_existente) {
            $query = "UPDATE precio_volumen SET id_producto = :id_producto, desde = :desde, hasta = :hasta, precio = :precio, id_estado = 1 WHERE id_precio_volumen = :id_precio_volumen";
        } else {
            $query = "INSERT INTO precio_volumen (id_precio_volumen, id_producto, desde, hasta, precio, id_estado) VALUES (:id_precio_volumen, :id_producto, :desde, :hasta, :precio, 1)";
        }

        // Preparar y ejecutar la consulta
        $statement = $conexion->prepare($query);
        $statement->bindParam(':id_precio_volumen', $id_precio_volumen);
        $statement->bindParam(':id_producto', $id_producto);
        $statement->bindParam(':desde', $desde);
        $statement->bindParam(':hasta', $hasta);
        $statement->bindParam(':precio', $precio);
        $statement->execute();
    }

    // Devolver una respuesta de éxito si todo salió bien
    echo json_encode(['success' => true, 'message' => 'Los precios de volumen se sincronizaron correctamente en la base de datos del lector.']);
} else {
    // Devolver una respuesta de error si los datos JSON no pudieron decodificarse correctamente o si los precios de volumen no están presentes
    echo json_encode(['success' => false, 'message' => 'Error: No se recibieron datos válidos de precios de volumen JSON.']);
}
?>
