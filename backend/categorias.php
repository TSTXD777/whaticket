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

$method = $_SERVER["REQUEST_METHOD"];
$data = json_decode(file_get_contents("php://input"), true) ?? [];

// GET → listar categorias
if ($method === "GET") {
    $stmt = $pdo->query("SELECT nombre FROM categories ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($categorias);
    exit;
}

// POST → add / delete
$action = $data["action"] ?? "";

if ($action === "add") {
    $nombre = trim($data["nombre"] ?? "");
    if ($nombre) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
        } catch(PDOException $e) {
            // Category might already exist, ignore error
        }
    }
}

if ($action === "delete") {
    $nombre = $data["nombre"] ?? "";
    if ($nombre) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE nombre = ?");
        $stmt->execute([$nombre]);
    }
}

echo json_encode(["ok" => true]);
