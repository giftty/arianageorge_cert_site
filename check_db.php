<?php
$dbFile = __DIR__ . '/backend/data.db';
$pdo = new PDO("sqlite:" . $dbFile);
$stmt = $pdo->query("SELECT id, username, email, password FROM admins");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users, JSON_PRETTY_PRINT);
?>
