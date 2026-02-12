<?php
// Migration script for users table - Add password field
// Run this if you have an existing users table without password field

$host = 'localhost';
$dbname = 'whaticket';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if password column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'");
    $exists = $result->fetch();

    if (!$exists) {
        // Add password column
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER correo");

        // Add updated_at column if it doesn't exist
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
        $exists_updated = $result->fetch();

        if (!$exists_updated) {
            $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER activo");
        }

        echo "Migration completed successfully!\n";
        echo "- Added password column to users table\n";
        echo "- Added updated_at column to users table\n";
        echo "\nIMPORTANT: Existing users don't have passwords set.\n";
        echo "You'll need to set passwords for existing users manually or ask them to reset their passwords.\n";
    } else {
        echo "Migration already completed - password column exists.\n";
    }

} catch(PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
