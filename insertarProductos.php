<?php
include('conexionLector.php');
$obj = new ConexionLector();
$conexion = $obj->Conectar();

$jsonData = file_get_contents('php://input');

// Decodificar los datos JSON a un array asociativo
$data = json_decode($jsonData, true);

// Verificar si se pudo decodificar correctamente
if ($data !== null && isset($data['productos'])) {
    try {
        // Obtener la lista de promociones existentes en la base de datos
        $query_get_existing_promotions = "SELECT id_promocion FROM promocion";
        $statement_get_existing_promotions = $conexion->prepare($query_get_existing_promotions);
        $statement_get_existing_promotions->execute();
        $existing_promotions = $statement_get_existing_promotions->fetchAll(PDO::FETCH_COLUMN);

        // Iterar sobre los productos y eliminar las imágenes asociadas a las promociones
        foreach ($data['productos'] as $producto) {
            $id_producto = $producto['id_producto'];

            // Eliminar la imagen asociada a las promociones de este producto
            $query_delete_image = "DELETE FROM images_promocion WHERE id_promocion IN (SELECT id_promocion FROM promocion WHERE id_producto = :id_producto)";
            $statement_delete_image = $conexion->prepare($query_delete_image);
            $statement_delete_image->bindParam(':id_producto', $id_producto);
            $statement_delete_image->execute();

            // Eliminar la promoción si está presente en la base de datos
            if (in_array($id_producto, $existing_promotions)) {
                $query_delete_promotion = "DELETE FROM promocion WHERE id_producto = :id_producto";
                $statement_delete_promotion = $conexion->prepare($query_delete_promotion);
                $statement_delete_promotion->bindParam(':id_producto', $id_producto);
                $statement_delete_promotion->execute();
            }
        }

        // Ahora proceder con la inserción o actualización de los productos
        foreach ($data['productos'] as $producto) {
            $id_producto = $producto['id_producto'];
            $nombre_producto = $producto['nombre_producto'];
            $id_familia = $producto['id_familia'];
            $id_umedida = $producto['id_umedida'];
            $cbarra = $producto['cbarra'];
            $total = $producto['total'];
            $precio_old = $producto['precio_old'];

            // Verificar si $precio_old es false o NULL y establecerlo en 0 en ese caso
            if ($precio_old === false || $precio_old === null) {
                $precio_old = 0;
            }

            // Verificar si el producto ya existe en la base de datos
            $query_check = "SELECT id_producto FROM producto WHERE id_producto = :id_producto";
            $statement_check = $conexion->prepare($query_check);
            $statement_check->bindParam(':id_producto', $id_producto);
            $statement_check->execute();
            $producto_existente = $statement_check->fetch(PDO::FETCH_ASSOC);

            // Si el producto existe, actualizarlo; de lo contrario, insertarlo
            if ($producto_existente) {
                $query = "UPDATE producto SET nombre_producto = :nombre_producto, id_familia = :id_familia, cbarra = :cbarra, id_umedida = :id_umedida, total = :total, precio_old = :precio_old WHERE id_producto = :id_producto";
            } else {
                $query = "INSERT INTO producto (id_producto, nombre_producto, id_familia, cbarra, id_umedida, total, id_estado, precio_old) VALUES (:id_producto, :nombre_producto, :id_familia, :cbarra, :id_umedida, :total, 1, :precio_old)";
            }

            $statement = $conexion->prepare($query);
            $statement->bindParam(':id_producto', $id_producto);
            $statement->bindParam(':nombre_producto', $nombre_producto);
            $statement->bindParam(':id_familia', $id_familia);
            $statement->bindParam(':cbarra', $cbarra);
            $statement->bindParam(':id_umedida', $id_umedida);
            $statement->bindParam(':total', $total);
            $statement->bindParam(':precio_old', $precio_old);
            $statement->execute();
        }

        // Devolver una respuesta de éxito si todo salió bien
        echo json_encode(['success' => true, 'message' => 'Los productos se sincronizaron correctamente en la base de datos del lector.']);
    } catch (PDOException $e) {
        // Capturar y manejar errores de PDO
        echo json_encode(['success' => false, 'message' => 'Error durante la sincronización: ' . $e->getMessage()]);
    }
} else {
    // Devolver una respuesta de error si los datos JSON no pudieron decodificarse correctamente o si los productos no están presentes
    echo json_encode(['success' => false, 'message' => 'Error: No se recibieron datos válidos de productos JSON.']);
}
?>
