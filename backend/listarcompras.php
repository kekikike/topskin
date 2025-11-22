<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$ciEmpleado = $input['ciEmpleado'] ?? null;

$desde = $input['desde'] ?? null;
$hasta = $input['hasta'] ?? null;

try {
    $pdo = Conexion::conectar();

    $empleadoNombre = "Usuario";
    if ($ciEmpleado) {
        $stmt = $pdo->prepare("
            SELECT CONCAT(TRIM(nombre1), ' ', TRIM(apellidoP)) AS nombreCompleto 
            FROM templeados 
            WHERE ciEmpleado = ? AND estado = 1
        ");
        $stmt->execute([$ciEmpleado]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($emp && !empty(trim($emp['nombreCompleto']))) {
            $empleadoNombre = trim($emp['nombreCompleto']);
        }
    }

    $sql = "
        SELECT 
            c.idCompra, 
            c.fechaCompra, 
            c.totalcompra AS total, 
            p.nombre AS proveedor
        FROM tcompras c
        LEFT JOIN tproveedores p ON c.idProveedor = p.idProveedor
        WHERE c.estado = 1
    ";
    $params = [];
    if ($desde) { $sql .= " AND c.fechaCompra >= ?"; $params[] = $desde; }
    if ($hasta) { $sql .= " AND c.fechaCompra <= ?"; $params[] = $hasta; }
    $sql .= " ORDER BY c.fechaCompra DESC, c.idCompra DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $proveedores = $pdo->query("SELECT idProveedor, nombre FROM tproveedores WHERE estado = 1 ORDER BY nombre")
                       ->fetchAll(PDO::FETCH_ASSOC);

    $productos = $pdo->query("SELECT idProducto, nombre, precioUnitario, stock FROM tproductos WHERE estado = 1 ORDER BY nombre")
                     ->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "compras" => $compras,
        "proveedores" => $proveedores,
        "productos" => $productos,
        "empleadoNombre" => $empleadoNombre  
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error del servidor: " . $e->getMessage()]);
}
?>