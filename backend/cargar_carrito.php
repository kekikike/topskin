<?php
session_start();
require_once 'connection/connection.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = Conexion::conectar(); 

if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    echo json_encode([]);
    exit;
}

$ids = array_keys($_SESSION['carrito']);
$placeholders = rtrim(str_repeat('?,', count($ids)), ',');

$sql = "SELECT idProducto, nombre, precioUnitario, foto 
        FROM tproductos 
        WHERE idProducto IN ($placeholders) AND estado = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);

$carrito = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['cantidad'] = $_SESSION['carrito'][$row['idProducto']];
    $row['precioUnitario'] = (float)$row['precioUnitario'];
    $carrito[] = $row;
}

echo json_encode($carrito, JSON_UNESCAPED_UNICODE);
