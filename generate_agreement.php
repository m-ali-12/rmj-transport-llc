<?php
// Enable error reporting to debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Include FPDF (use relative path)
require_once('libs/fpdf186/fpdf.php');

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

// Add signature if exists (no temp file needed)
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


function generateAgreementPDF($name, $date, $signaturePath) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('MJ Hauling United LLC');
    $pdf->SetAuthor('MJ Hauling United LLC');
    $pdf->SetTitle('Shipper Agreement');
    $pdf->SetSubject('Shipper Agreement Form');

    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Add content
    $html = '<h1 style="text-align:center;">Shipper Agreement</h1>';
    $html .= '<p><strong>Customer Name:</strong> ' . $name . '</p>';
    $html .= '<p><strong>Agreement Date:</strong> ' . $date . '</p>';
    $html .= '<h3>Terms & Conditions</h3>';
    $html .= '<h4>1. Exclusive Broker Arrangement</h4>';
    $html .= '<p>By signing this agreement, the Client agrees to work exclusively with the Broker for the entire shipping period. If another broker or carrier is engaged during this period, a non-refundable deposit will be retained.</p>';
    // Add all other terms here...

    // Add signature if available
    if (!empty($signaturePath)) {
        $html .= '<h3>Customer Signature:</h3>';
        $html .= '<img src="' . $signaturePath . '" width="150" />';
    }

    // Write HTML content
    $pdf->writeHTML($html, true, false, true, false, '');

    // Save PDF to a temporary file
    $pdfFileName = 'shipper_agreement_' . uniqid() . '.pdf';
    $pdfFilePath = 'assets/agreements/' . $pdfFileName;
    
    // Ensure directory exists
    if (!file_exists('assets/agreements/')) {
        mkdir('assets/agreements/', 0755, true);
    }

    // Save PDF
    $pdf->Output($pdfFilePath, 'F');

    return $pdfFilePath;
}

?>

