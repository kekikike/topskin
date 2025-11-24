<?php
header('Content-Type: application/json');

// Ajusta esta ruta según tu estructura
require_once 'connection/connection.php';

$action = $_GET['action'] ?? '';

if ($action !== 'listar') {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida']);
    exit;
}

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->query("
        SELECT 
            s.idSucursal,
            s.nombre,
            s.direccion,
            s.X,
            s.Y,
            s.telefono,
            s.horaIni,
            s.horaFin,
            COALESCE(c.nombre, 'Ciudad no asignada') AS ciudad
        FROM tsucursales s
        LEFT JOIN tciudades c ON s.idCiudad = c.idCiudad
        WHERE s.estado = 1
        ORDER BY s.nombre
    ");

    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar datos: quitar segundos de la hora y forzar +591
    foreach ($sucursales as &$s) {
        $s['horaIni'] = substr($s['horaIni'] ?? '08:00:00', 0, 5);
        $s['horaFin'] = substr($s['horaFin'] ?? '20:00:00', 0, 5);
        $s['telefono'] = trim($s['telefono']); // por si tiene espacios
    }
    unset($s); // buena práctica

    echo json_encode($sucursales);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en la base de datos',
        'detalle' => $e->getMessage()
    ]);
}
?>