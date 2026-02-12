<?php
header("Content-Type: application/json; charset=UTF-8");

// Database configuration
$host = 'localhost';
$dbname = 'whaticket';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(["error" => "Connection failed: " . $e->getMessage()]));
}

try {
    $stmt = $pdo->query("SELECT id, nombre, correo, rol, activo, created_at FROM users ORDER BY created_at DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($usuarios);
} catch(PDOException $e) {
    echo json_encode(["error" => "Error al obtener usuarios: " . $e->getMessage()]);
}
