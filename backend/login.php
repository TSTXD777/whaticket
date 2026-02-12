<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();

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

$correo = trim($data["correo"] ?? "");
$rol = $data["rol"] ?? "";

if (!$correo || !$rol) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Correo y rol son requeridos"
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

// Optional: Verify user exists and is active
try {
    $stmt = $pdo->prepare("SELECT id, nombre, password, rol, activo FROM users WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Usuario no encontrado"
        ]);
        exit;
    }

    if (!$user['activo']) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Usuario inactivo"
        ]);
        exit;
    }

    if ($user['rol'] !== $rol) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Rol incorrecto"
        ]);
        exit;
    }

    // Verificar contraseña
    if (!password_verify($data["password"] ?? "", $user['password'])) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Contraseña incorrecta"
        ]);
        exit;
    }

    // Guardamos login en sesión
    $_SESSION["user_id"] = $user['id'];
    $_SESSION["correo"] = $correo;
    $_SESSION["rol"] = $rol;
    $_SESSION["nombre"] = $user['nombre'];

    echo json_encode([
        "ok" => true,
        "mensaje" => "Acceso permitido"
    ]);

} catch(PDOException $e) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error de autenticación: " . $e->getMessage()
    ]);
}
