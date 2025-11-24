<?php
// === EVITAR CUALQUIER SALIDA ANTES DEL JSON ===
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['productos']) || !isset($data['idCliente'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$productos = $data['productos'];
$idCliente = $data['idCliente'];
$idPersonal = '1';
$ivaPorcentaje = 0.13;

try {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    // Calcular subtotal
    $subtotal = 0;
    foreach ($productos as $p) {
        $subtotal += $p['precioUnitario'] * $p['cantidad'];
    }
    $iva = $subtotal * $ivaPorcentaje;
    $costoTotal = $subtotal + $iva;

    // Insertar venta
    $sql = "INSERT INTO tventas 
            (fechaVenta, idPersonal, idCliente, costoTotal, IVA, usuarioA) 
            VALUES (NOW(), ?, ?, ?, ?, '1')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idPersonal, $idCliente, $costoTotal, $iva]);
    $nFactura = $pdo->lastInsertId();

    // Insertar detalle y restar stock
    $sqlDetalle = "INSERT INTO tdetalleVenta 
                   (idVenta, idProducto, cantidad, subtotal, usuarioA) 
                   VALUES (?, ?, ?, ?, '1')";
    $stmtDetalle = $pdo->prepare($sqlDetalle);

    $sqlStock = "UPDATE tproductos SET stock = stock - ? WHERE idProducto = ? AND stock >= ?";
    $stmtStock = $pdo->prepare($sqlStock);

    foreach ($productos as $p) {
        $subtotalItem = $p['precioUnitario'] * $p['cantidad'];

        // Insertar detalle
        $stmtDetalle->execute([$nFactura, $p['idProducto'], $p['cantidad'], $subtotalItem]);

        // Restar stock (solo si hay suficiente)
        $stmtStock->execute([$p['cantidad'], $p['idProducto'], $p['cantidad']]);
        
        if ($stmtStock->rowCount() === 0) {
            throw new Exception("Stock insuficiente para el producto: " . $p['idProducto']);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '¡Compra realizada con éxito!',
        'nFactura' => $nFactura,
        'subtotal' => number_format($subtotal, 2),
        'iva' => number_format($iva, 2),
        'total' => number_format($costoTotal, 2)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
?>