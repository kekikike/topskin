<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");

require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$nFactura = $input['nFactura'] ?? null;

if (!$nFactura) {
    echo json_encode(["success" => false, "message" => "Falta factura"]);
    exit;
}

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT 
            dv.cantidad,
            dv.subtotal,
            p.nombre AS nombreProducto,
            p.precioUnitario
        FROM tdetalleVenta dv
        JOIN tproductos p ON dv.idProducto = p.idProducto
        WHERE dv.idVenta = ? AND dv.estado = 1
    ");
    $stmt->execute([$nFactura]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "productos" => $productos
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error del servidor"]);
}
?>