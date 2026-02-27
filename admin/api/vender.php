<?php
// admin/api/vender.php (VERSIÓN MULTIVENTA SEGURA)
require_once '../../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.']);
    exit;
}

$canal = $_POST['canal'] ?? 'CAJA';
$oficina = $_POST['oficina'] ?? 'Oficina Central';
$id_viaje = $_POST['id_viaje'] ?? '';
$origen = $_POST['origen'] ?? '';
$destino = $_POST['destino'] ?? '';

// Ahora recibimos un String JSON con todos los pasajeros
$pasajeros_json = $_POST['pasajeros'] ?? '[]';
$pasajeros = json_decode($pasajeros_json, true);

if (!$id_viaje || empty($pasajeros)) {
    echo json_encode(['status' => 'error', 'msg' => 'Faltan datos del viaje o no hay asientos seleccionados.']);
    exit;
}

try {
    // 1. Tomar la "fotografía" del viaje (Solo se hace 1 vez para todo el grupo)
    $stmt_info = $pdo->prepare("
        SELECT v.fecha_hora, b.numero_maquina, b.patente 
        FROM viajes v 
        JOIN buses b ON v.id_bus = b.id 
        WHERE v.id = ?
    ");
    $stmt_info->execute([$id_viaje]);
    $info_viaje = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info_viaje) {
        echo json_encode(['status' => 'error', 'msg' => 'El viaje seleccionado ya no está disponible.']);
        exit;
    }

    $fecha_historica = $info_viaje['fecha_hora'];
    $bus_historico = $info_viaje['numero_maquina'] . ' (' . $info_viaje['patente'] . ')';

    // 2. INICIAR TRANSACCIÓN (Para evitar ventas parciales si hay un choque de asientos)
    $pdo->beginTransaction();
    
    $boletos_emitidos = [];

    // Preparamos las consultas para usarlas dentro del bucle
    $stmt_check = $pdo->prepare("SELECT id FROM ventas WHERE id_viaje = ? AND nro_asiento = ? AND estado IN ('CONFIRMADO', 'PENDIENTE')");
    
    $sql_venta = "INSERT INTO ventas (
        codigo_ticket, id_viaje, fecha_viaje_historico, bus_historico,
        nro_asiento, rut_pasajero, nombre_pasajero, telefono_contacto, 
        tipo_pasajero, origen_boleto, destino_boleto, total_pagado, canal, oficina_venta, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'CONFIRMADO')";
    $stmt_venta = $pdo->prepare($sql_venta);

    $sql_cliente = "INSERT INTO clientes (rut, nombre, telefono) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), telefono = VALUES(telefono)";
    $stmt_cliente = $pdo->prepare($sql_cliente);

    // 3. Procesar cada pasajero
    foreach ($pasajeros as $pax) {
        // Verificar disponibilidad de ESTE asiento específico
        $stmt_check->execute([$id_viaje, $pax['asiento']]);
        if ($stmt_check->fetch()) {
            // Si UNO falla, abortamos todo
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'msg' => 'El asiento ' . $pax['asiento'] . ' acaba de ser ocupado. Venta abortada para evitar errores.']);
            exit;
        }

        // Generar código único para este boleto
        $codigo_ticket = 'CAJA-' . strtoupper(substr(uniqid(), -5));
        
        // Insertar Venta
        $stmt_venta->execute([
            $codigo_ticket, $id_viaje, $fecha_historica, $bus_historico,
            $pax['asiento'], $pax['rut'], $pax['nombre'], $pax['telefono'], 
            $pax['tipo_pasajero'], $origen, $destino, $pax['total_pagado'], $canal, $oficina
        ]);

        // Guardar/Actualizar Cliente
        $stmt_cliente->execute([$pax['rut'], $pax['nombre'], $pax['telefono']]);

        // Guardar datos para responder a la pantalla y que pueda imprimir
        $pax['ticket'] = $codigo_ticket;
        $boletos_emitidos[] = $pax;
    }

    // 4. CONFIRMAR TRANSACCIÓN (Guardar todo definitivamente en la base de datos)
    $pdo->commit();

    echo json_encode([
        'status' => 'success', 
        'msg' => count($boletos_emitidos) . ' boletos emitidos correctamente.',
        'boletos' => $boletos_emitidos
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'msg' => 'Error del servidor al procesar la venta.']);
}