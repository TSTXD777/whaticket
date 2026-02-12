<?php
// Script to set default passwords for existing users who don't have passwords
// This is useful after migration when existing users don't have passwords set

$host = 'localhost';
$dbname = 'whaticket';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get users who don't have passwords set (empty or null)
    $stmt = $pdo->prepare("SELECT id, nombre, correo FROM users WHERE password = '' OR password IS NULL");
    $stmt->execute();
    $usersWithoutPasswords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($usersWithoutPasswords)) {
        echo "All users already have passwords set.\n";
        exit;
    }

    echo "Found " . count($usersWithoutPasswords) . " users without passwords.\n";
    echo "Setting default password 'password123' for all users without passwords.\n";
    echo "Users should change their passwords after first login.\n\n";

    // Default password
    $defaultPassword = 'password123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // Update all users without passwords
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE password = '' OR password IS NULL");
    $stmt->execute([$hashedPassword]);

    echo "Default passwords set successfully!\n\n";
    echo "Users with default passwords:\n";

    foreach ($usersWithoutPasswords as $user) {
        echo "- {$user['nombre']} ({$user['correo']})\n";
    }

    echo "\nDefault password: password123\n";
    echo "Please inform users to change their passwords after login.\n";

} catch(PDOException $e) {
    die("Error setting default passwords: " . $e->getMessage() . "\n");
}
?>
