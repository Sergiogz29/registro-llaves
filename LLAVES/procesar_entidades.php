<?php
session_start();
if (!isset($_SESSION["s_usuario"])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
$conexion->set_charset("utf8");

$tipo = $_GET['tipo'] ?? '';
$accion = $_GET['accion'] ?? '';
$id = $_GET['id'] ?? 0;
$valor = $_GET['valor'] ?? '';
$codigo = $_GET['codigo'] ?? '';
$id_propietario = $_GET['id_propietario'] ?? '';

switch ($accion) {
    case 'agregar':
        $tabla = $tipo;
        $campo_nombre = "nombre_$tipo";
        
        $stmt = $conexion->prepare("INSERT INTO $tabla ($campo_nombre) VALUES (?)");
        $stmt->bind_param("s", $valor);
        $stmt->execute();
        $nuevo_id = $conexion->insert_id;
        
        echo json_encode(['success' => true, 'id' => $nuevo_id]);
        break;
        
    case 'editar':
        $tabla = $tipo;
        $campo_nombre = "nombre_$tipo";
        $campo_id = "id_$tipo";
        
        $stmt = $conexion->prepare("UPDATE $tabla SET $campo_nombre = ? WHERE $campo_id = ?");
        $stmt->bind_param("si", $valor, $id);
        $stmt->execute();
        
        echo json_encode(['success' => $stmt->affected_rows > 0]);
        break;
        
    case 'eliminar':
        $tabla = $tipo;
        $campo_id = "id_$tipo";
        
        $stmt = $conexion->prepare("DELETE FROM $tabla WHERE $campo_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        echo json_encode(['success' => $stmt->affected_rows > 0]);
        break;
        
    default:
        if ($tipo === 'baja') {
            // Asegurar columna baja y actualizar
            $conexion->query("ALTER TABLE llaves ADD COLUMN IF NOT EXISTS baja TINYINT(1) NOT NULL DEFAULT 0");
            if ($codigo && $id_propietario !== '') {
                $marcar = ($accion === 'activar') ? 1 : 0;
                $stmt = $conexion->prepare("UPDATE llaves SET baja = ? WHERE codigo_principal = ? AND id_propietario = ?");
                if ($stmt) {
                    $stmt->bind_param("isi", $marcar, $codigo, $id_propietario);
                    $ok = $stmt->execute();
                    echo json_encode(['success' => $ok]);
                    exit;
                }
            }
            echo json_encode(['success' => false]);
            exit;
        }
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>