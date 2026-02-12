<?php
$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"];
$nombre = $data["nombre"];
$correo = $data["correo"];
$rol = $data["rol"];

$archivo = __DIR__ . "/usuarios.json";

$usuarios = json_decode(file_get_contents($archivo), true);

$usuarios[$id]["nombre"] = $nombre;
$usuarios[$id]["correo"] = $correo;
$usuarios[$id]["rol"] = $rol;

file_put_contents($archivo, json_encode($usuarios, JSON_PRETTY_PRINT));

echo json_encode(["mensaje" => "Usuario editado correctamente"]);
