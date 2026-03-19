<?php

session_start();
header("Content-Type: application/json; charset=UTF-8");

/*
Cargar variables del .env
*/

require_once __DIR__ . '/../../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'whaticket';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? 3306;

/*
Conexión a la base de datos
*/

try {

$pdo = new PDO(
"mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
$username,
$password
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e){

echo json_encode([
"ok"=>false,
"mensaje"=>"Error de conexión a la base de datos"
]);

exit;
}

/*
Leer datos enviados desde el frontend
*/

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){

echo json_encode([
"ok"=>false,
"mensaje"=>"No se recibieron datos"
]);

exit;

}

$correo = trim($data["correo"] ?? "");
$passwordIngresada = trim($data["password"] ?? "");
$rol = trim($data["rol"] ?? "");

/*
Validar campos
*/

if($correo == "" || $passwordIngresada == "" || $rol == ""){

echo json_encode([
"ok"=>false,
"mensaje"=>"Todos los campos son obligatorios"
]);

exit;

}

/*
Buscar usuario
*/

try{

$stmt = $pdo->prepare("SELECT * FROM users WHERE correo = ?");
$stmt->execute([$correo]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$usuario){

echo json_encode([
"ok"=>false,
"mensaje"=>"Usuario no encontrado"
]);

exit;

}

/*
Validar contraseña
*/

if($passwordIngresada !== $usuario["password"]){

echo json_encode([
"ok"=>false,
"mensaje"=>"Contraseña incorrecta"
]);

exit;

}

/*
Validar rol
*/

if($usuario["rol"] !== $rol){

echo json_encode([
"ok"=>false,
"mensaje"=>"Rol incorrecto"
]);

exit;

}

/*
Validar estado activo
*/

if($usuario["activo"] != 1){

echo json_encode([
"ok"=>false,
"mensaje"=>"Usuario inactivo"
]);

exit;

}

/*
Crear sesión
*/

$_SESSION["usuario_id"] = $usuario["id"];
$_SESSION["usuario_nombre"] = $usuario["nombre"];
$_SESSION["usuario_correo"] = $usuario["correo"];
$_SESSION["usuario_rol"] = $usuario["rol"];

/*
Respuesta correcta
*/

echo json_encode([
"ok"=>true,
"mensaje"=>"Acceso permitido"
]);

}catch(PDOException $e){

echo json_encode([
"ok"=>true,
"mensaje"=>"Acceso permitido",
"rol"=>$usuario["rol"]
]);
}