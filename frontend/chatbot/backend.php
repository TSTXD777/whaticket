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
$DATA_FILE = __DIR__ . '/data.json';

// Inicializar archivo si no existe
if(!file_exists($DATA_FILE)){
    file_put_contents($DATA_FILE, json_encode(["items" => []], JSON_PRETTY_PRINT));
}

// Lectura y escritura helpers
function read_data(){ global $DATA_FILE; $s = file_get_contents($DATA_FILE); return json_decode($s, true); }
function write_data($obj){ global $DATA_FILE; file_put_contents($DATA_FILE, json_encode($obj, JSON_PRETTY_PRINT)); }

$action = $_POST['action'] ?? '';

if($action === 'list'){
    $d = read_data();
    echo json_encode(['ok'=>true, 'data' => $d['items']]);
    exit;
}

if($action === 'get'){
    $id = $_POST['id'] ?? '';
    $d = read_data();
    foreach($d['items'] as $it) if($it['id']==$id){ echo json_encode(['ok'=>true,'item'=>$it]); exit; }
    echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit;
}

if($action === 'search'){
    $q = trim($_POST['q'] ?? '');
    $d = read_data();
    $hits = [];
    if($q === ''){
        $hits = $d['items'];
    } else {
        $qL = mb_strtolower($q);
        foreach($d['items'] as $it){
            $hay = mb_strtolower($it['title'].' '.$it['category'].' '.($it['keywords']??'').' '.$it['content']);
            if(mb_strpos($hay, $qL) !== false) $hits[] = $it;
        }
    }
    echo json_encode(['ok'=>true,'hits'=>$hits]); exit;
}

if($action === 'add'){
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $keywords = $_POST['keywords'] ?? '';
    $content = $_POST['content'] ?? '';
    $d = read_data();
    // generar id simple
    $id = uniqid();
    $item = ['id'=>$id,'title'=>$title,'category'=>$category,'keywords'=>$keywords,'content'=>$content];
    $d['items'][] = $item;
    write_data($d);
    echo json_encode(['ok'=>true,'item'=>$item]); exit;
}

if($action === 'update'){
    $id = $_POST['id'] ?? '';
    $d = read_data();
    foreach($d['items'] as &$it){
        if($it['id'] === $id){
            $it['title'] = $_POST['title'] ?? $it['title'];
            $it['category'] = $_POST['category'] ?? $it['category'];
            $it['keywords'] = $_POST['keywords'] ?? $it['keywords'];
            $it['content'] = $_POST['content'] ?? $it['content'];
            write_data($d);
            echo json_encode(['ok'=>true,'item'=>$it]); exit;
        }
    }
    echo json_encode(['ok'=>false,'msg'=>'No encontrado para actualizar']); exit;
}

if($action === 'delete'){
    $id = $_POST['id'] ?? '';
    $d = read_data();
    $found = false;
    foreach($d['items'] as $k => $it){
        if($it['id'] == $id){ unset($d['items'][$k]); $found = true; break; }
    }
    $d['items'] = array_values($d['items']);
    write_data($d);
    echo json_encode(['ok'=>$found]); exit;
}

// Acción inválida
echo json_encode(['ok'=>false,'msg'=>'Acción inválida']);
