<?php
// backend/chatbot.php — API para gestión de base de conocimiento usando MySQL
// Este archivo recibe peticiones POST con parámetro 'action' y devuelve JSON.
// Acciones soportadas:
// - list    : devuelve todos los artículos
// - get     : recibe 'id' y devuelve el artículo correspondiente
// - search  : recibe 'q' (texto) y devuelve coincidencias en título/categoría/keywords/contenido
// - add     : crea un artículo con title, category, keywords, content
// - update  : actualiza un artículo por id con los campos enviados
// - delete  : elimina un artículo por id

header('Content-Type: application/json; charset=utf-8');

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'whaticket';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['ok'=>false, 'msg'=>'Connection failed: ' . $e->getMessage()]));
}

// Función auxiliar para conectar con Ollama
function connectOllama($prompt, $model = 'gemini-3-flash-preview:cloud', $context = [], $temperature = 0.6, $maxTokens = 512) {
    $ollamaUrl = 'http://localhost:11434/api/generate';
    
    // Construir mensaje del sistema con contexto de KB
    $systemMsg = "Eres un asistente de soporte técnico de la plataforma Whaticket. Responde de forma clara, concisa y en texto pleno (sin formato markdown). 
    Tu función es ayudar al usuario con información disponible en tu base de conocimiento. Si la consulta no se encuentra en tu base de conocimiento, solicita al usuario más detalles para comprender mejor su problema. 
    Ten en cuenta lo siguiente (sin mencionarlo explícitamente al usuario): en caso de no contar con información suficiente, guía al usuario a crear un nuevo ticket de soporte o a solicitar a un técnico la creación de un nuevo artículo de conocimiento.";
    if (!empty($context)) {
        $contextText = "Contexto relevante de la base de conocimiento:\n";
        foreach ($context as $idx => $article) {
            $contextText .= "\n[" . ($idx + 1) . "] " . $article['title'] . "\n";
            $contextText .= substr($article['content'], 0, 500) . "...\n";
        }
        $systemMsg .= "\n\n" . $contextText;
    }
    
    // Preparar payload para Ollama
    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'system' => $systemMsg,
        'stream' => false,  // No usar streaming para simplificar respuesta
        'temperature' => $temperature,
        'max_tokens' => $maxTokens
    ];
    
    // Realizar request a Ollama
    $ch = curl_init($ollamaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // 30 segundos de timeout
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Manejo de errores
    if ($curlError) {
        return [
            'ok' => false,
            'msg' => 'Error de conexión con Ollama: ' . $curlError
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'ok' => false,
            'msg' => 'Ollama respondió con código: ' . $httpCode
        ];
    }
    
    // Parsear respuesta
    $decoded = json_decode($response, true);
    if (!$decoded || !isset($decoded['response'])) {
        return [
            'ok' => false,
            'msg' => 'Respuesta inválida de Ollama'
        ];
    }
    
    // Limpiar respuesta (Ollama puede incluir caracteres especiales)
    $ollamaResponse = trim($decoded['response']);
    
    return [
        'ok' => true,
        'response' => $ollamaResponse
    ];
}

$action = $_POST['action'] ?? '';

if($action === 'list'){
    $stmt = $pdo->query("SELECT id, title, category, keywords, content, created_at FROM kb_articles ORDER BY created_at DESC");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok'=>true, 'data' => $articles]);
    exit;
}

if($action === 'get'){
    $id = $_POST['id'] ?? '';
    if (!$id) {
        echo json_encode(['ok'=>false,'msg'=>'ID requerido']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, title, category, keywords, content, created_at FROM kb_articles WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        echo json_encode(['ok'=>true,'item'=>$item]);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'No encontrado']);
    }
    exit;
}

if($action === 'search'){
    $q = trim($_POST['q'] ?? '');
    $hits = [];

    if($q === ''){
        $stmt = $pdo->query("SELECT id, title, category, keywords, content, created_at FROM kb_articles ORDER BY created_at DESC");
        $hits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $qL = mb_strtolower($q);
        $stmt = $pdo->prepare("SELECT id, title, category, keywords, content, created_at FROM kb_articles WHERE
            LOWER(title) LIKE ? OR LOWER(category) LIKE ? OR LOWER(keywords) LIKE ? OR LOWER(content) LIKE ?");
        $searchTerm = '%' . $qL . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $hits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode(['ok'=>true,'hits'=>$hits]);
    exit;
}

// Acción híbrida: pregunta asistida por Ollama usando contexto de la KB
if($action === 'ai-search'){
    $q = trim($_POST['q'] ?? '');
    if($q === ''){
        echo json_encode(['ok'=>false,'msg'=>'Pregunta requerida']);
        exit;
    }

    // Parámetros ajustables para el LLM
    $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
    $temperature = max(0.0, min(1.0, $temperature));
    $maxTokens = isset($_POST['max_tokens']) ? intval($_POST['max_tokens']) : 512;
    $maxTokens = max(16, min(2048, $maxTokens));
    $contextSize = isset($_POST['context_size']) ? intval($_POST['context_size']) : 5;
    $contextSize = max(1, min(20, $contextSize));

    // buscar artículos relevantes como contexto
    $qL = mb_strtolower($q);
    $stmt = $pdo->prepare("SELECT id, title, category, keywords, content, created_at FROM kb_articles WHERE
            LOWER(title) LIKE ? OR LOWER(category) LIKE ? OR LOWER(keywords) LIKE ? OR LOWER(content) LIKE ?
            ORDER BY created_at DESC LIMIT " . (int)$contextSize);
    $searchTerm = '%' . $qL . '%';
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $contextArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // construir prompt para Ollama
    $prompt = "Respuesta a la siguiente pregunta basándote en el contexto proporcionado.\nPregunta: " . $q;

    $ollamaResult = connectOllama($prompt, 'gemini-3-flash-preview:cloud', $contextArticles, $temperature, $maxTokens);
    if(!$ollamaResult['ok']){
        // fallback: devolver contextos sin respuesta generada
        echo json_encode(['ok'=>false,'msg'=>$ollamaResult['msg'],'context'=>$contextArticles]);
        exit;
    }

    echo json_encode(['ok'=>true,'response'=>$ollamaResult['response'],'context'=>$contextArticles]);
    exit;
}

if($action === 'add'){
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $keywords = $_POST['keywords'] ?? '';
    $content = $_POST['content'] ?? '';

    if (!$title || !$content) {
        echo json_encode(['ok'=>false,'msg'=>'Título y contenido son requeridos']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO kb_articles (title, category, keywords, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $category, $keywords, $content]);

    $id = $pdo->lastInsertId();
    $item = ['id'=>(int)$id, 'title'=>$title, 'category'=>$category, 'keywords'=>$keywords, 'content'=>$content];
    echo json_encode(['ok'=>true,'item'=>$item]);
    exit;
}

if($action === 'update'){
    $id = $_POST['id'] ?? '';
    if (!$id) {
        echo json_encode(['ok'=>false,'msg'=>'ID requerido']);
        exit;
    }

    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $keywords = $_POST['keywords'] ?? '';
    $content = $_POST['content'] ?? '';

    if (!$title || !$content) {
        echo json_encode(['ok'=>false,'msg'=>'Título y contenido son requeridos']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE kb_articles SET title = ?, category = ?, keywords = ?, content = ? WHERE id = ?");
    $stmt->execute([$title, $category, $keywords, $content, $id]);

    if ($stmt->rowCount() > 0) {
        $item = ['id'=>(int)$id, 'title'=>$title, 'category'=>$category, 'keywords'=>$keywords, 'content'=>$content];
        echo json_encode(['ok'=>true,'item'=>$item]);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'No encontrado para actualizar']);
    }
    exit;
}

if($action === 'delete'){
    $id = $_POST['id'] ?? '';
    if (!$id) {
        echo json_encode(['ok'=>false,'msg'=>'ID requerido']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM kb_articles WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['ok'=>$stmt->rowCount() > 0]);
    exit;
}

// Acción inválida
echo json_encode(['ok'=>false,'msg'=>'Acción inválida']);
