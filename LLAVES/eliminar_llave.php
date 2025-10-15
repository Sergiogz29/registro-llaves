<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["s_usuario"])) {
    echo json_encode(["success" => false, "message" => "No autenticado."]);
    exit;
}

$esAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') || (isset($_SESSION['es_admin']) && $_SESSION['es_admin']);
if (!$esAdmin) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Permisos insuficientes."]);
    exit;
}

$codigo_principal = $_POST['codigo_principal'] ?? '';
$id_propietario = $_POST['id_propietario'] ?? '';

if ($codigo_principal === '' || $id_propietario === '') {
    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
if ($conexion->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos."]);
    exit;
}
$conexion->set_charset("utf8");

try {
    $conexion->begin_transaction();

    // Recoger movimientos asociados a la llave
    $ids_mov = [];
    $stmt = $conexion->prepare("SELECT id_movimiento FROM movimientos_llaves WHERE codigo_principal = ? AND id_propietario = ?");
    if (!$stmt) throw new Exception($conexion->error);
    $stmt->bind_param("ss", $codigo_principal, $id_propietario);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ids_mov[] = (int)$row['id_movimiento'];
    }
    $stmt->close();

    // Eliminar firmas que dependan de esos movimientos (evita restricciones de FK)
    if (!empty($ids_mov)) {
        $stmtDelFirma = $conexion->prepare("DELETE FROM firmas_llaves WHERE id_movimiento = ?");
        if (!$stmtDelFirma) throw new Exception($conexion->error);
        foreach ($ids_mov as $idMov) {
            $stmtDelFirma->bind_param("i", $idMov);
            if (!$stmtDelFirma->execute()) throw new Exception($stmtDelFirma->error);
        }
        $stmtDelFirma->close();
    }

    // Eliminar movimientos (por seguridad, aunque hay FK con ON DELETE CASCADE)
    $stmtDelMov = $conexion->prepare("DELETE FROM movimientos_llaves WHERE codigo_principal = ? AND id_propietario = ?");
    if (!$stmtDelMov) throw new Exception($conexion->error);
    $stmtDelMov->bind_param("ss", $codigo_principal, $id_propietario);
    if (!$stmtDelMov->execute()) throw new Exception($stmtDelMov->error);
    $stmtDelMov->close();

    // Eliminar la llave
    $stmtDelLlave = $conexion->prepare("DELETE FROM llaves WHERE codigo_principal = ? AND id_propietario = ?");
    if (!$stmtDelLlave) throw new Exception($conexion->error);
    $stmtDelLlave->bind_param("ss", $codigo_principal, $id_propietario);
    if (!$stmtDelLlave->execute()) throw new Exception($stmtDelLlave->error);
    $filas = $stmtDelLlave->affected_rows;
    $stmtDelLlave->close();

    $conexion->commit();

    if ($filas > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "La llave no existe."]);
    }
} catch (Exception $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error eliminando la llave: " . $e->getMessage()]);
}


