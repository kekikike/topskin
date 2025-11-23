<?php
session_start();

$con = mysqli_connect("localhost", "root", "", "topskin");
if (!$con) {
    die(json_encode(['success' => false, 'message' => 'Conexión fallida']));
}
mysqli_set_charset($con, "utf8");

ob_start();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

// === LISTAR PAÍSES CON CODIGOPAIS_TEL ===
if ($action === 'paises') {
    $sql = "SELECT idPais, nombre, codigopais_tel FROM tpaises WHERE estado = 1 ORDER BY nombre";
    $res = mysqli_query($con, $sql);
    $data = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    echo json_encode($data);
    exit;
}

// === GENERAR SIGUIENTE CÓDIGO DE MARCA (LAN01, LAN02...) ===
if ($action === 'siguiente_codigo_marca') {
    $sql = "SELECT idMarca FROM tmarcas WHERE idMarca LIKE 'LAN%' ORDER BY idMarca DESC LIMIT 1";
    $res = mysqli_query($con, $sql);
    
    if (mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $ultimo = $row['idMarca'];
        $numero = (int)substr($ultimo, 3);
        $siguiente = 'LAN' . str_pad($numero + 1, 2, '0', STR_PAD_LEFT);
    } else {
        $siguiente = 'LAN01';
    }

    echo json_encode(['siguiente' => $siguiente]);
    exit;
}

// === NUEVO PROVEEDOR ===
if ($action === 'nuevo_proveedor') {
    $id = trim($input['idProveedor'] ?? '');
    $nombre = trim($input['nombre'] ?? '');
    $pais = $input['idPais'] ?? '';
    $tel = ($input['codigoPais'] ?? '591') . trim($input['telefono'] ?? '');
    $x = $input['X'] ?? '';
    $y = $input['Y'] ?? '';
    $usuarioA = $input['usuarioA'] ?? 'ADMIN';

    $sql = "INSERT INTO tproveedores (idProveedor, nombre, X, Y, idPais, telefono, usuarioA) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $id, $nombre, $x, $y, $pais, $tel, $usuarioA);
    
    $exito = mysqli_stmt_execute($stmt);
    echo json_encode([
        'success' => $exito,
        'message' => $exito ? 'Proveedor registrado' : mysqli_error($con)
    ]);
    exit;
}

// === NUEVA MARCA ===
if ($action === 'nueva_marca') {
    $id = strtoupper(trim($input['idMarca'] ?? ''));
    $nombre = trim($input['nombre'] ?? '');
    $pais = $input['idPais'] ?? '';
    $desc = $input['descripcion'] ?? '';
    $usuarioA = $input['usuarioA'] ?? 'ADMIN';

    $sql = "INSERT INTO tmarcas (idMarca, nombre, descripcion, idPais, usuarioA) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $id, $nombre, $desc, $pais, $usuarioA);
    
    $exito = mysqli_stmt_execute($stmt);
    echo json_encode([
        'success' => $exito,
        'message' => $exito ? 'Marca registrada' : mysqli_error($con)
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>