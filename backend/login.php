<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'connection/connection.php';

$correo = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

if(!$correo || !$contrasena){
    echo json_encode(['success'=>false, 'message'=>'Todos los campos son obligatorios']);
    exit;
}

try {
    $pdo = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT ciCliente, nombre1, nombre2, apellidoP, apellidoM, correo, telefono, direccion FROM tclientes WHERE correo=:correo AND contrasena=:contrasena AND estado=1 LIMIT 1");
    $stmt->execute(['correo'=>$correo, 'contrasena'=>$contrasena]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if($usuario){
        echo json_encode(['success'=>true, 'usuario'=>$usuario]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Correo o contraseña incorrectos']);
    }

} catch(Exception $e) {
    echo json_encode(['success'=>false, 'message'=>'Error de servidor: ' . $e->getMessage()]);
}
