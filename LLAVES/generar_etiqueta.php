<?php
define('FPDF_FONTPATH', __DIR__ . '/font/');
require(__DIR__ . '/fpdf.php');
require(__DIR__ . '/conexion.php');

// Conversión mm a puntos para control fino si se necesitara (FPDF usa mm por defecto)

function generarEtiqueta($codigo_principal, $id_propietario)
{
    global $conexion;

    // Obtener datos de la llave. Se prepara el valor imprimible de alarma con fallback al código secundario
    $sql = "SELECT 
                l.codigo_principal,
                l.codigo_secundario,
                l.direccion,
                l.tiene_alarma,
                l.codigo_alarma,
                COALESCE(NULLIF(l.codigo_alarma, ''), NULLIF(l.codigo_secundario, '')) AS codigo_alarma_impresion,
                p.nombre_propietario,
                po.nombre_poblacion
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

    // Crear PDF de 50x20 mm, orientación horizontal
    $ancho = 50; // mm
    $alto  = 20; // mm
    $pdf = new FPDF('L', 'mm', array($ancho, $alto));
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);
    // Márgenes contenidos para maximizar área útil
    $pdf->SetMargins(1, 1, 1);

    // Borde fino
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Rect(0.8, 0.8, $ancho - 1.6, $alto - 1.6);

    // Contenido de la etiqueta (centrado y compacto)
    // Línea 1: Código principal (en negrita)
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell(0, 3.6, utf8_decode($llave['codigo_principal']), 0, 1, 'C');

    // Línea 2: Dirección (recortada si es muy larga)
    $pdf->SetFont('Arial', '', 6.2);
    $direccion = trim((string)$llave['direccion']);
    if (mb_strlen($direccion) > 60) {
        $direccion = mb_substr($direccion, 0, 60) . '…';
    }
    // MultiCell para permitir dos líneas estrechas
    $pdf->MultiCell(0, 2.8, utf8_decode($direccion), 0, 'C');

    // Línea 3: ALARMA → solo si tiene_alarma = 1 y hay codigo_alarma
    $tieneAlarma = isset($llave['tiene_alarma']) && (intval($llave['tiene_alarma']) === 1 || $llave['tiene_alarma'] === '1');
    $codigoAlarma = trim((string)($llave['codigo_alarma'] ?? ''));
    if ($tieneAlarma && $codigoAlarma !== '' && strtoupper($codigoAlarma) !== 'NULL') {
        $pdf->SetFont('Arial', 'B', 7.0);
        $pdf->Cell(0, 3.0, utf8_decode('ALARMA: ' . $codigoAlarma), 0, 1, 'C');
    }

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


