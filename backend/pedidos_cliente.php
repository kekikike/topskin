<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$ciCliente = $input['ciCliente'] ?? null;
$desde = $input['desde'] ?? '';
$hasta = $input['hasta'] ?? '';

if (!$ciCliente) {
    echo json_encode(["success"=>false, "message"=>"No autenticado"]);
    exit;
}

try {
    $pdo = Conexion::conectar();

    $sql = "
        SELECT v.nFactura, v.fechaVenta, v.costoTotal, v.idCliente, v.idPersonal,
               p.estadoEntrega, p.fechaEntrega
        FROM tventas v
        LEFT JOIN tpedidos p ON p.idVenta = v.nFactura
        WHERE v.idCliente = ?
    ";

    $params = [$ciCliente];

    if ($desde !== '') {
        $sql .= " AND DATE(v.fechaVenta) >= ?";
        $params[] = $desde;
    }
    if ($hasta !== '') {
        $sql .= " AND DATE(v.fechaVenta) <= ?";
        $params[] = $hasta;
    }

    $sql .= " ORDER BY v.fechaVenta DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traer detalles de cada venta
    foreach ($ventas as &$v) {
        $stmt2 = $pdo->prepare("
            SELECT d.idDetalleVenta, d.idProducto, d.cantidad, d.subtotal, pr.nombre AS nombreProducto
            FROM tdetalleventa d
            LEFT JOIN tproductos pr ON pr.idProducto = d.idProducto
            WHERE d.idVenta = ?
        ");
        $stmt2->execute([$v['nFactura']]);
        $v['detalle'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(["success"=>true, "pedidos"=>$ventas]);

} catch(Exception $e) {
    echo json_encode(["success"=>false, "message"=>"Error del servidor"]);
}
?>
