<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$ciEmpleado = $input['ciEmpleado'] ?? null;

if (!$ciEmpleado) {
    echo json_encode(["tiene" => false, "turnos" => []]);
    exit;
}

$hoy = new DateTime();
$lunesProxima = clone $hoy;
$lunesProxima->modify('next monday');

$pdo = Conexion::conectar();
$stmt = $pdo->prepare("
    SELECT dia, inicio, fin 
    FROM thorarioPersonal 
    WHERE idEmpleado = ? 
      AND fechaTurno >= ? 
      AND fechaTurno <= DATE_ADD(?, INTERVAL 6 DAY)
    ORDER BY fechaTurno, inicio
");
$stmt->execute([$ciEmpleado, $lunesProxima->format('Y-m-d'), $lunesProxima->format('Y-m-d')]);
$turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$turnosFormateados = array_map(function($t) {
    $dia = $t['dia'] === 'Miercoles' ? 'Miércoles' : $t['dia'];
    return [
        'dia' => $dia,
        'inicio' => substr($t['inicio'], 0, 5),
        'fin' => substr($t['fin'], 0, 5)
    ];
}, $turnos);

echo json_encode([
    "tiene" => count($turnos) > 0,
    "turnos" => $turnosFormateados
]);
?>