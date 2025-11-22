<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once 'connection/connection.php';
$conn = Conexion::conectar();

$input = json_decode(file_get_contents("php://input"), true);
$accion = $_REQUEST['accion'] ?? $_REQUEST['action'] ?? ($input['accion'] ?? ($input['action'] ?? ''));

if (!$accion) {
    echo json_encode(["error" => "No se envió acción"]);
    exit;
}

switch ($accion) {

    case "listar_todo":
        listar_todo($conn);
        break;

    case "buscar_categoria":
        buscar_categoria($conn);
        break;

    case "crear_producto":
        crear_producto($conn);
        break;

    case "actualizar_producto":
        actualizar_producto($conn);
        break;

    case "eliminar_producto":
        eliminar_producto($conn);
        break;

    default:
        echo json_encode(["error" => "Acción no válida: " . $accion]);
        exit;
}

// ==================================================
// 1. LISTAR TODO (productos + marcas + categorías)
// ==================================================
function listar_todo($conn)
{
    try {
        $sql = "
            SELECT 
                p.idProducto, p.nombre, p.precioUnitario, p.stock, p.foto,
                p.fechaA, m.idMarca, m.nombre AS nombreMarca,
                GROUP_CONCAT(cat.idCategoria) AS categorias_ids,
                GROUP_CONCAT(cat.nombre SEPARATOR ', ') AS categorias_nombres
            FROM tproductos p
            LEFT JOIN tmarcas m ON p.idMarca = m.idMarca
            LEFT JOIN tproductosCategorias pc ON pc.idProducto = p.idProducto
            LEFT JOIN tcategorias cat ON pc.idCategoria = cat.idCategoria
            WHERE p.estado = 1
            GROUP BY p.idProducto
            ORDER BY m.nombre, p.nombre
        ";

        $st = $conn->prepare($sql);
        $st->execute();
        $productos = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productos as &$p) {
            $p['categorias'] = $p['categorias_ids'] 
                ? array_map('trim', explode(',', $p['categorias_ids'])) 
                : [];
        }

        $sql_marcas = "SELECT idMarca, nombre FROM tmarcas WHERE estado = 1 ORDER BY nombre";
        $st_m = $conn->prepare($sql_marcas);
        $st_m->execute();
        $marcas = $st_m->fetchAll(PDO::FETCH_ASSOC);

        $sql_cats = "SELECT idCategoria, nombre FROM tcategorias WHERE estado = 1 ORDER BY nombre";
        $st_c = $conn->prepare($sql_cats);
        $st_c->execute();
        $categorias = $st_c->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "productos" => $productos,
            "marcas" => $marcas,
            "categorias" => $categorias
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// ==================================================
// 2. BUSCAR CATEGORÍAS
// ==================================================
function buscar_categoria($conn)
{
    try {
        $sql = "SELECT idCategoria, nombre FROM tcategorias WHERE estado = 1 ORDER BY nombre";
        $st = $conn->prepare($sql);
        $st->execute();
        $res = $st->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["data" => $res], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// ==================================================
// 3. CREAR PRODUCTO (FIX: usuarioA = '1' + URL externa)
// ==================================================
function crear_producto($conn)
{
    try {
        $nombre = $_POST['nombre'] ?? '';
        $precio = $_POST['precioUnitario'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $idMarca = $_POST['idMarca'] ?? '';
        $categorias = json_decode($_POST['categorias'] ?? '[]', true);
        $foto_url = $_POST['foto'] ?? '';

        $usuario = '1';  // ← FIJO: siempre '1'
        $foto_final = $foto_url;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $foto_final = basename($_FILES['foto']['name']);
            $ruta = "../../media/productos/" . $foto_final;
            move_uploaded_file($_FILES['foto']['tmp_name'], $ruta);
        }

        $idProd = "PROD" . str_pad($conn->query("SELECT COUNT(*) FROM tproductos")->fetchColumn() + 1, 6, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO tproductos (idProducto, nombre, precioUnitario, stock, foto, idMarca, usuarioA) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $st = $conn->prepare($sql);
        $st->execute([$idProd, $nombre, $precio, $stock, $foto_final, $idMarca, $usuario]);

        if (!empty($categorias) && is_array($categorias)) {
            $sql2 = "INSERT INTO tproductosCategorias (idProducto, idCategoria, usuarioA) VALUES (?, ?, ?)";
            $st2 = $conn->prepare($sql2);
            foreach ($categorias as $cat) {
                $st2->execute([$idProd, $cat, $usuario]);
            }
        }

        echo json_encode(["ok" => true, "idProducto" => $idProd]);

    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// ==================================================
// 4. ACTUALIZAR PRODUCTO (FIX: usuarioA = '1' + URL externa)
// ==================================================
function actualizar_producto($conn)
{
    try {
        $id = $_POST['idProducto'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $precio = $_POST['precioUnitario'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $idMarca = $_POST['idMarca'] ?? '';
        $foto_url = $_POST['foto'] ?? '';
        $categorias = json_decode($_POST['categorias'] ?? '[]', true);

        if (!$id || !$nombre) {
            echo json_encode(["error" => "Faltan datos obligatorios"]);
            exit;
        }

        $usuario = '1';  // ← FIJO: siempre '1'
        $foto_final = $foto_url;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $foto_final = basename($_FILES['foto']['name']);
            $ruta = "../../media/productos/" . $foto_final;
            move_uploaded_file($_FILES['foto']['tmp_name'], $ruta);
        }

        if (empty($foto_final)) {
            $stmt = $conn->prepare("SELECT foto FROM tproductos WHERE idProducto = ?");
            $stmt->execute([$id]);
            $foto_final = $stmt->fetchColumn() ?: '';
        }

        $sql = "UPDATE tproductos SET 
                nombre = ?, precioUnitario = ?, stock = ?, foto = ?, idMarca = ?, usuarioA = ? 
                WHERE idProducto = ?";
        $st = $conn->prepare($sql);
        $st->execute([$nombre, $precio, $stock, $foto_final, $idMarca, $usuario, $id]);

        $del = $conn->prepare("DELETE FROM tproductosCategorias WHERE idProducto = ?");
        $del->execute([$id]);

        if (!empty($categorias) && is_array($categorias)) {
            $ins = $conn->prepare("INSERT INTO tproductosCategorias (idProducto, idCategoria, usuarioA) VALUES (?, ?, ?)");
            foreach ($categorias as $cat) {
                $ins->execute([$id, $cat, $usuario]);
            }
        }

        echo json_encode(["ok" => true]);

    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// ==================================================
// 5. ELIMINAR PRODUCTO
// ==================================================
function eliminar_producto($conn)
{
    try {
        $id = $_POST['idProducto'] ?? '';
        if (!$id) {
            echo json_encode(["error" => "Falta ID"]);
            exit;
        }

        $sql = "UPDATE tproductos SET estado = 0 WHERE idProducto = ?";
        $st = $conn->prepare($sql);
        $st->execute([$id]);

        echo json_encode(["ok" => true]);

    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>