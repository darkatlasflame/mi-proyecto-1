<?php
// config/db.php

// 1. Forzar la hora de Chile para todas las funciones de PHP (date, strtotime, etc.)
date_default_timezone_set('America/Santiago');

// 2. Calcular el desfase horario dinámicamente (-03:00 en verano, -04:00 en invierno)
// Esto es súper útil para XAMPP porque a veces no tiene instalados los nombres de las zonas horarias
$now = new DateTime();
$mins = $now->getOffset() / 60;
$sgn = ($mins < 0 ? -1 : 1);
$mins = abs($mins);
$hrs = floor($mins / 60);
$mins -= $hrs * 60;
$offset = sprintf('%+03d:%02d', $hrs * $sgn, $mins); 

// 3. Credenciales de la Base de Datos (Configuración por defecto de XAMPP)
$host = 'localhost';
$db   = 'jetbus_db';
$user = 'root';
$pass = ''; // En XAMPP la contraseña suele estar vacía
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    
    // 4. EL TRUCO DE LA HORA: Forzar a MySQL a usar el desfase de Chile justo al conectarse
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '$offset'"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Si hay error (ej: no has prendido MySQL en XAMPP), se detiene y avisa
    die("<h3 style='color:red;'>Error de Base de Datos: No se pudo conectar. Verifica que XAMPP esté corriendo.</h3> Detalle: " . $e->getMessage());
}
?>