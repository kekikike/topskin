<?php
require_once 'connection/connection.php';
$pdo = Conexion::conectar();

try {
    // Obtener todas las marcas activas
    $stmt = $pdo->query("SELECT idMarca, nombre FROM tmarcas WHERE estado=1");
    $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];

    foreach ($marcas as $marca) {
        // Obtener productos de esa marca usando JOIN
        $stmt2 = $pdo->prepare("
            SELECT p.idProducto AS id, p.nombre, p.precioUnitario AS precio, p.foto
            FROM tproductos p
            WHERE p.estado=1 AND p.idMarca = :marca
            ORDER BY p.idProducto DESC
        ");
        $stmt2->execute(['marca' => $marca['idMarca']]);
        $productos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if ($productos) {
            $resultado[] = [
                'marca' => $marca['nombre'],
                'productos' => $productos
            ];
        }
    }
} catch (Exception $e) {
    $resultado = [];
    error_log("Error: " . $e->getMessage());
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
exit;
