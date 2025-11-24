<?php
session_start();

$ci = $_GET['ci'] ?? null;
$id = $_GET['id'] ?? null;

if (!$ci || !$id) {
    echo json_encode(['success'=>false]);
    exit;
}

unset($_SESSION['carrito'][$ci][$id]);

echo json_encode(['success'=>true]);
