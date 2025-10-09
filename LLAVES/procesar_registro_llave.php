<?php

session_start();
if (!isset($_SESSION["s_usuario"])) {
    header("Location: login.php");
    exit();
}

include 'conexion.php';

// Recoger datos del formulario
$codigo_principal = $_POST['codigo_principal'] ?? '';
$codigo_secundario = $_POST['codigo_secundario'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$fecha_recepcion = $_POST['fecha_recepcion'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$id_ubicacion = $_POST['id_ubicacion'] ?? '';
$id_propietario = $_POST['id_propietario'] ?? '';
$nuevo_propietario = trim($_POST['nuevo_propietario'] ?? '');
$id_poblacion = $_POST['id_poblacion'] ?? '';
$nueva_poblacion = trim($_POST['nueva_poblacion'] ?? '');
// Campos de alarma (nuevos)
$tiene_alarma = isset($_POST['tiene_alarma']) ? 1 : 0;
$codigo_alarma = trim($_POST['codigo_alarma'] ?? '');
if (!$tiene_alarma) {
    $codigo_alarma = '';
}

// Insertar nuevo propietario si se escribió uno
if (empty($id_propietario) && !empty($nuevo_propietario)) {
    $stmt = $conexion->prepare("INSERT INTO propietario (nombre_propietario) VALUES (?)");
    $stmt->bind_param("s", $nuevo_propietario);
    $stmt->execute();
    $id_propietario = $conexion->insert_id;
}

// Insertar nueva población si se escribió una
if (empty($id_poblacion) && !empty($nueva_poblacion)) {
    $stmt = $conexion->prepare("INSERT INTO poblacion (nombre_poblacion) VALUES (?)");
    $stmt->bind_param("s", $nueva_poblacion);
    $stmt->execute();
    $id_poblacion = $conexion->insert_id;
}

// Verificar campos requeridos
if ($codigo_principal && $id_propietario && $id_ubicacion && $fecha_recepcion) {
    // Asegurar columnas de alarma si no existen
    $hasAlarmaColumns = false;
    $resCols = $conexion->query("SHOW COLUMNS FROM llaves LIKE 'tiene_alarma'");
    if ($resCols && $resCols->num_rows > 0) {
        $hasAlarmaColumns = true;
    } else {
        $conexion->query("ALTER TABLE llaves ADD COLUMN tiene_alarma TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN codigo_alarma VARCHAR(100) NULL");
        $resCols2 = $conexion->query("SHOW COLUMNS FROM llaves LIKE 'tiene_alarma'");
        $hasAlarmaColumns = ($resCols2 && $resCols2->num_rows > 0);
    }

    if ($hasAlarmaColumns) {
        // Insertar incluyendo columnas de alarma
        $stmt = $conexion->prepare("INSERT INTO llaves (
                                        codigo_principal, id_propietario, codigo_secundario, direccion, id_poblacion, id_ubicacion, fecha_recepcion, observaciones, tiene_alarma, codigo_alarma
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param(
                "sisssissis",
                $codigo_principal,
                $id_propietario,
                $codigo_secundario,
                $direccion,
                $id_poblacion,
                $id_ubicacion,
                $fecha_recepcion,
                $observaciones,
                $tiene_alarma,
                $codigo_alarma
            );
        }
    } else {
        // Fallback: insertar sin columnas de alarma
        $stmt = $conexion->prepare("INSERT INTO llaves (codigo_principal, id_propietario, codigo_secundario, direccion, id_poblacion, id_ubicacion, fecha_recepcion, observaciones) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sisssiss", $codigo_principal, $id_propietario, $codigo_secundario, $direccion, $id_poblacion, $id_ubicacion, $fecha_recepcion, $observaciones);
        }
    }

    if ($stmt && $stmt->execute()) {
        $mensaje = "✅ Llave registrada correctamente.";
    } else {
        $mensaje = "❌ Error al registrar la llave: " . ($stmt ? $stmt->error : $conexion->error);
    }
} else {
    $mensaje = "❌ Faltan campos obligatorios.";
}

// Redirigir con mensaje
header("Location: registro_llave.php?mensaje=" . urlencode($mensaje));
exit;
