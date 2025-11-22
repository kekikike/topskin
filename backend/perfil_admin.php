<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
require_once 'connection/connection.php';
$pdo = Conexion::conectar();

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$accion = $_REQUEST['accion'] ?? ($data['accion'] ?? '');

try {

    // ==================================================================
    // CARGAR PERFIL DEL EMPLEADO
    // ==================================================================
    if ($accion === 'cargar') {
        $ci = $_GET['ci'] ?? $data['ci'] ?? null;

        if (!$ci) {
            echo json_encode(['success' => false, 'message' => 'Falta CI del empleado']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT ciEmpleado, nombre1, nombre2, apellidoP, apellidoM,
                   correo, telefono, direccion
            FROM templeados 
            WHERE ciEmpleado = ? AND estado = 1
        ");
        $stmt->execute([$ci]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empleado) {
            echo json_encode([
                'success' => true,
                'data'    => $empleado
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o inactivo']);
        }
        exit;
    }


    // ==================================================================
    // ACTUALIZAR PERFIL DEL EMPLEADO (SIN HASHEO DE CONTRASEÑA)
    // ==================================================================
    if ($accion === 'actualizar') {
        if (!$data || !isset($data['ciEmpleado'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos o JSON inválido']);
            exit;
        }

        $ci = $data['ciEmpleado'];
        $nuevaContrasena = $data['nuevaContrasena'] ?? '';

        // Verificar que el empleado existe
        $stmt = $pdo->prepare("SELECT ciEmpleado FROM templeados WHERE ciEmpleado = ? AND estado = 1");
        $stmt->execute([$ci]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o inactivo']);
            exit;
        }

        // Campos permitidos para actualizar
        $campos = ['nombre1', 'nombre2', 'apellidoP', 'apellidoM', 'correo', 'telefono', 'direccion'];
        $set = [];
        $params = [];

        foreach ($campos as $campo) {
            if (isset($data[$campo])) {
                $valor = trim($data[$campo]);
                $set[] = "$campo = ?";
                $params[] = $valor;
            }
        }

        // Si hay nueva contraseña, guardarla directamente en texto plano
        $contrasenaCambiada = false;
        if (!empty($nuevaContrasena)) {
            $set[] = "contrasena = ?";
            $params[] = $nuevaContrasena;           // <-- SIN password_hash()
            $contrasenaCambiada = true;
        }

        // Si no hay nada que actualizar
        if (empty($set)) {
            echo json_encode([
                'success' => true,
                'message' => 'No se realizaron cambios',
                'contrasenaCambiada' => false
            ]);
            exit;
        }

        // Agregar CI al final para el WHERE
        $params[] = $ci;
        $sql = "UPDATE templeados SET " . implode(', ', $set) . " WHERE ciEmpleado = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $filas = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'message' => $filas > 0 ? 'Perfil actualizado correctamente' : 'No se realizaron cambios',
            'filasAfectadas' => $filas,
            'contrasenaCambiada' => $contrasenaCambiada
        ]);
        exit;
    }


    // ==================================================================
    // ACCIÓN NO VÁLIDA
    // ==================================================================
    echo json_encode(['success' => false, 'message' => 'Acción no válida o no especificada']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor',
        'error'   => $e->getMessage()
    ]);
}
?>