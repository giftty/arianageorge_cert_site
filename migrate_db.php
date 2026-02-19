<?php
$dbFile = __DIR__ . '/backend/data.db';
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if columns exist
    $stmt = $pdo->query("PRAGMA table_info(certificates)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');

    if (!in_array('views', $columnNames)) {
        $pdo->exec("ALTER TABLE certificates ADD COLUMN views INTEGER DEFAULT 0");
        echo "Added 'views' column.\n";
    }
    else {
        echo "'views' column already exists.\n";
    }

    if (!in_array('downloads', $columnNames)) {
        $pdo->exec("ALTER TABLE certificates ADD COLUMN downloads INTEGER DEFAULT 0");
        echo "Added 'downloads' column.\n";
    }
    else {
        echo "'downloads' column already exists.\n";
    }
}
catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
