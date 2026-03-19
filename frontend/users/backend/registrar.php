<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'whaticket';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? 3306;


try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error de conexión a la base de datos: " . $e->getMessage()
    ]);
    exit;
}

$contenido = file_get_contents("php://input");
$data = json_decode($contenido, true);

if (!$data || !is_array($data)) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se recibieron datos para registrar el usuario"
    ]);
    exit;
}

$nombre = trim((string)($data["nombre"] ?? ""));
$correo = trim((string)($data["correo"] ?? ""));
$passwordIngresada = trim((string)($data["password"] ?? ""));
$rol = trim((string)($data["rol"] ?? ""));

if ($nombre === "" || $correo === "" || $passwordIngresada === "" || $rol === "") {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Todos los campos son obligatorios"
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

if (strlen($passwordIngresada) < 6) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "La contraseña debe tener al menos 6 caracteres"
    ]);
    exit;
}

if (!in_array($rol, ["admin", "tecnico", "usuario"])) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Rol inválido"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioExistente) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "El correo electrónico ya está registrado"
        ]);
        exit;
    }
$stmt = $pdo->prepare("SELECT id FROM users WHERE correo=?");
$stmt->execute([$correo]);

if($stmt->fetch()){

echo json_encode([
"ok"=>false,
"mensaje"=>"Este correo ya está registrado"
]);

exit;

}
    $stmt = $pdo->prepare("INSERT INTO users (nombre, correo, password, rol, activo) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$nombre, $correo, $passwordIngresada, $rol]);

    echo json_encode([
        "ok" => true,
        "mensaje" => "Usuario registrado correctamente"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al registrar usuario: " . $e->getMessage()
    ]);
}