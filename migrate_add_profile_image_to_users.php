<?php
$dbFile = __DIR__ . '/backend/data.db';
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if profile_image column already exists
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $exists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'profile_image') {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image TEXT");
        echo "Migration successful: Column 'profile_image' added to 'users' table.\n";
    }
    else {
        echo "Migration skipped: Column 'profile_image' already exists in 'users' table.\n";
    }
}
catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
