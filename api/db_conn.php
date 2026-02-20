<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'venta_pasajes_db';
$user = 'root'; // Cambiar si es necesario
$pass = '';     // Cambiar si es necesario

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la BD']);
    exit;
}