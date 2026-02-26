<?php

require_once('TCPDF-main/tcpdf_barcodes_2d.php');


$code = $qr_code;
$type = "PDF417";

// Create a new TCPDF2DBarcode object
$barcodeobj = new TCPDF2DBarcode($code, $type,);

// Output the barcode as a PNG image
// Parameters: module width (pixels), height to width ratio (ratio)
//echo $barcodeobj->getBarcodeHTML(2, 2); 

// Alternatively, you can use getBarcodePngData() to embed it as a data URI in HTML
echo '<div style="display:flex;flex-direction:column;justify-content-center;align-items:center;height:100%"> <img style="width:100%;height:100%" src="data:image/png;base64,'.base64_encode($barcodeobj->getBarcodePngData(2,2)).'"><span style="color:black">VERIFY HERE </span> </div>';
