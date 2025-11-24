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
$ci = $_GET['ci'] ?? null; // o vía POST
if (!$ci) {
    echo json_encode(['success'=>false,'message'=>'CI no especificado']);
    exit;
}

// Inicializar carrito por cliente
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
if (!isset($_SESSION['carrito'][$ci])) {
    $_SESSION['carrito'][$ci] = [];
}

// Guardar cantidades
foreach ($input as $item) {
    $id = $item['idProducto'] ?? $item['id'] ?? null;
    $cantidad = max(1, intval($item['cantidad'] ?? 1));

    if ($id) {
        $_SESSION['carrito'][$ci][$id] = $cantidad;
    }
}

$totalItems = array_sum($_SESSION['carrito'][$ci]);

echo json_encode([
    'success' => true,
    'totalItems' => $totalItems,
    'debug' => $_SESSION['carrito'][$ci]
]);

