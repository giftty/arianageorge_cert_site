<?php
session_start();
header('Content-Type: application/json');

$dbFile = __DIR__ . '/data.db';

if (!isset($_GET['code'])) {
    echo json_encode(['status' => 'error', 'message' => 'No code provided.']);
    exit;
}

$cert_code = $_GET['code'];

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE cert_code = :code");
    $stmt->execute([':code' => $cert_code]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cert) {
        $_SESSION['view_cert_code'] = $cert_code;
        $_SESSION['view_cert_timestamp'] = time();
        $has_owner = !empty($cert['owner'])&& strlen($cert['owner'])>2;
        echo json_encode(['status' => 'success', 'message' => 'Certificate found.', 'has_owner' => $has_owner]);
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Certificate not found.']);
    }
}
catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
