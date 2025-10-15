<?php
define('FPDF_FONTPATH', __DIR__ . '/font/');
require('fpdf.php');
require('conexion.php');

function generarPDFMovimiento($id_movimiento)
{
    global $conexion;

    $query = "SELECT m.*, p.nombre_propietario, u.nombre_ubicacion
              FROM movimientos_llaves m
              LEFT JOIN propietario p ON m.id_propietario = p.id_propietario
              LEFT JOIN ubicacion u ON m.id_ubicacion = u.id_ubicacion
              WHERE m.id_movimiento = ?";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $id_movimiento);
    $stmt->execute();
    $movimiento = $stmt->get_result()->fetch_assoc();

    if (!$movimiento) {
        die("Movimiento no encontrado.");
    }

    $pdf = new FPDF();
    $pdf->AddPage();

    // Márgenes más amplios
    $pdf->SetMargins(20, 20, 20);

    // Cabecera con logo y nombre
    $logo = __DIR__ . '/img/logo1.png';
    if (file_exists($logo)) {
        $pdf->Image($logo, 20, 12, 18); // logo a la izquierda
    }
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 15, utf8_decode('IDP GESTIÓN DE INMUEBLES'), 0, 1, 'C');
    $pdf->SetDrawColor(224, 142, 27);
    $pdf->SetLineWidth(0.7);
    $pdf->Line(20, 35, 190, 35);  // línea debajo del título

    $pdf->Ln(14); // más espacio después de la cabecera

    // Título del documento
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, utf8_decode('Registro de Movimiento de Llave'), 0, 1, 'C');

    $pdf->Ln(10); // espacio antes de los datos

    // Datos (sin recuadro)
    $pdf->SetX(22);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(45, 8, utf8_decode('Código Principal:'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $movimiento['codigo_principal'], 0, 1);

    $pdf->SetX(22);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(45, 8, utf8_decode('Ubicación:'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $movimiento['nombre_ubicacion'], 0, 1);

    $pdf->SetX(22);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(45, 8, 'Fecha:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, date('d/m/Y H:i', strtotime($movimiento['fecha'])), 0, 1);

    // Espacio para la firma
    $pdf->Ln(22);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, utf8_decode('Firma de la persona que recibe la llave:'), 0, 1);

    $pdf->Ln(20);
    $pdf->SetDrawColor(150, 150, 150);
    $pdf->Line(60, $pdf->GetY(), 150, $pdf->GetY());
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 8, utf8_decode('(Nombre y firma)'), 0, 1, 'C');

    // Generar el PDF en el navegador
    $nombre_pdf = "movimiento_" . $id_movimiento . ".pdf";
    $pdf->Output('I', $nombre_pdf);
    exit;
}

// --- EJECUCIÓN DEL SCRIPT ---
if (isset($_GET['id'])) {
    $id_mov = (int)$_GET['id'];
    generarPDFMovimiento($id_mov);
} else {
    die("Parámetros incompletos.");
}
