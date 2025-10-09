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

    // Cabecera con el nombre de la empresa
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 15, utf8_decode('IDP GESTIÓN DE INMUEBLES'), 0, 1, 'C');
    $pdf->SetDrawColor(50, 50, 50);
    $pdf->SetLineWidth(0.7);
    $pdf->Line(20, 35, 190, 35);  // línea debajo del título

    $pdf->Ln(15); // espacio después de la cabecera

    // Título del documento
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Registro de Movimiento de Llave', 0, 1, 'C');

    $pdf->Ln(10); // espacio antes de los datos

    // Mostrar solo los datos necesarios
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 10, utf8_decode('Código Principal:'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, $movimiento['codigo_principal'], 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 10, 'Propietario:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, $movimiento['nombre_propietario'], 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 10, utf8_decode('Ubicación:'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, $movimiento['nombre_ubicacion'], 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 10, 'Fecha:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, date('d/m/Y H:i', strtotime($movimiento['fecha'])), 0, 1);

    // Espacio para la firma
    $pdf->Ln(20);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Firma de la persona que recibe la llave:', 0, 1);

    $pdf->Ln(20);
    $pdf->Cell(0, 15, '_____________________________', 0, 1, 'C');
    $pdf->Cell(0, 10, '(Nombre y firma)', 0, 1, 'C');

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
