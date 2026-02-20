<?php
header('Content-Type: application/json');
require_once 'db_conn.php';

$vid = $_POST['viaje_id']; $oid = $_POST['o_id']; $did = $_POST['d_id'];

try {
    $stmt = $pdo->prepare("SELECT parada_id, orden FROM ruta_paradas WHERE ruta_id = (SELECT ruta_id FROM viajes WHERE id = ?)");
    $stmt->execute([$vid]);
    $sec = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $o_ord = $sec[$oid]; $d_ord = $sec[$did];

    $stmt = $pdo->prepare("SELECT numero_asiento, origen_id, destino_id FROM pasajes p JOIN ventas v ON p.venta_id = v.id WHERE p.viaje_id = ? AND v.estado = 'Confirmado'");
    $stmt->execute([$vid]);
    $vendidos = $stmt->fetchAll();

    $ocupados = [];
    foreach($vendidos as $v) {
        if($o_ord < $sec[$v['destino_id']] && $d_ord > $sec[$v['origen_id']]) {
            $ocupados[] = (string)$v['numero_asiento'];
        }
    }
    echo json_encode(['success' => true, 'asientos_ocupados' => array_values(array_unique($ocupados))]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}