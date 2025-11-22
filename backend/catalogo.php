<?php
// catalogo.php
header('Content-Type: application/json; charset=utf-8');
require_once 'connection/connection.php'; 


$sql = "SELECT 
            p.idProducto, 
            p.nombre, 
            p.precioUnitario, 
            p.stock, 
            p.foto,
            COALESCE(c.nombre, 'Otros') AS categoria,
            COALESCE(m.nombre, 'Genérico') AS marca
        FROM tproductos p
        LEFT JOIN tproductosCategorias pc ON p.idProducto = pc.idProducto
        LEFT JOIN tcategorias c ON pc.idCategoria = c.idCategoria
        LEFT JOIN tmarcas m ON p.idMarca = m.idMarca
        WHERE p.estado = 1
        ORDER BY p.nombre ASC";

$result = mysqli_query($conexion, $sql);

if (!$result) {
    echo json_encode(['error' => 'Error en la consulta SQL']);
    exit;
}

$productos = [45];
while ($row = mysqli_fetch_assoc($result)) {
    $row['precioUnitario'] = (float)$row['precioUnitario'];
    $productos[] = $row;
}

echo json_encode($productos);
?>