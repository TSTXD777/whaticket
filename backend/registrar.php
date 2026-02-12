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
    die(json_encode(["ok" => false, "mensaje" => "Connection failed: " . $e->getMessage()]));
}

$data = json_decode(file_get_contents("php://input"), true);

$nombre = trim($data["nombre"] ?? "");
$correo = trim($data["correo"] ?? "");
$password = $data["password"] ?? "";
$rol = $data["rol"] ?? "";

if (!$nombre || !$correo || !$password || !$rol) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Todos los campos son requeridos"
    ]);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Correo electrónico inválido"
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "La contraseña debe tener al menos 6 caracteres"
    ]);
    exit;
}

try {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (nombre, correo, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $correo, $hashedPassword, $rol]);

    echo json_encode([
        "ok" => true,
        "mensaje" => "Usuario registrado correctamente"
    ]);
} catch(PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode([
            "ok" => false,
            "mensaje" => "El correo electrónico ya está registrado"
        ]);
    } else {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Error al registrar usuario: " . $e->getMessage()
        ]);
    }
}
