<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once 'connection/connection.php';

$headers = getallheaders();
$ciEmpleado = $headers['X-CI-Empleado'] ?? null;

if (!$ciEmpleado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $ciEmpleado = $input['ciEmpleado'] ?? null;
}

if (!$ciEmpleado) {
    echo json_encode([
        "success" => false,
        "message" => "No se proporcionó el ID del empleado"
    ]);
    exit;
}

try {
    $pdo = Conexion::conectar();

    $sqlEmpleado = "SELECT 
                        ciEmpleado,
                        CONCAT(
                            TRIM(nombre1), ' ',
                            IFNULL(TRIM(nombre2), ''), ' ',
                            TRIM(apellidoP), ' ',
                            IFNULL(TRIM(apellidoM), '')
                        ) AS nombreCompleto,
                        correo
                    FROM templeados 
                    WHERE ciEmpleado = ? AND estado = 1 
                    LIMIT 1";

    $stmt = $pdo->prepare($sqlEmpleado);
    $stmt->execute([$ciEmpleado]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        echo json_encode([
            "success" => false,
            "message" => "Empleado no encontrado o inactivo"
        ]);
        exit;
    }

    $sqlHorario = "SELECT 
                        dia,
                        TIME_FORMAT(inicio, '%H:%i') AS inicio,
                        TIME_FORMAT(fin, '%H:%i') AS fin
                   FROM thorarioPersonal 
                   WHERE idEmpleado = ? 
                     AND estado = 1
                   ORDER BY 
                     FIELD(dia, 'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'),
                     inicio";

    $stmt = $pdo->prepare($sqlHorario);
    $stmt->execute([$ciEmpleado]);
    $horarioRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $horario = [];
    foreach ($horarioRaw as $h) {
        $diaNormalizado = $h['dia'] === 'Miercoles' ? 'Miércoles' : $h['dia'];
        $horario[] = [
            'dia' => $diaNormalizado,
            'inicio' => $h['inicio'],
            'fin' => $h['fin']
        ];
    }

    echo json_encode([
        "success" => true,
        "empleado" => [
            "ciEmpleado" => $empleado['ciEmpleado'],
            "nombreCompleto" => trim(preg_replace('/\s+/', ' ', $empleado['nombreCompleto'])),
            "correo" => $empleado['correo'],
            "foto" => null 
        ],
        "horario" => $horario
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error del servidor",
        "error" => $e->getMessage() 
    ]);
}
?>