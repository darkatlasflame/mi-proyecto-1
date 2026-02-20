<?php
// api/sales.php
header('Content-Type: application/json');
require_once 'db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
if(!$data) exit;

$pdo->beginTransaction();

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha_venta) = CURDATE()");
    $folio = date('dmy') . '-' . str_pad($stmt->fetch()['total'] + 1, 3, '0', STR_PAD_LEFT);

    $total_venta = 0;
    foreach($data['passengers'] as $p) {
        $st = $pdo->prepare("SELECT descuento FROM tarifas WHERE id = ?"); $st->execute([$p['tarifaId']]);
        $total_venta += $data['basePrice'] * (1 - $st->fetchColumn()/100);
    }

    // MODIFICADO: Agregamos oficina_id al INSERT
    $stmt = $pdo->prepare("INSERT INTO ventas (codigo_venta, viaje_id, oficina_id, comprador_rut, total_pagado, metodo_pago) VALUES (?, ?, ?, ?, ?, ?)");
    // Asumimos que $data['oficinaId'] viene del frontend
    $stmt->execute([$folio, $data['viajeId'], $data['oficinaId'], $data['passengers'][0]['rut'], $total_venta, $data['metodo']]);
    $venta_id = $pdo->lastInsertId();

    // ... (El resto del código de tickets sigue igual que antes) ...
    // Para brevedad, mantén el código original de generación de tickets aquí abajo
    
    // Información para el ticket
    $stmt = $pdo->prepare("SELECT v.fecha_hora_salida, b.numero_bus, b.patente, p1.nombre as o, p2.nombre as d 
                           FROM viajes v JOIN buses b ON v.bus_id = b.id JOIN paradas p1 ON p1.id = ? JOIN paradas p2 ON p2.id = ? WHERE v.id = ?");
    $stmt->execute([$data['oId'], $data['dId'], $data['viajeId']]);
    $vi = $stmt->fetch();

    $tickets = [];
    foreach($data['passengers'] as $pax) {
        $pdo->prepare("INSERT INTO pasajeros (rut, nombre_completo) VALUES (?, ?) ON DUPLICATE KEY UPDATE nombre_completo = ?")
            ->execute([$pax['rut'], $pax['nombre'], $pax['nombre']]);

        $st = $pdo->prepare("SELECT nombre, descuento FROM tarifas WHERE id = ?"); $st->execute([$pax['tarifaId']]);
        $ta = $st->fetch(); $valor = $data['basePrice'] * (1 - $ta['descuento']/100);

        $pdo->prepare("INSERT INTO pasajes (venta_id, viaje_id, numero_asiento, pasajero_rut, pasajero_nombre, origen_id, destino_id, tarifa_id, precio_final, anotacion_subida) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$venta_id, $data['viajeId'], $pax['asiento'], $pax['rut'], $pax['nombre'], $data['oId'], $data['dId'], $pax['tarifaId'], $valor, $data['anotacion']]);

        $tickets[] = [
            'folio'=>$folio, 'asiento'=>$pax['asiento'], 'origen'=>$vi['o'], 'destino'=>$vi['d'], 'salida'=>$vi['fecha_hora_salida'], 
            'numero_bus'=>$vi['numero_bus'], 'patente'=>$vi['patente'], 
            'pasajero'=>$pax['nombre'], 'rut'=>$pax['rut'], 'tarifa'=>$ta['nombre'], 'valor'=>$valor, 'anotacion'=>$data['anotacion']
        ];
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'tickets' => $tickets]);
} catch (Exception $e) { $pdo->rollBack(); echo json_encode(['success' => false, 'error' => $e->getMessage()]); }