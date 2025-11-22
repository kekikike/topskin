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
            m.nombre AS marca,
            GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ') AS categorias,
            GROUP_CONCAT(c.idCategoria ORDER BY c.nombre SEPARATOR ',') AS categoriasIds
        FROM tproductos p
        LEFT JOIN tmarcas m ON p.idMarca = m.idMarca
        LEFT JOIN tproductosCategorias pc ON pc.idProducto = p.idProducto AND pc.estado = 1
        LEFT JOIN tcategorias c ON pc.idCategoria = c.idCategoria AND c.estado = 1
        WHERE p.estado = 1
        GROUP BY p.idProducto, p.nombre, p.precioUnitario, p.stock, p.foto, m.nombre
        ORDER BY p.nombre
    ";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categorias = $pdo->query("SELECT idCategoria, nombre FROM tcategorias WHERE estado = 1 ORDER BY nombre")
                       ->fetchAll(PDO::FETCH_ASSOC);

    $marcas = $pdo->query("SELECT idMarca, nombre FROM tmarcas WHERE estado = 1 ORDER BY nombre")
                     ->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT CONCAT(nombre1,' ',apellidoP) AS nombre FROM templeados WHERE ciEmpleado = ?");
    $stmt->execute([$ciEmpleado]);
    $emp = $stmt->fetch();

    echo json_encode([
        "success" => true,
        "productos" => $productos,
        "categorias" => $categorias,
        "marcas" => $marcas,
        "empleadoNombre" => $emp['nombre'] ?? 'Empleado'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>