<?php

include('conexionLector.php');
$obj = new ConexionLector();
$conexion = $obj->Conectar();

$jsonData = file_get_contents('php://input');

// Decodificar los datos JSON a un array asociativo
$data = json_decode($jsonData, true);

// Verificar si se pudo decodificar correctamente
if ($data !== null && isset($data['umedidas'])) {
    // Obtener los IDs de las unidades de medida enviadas en el JSON
    $umedidas_json_ids = array_column($data['umedidas'], 'id_umedida');

    // Obtener los IDs de las unidades de medida almacenadas en la base de datos del lector
    $query_lector = "SELECT id_umedida FROM umedida";
    $statement_lector = $conexion->prepare($query_lector);
    $statement_lector->execute();
    $umedidas_lector_ids = $statement_lector->fetchAll(PDO::FETCH_COLUMN);

    // Obtener los IDs de las unidades de medida que están en el lector pero no en el JSON
    $umedidas_a_eliminar = array_diff($umedidas_lector_ids, $umedidas_json_ids);

    // Eliminar las unidades de medida no encontradas en el JSON
    foreach ($umedidas_a_eliminar as $id_umedida) {
        $query_delete = "DELETE FROM umedida WHERE id_umedida = :id_umedida";
        $statement_delete = $conexion->prepare($query_delete);
        $statement_delete->bindParam(':id_umedida', $id_umedida);
        $statement_delete->execute();
    }

    // Iterar sobre las unidades de medida y realizar la inserción o actualización en la base de datos del lector
    foreach ($data['umedidas'] as $umedida) {
        $id_umedida = $umedida['id_umedida'];
        $nombre_umedida = $umedida['nombre_umedida'];
        $nombre_corto = $umedida['nombre_corto'];

        // Verificar si la unidad de medida ya existe en la base de datos
        $query_check = "SELECT id_umedida FROM umedida WHERE id_umedida = :id_umedida";
        $statement_check = $conexion->prepare($query_check);
        $statement_check->bindParam(':id_umedida', $id_umedida);
        $statement_check->execute();
        $umedida_existente = $statement_check->fetch(PDO::FETCH_ASSOC);

        if ($umedida_existente) {
            $query = "UPDATE umedida SET nombre_umedida = :nombre_umedida, nombre_corto = :nombre_corto, id_estado = 1 WHERE id_umedida = :id_umedida";
        } else {
            $query = "INSERT INTO umedida (id_umedida, nombre_umedida, nombre_corto, id_estado) VALUES (:id_umedida, :nombre_umedida, :nombre_corto, 1)";
        }

        // Preparar y ejecutar la consulta
        $statement = $conexion->prepare($query);
        $statement->bindParam(':id_umedida', $id_umedida);
        $statement->bindParam(':nombre_umedida', $nombre_umedida);
        $statement->bindParam(':nombre_corto', $nombre_corto);
        $statement->execute();
    }

    // Devolver una respuesta de éxito si todo salió bien
    echo json_encode(['success' => true, 'message' => 'Las unidades de medida se sincronizaron correctamente en la base de datos del lector.']);
} else {
    // Devolver una respuesta de error si los datos JSON no pudieron decodificarse correctamente o si las unidades de medida no están presentes
    echo json_encode(['success' => false, 'message' => 'Error: No se recibieron datos válidos de unidades de medida JSON.']);
}
