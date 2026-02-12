<?php
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$correo = $data["correo"];
$rol = $data["rol"];

// Guardamos login temporal
$_SESSION["correo"] = $correo;
$_SESSION["rol"] = $rol;

echo json_encode([
    "ok" => true,
    "mensaje" => "Acceso permitido"
]);
