<?php

require_once('TCPDF-main/tcpdf.php'); // adjust path if needed

// =====================
// DYNAMIC VARIABLES
// =====================
$name              = "JOHN DOE";
$issued            = "January 10, 2026";
$validity_period   = "Valid for 1 Year";
$certificate_code  = "AGISL/EHS/2026/001";
$unique_code       = "UNQ-883746-XY";
$expiration_date   = "January 10, 2027";
$bg_image          = __DIR__ . "/certificate-bg.jpg"; // local file path

// =====================
// CREATE PDF
// =====================
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);


// IMPORTANT: Remove margins
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

$pdf->AddPage();

// =====================
// BACKGROUND IMAGE
// =====================

// A4 Landscape size in mm = 297 x 210
$pdf->Image($bg_image, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

// =====================
// NAME (Centered like your HTML)
// =====================

$pdf->SetFont('helvetica', 'B', 28);
$pdf->SetTextColor(0, 0, 0);

// Centered horizontally
$pdf->SetXY(0, 95);
$pdf->Cell(297, 0, strtoupper($name), 0, 1, 'C');

// =====================
// ISSUE DATE
// =====================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(20, 155);
$pdf->Cell(0, 0, $issued, 0, 1);

// =====================
// VALIDITY PERIOD
// =====================
$pdf->SetXY(40, 165);
$pdf->Cell(0, 0, $validity_period, 0, 1);

// =====================
// CERTIFICATE CODE
// =====================
$pdf->SetXY(50, 150);
$pdf->Cell(0, 0, $certificate_code, 0, 1);

// =====================
// UNIQUE CODE
// =====================
$pdf->SetXY(25, 145);
$pdf->Cell(0, 0, $unique_code, 0, 1);

// =====================
// EXPIRATION DATE
// =====================
$pdf->SetXY(35, 170);
$pdf->Cell(0, 0, $expiration_date, 0, 1);

// =====================
// PDF417 BARCODE
// =====================

$style = array(
    'border' => false,
    'padding' => 0,
    'fgcolor' => array(0,0,0),
    'bgcolor' => false
);

// Position similar to your HTML (bottom center area)
$pdf->write2DBarcode(
    $certificate_code,
    'PDF417',
    110,   // X
    175,   // Y
    60,    // Width
    20,    // Height
    $style,
    'N'
);

// =====================
// OUTPUT
// =====================
//$pdf->Output('Certificate_'.$certificate_code.'.pdf', 'I');
$pdf->Output('Certificate_'.$certificate_code.'.pdf', 'D');