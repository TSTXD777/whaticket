<?php
require __DIR__ . '/vendor/autoload.php'; // carga Composer y phpdotenv

// Crea y carga las variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Ahora las variables están en $_ENV
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASSWORD'];
$port = $_ENV['DB_PORT'];

// Conexión a MySQL
$conn = new mysqli($host, $user, $pass, $db, $port);

// Verifica conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "Conectado correctamente";
?>