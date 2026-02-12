<?php
$archivo = __DIR__ . "/usuarios.json";

if (!file_exists($archivo)) {
    echo json_encode([]);
    exit;
}

$usuarios = json_decode(file_get_contents($archivo), true);

if ($usuarios === null) $usuarios = [];

echo json_encode($usuarios);
