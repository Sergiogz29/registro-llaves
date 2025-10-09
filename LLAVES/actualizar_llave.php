<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["s_usuario"])) {
    echo json_encode(["success" => false, "message" => "No autenticado."]);
    exit;
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
if ($conexion->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos."]);
    exit;
}
$conexion->set_charset("utf8");

$codigo_principal = $_POST['codigo_principal'] ?? null;
$id_propietario = $_POST['id_propietario'] ?? null;
$direccion = trim($_POST['direccion'] ?? '');
$id_ubicacion = $_POST['id_ubicacion'] ?? null;
$fecha_recepcion = trim($_POST['fecha_recepcion'] ?? '');

if (!$codigo_principal || !$id_propietario || !$direccion || !$id_ubicacion || !$fecha_recepcion) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan campos requeridos.",
        "received_data" => $_POST
    ]);
    exit;
}

try {
    $conexion->begin_transaction();

    // Actualizar tabla llaves
    $stmt = $conexion->prepare("UPDATE llaves SET 
        direccion = ?, 
        id_ubicacion = ?, 
        fecha_recepcion = ? 
        WHERE codigo_principal = ? AND id_propietario = ?");
    if (!$stmt) throw new Exception("Error preparando consulta: " . $conexion->error);
    $stmt->bind_param("sisss", $direccion, $id_ubicacion, $fecha_recepcion, $codigo_principal, $id_propietario);
    if (!$stmt->execute()) throw new Exception("Error ejecutando actualización: " . $stmt->error);
    $stmt->close();

    // Obtener datos para el movimiento
    $query = "SELECT p.nombre_propietario, u.nombre_ubicacion 
              FROM llaves l
              JOIN propietario p ON l.id_propietario = p.id_propietario
              JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion
              WHERE l.codigo_principal = ? AND l.id_propietario = ?";
    $stmt = $conexion->prepare($query);
    if (!$stmt) throw new Exception("Error preparando consulta: " . $conexion->error);
    $stmt->bind_param("ss", $codigo_principal, $id_propietario);
    if (!$stmt->execute()) throw new Exception("Error ejecutando consulta: " . $stmt->error);
    $result = $stmt->get_result();
    $datos = $result->fetch_assoc();
    $stmt->close();

    if (!$datos) throw new Exception("No se encontraron datos para registrar el movimiento.");

    // Buscar id del usuario en la tabla usuarios
    $usuario_nombre = $_SESSION['s_usuario'];
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE nombre = ?");
    if (!$stmt) throw new Exception("Error preparando consulta usuario: " . $conexion->error);
    $stmt->bind_param("s", $usuario_nombre);
    if (!$stmt->execute()) throw new Exception("Error ejecutando consulta usuario: " . $stmt->error);
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) throw new Exception("Usuario no encontrado en la base de datos.");

    $id_usuario = $user['id'];
    $tipo_movimiento = 'Modificación';
    $fecha_actual = date("Y-m-d H:i:s");

    // Insertar movimiento
    $stmt = $conexion->prepare("INSERT INTO movimientos_llaves 
        (codigo_principal, id_propietario, id_ubicacion, tipo_movimiento, fecha, id_usuario) 
        VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception("Error preparando inserción de movimiento: " . $conexion->error);

    $stmt->bind_param(
        "ssssss",
        $codigo_principal,
        $id_propietario,
        $id_ubicacion,
        $tipo_movimiento,
        $fecha_actual,
        $id_usuario
    );

    if (!$stmt->execute()) throw new Exception("Error registrando movimiento: " . $stmt->error);
    $stmt->close();

    $conexion->commit();

    echo json_encode([
        "success" => true,
        "message" => "Actualización correcta.",
        "movimiento_registrado" => true
    ]);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "error_details" => $conexion->error
    ]);
}
