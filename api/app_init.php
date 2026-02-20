<?php
// api/app_init.php
header('Content-Type: application/json');
require_once 'db_conn.php';

try {
    $data = [
        'paradas' => $pdo->query("SELECT * FROM paradas")->fetchAll(),
        'buses' => $pdo->query("SELECT * FROM buses")->fetchAll(),
        'tarifas' => $pdo->query("SELECT * FROM tarifas")->fetchAll(),
        'rutas' => $pdo->query("SELECT * FROM rutas")->fetchAll(),
        'preciosTramos' => $pdo->query("SELECT * FROM precios_tramos")->fetchAll(),
        'viajes' => $pdo->query("SELECT v.*, b.numero_bus, b.patente FROM viajes v JOIN buses b ON v.bus_id = b.id WHERE v.estado = 'Programado' ORDER BY v.fecha_hora_salida ASC")->fetchAll(),
        // NUEVO: Cargar oficinas
        'oficinas' => $pdo->query("SELECT * FROM oficinas")->fetchAll()
    ];
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}