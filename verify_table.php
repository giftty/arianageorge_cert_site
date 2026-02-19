<?php
$dbFile = __DIR__ . '/backend/data.db';
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $stmt = $pdo->query("PRAGMA table_info(certificates)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($columns)) {
        echo "Table 'certificates' NOT found.";
    }
    else {
        echo "Table 'certificates' schema:\n";
        print_r($columns);
    }
}
catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
