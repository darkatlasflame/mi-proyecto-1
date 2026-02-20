<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
$rut = $_GET['rut'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM pasajeros WHERE rut = ?");
$stmt->execute([$rut]);
echo json_encode(['success' => true, 'pasajero' => $stmt->fetch()]);