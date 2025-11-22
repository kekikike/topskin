<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
require_once 'connection/connection.php';
$pdo = Conexion::conectar();

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$accion = $_REQUEST['accion'] ?? ($data['accion'] ?? '');

try {

    // === NUEVO: Obtener horario de apertura y cierre de la sucursal ===
    if ($accion === 'horario_sucursal') {
        $idSucursal = $_GET['idSucursal'] ?? '';
        if (!$idSucursal) {
            echo json_encode(['success' => false, 'message' => 'Falta idSucursal']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT horaInicio, horaFin FROM tsucursales WHERE idSucursal = ?");
        $stmt->execute([$idSucursal]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode([
                'success' => true,
                'apertura' => $row['horaInicio'] ?? '08:00',
                'cierre'   => $row['horaFin']    ?? '20:00'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sucursal no encontrada']);
        }
        exit;
    }

    // === GUARDAR HORARIOS (turnos) ===
    if ($accion === 'guardar') {
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'JSON inválido']);
            exit;
        }

        $ciEmpleado = $data['ciEmpleado'] ?? '';
        $horarios = $data['horarios'] ?? [];

        if (!$ciEmpleado || count($horarios) !== 7) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $pdo->beginTransaction();

        // Obtener último idHorarioPersonal
        $stmt = $pdo->query("SELECT idHorarioPersonal FROM thorarioPersonal ORDER BY idHorarioPersonal DESC LIMIT 1");
        $ultimo = $stmt->fetchColumn();
        $siguienteNumero = $ultimo ? intval(substr($ultimo, 1)) + 1 : 1;

        $buscar = $pdo->prepare("SELECT idHorarioPersonal FROM thorarioPersonal WHERE idEmpleado = ? AND dia = ?");
        $insertar = $pdo->prepare("INSERT INTO thorarioPersonal (idHorarioPersonal, dia, inicio, fin, idEmpleado, usuarioA, estado) VALUES (?, ?, ?, ?, ?, '1', ?)");
        $actualizar = $pdo->prepare("UPDATE thorarioPersonal SET inicio = ?, fin = ?, estado = ? WHERE idHorarioPersonal = ?");

        foreach ($horarios as $h) {
            $dia = $h['dia'];
            $inicio = !empty($h['inicio']) ? $h['inicio'] : null;
            $fin = !empty($h['fin']) ? $h['fin'] : null;
            $estado = ($inicio && $fin) ? 1 : 0;

            $buscar->execute([$ciEmpleado, $dia]);
            $idExistente = $buscar->fetchColumn();

            if ($estado == 1) {
                if ($idExistente) {
                    $actualizar->execute([$inicio, $fin, $estado, $idExistente]);
                } else {
                    $idHorario = 'H' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);
                    $siguienteNumero++;
                    $insertar->execute([$idHorario, $dia, $inicio, $fin, $ciEmpleado, $estado]);
                }
            } else {
                if ($idExistente) {
                    $actualizar->execute([null, null, 0, $idExistente]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Turnos guardados correctamente']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>