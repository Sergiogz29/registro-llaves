<?php
define('FPDF_FONTPATH', __DIR__ . '/font/');
require(__DIR__ . '/fpdf.php');
require(__DIR__ . '/conexion.php');

// Conversión mm a puntos para control fino si se necesitara (FPDF usa mm por defecto)

function generarEtiqueta($codigo_principal, $id_propietario)
{
    global $conexion;

    // Obtener datos de la llave
    $sql = "SELECT l.codigo_principal, l.codigo_secundario, l.direccion, l.tiene_alarma, l.codigo_alarma,
                   p.nombre_propietario, po.nombre_poblacion
            FROM llaves l
            INNER JOIN propietario p ON l.id_propietario = p.id_propietario
            LEFT JOIN poblacion po ON l.id_poblacion = po.id_poblacion
            WHERE l.codigo_principal = ? AND l.id_propietario = ?";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die('Error preparando consulta');
    }
    $stmt->bind_param('si', $codigo_principal, $id_propietario);
    $stmt->execute();
    $res = $stmt->get_result();
    $llave = $res->fetch_assoc();
    $stmt->close();

    if (!$llave) {
        die('Llave no encontrada');
    }

    // Crear PDF de 50x20 mm, orientación horizontal para mejor aprovechamiento
    $ancho = 50; // mm
    $alto = 20;  // mm
    $pdf = new FPDF('L', 'mm', array($ancho, $alto));
    $pdf->AddPage();

    // Márgenes mínimos
    $pdf->SetMargins(2, 2, 2);
    $pdf->SetAutoPageBreak(false);

    // Contenido de la etiqueta:
    // Línea 1: Código principal (en negrita un poco más grande)
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 5, utf8_decode($llave['codigo_principal']), 0, 1, 'L');

    // Línea 2: Dirección (recortada si es muy larga)
    $pdf->SetFont('Arial', '', 7);
    $direccion = trim((string)$llave['direccion']);
    if (mb_strlen($direccion) > 60) {
        $direccion = mb_substr($direccion, 0, 60) . '…';
    }
    $pdf->Cell(0, 4, utf8_decode($direccion), 0, 1, 'L');

    // Línea 3: Alarma (solo si tiene y hay código)
    $tieneAlarma = isset($llave['tiene_alarma']) && intval($llave['tiene_alarma']) === 1;
    if ($tieneAlarma) {
        $codigoAlarma = trim((string)($llave['codigo_alarma'] ?? ''));
        if ($codigoAlarma !== '') {
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(0, 4, utf8_decode('ALARMA: ' . $codigoAlarma), 0, 1, 'L');
        }
    }

    // Línea 4: Municipio (opcional)
    $municipio = trim((string)($llave['nombre_poblacion'] ?? ''));
    if ($municipio !== '') {
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(0, 4, utf8_decode('Municipio: ' . $municipio), 0, 1, 'L');
    }

    // Borde fino opcional
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Rect(1, 1, $ancho - 2, $alto - 2);

    $nombre_pdf = 'etiqueta_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $codigo_principal) . '.pdf';
    $pdf->Output('I', $nombre_pdf);
    exit;
}

// --- EJECUCIÓN ---
if (isset($_GET['codigo_principal']) && isset($_GET['id_propietario'])) {
    $codigo = $_GET['codigo_principal'];
    $idProp = (int)$_GET['id_propietario'];
    generarEtiqueta($codigo, $idProp);
} else {
    die('Parámetros incompletos.');
}


