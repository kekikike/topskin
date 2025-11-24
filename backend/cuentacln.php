<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'connection/connection.php';


try {
    $pdo = Conexion::conectar();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $ci = $_GET['ci'] ?? '';
        if (!$ci) {
            echo json_encode(['success'=>false,'message'=>'CI no especificado']);
            exit;
        }

        // === USUARIO ===
        $stmt = $pdo->prepare("SELECT ciCliente,nombre1,nombre2,apellidoP,apellidoM,correo,telefono,direccion FROM tclientes WHERE ciCliente=?");
        $stmt->execute([$ci]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            echo json_encode(['success'=>false,'message'=>'Cliente no encontrado']);
            exit;
        }

        // === COMPRAS ===
        $stmtVentas = $pdo->prepare("SELECT nFactura AS idVenta, fechaVenta, costoTotal AS total, estado FROM tventas WHERE idCliente=? ORDER BY fechaVenta DESC");
        $stmtVentas->execute([$ci]);
        $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ventas as $i => $venta) {
            $ventas[$i]['total'] = (float)$venta['total'];
            $stmtDet = $pdo->prepare("
                SELECT d.idDetalleVenta, d.idProducto, p.nombre AS nombreProducto, d.cantidad, d.subtotal, p.foto
                FROM tdetalleventa d
                JOIN tproductos p ON p.idProducto = d.idProducto
                WHERE d.idVenta = ?
            ");
            $stmtDet->execute([$venta['idVenta']]);
            $detalle = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

            // Normalizamos URL de imágenes en el historial
            foreach ($detalle as $k => $prod) {
                $detalle[$k]['subtotal'] = (float)$prod['subtotal'];
                if (!preg_match('/^https?:\/\//', $prod['foto'])) {
                    $detalle[$k]['foto'] = '../../media/' . $prod['foto'];
                }
            }

            $ventas[$i]['detalle'] = $detalle;

            // Formatear fecha a YYYY-MM-DD
            $ventas[$i]['fecha'] = date('Y-m-d', strtotime($venta['fechaVenta']));
        }

        // === CARRITO ===
        $sessionCarrito = $_SESSION['carrito'][$ci] ?? [];
        $carrito = [];
        if (!empty($sessionCarrito)) {
            $ids = array_keys($sessionCarrito);
            $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
            $stmtProd = $pdo->prepare("SELECT idProducto, nombre, precioUnitario, foto FROM tproductos WHERE idProducto IN ($placeholders) AND estado=1");
            $stmtProd->execute($ids);
            while ($row = $stmtProd->fetch(PDO::FETCH_ASSOC)) {
                $row['cantidad'] = $sessionCarrito[$row['idProducto']];
                $row['precioUnitario'] = (float)$row['precioUnitario'];
                if (!preg_match('/^https?:\/\//', $row['foto'])) {
                    $row['foto'] = '../../media/' . $row['foto'];
                }
                $carrito[] = $row;
            }
        }

        echo json_encode([
            'success'=>true,
            'usuario'=>$usuario,
            'compras'=>$ventas,
            'carrito'=>$carrito
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['ciCliente'])) {
            echo json_encode(['success'=>false,'message'=>'Datos incompletos']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tclientes SET nombre1=?, nombre2=?, apellidoP=?, apellidoM=?, correo=?, telefono=?, direccion=? WHERE ciCliente=?");
        $ok = $stmt->execute([
            $input['nombre1'] ?? null,
            $input['nombre2'] ?? null,
            $input['apellidoP'] ?? null,
            $input['apellidoM'] ?? null,
            $input['correo'] ?? null,
            $input['telefono'] ?? null,
            $input['direccion'] ?? null,
            $input['ciCliente']
        ]);

        echo json_encode([
            'success'=>$ok,
            'message'=>$ok ? 'Datos actualizados' : 'Error al actualizar'
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
?>
