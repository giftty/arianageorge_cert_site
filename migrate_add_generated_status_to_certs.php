<?php
$dbFile = __DIR__ . '/backend/data.db';
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if generated_status column already exists
    $stmt = $pdo->query("PRAGMA table_info(certificates)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $exists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'generated_status') {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $pdo->exec("ALTER TABLE certificates ADD COLUMN generated_status TEXT DEFAULT 'not-generated'");
        echo "Migration successful: Column 'generated_status' added to 'certificates' table.\n";
    }
    else {
        echo "Migration skipped: Column 'generated_status' already exists in 'certificates' table.\n";
    }
}
catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
