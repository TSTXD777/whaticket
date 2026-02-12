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

$id = intval($data["id"] ?? 0);
$nombre = trim($data["nombre"] ?? "");
$correo = trim($data["correo"] ?? "");
$rol = $data["rol"] ?? "";

if (!$id || !$nombre || !$correo || !$rol) {
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

try {
    $stmt = $pdo->prepare("UPDATE users SET nombre = ?, correo = ?, rol = ? WHERE id = ?");
    $stmt->execute([$nombre, $correo, $rol, $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "ok" => true,
            "mensaje" => "Usuario editado correctamente"
        ]);
    } else {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Usuario no encontrado"
        ]);
    }
} catch(PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode([
            "ok" => false,
            "mensaje" => "El correo electrónico ya está en uso por otro usuario"
        ]);
    } else {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Error al editar usuario: " . $e->getMessage()
        ]);
    }
}
