<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Include FPDF - Using relative path
require_once __DIR__ . '/libs/fpdf186/fpdf.php';

// 2. Get form data
$name = $_POST['f_name'] ?? 'No Name';
$date = $_POST['email'] ?? date('Y-m-d');
$signature = $_POST['signature'] ?? '';

// 3. Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'AGREEMENT - ' . $name, 0, 1, 'C');
$pdf->Ln(10);

// Add content
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 10, "Name: $name\nDate: $date");

// Add signature if exists
if (!empty($signature)) {
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Signature:', 0, 1);
    $img = str_replace('data:image/png;base64,', '', $signature);
    $img = base64_decode($img);
    $pdf->Image('@' . $img, 10, $pdf->GetY(), 50, 20, 'PNG');
}

// 4. Force download
$pdf->Output('D', 'agreement.pdf');
exit();
?>