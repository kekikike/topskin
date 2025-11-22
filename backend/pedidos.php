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

$estado = $input['estado'] ?? '';
$desde = $input['desde'] ?? '';
$hasta = $input['hasta'] ?? '';

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("SELECT CONCAT(nombre1,' ',apellidoP) AS nombre FROM templeados WHERE ciEmpleado = ?");
    $stmt->execute([$ciEmpleado]);
    $emp = $stmt->fetch();
    $empleadoNombre = $emp['nombre'] ?? 'Empleado';

    $sql = "
        SELECT 
            p.idPedido, p.idVenta AS nFactura, p.estadoEntrega, p.fechaEntrega,
            v.fechaVenta,
            c.nombre1, c.apellidoP
        FROM tpedidos p
        JOIN tventas v ON p.idVenta = v.nFactura
        LEFT JOIN tclientes c ON v.idCliente = c.ciCliente
        WHERE p.estado = 1 AND v.idPersonal = ?
    ";

    $params = [$ciEmpleado];

    if ($estado !== '') {
        $sql .= " AND p.estadoEntrega = ?";
        $params[] = $estado;
    }
    if ($desde !== '') {
        $sql .= " AND DATE(v.fechaVenta) >= ?";
        $params[] = $desde;
    }
    if ($hasta !== '') {
        $sql .= " AND DATE(v.fechaVenta) <= ?";
        $params[] = $hasta;
    }

    $sql .= " ORDER BY v.fechaVenta DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pedidos as &$p) {
        $p['nombreCliente'] = trim(($p['nombre1'] ?? '') . ' ' . ($p['apellidoP'] ?? ''));
        if (empty($p['nombreCliente'])) $p['nombreCliente'] = 'Cliente genérico';
    }

    echo json_encode([
        "success" => true,
        "pedidos" => $pedidos,
        "empleadoNombre" => $empleadoNombre
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error del servidor"]);
}
?>