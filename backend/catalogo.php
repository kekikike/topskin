<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Conexión directa
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'topskin';

$conexion = mysqli_connect($host, $user, $pass, $db);
if (!$conexion) {
    echo json_encode(['error' => 'No conectado a MySQL']);
    exit;
}

// CONSULTA CORREGIDA: evita duplicados con GROUP BY + GROUP_CONCAT
$sql = "SELECT 
            p.idProducto,
            p.nombre,
            p.precioUnitario,
            p.stock,
            p.foto,
            COALESCE(m.nombre, 'Sin marca') AS marca,
            COALESCE(GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', '), 'Otros') AS categorias
        FROM tproductos p
        LEFT JOIN tmarcas m ON p.idMarca = m.idMarca
        LEFT JOIN tproductosCategorias pc ON p.idProducto = pc.idProducto
        LEFT JOIN tcategorias c ON pc.idCategoria = c.idCategoria
        WHERE p.estado = 1
        GROUP BY p.idProducto, p.nombre, p.precioUnitario, p.stock, p.foto, m.nombre
        ORDER BY p.nombre ASC";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    echo json_encode(['error' => mysqli_error($conexion)]);
    exit;
}

$productos = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $row['precioUnitario'] = (float)$row['precioUnitario'];
    $row['stock'] = (int)($row['stock'] ?? 0);

    // Si no hay foto
    if (empty($row['foto']) || $row['foto'] == null) {
        $row['foto'] = 'https://via.placeholder.com/300x300.png?text=Sin+Foto';
    }

    // Para los filtros, también guardamos las categorías como array
    $row['categorias_array'] = !empty($row['categorias']) && $row['categorias'] !== 'Otros' 
        ? array_map('trim', explode(',', $row['categorias'])) 
        : [];

    $productos[] = $row;
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>