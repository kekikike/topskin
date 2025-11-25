<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
require_once 'connection/connection.php'; // Asegúrate de que esta ruta sea correcta

// Leer datos enviados desde el frontend (Vue envía JSON)
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "Datos no recibidos"]);
    exit;
}

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Campos obligatorios
    $required = ['ciCliente', 'nombre1', 'apellidoP', 'direccion', 'correo', 'telefono', 'contrasena'];
    foreach ($required as $campo) {
        if (empty(trim($input[$campo] ?? ''))) {
            echo json_encode(["success" => false, "message" => "El campo $campo es obligatorio"]);
            exit;
        }
    }

    // Recoger y limpiar datos
    $ciCliente  = trim($input['ciCliente']);
    $nombre1    = trim($input['nombre1']);
    $nombre2    = trim($input['nombre2'] ?? '');
    $apellidoP  = trim($input['apellidoP']);
    $apellidoM  = trim($input['apellidoM'] ?? '');
    $direccion  = trim($input['direccion']);
    $correo     = trim($input['correo']);
    $sexo       = $input['sexo'] ?? '1'; // 1 = masculino, 0 = femenino
    $telefono   = trim($input['telefono']);
    $contrasena = password_hash($input['contrasena'], PASSWORD_DEFAULT); // ¡Contraseña encriptada!
    $usuarioA   = $input['usuarioA'] ?? '1'; // quien registra (puedes poner el ID del admin o empleado)

    // Validaciones extra
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Correo electrónico inválido"]);
        exit;
    }

    if (!preg_match('/^\d{7,8}$/', $telefono)) {
        echo json_encode(["success" => false, "message" => "Teléfono debe tener 7 u 8 dígitos"]);
        exit;
    }

    if (!preg_match('/^\d{7,10}$/', $ciCliente)) {
        echo json_encode(["success" => false, "message" => "La CI debe tener entre 7 y 10 dígitos"]);
        exit;
    }

    // Verificar si ya existe el CI o el correo
    $check = $pdo->prepare("SELECT ciCliente FROM tclientes WHERE ciCliente = ? OR correo = ?");
    $check->execute([$ciCliente, $correo]);
    if ($check->rowCount() > 0) {
        $existe = $check->fetch(PDO::FETCH_ASSOC);
        if ($existe['ciCliente'] === $ciCliente) {
            echo json_encode(["success" => false, "message" => "Esta cédula ya está registrada"]);
        } else {
            echo json_encode(["success" => false, "message" => "Este correo ya está en uso"]);
        }
        exit;
    }

    // INSERTAR EL NUEVO CLIENTE
    $sql = "INSERT INTO tclientes 
            (ciCliente, nombre1, nombre2, apellidoP, apellidoM, direccion, correo, sexo, telefono, contrasena, usuarioA)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $ciCliente,
        $nombre1,
        $nombre2,
        $apellidoP,
        $apellidoM,
        $direccion,
        $correo,
        $sexo,
        $telefono,
        $contrasena,
        $usuarioA
    ]);

    echo json_encode([
        "success" => true,
        "message" => "¡Cliente registrado exitosamente!"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error del servidor",
        "detalles" => $e->getMessage() // Quitar esto en producción
    ]);
}