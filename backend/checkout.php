<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    echo json_encode(['success'=>false, 'message'=>'Carrito vacío']);
    exit;
}



$_SESSION['carrito'] = [];

echo json_encode(['success'=>true, 'message'=>'Compra realizada']);
