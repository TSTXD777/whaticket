<?php

header('Content-Type: application/json; charset=utf-8');

$env = parse_ini_file(__DIR__ . '/../.env');

$host = $env['DB_HOST'];
$dbname = $env['DB_NAME'];
$username = $env['DB_USER'];
$password = $env['DB_PASSWORD'];
$port = $env['DB_PORT'];

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die(json_encode([
        "ok"=>false,
        "mensaje"=>$e->getMessage()
    ]));
}

// Helper: return config value by key
function getConfigValue(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare('SELECT config_value FROM config WHERE config_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['config_value'] : $default;
}

// Helper: upsert config value by key
function upsertConfigValue(PDO $pdo, string $key, string $value): bool {
    $stmt = $pdo->prepare('INSERT INTO config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)');
    return $stmt->execute([$key, $value]);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !isset($input['mensaje'])) {
        echo json_encode(['ok' => false, 'mensaje' => 'Parámetros inválidos']);
        exit;
    }

    $mensaje = trim($input['mensaje']);
    if ($mensaje === '') {
        echo json_encode(['ok' => false, 'mensaje' => 'El mensaje no puede estar vacío']);
        exit;
    }

    $success = upsertConfigValue($pdo, 'chatbot_message', $mensaje);
    echo json_encode(['ok' => $success]);
    exit;
}

// Default: GET (or other) - return configuration
$mensaje = getConfigValue($pdo, 'chatbot_message', '¡Hola! ¿En qué puedo ayudarte?');

echo json_encode(['ok' => true, 'mensaje' => $mensaje]);
