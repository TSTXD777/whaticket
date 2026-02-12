<?php
$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"];

$archivo = __DIR__ . "/usuarios.json";
$usuarios = json_decode(file_get_contents($archivo), true);

$usuarios[$id]["activo"] = false;

file_put_contents($archivo, json_encode($usuarios, JSON_PRETTY_PRINT));

echo json_encode(["mensaje" => "Usuario desactivado"]);
