<?php
// ================================================
// reporte.php - ARCHIVO ÚNICO (todo incluido)
// Funciona solo, sin dependencias externas
// ================================================

header('Content-Type: application/json; charset=utf-8');
ob_start(); // Evita cualquier salida accidental antes del JSON

try {
    // ================= CONFIGURACIÓN DE LA BASE DE DATOS =================
    $host = 'localhost';
    $user = 'root';           // Cambia si tu usuario es diferente
    $pass = '';               // Pon aquí tu contraseña si tienes una
    $db   = 'topskin';        // Nombre exacto de tu base de datos

    $con = mysqli_connect($host, $user, $pass, $db);
    
    if (!$con) {
        throw new Exception('No se pudo conectar a MySQL: ' . mysqli_connect_error());
    }

    mysqli_set_charset($con, 'utf8mb4');

    // ================= PARÁMETROS =================
    $action = $_GET['action'] ?? '';
    if ($action !== 'reporte') {
        echo json_encode(['error' => 'Acción no válida']);
        exit;
    }

    $month = !empty($_GET['month']) ? (int)$_GET['month'] : '';
    $year  = date('Y'); // Año actual (puedes cambiarlo si quieres)

    if ($month !== '' && ($month < 1 || $month > 12)) {
        echo json_encode(['error' => 'Mes inválido']);
        exit;
    }

    // ================= CONDICIÓN WHERE =================
    $where = "v.estado = 1 AND dv.estado = 1 AND YEAR(v.fechaVenta) = $year";
    if ($month !== '') {
        $where .= " AND MONTH(v.fechaVenta) = $month";
    }

    // ================= CONSULTA DE VENTAS =================
    $sql = "SELECT 
                v.nFactura,
                v.fechaVenta,
                v.costoTotal,
                CONCAT(IFNULL(c.nombre1,''), ' ', IFNULL(c.apellidoP,'')) AS nombreCliente,
                dv.idDetalleVenta,
                dv.cantidad,
                dv.subtotal,
                p.nombre AS nombreProducto
            FROM tventas v
            LEFT JOIN tclientes c ON v.idCliente = c.ciCliente
            INNER JOIN tdetalleVenta dv ON v.nFactura = dv.idVenta
            INNER JOIN tproductos p ON dv.idProducto = p.idProducto
            WHERE $where
            ORDER BY v.fechaVenta DESC, v.nFactura DESC";

    $result = mysqli_query($con, $sql);
    if (!$result) {
        throw new Exception('Error en ventas: ' . mysqli_error($con));
    }

    $ventas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['nFactura'];
        if (!isset($ventas[$id])) {
            $ventas[$id] = [
                'nFactura'     => $id,
                'fechaVenta'   => $row['fechaVenta'],
                'costoTotal'   => number_format((float)$row['costoTotal'], 2, '.', ''),
                'nombreCliente'=> trim($row['nombreCliente']) ?: 'Cliente no registrado',
                'detalles'     => []
            ];
        }
        $ventas[$id]['detalles'][] = [
            'idDetalleVenta'=> $row['idDetalleVenta'],
            'nombreProducto'=> $row['nombreProducto'],
            'cantidad'      => (int)$row['cantidad'],
            'subtotal'      => number_format((float)$row['subtotal'], 2, '.', '')
        ];
    }
    $ventas = array_values($ventas);

    // ================= TOP 6 PRODUCTOS =================
    $sql_top = "SELECT 
                    p.nombre,
                    SUM(dv.cantidad) AS total_vendido
                FROM tdetalleVenta dv
                JOIN tventas v ON dv.idVenta = v.nFactura
                JOIN tproductos p ON dv.idProducto = p.idProducto
                WHERE $where
                GROUP BY p.idProducto
                ORDER BY total_vendido DESC
                LIMIT 6";

    $result_top = mysqli_query($con, $sql_top);
    if (!$result_top) {
        throw new Exception('Error en top 6: ' . mysqli_error($con));
    }

    $top6 = [];
    while ($row = mysqli_fetch_assoc($result_top)) {
        $top6[] = [
            'nombre'        => $row['nombre'],
            'total_vendido' => (int)$row['total_vendido']
        ];
    }

    // ================= RESPUESTA FINAL (JSON LIMPIO) =================
    ob_end_clean(); // Limpia cualquier cosa antes del JSON
    echo json_encode([
        'ventas' => $ventas,
        'top6'   => $top6
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Si algo falla, siempre devolvemos JSON válido (nunca HTML)
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error interno del servidor',
        'detalle' => $e->getMessage()
    ]);
}

exit;
?>