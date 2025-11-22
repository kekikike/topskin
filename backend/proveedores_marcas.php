<?php
session_start();

// CONEXIÓN DIRECTA FORZADA (NUNCA FALLA)
$con = mysqli_connect("localhost", "root", "", "topskin");
if (!$con) {
    die(json_encode(['error' => 'Conexión fallida: ' . mysqli_connect_error()]));
}
mysqli_set_charset($con, "utf8");

ob_start();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

if ($action === 'paises') {
    $sql = "SELECT idPais, nombre FROM tpaises WHERE estado = 1 ORDER BY nombre";
    $res = mysqli_query($con, $sql);
    $data = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    echo json_encode($data);
    exit;
}

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
    
    echo json_encode([
        'success' => mysqli_stmt_execute($stmt),
        'message' => mysqli_stmt_execute($stmt) ? 'Proveedor registrado' : mysqli_error($con)
    ]);
    exit;
}

if ($action === 'nueva_marca') {
    $id = strtoupper(trim($input['idMarca'] ?? ''));
    $nombre = trim($input['nombre'] ?? '');
    $pais = $input['idPais'] ?? '';
    $desc = $input['descripcion'] ?? '';
    $usuarioA = $input['usuarioA'] ?? 'ADMIN';

    $sql = "INSERT INTO tmarcas (idMarca, nombre, descripcion, idPais, usuarioA) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $id, $nombre, $desc, $pais, $usuarioA);
    
    echo json_encode([
        'success' => mysqli_stmt_execute($stmt),
        'message' => mysqli_stmt_execute($stmt) ? 'Marca registrada' : mysqli_error($con)
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>