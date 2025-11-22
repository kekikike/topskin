<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php';

$ciEmpleado = $_POST['ciEmpleado'] ?? null;
$accion     = $_POST['accion'] ?? null;

if (!$ciEmpleado || !$accion || !in_array($accion, ['agregar','editar'])) {
    echo json_encode(["success" => false, "message" => "Acción inválida"]);
    exit;
}

try {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    $nombre        = trim($_POST['nombre'] ?? '');
    $precio        = $_POST['precioUnitario'] ?? 0;
    $stock         = $_POST['stock'] ?? 0;
    $idMarca       = $_POST['idMarca'] ?? null;
    $categoriasStr = $_POST['categorias'] ?? ''; 
    $categorias    = array_filter(array_map('trim', explode(',', $categoriasStr)));

    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombreFoto = uniqid('prod_') . '.' . strtolower($ext);
        $ruta = '../img/productos/' . $nombreFoto;
        
        if (!is_dir('../img/productos/')) mkdir('../img/productos/', 0755, true);
        move_uploaded_file($_FILES['foto']['tmp_name'], $ruta);
        $foto = 'img/productos/' . $nombreFoto;
    }
if ($accion === 'agregar') {

    $stmt = $pdo->query("SELECT idProducto FROM tproductos ORDER BY idProducto DESC LIMIT 1");
    $ultimo = $stmt->fetchColumn();

    if ($ultimo) {
        $numero = (int) substr($ultimo, 1);
        $siguiente = $numero + 1;
    } else {
        $siguiente = 1;
    }

    $idProducto = 'P' . str_pad($siguiente, 3, '0', STR_PAD_LEFT); // P001 hasta P999

    $stmt = $pdo->prepare("
        INSERT INTO tproductos 
        (idProducto, nombre, precioUnitario, stock, idMarca, foto, usuarioA, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$idProducto, $nombre, $precio, $stock, $idMarca, $foto, $ciEmpleado]);

    if (!empty($categorias)) {
        $stmtCat = $pdo->prepare("
            INSERT INTO tproductosCategorias (idProducto, idCategoria, usuarioA, estado) 
            VALUES (?, ?, ?, 1)
        ");
        foreach ($categorias as $idCat) {
            $stmtCat->execute([$idProducto, trim($idCat), $ciEmpleado]);
        }
    }

    $_SESSION['ultimoProductoAgregado'] = $idProducto;
}
    elseif ($accion === 'editar') {
        $idProducto = $_POST['idProducto'] ?? null;
        if (!$idProducto) throw new Exception("Falta ID del producto");

        $sql = "UPDATE tproductos SET nombre=?, precioUnitario=?, stock=?, idMarca=?, usuarioA=?";
        $params = [$nombre, $precio, $stock, $idMarca, $ciEmpleado];
        if ($foto) {
            $sql .= ", foto=?";
            $params[] = $foto;
        }
        $sql .= " WHERE idProducto=?";
        $params[] = $idProducto;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $pdo->prepare("UPDATE tproductosCategorias SET estado=0 WHERE idProducto=?")->execute([$idProducto]);
        
        if (!empty($categorias)) {
            $stmtCat = $pdo->prepare("
                INSERT INTO tproductosCategorias (idProducto, idCategoria, usuarioA, estado)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE estado=1, usuarioA=VALUES(usuarioA)
            ");
            foreach ($categorias as $idCat) {
                $stmtCat->execute([$idProducto, $idCat, $ciEmpleado]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Producto guardado correctamente"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>