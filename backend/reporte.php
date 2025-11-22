<?php
// Desactiva el reporte de errores de MySQLi para que no envíe Warnings o Notices en HTML
mysqli_report(MYSQLI_REPORT_OFF); 

require_once "connection/connection.php"; 
header('Content-Type: application/json; charset=utf-8');

// --- Verificación de Conexión ---
if (mysqli_connect_errno()) {
    echo json_encode(['error' => 'Fallo al conectar a MySQL: ' . mysqli_connect_error()]);
    exit;
}
// ---------------------------------

$action = $_GET['action'] ?? '';

if ($action === 'reporte') {
    $month = $_GET['month'] ?? '';
    $currentYear = date('Y'); // Obtener el año actual

    // Condición base, usando el alias 'v' para tventas
    $where = "v.estado = 1 AND YEAR(v.fechaVenta) = $currentYear"; 
    
    if ($month != '') {
        $where .= " AND MONTH(v.fechaVenta) = " . mysqli_real_escape_string($con, $month);
    }

    // Consulta de Ventas completas con detalles
    $sql_ventas = "
        SELECT 
            v.nFactura, v.fechaVenta, v.costoTotal,
            CONCAT(c.nombre1,' ',c.apellidoP) AS nombreCliente,
            dv.idDetalleVenta, dv.idProducto, dv.cantidad, dv.subtotal,
            p.nombre AS nombreProducto
        FROM tventas v
        LEFT JOIN tclientes c ON v.idCliente = c.ciCliente
        INNER JOIN tdetalleVenta dv ON v.nFactura = dv.idVenta
        INNER JOIN tproductos p ON dv.idProducto = p.idProducto
        WHERE $where AND dv.estado = 1
        ORDER BY v.fechaVenta DESC
    ";
    $res_ventas = mysqli_query($con, $sql_ventas);

    if (!$res_ventas) {
        echo json_encode(['error' => 'Error al consultar ventas: ' . mysqli_error($con), 'sql' => $sql_ventas]);
        exit;
    }

    $ventas = [];
    while ($row = mysqli_fetch_assoc($res_ventas)) {
        $nFactura = $row['nFactura'];
        if (!isset($ventas[$nFactura])) {
            $ventas[$nFactura] = [
                'nFactura' => $row['nFactura'],
                'fechaVenta' => $row['fechaVenta'],
                'costoTotal' => $row['costoTotal'],
                'nombreCliente' => $row['nombreCliente'] ?? 'Cliente no registrado',
                'detalles' => []
            ];
        }
        $ventas[$nFactura]['detalles'][] = [
            'idDetalleVenta' => $row['idDetalleVenta'],
            'nombreProducto' => $row['nombreProducto'],
            'cantidad' => (int)$row['cantidad'],
            'subtotal' => (float)$row['subtotal']
        ];
    }
    $ventas = array_values($ventas);

    // Consulta de Top 6 productos más vendidos (por cantidad)
    $sql_top = "
        SELECT 
            p.nombre,
            SUM(dv.cantidad) AS total_vendido
        FROM tdetalleVenta dv
        INNER JOIN tventas v ON dv.idVenta = v.nFactura
        INNER JOIN tproductos p ON dv.idProducto = p.idProducto
        WHERE $where AND dv.estado = 1 
        GROUP BY dv.idProducto, p.nombre
        ORDER BY total_vendido DESC
        LIMIT 6
    ";
    $res_top = mysqli_query($con, $sql_top);

    if (!$res_top) {
        echo json_encode(['error' => 'Error al consultar Top 6: ' . mysqli_error($con), 'sql' => $sql_top]);
        exit;
    }

    $top6 = [];
    while ($row = mysqli_fetch_assoc($res_top)) {
        $top6[] = [
            'nombre' => $row['nombre'],
            'total_vendido' => (int)$row['total_vendido']
        ];
    }

    echo json_encode([
        'ventas' => $ventas,
        'top6' => $top6
    ]);
    exit;
}

echo json_encode(['error' => 'Acción no válida']);
?>