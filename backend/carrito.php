<?php
// Evitar warnings que rompan el JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
session_start();
require_once 'connection/connection.php';
header('Content-Type: application/json; charset=utf-8');

// Datos del empleado por defecto
$empleadoPorDefecto = ['ciEmpleado' => '1'];

// Leer datos enviados por fetch (JSON)
$datos = json_decode(file_get_contents('php://input'), true);
$cliente = $datos['cliente'] ?? null;
$productos = $datos['productos'] ?? $_SESSION['carrito'] ?? [];
$pagoMetodo = $datos['pago']['metodo'] ?? null;
$tarjeta = $datos['pago']['tarjeta'] ?? [];

// Validaciones
if (empty($productos)) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
    exit;
}
if (!$cliente) {
    echo json_encode(['success' => false, 'message' => 'Cliente no especificado']);
    exit;
}

$ivaPorcentaje = 0.13;

try {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    // Calcular subtotal y total
    $subtotal = 0;
    foreach ($productos as $p) {
        $subtotal += $p['precioUnitario'] * $p['cantidad'];
    }
    $iva = $subtotal * $ivaPorcentaje;
    $total = $subtotal + $iva;

    // --------------------------
    // Insertar venta
    // --------------------------
    $stmtVenta = $pdo->prepare(
        "INSERT INTO tventas (fechaVenta, idPersonal, idCliente, costoTotal, IVA, usuarioA) 
         VALUES (NOW(), ?, ?, ?, ?, ?)"
    );
    $stmtVenta->execute([
        $empleadoPorDefecto['ciEmpleado'],
        $cliente['ciCliente'] ?? $cliente['ci'],
        $total,
        $iva,
        $empleadoPorDefecto['ciEmpleado']
    ]);

    // Obtener el ID de la venta recién creada
    $nFactura = $pdo->lastInsertId();

    // --------------------------
    // Insertar detalle de pago
    // --------------------------
    $stmtPago = $pdo->prepare(
        "INSERT INTO tdetallepagos (idVenta, tipoPago, monto, usuarioA) VALUES (?, ?, ?, ?)"
    );
    $stmtPago->execute([
        $nFactura,
        $pagoMetodo,  // "tarjeta" o "qr"
        $total,
        $empleadoPorDefecto['ciEmpleado']
    ]);

    // --------------------------
    // Insertar detalle venta y actualizar stock
    // --------------------------
    $stmtDetalle = $pdo->prepare(
        "INSERT INTO tdetalleVenta (idVenta, idProducto, cantidad, subtotal, usuarioA) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmtStock = $pdo->prepare(
        "UPDATE tproductos SET stock = stock - ? WHERE idProducto = ? AND stock >= ?"
    );

    foreach ($productos as $p) {
        $subtotalItem = $p['precioUnitario'] * $p['cantidad'];

        // Insertar detalle
        $stmtDetalle->execute([$nFactura, $p['idProducto'], $p['cantidad'], $subtotalItem, $empleadoPorDefecto['ciEmpleado']]);

        // Actualizar stock
        $stmtStock->execute([$p['cantidad'], $p['idProducto'], $p['cantidad']]);
        if ($stmtStock->rowCount() === 0) {
            throw new Exception("Stock insuficiente para el producto: " . $p['idProducto']);
        }
    }

    $pdo->commit();

    // Limpiar carrito de sesión
    $_SESSION['carrito'] = [];

    echo json_encode([
        'success' => true,
        'message' => 'Compra realizada',
        'nFactura' => $nFactura,
        'subtotal' => number_format($subtotal, 2),
        'iva' => number_format($iva, 2),
        'total' => number_format($total, 2)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
