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

if (!$id) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "ID de usuario requerido"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET activo = FALSE WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "ok" => true,
            "mensaje" => "Usuario desactivado correctamente"
        ]);
    } else {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Usuario no encontrado"
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al desactivar usuario: " . $e->getMessage()
    ]);
}
