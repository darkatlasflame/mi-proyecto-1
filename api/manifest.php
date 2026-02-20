<?php
// api/manifest.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require_once 'db_conn.php';

$action = $_GET['action'] ?? '';

try {
    // 1. OBTENER VIAJES DE HOY (Para que el auxiliar elija)
    if ($action === 'today_trips') {
        $sql = "SELECT v.id, v.fecha_hora_salida, b.numero_bus, b.patente, r.nombre as ruta
                FROM viajes v
                JOIN buses b ON v.bus_id = b.id
                JOIN rutas r ON v.ruta_id = r.id
                WHERE DATE(v.fecha_hora_salida) = CURDATE() AND v.estado != 'Cancelado'
                ORDER BY v.fecha_hora_salida ASC";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'trips' => $stmt->fetchAll()]);
    } 
    
    // 2. OBTENER DETALLE DEL BUS (MANIFIESTO)
    elseif ($action === 'details') {
        $viaje_id = $_GET['viaje_id'];
        
        // Info del Bus (para saber cuántos asientos dibujar)
        $stmt = $pdo->prepare("SELECT b.asientos FROM viajes v JOIN buses b ON v.bus_id = b.id WHERE v.id = ?");
        $stmt->execute([$viaje_id]);
        $busInfo = $stmt->fetch();

        // Info de Pasajeros
        $sql = "SELECT p.numero_asiento, p.pasajero_nombre, p.pasajero_rut, 
                       par1.nombre as sube_en, par2.nombre as baja_en, p.anotacion_subida
                FROM pasajes p
                JOIN ventas v ON p.venta_id = v.id
                JOIN paradas par1 ON p.origen_id = par1.id
                JOIN paradas par2 ON p.destino_id = par2.id
                WHERE p.viaje_id = ? AND v.estado = 'Confirmado'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$viaje_id]);
        $pasajeros = $stmt->fetchAll();

        // Convertimos el array a un objeto indexado por número de asiento para acceso rápido
        $mapa = [];
        foreach($pasajeros as $p) {
            $mapa[$p['numero_asiento']] = $p;
        }

        echo json_encode([
            'success' => true, 
            'total_seats' => $busInfo['asientos'],
            'passengers' => $mapa
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}