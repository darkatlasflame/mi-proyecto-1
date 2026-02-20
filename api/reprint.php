<?php
// api/reprint.php
header('Content-Type: application/json');
require_once 'db_conn.php';

// AHORA BUSCAMOS POR RUT
$rut = $_POST['rut'] ?? '';

if(empty($rut)) {
    echo json_encode(['success' => false, 'error' => 'Ingrese el RUT del pasajero']);
    exit;
}

try {
    // Buscamos pasajes asociados al RUT, priorizando viajes futuros o del día
    // Ordenamos por fecha de salida descendente (el más reciente primero)
    $sql = "SELECT 
                v.codigo_venta as folio,
                p.numero_asiento as asiento,
                par1.nombre as origen,
                par2.nombre as destino,
                vi.fecha_hora_salida as salida,
                b.numero_bus,
                b.patente,
                p.pasajero_nombre as pasajero,
                p.pasajero_rut as rut,
                t.nombre as tarifa,
                p.precio_final as valor,
                p.anotacion_subida as anotacion
            FROM pasajes p
            JOIN ventas v ON p.venta_id = v.id
            JOIN viajes vi ON p.viaje_id = vi.id
            JOIN buses b ON vi.bus_id = b.id
            JOIN paradas par1 ON p.origen_id = par1.id
            JOIN paradas par2 ON p.destino_id = par2.id
            JOIN tarifas t ON p.tarifa_id = t.id
            WHERE p.pasajero_rut = ? 
              AND v.estado = 'Confirmado'
              AND vi.fecha_hora_salida >= CURDATE() -- Solo viajes desde hoy en adelante
            ORDER BY vi.fecha_hora_salida ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rut]);
    $tickets = $stmt->fetchAll();

    if(count($tickets) > 0) {
        echo json_encode(['success' => true, 'tickets' => $tickets]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontraron pasajes vigentes para este RUT']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}