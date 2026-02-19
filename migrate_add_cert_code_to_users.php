<?php
$dbFile = __DIR__ . '/backend/data.db';
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if cert_code column already exists
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $exists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'cert_code') {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN cert_code TEXT");
        echo "Migration successful: Column 'cert_code' added to 'users' table.\n";
    }
    else {
        echo "Migration skipped: Column 'cert_code' already exists in 'users' table.\n";
    }
}
catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
