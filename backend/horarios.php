<?php
ob_clean();
header("Content-Type: application/json; charset=UTF-8");

require_once 'connection/connection.php';

$input = json_decode(file_get_contents("php://input"), true);
$ciEmpleado = $input['ciEmpleado'] ?? null;
$turnos = $input['turnos'] ?? [];

if (!$ciEmpleado || !is_array($turnos) || count($turnos) < 5 || count($turnos) > 12) {
    echo json_encode([
        "success" => false,
        "message" => "Debes seleccionar entre 5 y 12 turnos"
    ]);
    exit;
}

try {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    $deleteSql = "DELETE FROM thorarioPersonal WHERE idEmpleado = ? AND fechaA > NOW()";
    $stmtDelete = $pdo->prepare($deleteSql);
    $stmtDelete->execute([$ciEmpleado]);

    $insertSql = "
        INSERT INTO thorarioPersonal 
        (idHorarioPersonal, inicio, fin, dia, idEmpleado, usuarioA, estado) 
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ";
    $stmtInsert = $pdo->prepare($insertSql);

    function generarId($longitud = 5) {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $id = '';
        for ($i = 0; $i < $longitud; $i++) {
            $id .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        return $id;
    }

    foreach ($turnos as $t) {
        $diaDB = $t['dia'];
        $diaDB = $diaDB === 'Miércoles' ? 'Miercoles' : $diaDB;

        $idHorario = generarId(5);

        $stmtInsert->execute([
            $idHorario,
            $t['inicio'],
            $t['fin'],
            $diaDB,
            $ciEmpleado,
            $ciEmpleado  
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "¡Horario guardado con éxito para la próxima semana!"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar: " . $e->getMessage()
    ]);
}
?>