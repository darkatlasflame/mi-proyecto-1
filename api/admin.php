<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
if($_POST['action'] === 'update_viaje') {
    $stmt = $pdo->prepare("SELECT id FROM buses WHERE numero_bus = ?"); $stmt->execute([$_POST['numero_bus']]);
    $bid = $stmt->fetchColumn();
    if($bid) {
        $pdo->prepare("UPDATE viajes SET bus_id = ?, chofer_nombre = ? WHERE id = ?")->execute([$bid, $_POST['chofer'], $_POST['viaje_id']]);
        echo json_encode(['success' => true]);
    } else echo json_encode(['success' => false, 'error' => 'Bus no existe']);
}