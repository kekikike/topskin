<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$idCompra = $input['idCompra'] ?? null;
if (!$idCompra) { echo json_encode(["success"=>false,"message"=>"Falta ID"]); exit; }

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("SELECT c.idCompra, c.fechaCompra, c.totalcompra AS total, p.nombre AS proveedor
                           FROM tcompras c
                           LEFT JOIN tproveedores p ON c.idProveedor = p.idProveedor
                           WHERE c.idCompra = ? AND c.estado = 1");
    $stmt->execute([$idCompra]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT d.*, pr.nombre FROM tdetallecompra d
                           LEFT JOIN tproductos pr ON d.idProducto = pr.idProducto
                           WHERE d.idCompra = ?");
    $stmt->execute([$idCompra]);
    $compra['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($compra);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>