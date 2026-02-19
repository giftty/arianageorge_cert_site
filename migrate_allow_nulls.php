<?php
$dbFile = __DIR__ . '/backend/data.db';
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQLite doesn't support ALTER TABLE for changing NOT NULL constraints directly.
    // We need to recreate the table.

    $pdo->exec("BEGIN TRANSACTION");

    // 1. Rename existing table
    $pdo->exec("ALTER TABLE certificates RENAME TO certificates_old");

    // 2. Create new table with updated constraints
    $pdo->exec("CREATE TABLE certificates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cert_code TEXT UNIQUE NOT NULL,
        owner TEXT NOT NULL,
        cert_type TEXT,
        date_issued DATE,
        expiration_date DATE,
        event TEXT,
        views INTEGER DEFAULT 0,
        downloads INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Copy data from old table
    $pdo->exec("INSERT INTO certificates (id, cert_code, owner, cert_type, date_issued, expiration_date, event, views, downloads, created_at)
                SELECT id, cert_code, owner, cert_type, date_issued, expiration_date, event, views, downloads, created_at FROM certificates_old");

    // 4. Drop old table
    $pdo->exec("DROP TABLE certificates_old");

    $pdo->exec("COMMIT");
    echo "Migration successful: cert_type and date_issued are now optional.\n";
}
catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
