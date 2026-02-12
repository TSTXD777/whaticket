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

if ($method === "GET") {
    // Get chatbot configuration
    $stmt = $pdo->prepare("SELECT config_value FROM config WHERE config_key = 'chatbot_message'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $config = ["mensaje" => $result ? $result['config_value'] : "Hola, ¿en qué puedo ayudarte?"];
    echo json_encode($config);
    exit;
}

if ($method === "POST") {
    $mensaje = trim($data["mensaje"] ?? "Hola, ¿en qué puedo ayudarte?");

    // Update or insert config
    $stmt = $pdo->prepare("INSERT INTO config (config_key, config_value) VALUES ('chatbot_message', ?) ON DUPLICATE KEY UPDATE config_value = ?");
    $stmt->execute([$mensaje, $mensaje]);

    echo json_encode(["ok" => true]);
}
