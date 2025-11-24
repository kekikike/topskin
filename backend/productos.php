<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$ciEmpleado = $input['ciEmpleado'] ?? null;

if (!$ciEmpleado) {
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

try {
    $pdo = Conexion::conectar();

    $sql = "
        SELECT 
            p.idProducto,
            p.nombre,
            p.precioUnitario,
            p.stock,
            p.foto,
            p.idMarca,
            COALESCE(m.nombre, 'Sin marca') AS marca,
            GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS categorias,
            GROUP_CONCAT(DISTINCT c.idCategoria ORDER BY c.nombre SEPARATOR ',') AS categoriasIds
        FROM tproductos p
        LEFT JOIN tmarcas m ON p.idMarca = m.idMarca
        LEFT JOIN tproductosCategorias pc ON pc.idProducto = p.idProducto AND pc.estado = 1
        LEFT JOIN tcategorias c ON pc.idCategoria = c.idCategoria AND c.estado = 1
        WHERE p.estado = 1
        GROUP BY p.idProducto
        ORDER BY p.nombre ASC
    ";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categorias = $pdo->query("SELECT idCategoria, nombre FROM tcategorias WHERE estado = 1 ORDER BY nombre")
                       ->fetchAll(PDO::FETCH_ASSOC);

    $marcas = $pdo->query("SELECT idMarca, nombre FROM tmarcas WHERE estado = 1 ORDER BY nombre")
                     ->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT CONCAT(nombre1,' ',IFNULL(nombre2,''),' ',apellidoP,' ',IFNULL(apellidoM,'')) AS nombreCompleto 
        FROM templeados 
        WHERE ciEmpleado = ? AND estado = 1
    ");
    $stmt->execute([$ciEmpleado]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    $empleadoNombre = $emp['nombreCompleto'] ?? 'Empleado';

    echo json_encode([
        "success"        => true,
        "productos"      => $productos,
        "categorias"     => $categorias,
        "marcas"         => $marcas,
        "empleadoNombre" => $empleadoNombre
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error del servidor: " . $e->getMessage()
    ]);
}
?>