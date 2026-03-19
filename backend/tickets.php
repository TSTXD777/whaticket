<?php
// backend/tickets.php
header("Content-Type: application/json; charset=UTF-8");

/*
---------------------------------------------------------
CARGAR VARIABLES DE ENTORNO
---------------------------------------------------------
Se cargan las credenciales de la base de datos desde
el archivo .env usando la librería phpdotenv.
*/

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

/*
---------------------------------------------------------
CONFIGURACIÓN DE BASE DE DATOS
---------------------------------------------------------
Las credenciales se obtienen desde el archivo .env
*/

$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];
$port = $_ENV['DB_PORT'];

try {
    $pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8",
    $username,
    $password
);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(["error" => "Connection failed: " . $e->getMessage()]));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// GET: devolver lista de tickets
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, categoria, prioridad, descripcion, estado, comentarios, created_at FROM tickets ORDER BY created_at DESC");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert JSON comments back to array
    foreach ($tickets as &$ticket) {
        $ticket['comentarios'] = json_decode($ticket['comentarios'], true) ?? [];
    }

    echo json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// POST: acciones
$raw = file_get_contents("php://input");
$input = json_decode($raw, true) ?? [];
$act = $input['action'] ?? $action ?? 'list';

switch ($act) {
    case 'create':
        $categoria = trim($input['categoria'] ?? 'General');
        $prioridad = trim($input['prioridad'] ?? 'Media');
        $descripcion = trim($input['descripcion'] ?? '');

        if ($descripcion === '') {
            http_response_code(400);
            echo json_encode(["error" => "Descripción vacía"]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tickets (categoria, prioridad, descripcion, estado, comentarios) VALUES (?, ?, ?, 'Pendiente', '[]')");
        $stmt->execute([$categoria, $prioridad, $descripcion]);

        $id = $pdo->lastInsertId();

        $nuevo = [
            "id" => (int)$id,
            "categoria" => $categoria,
            "prioridad" => $prioridad,
            "descripcion" => $descripcion,
            "estado" => "Pendiente",
            "comentarios" => []
        ];

        echo json_encode(["ok" => true, "ticket" => $nuevo]);
        break;

    case 'update':
        $id = intval($input['id'] ?? 0);
        $field = $input['field'] ?? null;
        $value = $input['value'] ?? null;

        if (!$id || !$field) {
            http_response_code(400);
            echo json_encode(["error" => "Faltan parámetros"]);
            exit;
        }

        if ($field === 'estado') {
            $stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
            $stmt->execute([$value, $id]);
        }

        echo json_encode(["ok" => true]);
        break;

    case 'comment':
        $id = intval($input['id'] ?? 0);
        $comment = trim($input['comment'] ?? '');

        if (!$id || $comment === '') {
            http_response_code(400);
            echo json_encode(["error" => "Faltan parámetros"]);
            exit;
        }

        // Get current comments
        $stmt = $pdo->prepare("SELECT comentarios FROM tickets WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Ticket no encontrado"]);
            exit;
        }

        $comentarios = json_decode($result['comentarios'], true) ?? [];
        $comentarios[] = $comment;

        // Update comments
        $stmt = $pdo->prepare("UPDATE tickets SET comentarios = ? WHERE id = ?");
        $stmt->execute([json_encode($comentarios), $id]);

        echo json_encode(["ok" => true]);
        break;

    /*
    ---------------------------------------------------------
    ESTADÍSTICAS DE TICKETS
    ---------------------------------------------------------
*/
    case 'stats':

        // Contar el total de tickets registrados
        $total = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

        // Contar tickets con estado "Pendiente"
        $pendientes = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado='Pendiente'")->fetchColumn();

        // Contar tickets con estado "Resuelto"
        $resueltos = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado='Resuelto'")->fetchColumn();

        // Contar tickets con estado "En proceso"
        $en_proceso = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado='En proceso'")->fetchColumn();

        // Retornar los datos en formato JSON para el frontend
        echo json_encode([
            "total" => (int)$total,
            "pendientes" => (int)$pendientes,
            "resueltos" => (int)$resueltos,
            "en_proceso" => (int)$en_proceso
        ]);

        break;

    default:
        $stmt = $pdo->query("SELECT id, categoria, prioridad, descripcion, estado, comentarios, created_at FROM tickets ORDER BY created_at DESC");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert JSON comments back to array
        foreach ($tickets as &$ticket) {
            $ticket['comentarios'] = json_decode($ticket['comentarios'], true) ?? [];
        }

        echo json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
}
