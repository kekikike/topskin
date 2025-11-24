<?php
header('Content-Type: application/json');
require_once 'connection/connection.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = Conexion::conectar();

    // 1. Solo vendedores (rol VND) de la sucursal
    if ($action === 'vendedores') {
        $sucursal = $_GET['sucursal'] ?? '';
        $sql = "SELECT e.ciEmpleado, 
                       CONCAT(e.nombre1,' ',COALESCE(e.nombre2,''),' ',e.apellidoP,' ',e.apellidoM) AS nombreCompleto
                FROM templeados e 
                WHERE e.idSucursal = ? AND e.idRol = 'VND' AND e.estado = 1
                ORDER BY e.apellidoP";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sucursal]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Ventas sin asignar
    if ($action === 'ventas') {
        $sql = "SELECT nFactura, fechaVenta, idCliente, costoTotal, IVA, idPersonal
                FROM tventas 
                WHERE idPersonal = '1' OR idPersonal IS NULL OR idPersonal = ''
                ORDER BY fechaVenta DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    
    if ($action === 'detalle') {
        $factura = $_GET['factura'] ?? 0;

        $sql = "SELECT 
                    p.idProducto,
                    p.nombre,
                    p.foto,
                    d.cantidad,
                    p.precioUnitario AS precioUnitario        -- AQUÍ ESTÁ EL PRECIO REAL
                FROM tdetalleventa d
                INNER JOIN tproductos p ON d.idProducto = p.idProducto
                WHERE d.idVenta = ?";  

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$factura]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Corrección de URLs de fotos (opcional pero recomendado)
        foreach ($productos as &$p) {
            if (empty($p['foto']) || $p['foto'] === '' || $p['foto'] === null) {
                $p['foto'] = 'https://via.placeholder.com/150?text=Sin+Foto';
            } elseif (!filter_var($p['foto'], FILTER_VALIDATE_URL)) {
                // Si es ruta relativa
                $p['foto'] = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI'], 2) . '/' . ltrim($p['foto'], '/');
            }
        }

        echo json_encode($productos);
        exit;
    }

    // 4. Asignar venta
    if ($action === 'asignar') {
        $nFactura = $input['nFactura'] ?? '';
        $ciEmpleado = $input['ciEmpleado'] ?? '';
        if (!$nFactura || !$ciEmpleado) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE tventas SET idPersonal = ? WHERE nFactura = ?");
        $ok = $stmt->execute([$ciEmpleado, $nFactura]);
        echo json_encode(['success' => $ok]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}