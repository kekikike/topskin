<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

if (!@include 'connection/connection.php') {
    die(json_encode(['success' => false, 'message' => 'Error: No se encontró el archivo de conexión']));
}

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['action'])) {
    die(json_encode(['success' => false, 'message' => 'JSON inválido o falta acción']));
}

$action = $data['action'];

if ($action === 'init') {
    $idSucursal = $data['idSucursal'] ?? null;

    $stmt = $pdo->query("SELECT idRol, nombreRol FROM troles WHERE estado = 1 ORDER BY nombreRol");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $horaIni = '08:00';
    $horaFin = '20:00';

    if ($idSucursal && preg_match('/^\d+$/', $idSucursal)) {
        $stmt = $pdo->prepare("SELECT horaIni, horaFin FROM tsucursales WHERE idSucursal = ? AND estado = 1");
        $stmt->execute([$idSucursal]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['horaIni'] && $row['horaFin']) {
            $horaIni = (new DateTime($row['horaIni']))->format('H:i');
            $horaFin = (new DateTime($row['horaFin']))->format('H:i');
        }
    }

    echo json_encode([
        'success' => true,
        'roles'   => $roles,
        'horaIni' => $horaIni,
        'horaFin' => $horaFin
    ]);
    exit;
}

if ($action === 'registrar') {
    $f = $data['form'] ?? [];
    $idSucursal = $data['idSucursal'] ?? null;

    if (!$idSucursal) {
        echo json_encode(['success' => false, 'message' => 'Falta idSucursal']);
        exit;
    }

    if (!preg_match('/^\d{9}$/', $f['ciEmpleado'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CI debe tener 9 dígitos']);
        exit;
    }
    if (!filter_var($f['correo'] ?? '', FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Correo inválido']);
        exit;
    }
    if (($f['contrasena'] ?? '') !== ($f['confirmarContrasena'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit;
    }
    if (empty($f['idRol'])) {
        echo json_encode(['success' => false, 'message' => 'Selecciona un rol']);
        exit;
    }
    if (!preg_match('/^\d{7,8}$/', $f['telefono'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Teléfono inválido (7-8 dígitos)']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT ciEmpleado FROM templeados WHERE ciEmpleado = ?");
    $stmt->execute([$f['ciEmpleado']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Este CI ya está registrado']);
        exit;
    }

    $usuarioA = $_SESSION['ciEmpleado'] ?? '1';

    $sql = "INSERT INTO templeados (
                ciEmpleado, nombre1, nombre2, apellidoP, apellidoM, direccion, correo, sexo,
                telefono, fechaNacimiento, idSucursal, idRol, contrasena, usuarioA, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $f['ciEmpleado'],
        $f['nombre1'],
        $f['nombre2'] ?? null,
        $f['apellidoP'],
        $f['apellidoM'] ?? null,
        $f['direccion'],
        $f['correo'],
        (int)($f['sexo'] ?? 1),
        $f['telefono'],
        $f['fechaNacimiento'],
        $idSucursal,
        $f['idRol'],
        $f['contrasena'],
        $usuarioA
    ]);

    echo json_encode([
        'success'    => true,
        'ciEmpleado' => $f['ciEmpleado'],
        'nombre'     => trim($f['nombre1'] . ' ' . ($f['apellidoP'] ?? ''))
    ]);
    exit;
}

if ($action === 'horarios') {
    $ciEmpleado = $data['ciEmpleado'] ?? null;
    $horarios   = $data['horarios'] ?? [];

    if (!$ciEmpleado) {
        echo json_encode(['success' => false, 'message' => 'Falta CI del empleado']);
        exit;
    }

    if (empty($horarios)) {
        echo json_encode(['success' => true, 'message' => 'Sin horarios asignados']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(idHorarioPersonal, 2) AS UNSIGNED)) AS max_id 
                         FROM thorarioPersonal 
                         WHERE idHorarioPersonal REGEXP '^H[0-9]+$'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $siguiente = ($row && $row['max_id']) ? (int)$row['max_id'] + 1 : 1;

    $insert = $pdo->prepare("INSERT INTO thorarioPersonal 
        (idHorarioPersonal, inicio, fin, dia, idEmpleado, usuarioA, estado) 
        VALUES (?, ?, ?, ?, ?, '1', 1)");

    foreach ($horarios as $h) {
        if (empty($h['inicio']) || empty($h['fin'])) continue;

        $idHorario = 'H' . $siguiente++;
        $insert->execute([
            $idHorario,
            $h['inicio'] . ':00',
            $h['fin'] . ':00',
            $h['dia'],
            $ciEmpleado
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Horarios guardados correctamente']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>