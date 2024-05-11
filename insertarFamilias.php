<?php
include('conexionLector.php'); 
$obj = new ConexionLector();
$conexion = $obj->Conectar();

$jsonData = file_get_contents('php://input');

// Decodificar los datos JSON a un array asociativo
$data = json_decode($jsonData, true);

// Verificar si se pudo decodificar correctamente
if ($data !== null && isset($data['familias'])) {
    // Obtener los IDs de las familias enviadas en el JSON
    $familias_json_ids = array_column($data['familias'], 'id_familia');

    // Obtener los IDs de las familias almacenadas en la base de datos del lector
    $query_lector = "SELECT id_familia FROM familia";
    $statement_lector = $conexion->prepare($query_lector);
    $statement_lector->execute();
    $familias_lector_ids = $statement_lector->fetchAll(PDO::FETCH_COLUMN);

    // Obtener los IDs de las familias que están en el lector pero no en el JSON
    $familias_a_eliminar = array_diff($familias_lector_ids, $familias_json_ids);

    // Eliminar las familias no encontradas en el JSON
    foreach ($familias_a_eliminar as $id_familia) {
        $query_delete = "DELETE FROM familia WHERE id_familia = :id_familia";
        $statement_delete = $conexion->prepare($query_delete);
        $statement_delete->bindParam(':id_familia', $id_familia);
        $statement_delete->execute();
    }

    // Iterar sobre las familias y realizar la inserción o actualización en la base de datos del lector
    foreach ($data['familias'] as $familia) {
        $id_familia = $familia['id_familia'];
        $nombre_familia = $familia['nombre_familia'];
        $id_estado = 1; // Establecer el ID de estado

        // Verificar si la familia ya existe en la base de datos
        $query_check = "SELECT id_familia FROM familia WHERE id_familia = :id_familia";
        $statement_check = $conexion->prepare($query_check);
        $statement_check->bindParam(':id_familia', $id_familia);
        $statement_check->execute();
        $familia_existente = $statement_check->fetch(PDO::FETCH_ASSOC);

        if ($familia_existente) {
            $query = "UPDATE familia SET nombre_familia = :nombre_familia, id_estado = :id_estado WHERE id_familia = :id_familia";
        } else {
            $query = "INSERT INTO familia (id_familia, nombre_familia, id_estado) VALUES (:id_familia, :nombre_familia, :id_estado)";
        }

        // Preparar y ejecutar la consulta
        $statement = $conexion->prepare($query);
        $statement->bindParam(':id_familia', $id_familia);
        $statement->bindParam(':nombre_familia', $nombre_familia);
        $statement->bindParam(':id_estado', $id_estado);
        $statement->execute();
    }

    // Devolver una respuesta de éxito si todo salió bien
    echo json_encode(['success' => true, 'message' => 'Las familias se sincronizaron correctamente en la base de datos del lector.']);
} else {
    // Devolver una respuesta de error si los datos JSON no pudieron decodificarse correctamente o si las familias no están presentes
    echo json_encode(['success' => false, 'message' => 'Error: No se recibieron datos válidos de familias JSON.']);
}
?>
