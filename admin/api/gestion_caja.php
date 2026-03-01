<?php
// admin/api/gestion_caja.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$accion = $_REQUEST['accion'] ?? '';

try {
    if ($accion === 'cierre') {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $oficina = $_GET['oficina'] ?? 'Oficina Central';

        $stmt_ingresos = $pdo->prepare("SELECT COUNT(*) as cantidad, COALESCE(SUM(total_pagado), 0) as total FROM ventas WHERE DATE(fecha_venta) = ? AND oficina_venta = ? AND estado = 'CONFIRMADO'");
        $stmt_ingresos->execute([$fecha, $oficina]);
        $ingresos = $stmt_ingresos->fetch(PDO::FETCH_ASSOC);

        $stmt_egresos = $pdo->prepare("SELECT COUNT(*) as cantidad, COALESCE(SUM(total_pagado), 0) as total FROM ventas WHERE DATE(fecha_venta) = ? AND oficina_venta = ? AND estado = 'ANULADO'");
        $stmt_egresos->execute([$fecha, $oficina]);
        $egresos = $stmt_egresos->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'vendidos' => $ingresos['cantidad'], 'ingresos' => $ingresos['total'], 'anulados' => $egresos['cantidad'], 'egresos' => $egresos['total'], 'total_caja' => $ingresos['total']]);
        exit;
    }

    if ($accion === 'buscar') {
        $ticket = $_GET['ticket'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM ventas WHERE codigo_ticket = ? OR rut_pasajero = ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$ticket, $ticket]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($resultados) { echo json_encode(['success' => true, 'tickets' => $resultados]); } else { echo json_encode(['success' => false, 'msg' => 'No se encontró.']); }
        exit;
    }

    if ($accion === 'anular') {
        $codigo_ticket = $_POST['codigo_ticket'] ?? '';
        $oficina = $_POST['oficina'] ?? 'Desconocida';
        $stmt_check = $pdo->prepare("SELECT id, estado FROM ventas WHERE codigo_ticket = ?");
        $stmt_check->execute([$codigo_ticket]);
        $venta = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$venta) { echo json_encode(['success' => false, 'msg' => 'El ticket no existe.']); exit; }
        if ($venta['estado'] === 'ANULADO') { echo json_encode(['success' => false, 'msg' => 'Este pasaje ya se encontraba anulado.']); exit; }

        $stmt_anular = $pdo->prepare("UPDATE ventas SET estado = 'ANULADO' WHERE codigo_ticket = ?");
        if ($stmt_anular->execute([$codigo_ticket])) { echo json_encode(['success' => true, 'msg' => "Pasaje $codigo_ticket ANULADO con éxito."]); } else { echo json_encode(['success' => false, 'msg' => 'Error al anular.']); }
        exit;
    }

    if ($accion === 'buscar_cliente') {
        $rut = $_GET['rut'] ?? '';
        $stmt = $pdo->prepare("SELECT nombre, telefono FROM clientes WHERE rut = ?");
        $stmt->execute([$rut]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) { echo json_encode(['success' => true, 'nombre' => $cliente['nombre'], 'telefono' => $cliente['telefono']]); } else { echo json_encode(['success' => false]); }
        exit;
    }

    if ($accion === 'actualizar_cliente') {
        $rut = $_POST['rut'] ?? ''; $nombre = $_POST['nombre'] ?? ''; $telefono = $_POST['telefono'] ?? '';
        $stmt = $pdo->prepare("UPDATE clientes SET nombre = ?, telefono = ? WHERE rut = ?");
        if ($stmt->execute([$nombre, $telefono, $rut])) {
            $pdo->prepare("UPDATE ventas SET nombre_pasajero = ?, telefono_contacto = ? WHERE rut_pasajero = ?")->execute([$nombre, $telefono, $rut]);
            echo json_encode(['success' => true, 'msg' => 'Datos actualizados.']);
        } else { echo json_encode(['success' => false, 'msg' => 'Error.']); }
        exit;
    }

    // --- NUEVAS FUNCIONES PARA LA PLANILLA DEL CHOFER ---
    if ($accion === 'viajes_hoy') {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        // Busca todos los viajes de un día en específico
        $stmt = $pdo->prepare("
            SELECT v.id, DATE_FORMAT(v.fecha_hora, '%H:%i') as hora, v.origen, v.destino, b.numero_maquina, b.patente 
            FROM viajes v JOIN buses b ON v.id_bus = b.id 
            WHERE DATE(v.fecha_hora) = ? AND v.estado = 'PROGRAMADO' 
            ORDER BY v.fecha_hora ASC
        ");
        $stmt->execute([$fecha]);
        echo json_encode(['success' => true, 'viajes' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($accion === 'planilla') {
        $id_viaje = $_GET['id_viaje'] ?? '';
        // Extrae los pasajeros confirmados ordenados numéricamente por el asiento
        $stmt = $pdo->prepare("
            SELECT nro_asiento, nombre_pasajero, rut_pasajero, origen_boleto, destino_boleto, anotaciones 
            FROM ventas 
            WHERE id_viaje = ? AND estado = 'CONFIRMADO' 
            ORDER BY CAST(nro_asiento AS UNSIGNED) ASC
        ");
        $stmt->execute([$id_viaje]);
        echo json_encode(['success' => true, 'pasajeros' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Acción no válida.']);

} catch (Exception $e) { echo json_encode(['success' => false, 'msg' => 'Error BD: ' . $e->getMessage()]); }