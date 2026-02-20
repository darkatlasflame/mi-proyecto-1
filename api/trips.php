<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
$o = $_POST['o']; $d = $_POST['d']; $f = $_POST['f'];
try {
    // Busca viajes donde el origen esté antes que el destino en la secuencia de la ruta
    $sql = "SELECT v.*, b.numero_bus, b.patente FROM viajes v 
            JOIN buses b ON v.bus_id = b.id
            JOIN ruta_paradas rp1 ON v.ruta_id = rp1.ruta_id AND rp1.parada_id = ?
            JOIN ruta_paradas rp2 ON v.ruta_id = rp2.ruta_id AND rp2.parada_id = ?
            WHERE DATE(v.fecha_hora_salida) = ? AND rp1.orden < rp2.orden AND v.estado = 'Programado'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$o, $d, $f]);
    echo json_encode(['success' => true, 'viajes' => $stmt->fetchAll()]);
} catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }