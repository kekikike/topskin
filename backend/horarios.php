<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once 'connection/connection.php';

// LEEMOS EL JSON DEL BODY (IMPORTANTE!)
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = Conexion::conectar();

    if ($action === 'pendientes') {
        $sucursal = $_GET['sucursal'] ?? '';
        if (!$sucursal) {
            echo json_encode([]);
            exit;
        }

        $sql = "SELECT 
                    h.idHorarioPersonal,
                    h.inicio, h.fin, h.dia, h.fechaTurno,
                    h.estadoSolicitud,
                    e.ciEmpleado,
                    CONCAT(
                        COALESCE(e.nombre1,''), ' ',
                        COALESCE(e.nombre2,''), ' ',
                        COALESCE(e.apellidoP,''), ' ',
                        COALESCE(e.apellidoM,'')
                    ) AS nombreEmpleado
                FROM thorariopersonal h
                INNER JOIN templeados e ON h.idEmpleado = e.ciEmpleado
                WHERE e.idSucursal = :sucursal
                  AND h.estado = 1
                  AND h.estadoSolicitud = 'pendiente'
                ORDER BY h.fechaTurno DESC, h.inicio";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':sucursal' => $sucursal]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($result);
        exit;
    }

    if ($action === 'actualizar_estado') {
        // YA NO NECESITAS LEER DE NUEVO, YA TENEMOS $input
        $idHorario = $input['idHorarioPersonal'] ?? '';
        $estado = $input['estadoSolicitud'] ?? '';
        $usuarioA = $input['usuarioA'] ?? 'ADMIN';

        if (!in_array($estado, ['aprobado', 'rechazado'])) {
            echo json_encode(['success' => false, 'message' => 'Estado inválido']);
            exit;
        }

        $sql = "UPDATE thorariopersonal 
                SET estadoSolicitud = :estado, usuarioA = :usuarioA, fechaA = NOW()
                WHERE idHorarioPersonal = :id";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ':estado' => $estado,
            ':usuarioA' => $usuarioA,
            ':id' => $idHorario
        ]);

        echo json_encode(['success' => $ok]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}