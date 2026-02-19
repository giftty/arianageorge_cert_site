<?php
// Mock POST data
$_POST = [
    'cert_code' => '123456789012',
    'owner' => 'John Doe',
    'cert_type' => 'Diploma',
    'date_issued' => '2026-02-14',
    'event' => 'Global AI Summit'
];

$_GET['action'] = 'add_certificate';
$_GET['ajax'] = '1';

// Mock server env
$_SERVER['REQUEST_METHOD'] = 'POST';

// Start output buffering to capture JSON response
ob_start();
require 'backend/backend.php';
$response = ob_get_clean();

echo "Response from backend:\n";
echo $response . "\n\n";

// Verify in DB
$dbFile = __DIR__ . '/backend/data.db';
$pdo = new PDO("sqlite:" . $dbFile);
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE cert_code = '123456789012'");
$stmt->execute();
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cert) {
    echo "Verification SUCCESS: Certificate found in database.\n";
    print_r($cert);
}
else {
    echo "Verification FAILED: Certificate not found in database.\n";
}
?>
