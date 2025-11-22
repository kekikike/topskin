<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'connection/connection.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Conexion::conectar();

    if ($method === 'GET') {
        $ciCliente = $_GET['ci'] ?? '';
        if (!$ciCliente) {
            echo json_encode(['success'=>false,'message'=>'Cliente no especificado']);
            exit;
        }

        // Datos del cliente
        $stmt = $pdo->prepare("SELECT ciCliente, nombre1, nombre2, apellidoP, apellidoM, correo, telefono, direccion FROM tclientes WHERE ciCliente = ?");
        $stmt->execute([$ciCliente]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Historial de compras
        $stmt2 = $pdo->prepare("SELECT idCompra, idProveedor, fechaA FROM tcompras WHERE idPersonal=? AND estado=1 ORDER BY fechaA DESC");
        $stmt2->execute([$ciCliente]);
        $compras = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if ($usuario) {
            echo json_encode(['success'=>true, 'usuario'=>$usuario, 'compras'=>$compras]);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Usuario no encontrado']);
        }
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['ciCliente'])) {
            echo json_encode(['success'=>false,'message'=>'Datos incompletos']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tclientes SET nombre1=?, nombre2=?, apellidoP=?, apellidoM=?, correo=?, telefono=?, direccion=? WHERE ciCliente=?");
        $stmt->execute([
            $input['nombre1'],
            $input['nombre2'],
            $input['apellidoP'],
            $input['apellidoM'],
            $input['correo'],
            $input['telefono'],
            $input['direccion'],
            $input['ciCliente']
        ]);
        echo json_encode(['success'=>true,'message'=>'Datos actualizados']);
        exit;
    }
} catch(Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error de servidor: '.$e->getMessage()]);
}
