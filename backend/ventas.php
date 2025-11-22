<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");

require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$ciEmpleado = $input['ciEmpleado'] ?? null;

if (!$ciEmpleado) {
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit;
}

$cliente = trim($input['cliente'] ?? '');
$empleado = trim($input['empleado'] ?? '');
$desde = $input['desde'] ?? '';
$hasta = $input['hasta'] ?? '';

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT CONCAT(nombre1,' ',IFNULL(nombre2,''),' ',apellidoP,' ',IFNULL(apellidoM,'')) AS nombre 
        FROM templeados WHERE ciEmpleado = ?
    ");
    $stmt->execute([$ciEmpleado]);
    $emp = $stmt->fetch();
    $nombreEmpleadoLogueado = $emp['nombre'] ?? 'Empleado';

    $sql = "
        SELECT 
            v.nFactura,
            v.fechaVenta,
            v.costoTotal,
            c.nombre1 AS clienteNombre1,
            c.apellidoP AS clienteApellidoP,
            e.nombre1 AS empNombre1,
            e.apellidoP AS empApellidoP,
            (SELECT COUNT(*) FROM tdetalleVenta dv WHERE dv.idVenta = v.nFactura) AS cantidadProductos
        FROM tventas v
        LEFT JOIN tclientes c ON v.idCliente = c.ciCliente
        LEFT JOIN templeados e ON v.idPersonal = e.ciEmpleado
        WHERE v.estado = 1
          AND v.idPersonal = ?  -- Solo ventas del empleado logueado
    ";

    $params = [$ciEmpleado];

    if ($cliente !== '') {
        $sql .= " AND (c.nombre1 LIKE ? OR c.apellidoP LIKE ?)";
        $params[] = "%$cliente%";
        $params[] = "%$cliente%";
    }
    if ($empleado !== '') {
        $sql .= " AND (e.nombre1 LIKE ? OR e.apellidoP LIKE ?)";
        $params[] = "%$empleado%";
        $params[] = "%$empleado%";
    }
    if ($desde !== '') {
        $sql .= " AND DATE(v.fechaVenta) >= ?";
        $params[] = $desde;
    }
    if ($hasta !== '') {
        $sql .= " AND DATE(v.fechaVenta) <= ?";
        $params[] = $hasta;
    }

    $sql .= " ORDER BY v.fechaVenta DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ventas as &$v) {
        $v['nombreCliente'] = trim(($v['clienteNombre1'] ?? '') . ' ' . ($v['clienteApellidoP'] ?? ''));
        $v['nombreEmpleado'] = trim(($v['empNombre1'] ?? '') . ' ' . ($v['empApellidoP'] ?? ''));
        if (empty($v['nombreCliente'])) $v['nombreCliente'] = 'Cliente genérico';
    }

    echo json_encode([
        "success" => true,
        "ventas" => $ventas,
        "empleadoNombre" => $nombreEmpleadoLogueado
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error del servidor"]);
}
?>