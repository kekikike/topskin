<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $input['action'] ?? '';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ========================================
    // 1. INIT - CARGAR ROLES Y HORARIO DE LA SUCURSAL
    // ========================================
    if ($action === 'init') {
        $idSucursal = $input['idSucursal'] ?? '00001';

        $stmt = $pdo->query("SELECT idRol, nombreRol FROM troles WHERE estado = 1 ORDER BY nombreRol");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $horaIni = '08:00';
        $horaFin = '20:00';
        if (preg_match('/^\d+$/', $idSucursal)) {
            $stmt = $pdo->prepare("SELECT horaIni, horaFin FROM tsucursales WHERE idSucursal = ? AND estado = 1");
            $stmt->execute([$idSucursal]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $horaIni = (new DateTime($row['horaIni']))->format('H:i');
                $horaFin = (new DateTime($row['horaFin']))->format('H:i');
            }
        }

        echo json_encode([
            'success' => true,
            'roles' => $roles,
            'horaIni' => $horaIni,
            'horaFin' => $horaFin
        ]);
        exit;
    }

    // ========================================
    // 2. REGISTRAR EMPLEADO
    // ========================================
    if ($action === 'registrar') {
        $f = $input['form'] ?? [];
        $idSucursal = $input['idSucursal'] ?? null;

        if (!$idSucursal || !preg_match('/^\d{9}$/', $f['ciEmpleado'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }

        $camposRequeridos = ['nombre1','apellidoP','telefono','fechaNacimiento','direccion','correo','idRol','contrasena'];
        foreach ($camposRequeridos as $c) {
            if (empty($f[$c])) {
                echo json_encode(['success' => false, 'message' => "Falta $c"]);
                exit;
            }
        }

        if ($f['contrasena'] !== ($f['confirmarContrasena'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Contraseñas no coinciden']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT ciEmpleado FROM templeados WHERE ciEmpleado = ?");
        $stmt->execute([$f['ciEmpleado']]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'CI ya registrado']);
            exit;
        }

        $usuarioA = $_SESSION['ciEmpleado'] ?? $f['ciEmpleado'];

        $sql = "INSERT INTO templeados (
                    ciEmpleado, nombre1, nombre2, apellidoP, apellidoM, direccion, correo, sexo,
                    telefono, fechaNacimiento, idSucursal, idRol, contrasena, usuarioA, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

        // SIN HASH
        $pdo->prepare($sql)->execute([
            $f['ciEmpleado'], $f['nombre1'], $f['nombre2']??null, $f['apellidoP'], $f['apellidoM']??null,
            $f['direccion'], $f['correo'], (int)($f['sexo']??1), $f['telefono'], $f['fechaNacimiento'],
            $idSucursal, $f['idRol'], $f['contrasena'], $usuarioA
        ]);

        echo json_encode([
            'success' => true,
            'ciEmpleado' => $f['ciEmpleado'],
            'nombre' => trim($f['nombre1'] . ' ' . ($f['nombre2'] ? $f['nombre2'] . ' ' : '') . $f['apellidoP'] . ' ' . ($f['apellidoM'] ?? ''))
        ]);
        exit;
    }

    // ========================================
    // 3. VERIFICAR DISPONIBILIDAD EN TIEMPO REAL
    // ========================================
    if ($action === 'verificar_disponibilidad') {
        $idSucursal = $input['idSucursal'] ?? null;
        $turnos = $input['turnos'] ?? [];

        if (!$idSucursal || empty($turnos)) {
            echo json_encode(['disponible' => true]);
            exit;
        }

        foreach ($turnos as $t) {
            $diaDB = $t['dia'] === 'Miércoles' ? 'Miercoles' : $t['dia'];
            $inicio = $t['inicio'];
            $fin = $t['fin'];

            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM thorarioPersonal hp
                JOIN templeados e ON hp.idEmpleado = e.ciEmpleado
                WHERE hp.dia = ? 
                  AND hp.inicio = ? 
                  AND hp.fin = ?
                  AND e.idSucursal = ?
                  AND hp.estado = 1 
                  AND hp.estadoSolicitud = 'aprobado'
            ");

            $stmt->execute([$diaDB, $inicio, $fin, $idSucursal]);
            $ocupados = $stmt->fetchColumn();

            if ($ocupados >= 2) {
                echo json_encode([
                    'disponible' => false,
                    'mensaje' => "El turno del {$t['dia']} de {$inicio} a {$fin} ya tiene 2 empleados."
                ]);
                exit;
            }
        }

        echo json_encode(['disponible' => true]);
        exit;
    }

    // ========================================
    // 4. GUARDAR HORARIOS DEL NUEVO EMPLEADO
    // ========================================
    if ($action === 'horarios') {
        $ciEmpleado = $input['ciEmpleado'] ?? null;
        $horarios = $input['horarios'] ?? [];
        $idSucursal = $input['idSucursal'] ?? null;

        if (!$ciEmpleado || !$idSucursal) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
            exit;
        }

        if (count($horarios) < 5) {
            echo json_encode(['success' => false, 'message' => 'Debe asignar al menos 5 turnos por semana']);
            exit;
        }

        foreach ($horarios as $h) {
            $diaDB = $h['dia'] === 'Miércoles' ? 'Miercoles' : $h['dia'];

            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM thorarioPersonal hp
                JOIN templeados e ON hp.idEmpleado = e.ciEmpleado
                WHERE hp.dia = ? 
                  AND hp.inicio = ? 
                  AND hp.fin = ?
                  AND e.idSucursal = ?
                  AND hp.estado = 1 
                  AND hp.estadoSolicitud = 'aprobado'
            ");

            $stmt->execute([$diaDB, $h['inicio'], $h['fin'], $idSucursal]);
            if ($stmt->fetchColumn() >= 2) {
                echo json_encode([
                    'success' => false,
                    'message' => "El turno {$h['dia']} ({$h['inicio']}-{$h['fin']}) ya está lleno (2 empleados)"
                ]);
                exit;
            }
        }

        $pdo->beginTransaction();

        $insert = $pdo->prepare("INSERT INTO thorarioPersonal 
            (idHorarioPersonal, inicio, fin, dia, fechaTurno, idEmpleado, usuarioA, estado, estadoSolicitud)
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 1, 'aprobado')");

        foreach ($horarios as $h) {
            $uniq = substr(md5(uniqid(rand(), true)), 0, 10);
            $idHorario = 'H' . strtoupper($uniq);
            $diaDB = $h['dia'] === 'Miércoles' ? 'Miercoles' : $h['dia'];

            $insert->execute([
                $idHorario,
                $h['inicio'],
                $h['fin'],
                $diaDB,
                $ciEmpleado,
                $ciEmpleado
            ]);
        }

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Horarios guardados correctamente']);
        exit;
    }

    // ========================================
    // ACCIÓN NO VÁLIDA
    // ========================================
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
