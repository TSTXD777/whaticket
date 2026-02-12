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

// Database configuration
$host = 'localhost';
$dbname = 'whaticket';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['ok'=>false, 'msg'=>'Connection failed: ' . $e->getMessage()]));
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
