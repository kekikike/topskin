<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$idVenta = $input['idVenta'] ?? null;

if (!$idVenta) {
    echo json_encode(["success" => false, "message" => "Falta idVenta"]);
    exit;
}

try {
    $pdo = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT tipoPago, monto FROM tdetallepagos WHERE idVenta = ? AND estado = 1");
    $stmt->execute([$idVenta]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "pagos" => $pagos]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error"]);
}
?>