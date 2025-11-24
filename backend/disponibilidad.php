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

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("SELECT idSucursal FROM templeados WHERE ciEmpleado = ? AND estado = 1");
    $stmt->execute([$ciEmpleado]);
    $idSucursal = $stmt->fetchColumn();
    if (!$idSucursal) throw new Exception("Empleado no encontrado");

    $hoy = new DateTime();
    $lunesProxima = (clone $hoy)->modify('monday next week'); 

    $diasNombre = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $fechas = [];

    for ($i = 0; $i < 7; $i++) {
        $fecha = (clone $lunesProxima)->modify("+$i days")->format('Y-m-d');
        $fechas[$diasNombre[$i]] = $fecha;
    }

    $turnos = [
        't1' => ['inicio' => '08:00:00', 'fin' => '12:00:00'],
        't2' => ['inicio' => '12:00:00', 'fin' => '16:00:00'],
        't3' => ['inicio' => '16:00:00', 'fin' => '20:00:00']
    ];

    $disponibilidad = [];

    foreach ($fechas as $dia => $fechaTurno) {
        foreach ($turnos as $turnoId => $horario) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM thorariopersonal hp
                JOIN templeados e ON hp.idEmpleado = e.ciEmpleado
                WHERE hp.fechaTurno = ?
                  AND hp.inicio = ?
                  AND hp.fin = ?
                  AND e.idSucursal = ?
                  AND hp.estado = 1
                  AND hp.estadoSolicitud IN ('pendiente', 'aprobado')
            ");
            $stmt->execute([$fechaTurno, $horario['inicio'], $horario['fin'], $idSucursal]);
            $ocupados = (int)$stmt->fetchColumn();

            $disponibilidad[$dia][$turnoId] = $ocupados < 2;
        }
    }

    echo json_encode([
        "success" => true,
        "disponibilidad" => $disponibilidad,
        "semana" => $fechas  
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>