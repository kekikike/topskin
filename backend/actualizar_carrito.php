<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'connection/connection.php';  
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    $input = [];
} elseif (!is_array($input)) {
    $input = [];  
}

$_SESSION['carrito'] = [];

foreach ($input as $item) {
    $id = intval($item['idProducto'] ?? 0);
    $cantidad = max(1, intval($item['cantidad'] ?? 1));
    if ($id > 0) {
        $_SESSION['carrito'][$id] = $cantidad;
    }
}

$totalItems = array_sum($_SESSION['carrito']);

echo json_encode([
    'success' => true,
    'totalItems' => $totalItems,
    'debug' => $_SESSION['carrito']  
]);
?>