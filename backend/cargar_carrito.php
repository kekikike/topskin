<?php
session_start();
require_once 'connection/connection.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = Conexion::conectar();

// Obtener CI del cliente (por GET)
$ci = $_GET['ci'] ?? '';
if (!$ci) {
    echo json_encode([]);
    exit;
}

// Verificar si el carrito existe para este cliente
$sessionCarrito = $_SESSION['carrito'][$ci] ?? [];
if (empty($sessionCarrito)) {
    echo json_encode([]);
    exit;
}

// Preparar consulta para productos en el carrito
$ids = array_keys($sessionCarrito);
$placeholders = rtrim(str_repeat('?,', count($ids)), ',');

$sql = "SELECT p.idProducto, p.nombre, p.precioUnitario, p.foto, m.nombre AS marca
        FROM tproductos p
        LEFT JOIN tmarcas m ON p.idMarca = m.idMarca
        WHERE p.idProducto IN ($placeholders) AND p.estado = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);

$carrito = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['cantidad'] = $sessionCarrito[$row['idProducto']];
    $row['precioUnitario'] = (float)$row['precioUnitario'];

    // Normalizar URL de imagen
    if (!empty($row['foto']) && !preg_match('/^https?:\/\//', $row['foto'])) {
        $row['foto'] = '../../media/' . $row['foto'];
    }

    $carrito[] = $row;
}

echo json_encode($carrito, JSON_UNESCAPED_UNICODE);
