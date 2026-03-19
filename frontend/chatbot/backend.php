<?php
// backend.php — API simple que usa data.json como almacenamiento temporal.
// Este archivo recibe peticiones POST con parámetro 'action' y devuelve JSON.
// Acciones soportadas:
// - list    : devuelve todos los artículos
// - get     : recibe 'id' y devuelve el artículo correspondiente
// - search  : recibe 'q' (texto) y devuelve coincidencias en título/categoría/keywords/contenido
// - add     : crea un artículo con title, category, keywords, content
// - update  : actualiza un artículo por id con los campos enviados
// - delete  : elimina un artículo por id

header('Content-Type: application/json; charset=utf-8');
///NUEVO CÓDIGO PARA BASE DE DATOS EN MYSQL
require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];
$dbport = $_ENV['DB_PORT'];

// crear conexion
$conn = new mysqli($servername, $username, $password, $dbname, $dbport);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si la tabla 'articles' existe
$query = "SHOW TABLES LIKE 'kb_articles'";
$result = $conn->query($query);
if ($result->num_rows == 0) {
    // Crear tabla
    $createTable = "CREATE TABLE kb_articles (
        id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
        title VARCHAR(500) NOT NULL,
        category VARCHAR(255),
        keywords TEXT,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    if ($conn->query($createTable) === TRUE) {
        echo "Tabla 'kb_articles' creada exitosamente.\n";
    } else {
        echo "Error creando tabla: " . $conn->error . "\n";
    }
} else {
    echo "La tabla 'kb_articles' ya existe.\n";
}

//acción de la petición
$action = $_POST['action'] ?? '';

if($action === 'list'){
    $result = $conn->query("SELECT * FROM kb_articles ORDER BY created_at DESC");
    $data = [];
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    echo json_encode(['ok'=>true, 'data'=>$data]);
    exit;
}

if($action === 'get'){
    $id = $_POST['id'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM kb_articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        echo json_encode(['ok'=>true, 'item'=>$row]);
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'No encontrado']);
    }
    $stmt->close();
    exit;
}

if($action === 'search' || $action === 'ai-search'){
    $q = trim($_POST['q'] ?? '');
    if($q === ''){
        $result = $conn->query("SELECT * FROM kb_articles ORDER BY created_at DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM kb_articles WHERE title LIKE ? OR category LIKE ? OR keywords LIKE ? OR content LIKE ?");
        $like = "%$q%";
        $stmt->bind_param("ssss", $like, $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    $hits = [];
    while($row = $result->fetch_assoc()){
        $hits[] = $row;
    }
    echo json_encode(['ok'=>true, 'hits'=>$hits]);
    exit;
}

if($action === 'add'){
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $keywords = $_POST['keywords'] ?? '';
    $content = $_POST['content'] ?? '';
    $stmt = $conn->prepare("INSERT INTO kb_articles (title, category, keywords, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $category, $keywords, $content);
    if($stmt->execute()){
        $id = $conn->insert_id;
        echo json_encode(['ok'=>true, 'item'=>['id'=>$id, 'title'=>$title, 'category'=>$category, 'keywords'=>$keywords, 'content'=>$content]]);
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'Error inserting']);
    }
    $stmt->close();
    exit;
}

if($action === 'update'){
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $keywords = $_POST['keywords'] ?? '';
    $content = $_POST['content'] ?? '';
    $stmt = $conn->prepare("UPDATE kb_articles SET title = ?, category = ?, keywords = ?, content = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $category, $keywords, $content, $id);
    if($stmt->execute() && $stmt->affected_rows > 0){
        echo json_encode(['ok'=>true, 'item'=>['id'=>$id, 'title'=>$title, 'category'=>$category, 'keywords'=>$keywords, 'content'=>$content]]);
    } else {
        echo json_encode(['ok'=>false, 'msg'=>'No encontrado para actualizar']);
    }
    $stmt->close();
    exit;
}

if($action === 'delete'){
    $id = $_POST['id'] ?? '';
    $stmt = $conn->prepare("DELETE FROM kb_articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $found = $stmt->affected_rows > 0;
    echo json_encode(['ok'=>$found]);
    $stmt->close();
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'Acción inválida']);

$conn->close();