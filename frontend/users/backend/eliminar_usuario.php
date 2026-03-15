<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

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