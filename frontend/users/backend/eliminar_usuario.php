<?php
header("Content-Type: application/json; charset=UTF-8");

$env = parse_ini_file(__DIR__ . '/../.env');

$host = $env['DB_HOST'];
$dbname = $env['DB_NAME'];
$username = $env['DB_USER'];
$password = $env['DB_PASSWORD'];
$port = $env['DB_PORT'];


$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"];

try {

$stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
$stmt->execute([$id]);

echo json_encode([
"ok"=>true,
"mensaje"=>"Usuario eliminado"
]);

} catch(PDOException $e){

echo json_encode([
"ok"=>false,
"mensaje"=>$e->getMessage()
]);

}