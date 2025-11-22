<?php
require_once 'connection/connection.php';

$pdo = Conexion::conectar();

try {
    $stmt = $pdo->query("SELECT idProducto AS id, nombre, precioUnitario AS precio, foto AS foto, idMarca AS marca FROM tproductos WHERE estado=1 ORDER BY idProducto DESC LIMIT 12");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $productos = [];
    error_log("Error: " . $e->getMessage());
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($productos, JSON_UNESCAPED_UNICODE);
exit;

