<?php
session_start();
require_once 'connection/connection.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    echo json_encode([]);
    exit;
}

$ids = array_keys($_SESSION['carrito']);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$sql = "SELECT idProducto, nombre, precioUnitario, foto 
        FROM tproductos 
        WHERE idProducto IN ($placeholders) AND estado = 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(str_repeat('s', count($ids)), ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$carrito = [];
while ($row = $result->fetch_assoc()) {
    $row['cantidad'] = $_SESSION['carrito'][$row['idProducto']];
    $row['precioUnitario'] = (float)$row['precioUnitario'];
    $carrito[] = $row;
}

echo json_encode($carrito);
?>
