<?php
// Test Valid Code
$valid_code = 'AGISL/EHS/FA/017/W13122X4HP3KN';

echo "Testing valid code: $valid_code\n";
$response = file_get_contents("http://localhost:8200/backend/verify_cert.php?code=" . urlencode($valid_code));
echo "Response: $response\n\n";

// Test Invalid Code
$invalid_code = 'NON_EXISTENT_CODE';
echo "Testing invalid code: $invalid_code\n";
$response = file_get_contents("http://localhost:8200/backend/verify_cert.php?code=" . urlencode($invalid_code));
echo "Response: $response\n\n";
?>
