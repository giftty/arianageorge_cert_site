<?php
$username = 'testadmin';
$password = '12345678';

$dbFile = __DIR__ . '/backend/data.db';
$pdo = new PDO("sqlite:" . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username AND password = :password");
$stmt->execute([
    ':username' => $username,
    ':password' => $password
]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Login successful for user: " . $user['username'] . "\n";
}
else {
    echo "Login failed for credentials: " . $username . " / " . $password . "\n";

    // Check if user exists at all
    $stmt2 = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
    $stmt2->execute([':username' => $username]);
    $user2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($user2) {
        echo "User exists, but password mismatch.\n";
        echo "DB Password: [" . $user2['password'] . "]\n";
        echo "Input Password: [" . $password . "]\n";
        echo "Length DB: " . strlen($user2['password']) . "\n";
        echo "Length Input: " . strlen($password) . "\n";
    }
    else {
        echo "User does not exist.\n";
    }
}
?>
