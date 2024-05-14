<?php
include('conexionLector.php');
$obj = new ConexionLector();
$conexion = $obj->Conectar();

$jsonData = file_get_contents('php://input');

// Decodificar los datos JSON a un array asociativo
$data = json_decode($jsonData, true);

// Verificar si se pudo decodificar correctamente
if ($data !== null && isset($data['promocion'])) {
    try {
        // Iterar sobre las promociones y guardarlas o actualizarlas en la base de datos
        foreach ($data['promocion'] as $promocion) {
            $id_promocion = $promocion['id_promocion'];
            $id_producto = $promocion['id_producto'];
            $fecha_inicio = $promocion['fecha_inicio'];
            $fecha_fin = $promocion['fecha_fin'];
            $lunes = $promocion['lunes'];
            $martes = $promocion['martes'];
            $miercoles = $promocion['miercoles'];
            $jueves = $promocion['jueves'];
            $viernes = $promocion['viernes'];
            $sabado = $promocion['sabado'];
            $domingo = $promocion['domingo'];
            $descripcion = $promocion['observacion']; // Se cambia a 'observacion'

            // Convertir los valores booleanos en representaciones de cadena 't' o 'f'
            $lunes = $lunes ? 't' : 'f';
            $martes = $martes ? 't' : 'f';
            $miercoles = $miercoles ? 't' : 'f';
            $jueves = $jueves ? 't' : 'f';
            $viernes = $viernes ? 't' : 'f';
            $sabado = $sabado ? 't' : 'f';
            $domingo = $domingo ? 't' : 'f';

            // Verificar si la promoción ya existe en la base de datos
            $query = "SELECT id_promocion FROM promocion WHERE id_promocion = :id_promocion";
            $statement = $conexion->prepare($query);
            $statement->bindParam(':id_promocion', $id_promocion);
            $statement->execute();
            $promocion_existente = $statement->fetchColumn();

            if ($promocion_existente) {
                // Actualizar la promoción utilizando el ID obtenido
                $query = "UPDATE promocion SET id_producto = :id_producto, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, lunes = :lunes, martes = :martes, miercoles = :miercoles, jueves = :jueves, viernes = :viernes, sabado = :sabado, domingo = :domingo, descripcion = :descripcion WHERE id_promocion = :id_promocion";
                $statement = $conexion->prepare($query);
                $statement->bindParam(':id_producto', $id_producto);
                $statement->bindParam(':fecha_inicio', $fecha_inicio);
                $statement->bindParam(':fecha_fin', $fecha_fin);
                $statement->bindParam(':lunes', $lunes);
                $statement->bindParam(':martes', $martes);
                $statement->bindParam(':miercoles', $miercoles);
                $statement->bindParam(':jueves', $jueves);
                $statement->bindParam(':viernes', $viernes);
                $statement->bindParam(':sabado', $sabado);
                $statement->bindParam(':domingo', $domingo);
                $statement->bindParam(':descripcion', $descripcion);
                $statement->bindParam(':id_promocion', $promocion_existente);
            } else {
                // Insertar la promoción
                $query = "INSERT INTO promocion (id_promocion, id_producto, fecha_inicio, fecha_fin, lunes, martes, miercoles, jueves, viernes, sabado, domingo, descripcion, id_estado) VALUES (:id_promocion, :id_producto, :fecha_inicio, :fecha_fin, :lunes, :martes, :miercoles, :jueves, :viernes, :sabado, :domingo, :descripcion, 1)";
                $statement = $conexion->prepare($query);
                $statement->bindParam(':id_promocion', $id_promocion);
                $statement->bindParam(':id_producto', $id_producto);
                $statement->bindParam(':fecha_inicio', $fecha_inicio);
                $statement->bindParam(':fecha_fin', $fecha_fin);
                $statement->bindParam(':lunes', $lunes);
                $statement->bindParam(':martes', $martes);
                $statement->bindParam(':miercoles', $miercoles);
                $statement->bindParam(':jueves', $jueves);
                $statement->bindParam(':viernes', $viernes);
                $statement->bindParam(':sabado', $sabado);
                $statement->bindParam(':domingo', $domingo);
                $statement->bindParam(':descripcion', $descripcion);
            }

            $statement->execute();
        }

        // Devolver una respuesta de éxito si todo salió bien
        echo json_encode(['success' => true, 'message' => 'Las promociones se guardaron o actualizaron correctamente en la base de datos.']);
    } catch (PDOException $e) {
        // Capturar y manejar errores de PDO
        echo json_encode(['success' => false, 'message' => 'Error durante la inserción o actualización de promociones: ' . $e->getMessage()]);
    }
} else {
    // Devolver una respuesta de error si los datos JSON no pudieron decodificarse correctamente o si las promociones no están presentes
    echo json_encode(['success' => false, 'message' => 'Error: No se recibieron datos válidos de promociones JSON.']);
}
?>
