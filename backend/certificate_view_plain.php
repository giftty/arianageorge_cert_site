
<?php
session_start();
require_once('TCPDF-main/tcpdf.php');


generateCertificate(false);
function generateCertificate($download = true)
{

  $data = $_SESSION['cert_data'];
  $name = $data['name'];
  $issued = $data['issued'];
  $validity_period = $data['validity_period'];
  $certificate_code = $data['certificate_code'];
  $unique_code = $data['unique_code'];
  $qr_code = $data['qr_code']; // Optional
  $expiration_date = $data['expiration_date'];
  $bg_image = $data['bg_image'];
  // Create PDF (A4 Landscape)
  $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

  // Remove header/footer
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);

  // Remove margins and auto page breaks
  $pdf->SetMargins(0, 0, 0);
  $pdf->SetAutoPageBreak(false, 0);

  $pdf->AddPage();

  // Check background image exists
  if (!file_exists($bg_image)) {
    die('Background image not found: ' . $bg_image);
  }

  // Render background full page
  $pdf->Image(
    $bg_image,
    0, // X
    0, // Y
    297, // Width (A4 landscape)
    210, // Height
    'JPG',
    '',
    '',
    false,
    300,
    '',
    false,
    false,
    0
  );

  // =======================
  // Place name (centered)
  // =======================
  $pdf->SetFont('helvetica', 'B', 28);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetXY(0, 92); // Adjust Y as needed
  $pdf->Cell(297, 0, strtoupper($name), 0, 1, 'C');

  // =======================
  // Bottom Left Details
  // =======================
  // Prepare values
  $issueDateText = "Issue Date : " . $issued;
  $validityText = "Validity Period : " . $validity_period;
  $certNumberText = "Certificate Number : " . $certificate_code;
  $uniqueIdText = "Unique number : " . $unique_code;
  $expiryDateText = "Expiry Date : " . $expiration_date;

  // Draw White Background
  // $startX = 14;
  // $startY = 133;
  // $rectWidth = 120;
  // $rectHeight = 42;
  // $pdf->SetFillColor(255, 255, 255);
  // $pdf->Rect($startX, $startY, $rectWidth, $rectHeight, 'F');

  // Output formatting
  $pdf->SetTextColor(70, 50, 110); // A dark purplish color to match the image
  $startX = 14; // Adjust left margin
  $startY = 151; // Adjust starting Y position

  $pdf->SetFont('helvetica', '', 11);

  // Issue Date
  $pdf->SetXY($startX, $startY);
  $pdf->Write(0, "Issue Date : ");
  $pdf->SetFont('helvetica', 'B', 11);
  $pdf->SetTextColor(0, 0, 0); // Black for the value
  $pdf->Write(0, $issued);

  // Validity Period
  $pdf->SetFont('helvetica', '', 11);
  $pdf->SetTextColor(70, 50, 110);
  $startY += 6;
  $pdf->SetXY($startX, $startY);
  $pdf->Write(0, "Validity Period : ");
  $pdf->SetFont('helvetica', 'B', 11);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->Write(0, $validity_period);

  // Certificate Number
  $pdf->SetFont('helvetica', '', 11);
  $pdf->SetTextColor(70, 50, 110);
  $startY += 6;
  $pdf->SetXY($startX, $startY);
  $pdf->Write(0, "Certificate Number : ");
  $pdf->SetFont('helvetica', 'B', 11);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->Write(0, $certificate_code);

  // Unique number
//   $pdf->SetFont('helvetica', '', 11);
//   $pdf->SetTextColor(70, 50, 110);
//   $startY += 6;
//   $pdf->SetXY($startX, $startY);
//   $pdf->Write(0, "Unique number : ");
//   $pdf->SetFont('helvetica', 'B', 11);
//   $pdf->SetTextColor(0, 0, 0);
//   $pdf->Write(0, $unique_code);

  // Expiry Date
  $pdf->SetFont('helvetica', '', 11);
  $pdf->SetTextColor(70, 50, 110);
  $startY += 6;
  $pdf->SetXY($startX, $startY);
  $pdf->Write(0, "Expiry Date : ");
  $pdf->SetFont('helvetica', 'B', 11);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->Write(0, $expiration_date);

  // =======================
  // PDF417 barcode
  // =======================
  $style = [
    'border' => false,
    'padding' => 0,
    'fgcolor' => [0, 0, 0],
    'bgcolor' => false
  ];
  $pdf->write2DBarcode(
    $qr_code,
    'PDF417',
    82, // X
    175,
    50,
    17,
    $style,
    'M',
    true
  );
  $pdf->SetXY(93, 192);
  $pdf->Cell(0, 0, 'VERIFY HERE', 0, 1);
  // =======================
  // Output PDF
  // =======================
  $safe_code = str_replace('/', '-', $certificate_code);
  $safe_name = preg_replace('/[^A-Za-z0-9_\- ]/', '', $name);

  $filename = 'Certificate_for_' . $safe_name . '_code_' . $safe_code . '.pdf';
   ob_end_clean();
  if ($download) {
    // Force download
    $pdf->Output($filename, 'D');
  }
  else {
    // Display inline
    $pdf->Output($filename, 'I');
  }

  exit;
}