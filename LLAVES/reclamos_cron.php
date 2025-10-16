<?php
// Script para enviar correo con llaves no devueltas en ubicaciones que requieren reclamo
// Se puede ejecutar manualmente o por el Programador de tareas de Windows.
// php LLAVES/reclamos_cron.php

require_once __DIR__ . '/config_mail.php';

$conexion = new mysqli("localhost", "root", "", "registro_llaves");
if ($conexion->connect_error) {
    echo "Error DB";
    exit;
}
$conexion->set_charset('utf8');

// Modo forzado (envío manual desde el botón): incluye también las del mismo día
$force = isset($_GET['force']) && $_GET['force'] == '1';

// Asegurar columnas necesarias
$conexion->query("ALTER TABLE ubicacion ADD COLUMN IF NOT EXISTS requiere_reclamo TINYINT(1) NOT NULL DEFAULT 0");
$conexion->query("ALTER TABLE llaves ADD COLUMN IF NOT EXISTS estado VARCHAR(20) DEFAULT NULL");

// Seleccionar llaves en 'salida' con más de 7 días, y cuya ubicacion requiere reclamo
$dateCondition = $force ? "1=1" : "m.fecha < DATE_SUB(NOW(), INTERVAL 7 DAY)";
$reclamoCondition = $force ? "1=1" : "u.requiere_reclamo = 1";
$sql = "
SELECT l.codigo_principal, l.direccion, p.nombre_propietario, u.nombre_ubicacion, m.fecha
FROM llaves l
JOIN ubicacion u ON l.id_ubicacion = u.id_ubicacion
JOIN propietario p ON l.id_propietario = p.id_propietario
JOIN (
    SELECT codigo_principal, id_propietario, MAX(fecha) AS fecha
    FROM movimientos_llaves
    GROUP BY codigo_principal, id_propietario
) m ON m.codigo_principal = l.codigo_principal AND m.id_propietario = l.id_propietario
WHERE $reclamoCondition
  AND l.estado = 'salida'
  AND $dateCondition
ORDER BY m.fecha ASC";

$res = $conexion->query($sql);
if (!$res || $res->num_rows === 0) {
    echo "Sin reclamos pendientes\n";
    exit;
}

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

$asunto = $force ? "Llaves sin devolver (envío manual)" : "Llaves sin devolver (reclamo)";
$cuerpo = "Se detectaron llaves no devueltas en más de 7 días:\n\n";
foreach ($rows as $row) {
    $cuerpo .= sprintf("- Código: %s | Propietario: %s | Ubicación: %s | Último mov.: %s | Dirección: %s\n",
        $row['codigo_principal'],
        $row['nombre_propietario'],
        $row['nombre_ubicacion'],
        $row['fecha'],
        $row['direccion']
    );
}

// Obtener correo(s) de administradores configurados
$emails = [];
$resAdmins = $conexion->query("SELECT correo FROM usuarios WHERE rol='admin' AND correo IS NOT NULL AND correo <> ''");
if ($resAdmins) {
    while ($u = $resAdmins->fetch_assoc()) {
        $emails[] = $u['correo'];
    }
}
if (empty($emails) && defined('MAIL_TO')) {
    $emails[] = MAIL_TO; // fallback
}

if (!empty($emails)) {
    $to = implode(',', $emails);
    $headers = 'From: ' . MAIL_FROM . "\r\n" . 'X-Mailer: PHP/' . phpversion();
    @mail($to, $asunto, $cuerpo, $headers);
    echo ($force ? "Envío manual realizado a " : "Enviado reclamo a ") . $to . " (" . count($rows) . " llaves)\n";
} else {
    echo "No hay correos de admin configurados\n";
}



