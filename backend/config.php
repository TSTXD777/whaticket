<?php

$env = parse_ini_file(__DIR__ . '/../.env');

$host = $env['DB_HOST'];
$dbname = $env['DB_NAME'];
$username = $env['DB_USER'];
$password = $env['DB_PASSWORD'];
$port = $env['DB_PORT'];

try {

$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
    $username,
    $password
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e){

die(json_encode([
"ok"=>false,
"mensaje"=>$e->getMessage()
]));

}