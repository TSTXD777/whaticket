<?php
$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data["nombre"];
$correo = $data["correo"];
$rol = $data["rol"];

$archivo = __DIR__ . "/usuarios.json";
$usuarios = json_decode(file_get_contents($archivo), true);

if ($usuarios === null) $usuarios = [];

$usuarios[] = [
    "nombre" => $nombre,
    "correo" => $correo,
    "rol" => $rol,
    "activo" => true
];

file_put_contents($archivo, json_encode($usuarios, JSON_PRETTY_PRINT));

echo json_encode([
    "ok" => true,
    "mensaje" => "Usuario registrado correctamente"
]);
