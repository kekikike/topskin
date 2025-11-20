<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");

require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['correo']) || empty($input['contrasena'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos"]);
    exit;
}

$correo = trim($input['correo']);
$pass   = $input['contrasena'];

try {
    $pdo = Conexion::conectar();

    $sql = "SELECT 
                e.ciEmpleado,
                e.nombre1,
                e.nombre2,
                e.apellidoP,
                e.apellidoM,
                e.correo,
                e.idRol,
                e.idSucursal,          -- AQUÍ TRAEMOS LA SUCURSAL
                r.nombreRol
            FROM templeados e
            LEFT JOIN troles r ON e.idRol = r.idRol
            WHERE e.correo = ? AND e.contrasena = ? AND e.estado = 1
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$correo, $pass]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $nombreCompleto = trim(
            $usuario['nombre1'] . ' ' .
            ($usuario['nombre2'] ?? '') . ' ' .
            $usuario['apellidoP'] . ' ' .
            ($usuario['apellidoM'] ?? '')
        );

        echo json_encode([
            "success" => true,
            "message" => "Login exitoso",
            "usuario" => [
                "ciEmpleado"    => $usuario['ciEmpleado'],
                "nombre"        => $nombreCompleto,
                "correo"        => $usuario['correo'],
                "idRol"         => $usuario['idRol'],
                "rol"           => $usuario['nombreRol'],
                "idSucursal"    => $usuario['idSucursal']  // ← ¡NUEVO!
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Correo o contraseña incorrectos"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error del servidor"]);
}