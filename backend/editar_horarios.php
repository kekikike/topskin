<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$accion = $input['accion'] ?? $_GET['accion'] ?? $_POST['accion'] ?? '';

date_default_timezone_set('America/La_Paz');

try {
    $pdo = Conexion::conectar();

    // ==================================================================
    // SEMANA ACTUAL CORRECTA
    // ==================================================================
    $hoy = new DateTime();
    $diaSemana = $hoy->format('N');

    $lunes = clone $hoy;
    if ($diaSemana == 7) {
        $lunes->modify('next monday');
    } else {
        $lunes->modify('monday this week');
    }

    $domingo = clone $lunes;
    $domingo->modify('+6 days');

    $fechaInicio = $lunes->format('Y-m-d');
    $fechaFin = $domingo->format('Y-m-d');

    $diasNombre = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
    $fechas = [];
    $temp = clone $lunes;
    for ($i = 0; $i < 7; $i++) {
        $fechas[$diasNombre[$i]] = $temp->format('Y-m-d');
        $temp->modify('+1 day');
    }

    // ==================================================================
    // 1. DISPONIBILIDAD
    // ==================================================================
    if ($accion === 'disponibilidad') {
        $idSucursal = $input['idSucursal'] ?? '';
        if (!$idSucursal) throw new Exception("Falta idSucursal");

        $disp = [];
        $turnosDefinidos = [
            't1' => ['08:00:00', '12:00:00'],
            't2' => ['12:00:00', '16:00:00'],
            't3' => ['16:00:00', '20:00:00']
        ];

        foreach ($fechas as $dia => $fecha) {
            $disp[$dia] = [];
            foreach ($turnosDefinidos as $t => $h) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM thorarioPersonal hp
                    JOIN templeados e ON hp.idEmpleado = e.ciEmpleado
                    WHERE hp.fechaTurno = ? AND hp.inicio = ? AND hp.fin = ?
                      AND e.idSucursal = ? AND hp.estado = 1 AND hp.estadoSolicitud = 'aprobado'
                ");
                $stmt->execute([$fecha, $h[0], $h[1], $idSucursal]);
                $disp[$dia][$t] = $stmt->fetchColumn() < 2;
            }
        }

        echo json_encode(['success' => true, 'disp' => $disp]);
        exit;
    }

    // ==================================================================
    // 2. HORARIO ACTUAL
    // ==================================================================
    if ($accion === 'horario_actual') {
        $ci = $input['ciEmpleado'] ?? '';
        if (!$ci) throw new Exception("Falta ciEmpleado");

        $stmt = $pdo->prepare("
            SELECT hp.dia, hp.inicio, hp.fin 
            FROM thorarioPersonal hp
            JOIN templeados e ON hp.idEmpleado = e.ciEmpleado
            WHERE hp.idEmpleado = ? AND hp.fechaTurno >= ? AND hp.fechaTurno <= ?
              AND e.idSucursal = (SELECT idSucursal FROM templeados WHERE ciEmpleado = ?)
              AND hp.estado = 1 AND hp.estadoSolicitud = 'aprobado'
        ");
        $stmt->execute([$ci, $fechaInicio, $fechaFin, $ci]);
        $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($turnos as &$t) {
            if ($t['dia'] === 'Miercoles') $t['dia'] = 'Miércoles';
        }

        echo json_encode(['success' => true, 'turnos' => $turnos]);
        exit;
    }

    // ==================================================================
    // 3. GUARDAR HORARIO - ID 100% ÚNICO Y SEGURO
    // ==================================================================
    if ($accion === 'guardar_admin') {
        $ci = $input['ciEmpleado'] ?? '';
        $turnos = $input['turnos'] ?? [];

        if (!$ci || !is_array($turnos) || count($turnos) < 5 || count($turnos) > 12) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar entre 5 y 12 turnos']);
            exit;
        }

        $pdo->beginTransaction();

        // Borrar horarios de esta semana
        $pdo->prepare("DELETE FROM thorarioPersonal WHERE idEmpleado = ? AND fechaTurno >= ? AND fechaTurno <= ?")
            ->execute([$ci, $fechaInicio, $fechaFin]);

        $stmtInsert = $pdo->prepare("
            INSERT INTO thorarioPersonal 
            (idHorarioPersonal, inicio, fin, dia, fechaTurno, idEmpleado, usuarioA, estado, estadoSolicitud)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'aprobado')
        ");

        $mapDia = ['Lunes'=>0,'Martes'=>1,'Miércoles'=>2,'Jueves'=>3,'Viernes'=>4,'Sábado'=>5,'Domingo'=>6];

        foreach ($turnos as $index => $t) {
            $diaFront = $t['dia'];
            $diaDB = $diaFront === 'Miércoles' ? 'Miercoles' : $diaFront;

            $fechaObj = clone $lunes;
            $fechaObj->modify('+' . $mapDia[$diaFront] . ' days');
            $fechaTurno = $fechaObj->format('Y-m-d');

            // GENERAR ID ÚNICO USANDO TIMESTAMP + ÍNDICE + RANDOM
            $uniq = substr(md5(uniqid(rand(), true)), 0, 8);
            $idHorario = 'H' . strtoupper($uniq);

            // Si por algún milagro ya existe (imposible), cambia el último dígito
            $attempt = 0;
            while (true) {
                $check = $pdo->prepare("SELECT 1 FROM thorarioPersonal WHERE idHorarioPersonal = ?");
                $check->execute([$idHorario]);
                if (!$check->fetch()) break;

                $idHorario = 'H' . substr($idHorario, 1, 7) . chr(65 + $attempt % 26);
                $attempt++;
            }

            $stmtInsert->execute([
                $idHorario,
                $t['inicio'],
                $t['fin'],
                $diaDB,
                $fechaTurno,
                $ci,
                $ci
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Horario asignado correctamente']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>