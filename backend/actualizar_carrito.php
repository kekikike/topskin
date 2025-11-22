<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'connection/connection.php';  
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = [];
}

// Inicializamos carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

foreach ($input as $item) {
    $id = $item['idProducto'] ?? $item['id'] ?? null;
    $cantidad = max(1, intval($item['cantidad'] ?? 1));

    if ($id !== null && $id !== '') {
        $_SESSION['carrito'][$id] = $cantidad;
    }

    // Debug
    file_put_contents('debug_ids.txt', print_r($item, true) . "\n", FILE_APPEND);
}

$totalItems = array_sum($_SESSION['carrito']);

echo json_encode([
    'success' => true,
    'totalItems' => $totalItems,
    'debug' => $_SESSION['carrito']  
]);
