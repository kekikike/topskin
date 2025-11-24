<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");

require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$ciEmpleado = $input['ciEmpleado'] ?? null;
$turnos = $input['turnos'] ?? [];

if (!$ciEmpleado || !is_array($turnos) || count($turnos) < 5 || count($turnos) > 12) {
    echo json_encode(["success" => false, "message" => "Debes seleccionar entre 5 y 12 turnos"]);
    exit;
}

$hoy = new DateTime();
$lunesProxima = clone $hoy;
$lunesProxima->modify('next monday'); 

try {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    $check = $pdo->prepare("
        SELECT COUNT(*) FROM thorarioPersonal 
        WHERE idEmpleado = ? 
          AND fechaTurno >= ? 
          AND fechaTurno <= DATE_ADD(?, INTERVAL 6 DAY)
    ");
    $check->execute([$ciEmpleado, $lunesProxima->format('Y-m-d'), $lunesProxima->format('Y-m-d')]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Ya elegiste horarios para la próxima semana. Espera confirmación del supervisor."]);
        exit;
    }

    $pdo->prepare("
        DELETE FROM thorarioPersonal 
        WHERE idEmpleado = ? 
          AND fechaTurno >= ? 
          AND estadoSolicitud = 'pendiente'
    ")->execute([$ciEmpleado, $lunesProxima->format('Y-m-d')]);

    $stmt = $pdo->prepare("
        INSERT INTO thorarioPersonal 
        (idHorarioPersonal, inicio, fin, dia, fechaTurno, idEmpleado, usuarioA, estado, estadoSolicitud) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'pendiente')
    ");

    foreach ($turnos as $t) {
        $diaDB = $t['dia'] === 'Miércoles' ? 'Miercoles' : $t['dia'];
        $fechaDia = clone $lunesProxima;
        $diasMap = ['Lunes'=>0, 'Martes'=>1, 'Miércoles'=>2, 'Jueves'=>3, 'Viernes'=>4, 'Sábado'=>5, 'Domingo'=>6];
        $fechaDia->modify('+' . $diasMap[$t['dia']] . ' days');

        $stmt->execute([
            substr(md5(uniqid()), 0, 5),
            $t['inicio'], $t['fin'],
            $diaDB,
            $fechaDia->format('Y-m-d'),
            $ciEmpleado,
            $ciEmpleado
        ]);
    }

    $pdo->commit();
    echo json_encode([
        "success" => true,
        "message" => "¡Horario enviado! Espera la aprobación de tu supervisor."
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Error del servidor"]);
}
?>