<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once 'connection/connection.php';

$accion = $_REQUEST['accion'] ?? '';

try {
    $pdo = Conexion::conectar();

    if ($accion === 'listar_empleados') {
        $sql = "SELECT 
                    e.ciEmpleado,
                    e.nombre1,
                    IFNULL(e.nombre2, '') AS nombre2,
                    e.apellidoP,
                    IFNULL(e.apellidoM, '') AS apellidoM,
                    r.nombreRol AS cargo
                FROM templeados e
                LEFT JOIN troles r ON e.idRol = r.idRol
                WHERE e.estado = 1
                ORDER BY e.apellidoP, e.apellidoM";

        $stmt = $pdo->query($sql);
        echo json_encode(["success" => true, "empleados" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($accion === 'cargar_horarios') {
        $ci = $_REQUEST['ciEmpleado'] ?? '';
        if (!$ci) {
            echo json_encode(["success" => false, "message" => "Falta ciEmpleado"]);
            exit;
        }

        $sql = "SELECT 
                    dia,
                    COALESCE(TIME_FORMAT(inicio, '%H:%i'), '-') AS inicio,
                    COALESCE(TIME_FORMAT(fin, '%H:%i'), '-') AS fin
                FROM thorarioPersonal
                WHERE idEmpleado = ? AND estado = 1
                ORDER BY FIELD(dia,'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ci]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        $existentes = array_column($horarios, 'dia');

        foreach ($dias as $d) {
            if (!in_array($d, $existentes)) {
                $horarios[] = ['dia' => $d, 'inicio' => '-', 'fin' => '-'];
            }
        }

        echo json_encode(["success" => true, "horarios" => $horarios]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Acción no válida"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}