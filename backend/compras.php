<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
if (!$input || empty($input['proveedor']) || empty($input['items']) || empty($input['ciEmpleado']) || empty($input['fechaCompra'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos"]);
    exit;
}

$ciEmpleado = $input['ciEmpleado'];
$idProveedor = $input['proveedor'];
$fechaCompra = $input['fechaCompra'];
$items = $input['items'];

try {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT ciEmpleado FROM templeados WHERE ciEmpleado = ? AND estado = 1");
    $stmt->execute([$ciEmpleado]);
    if (!$stmt->fetch()) throw new Exception("Empleado no válido");

    $total = array_sum(array_map(fn($i) => $i['cantidad'] * $i['precio'], $items));

    $stmt = $pdo->prepare("INSERT INTO tcompras (idProveedor, idPersonal, totalcompra, fechaCompra, usuarioA, estado) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$idProveedor, $ciEmpleado, $total, $fechaCompra, $ciEmpleado]);
    $idCompra = $pdo->lastInsertId();

    $stmtDet = $pdo->prepare("INSERT INTO tdetallecompra (idCompra, idProducto, cantidad, subtotal) VALUES (?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE tproductos SET stock = stock + ? WHERE idProducto = ?");

    foreach ($items as $i) {
        $stmtDet->execute([$idCompra, $i['idProducto'], $i['cantidad'], $i['precio']]);
        $stmtStock->execute([$i['cantidad'], $i['idProducto']]);
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Compra registrada correctamente"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>