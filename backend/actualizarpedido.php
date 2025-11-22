<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");

require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$idPedido = $input['idPedido'] ?? null;
$estado = $input['estado'] ?? null;
$ciEmpleado = $input['ciEmpleado'] ?? null;

if (!$idPedido || !$estado || !$ciEmpleado || !in_array($estado, ['P','E','C'])) {
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        UPDATE tpedidos 
        SET estadoEntrega = ?, usuarioA = ? 
        WHERE idPedido = ? AND estado = 1
    ");
    $stmt->execute([$estado, $ciEmpleado, $idPedido]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Estado actualizado"]);
    } else {
        echo json_encode(["success" => false, "message" => "Pedido no encontrado"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error del servidor"]);
}
?>