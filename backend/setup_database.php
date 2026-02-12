<?php
// Database setup script for Whaticket
// Run this once to create the database and tables

$host = 'localhost';
$dbname = 'whaticket';
$username = 'root';
$password = '';

try {
    // Connect to MySQL (without specifying database first)
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Select the database
    $pdo->exec("USE `$dbname`");

    // Create tables
    $tables = [
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            correo VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol ENUM('admin', 'tecnico', 'usuario') NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Tickets table
        "CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria VARCHAR(100) NOT NULL,
            prioridad ENUM('Baja', 'Media', 'Alta', 'Urgente') NOT NULL,
            descripcion TEXT NOT NULL,
            estado ENUM('Pendiente', 'En Progreso', 'Resuelto', 'Cerrado') DEFAULT 'Pendiente',
            comentarios JSON DEFAULT ('[]'),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Categories table
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // Knowledge base articles table
        "CREATE TABLE IF NOT EXISTS kb_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            category VARCHAR(255) NOT NULL,
            keywords TEXT,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Configuration table
        "CREATE TABLE IF NOT EXISTS config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(255) UNIQUE NOT NULL,
            config_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
        echo "Table created successfully\n";
    }

    // Insert default categories
    $pdo->exec("INSERT IGNORE INTO categories (nombre) VALUES
        ('Soporte'),
        ('Redes'),
        ('Software'),
        ('Hardware')");

    // Insert default config
    $pdo->exec("INSERT IGNORE INTO config (config_key, config_value) VALUES
        ('chatbot_message', 'Hola, ¿en qué puedo ayudarte?')");

    echo "Database setup completed successfully!\n";

} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
?>
