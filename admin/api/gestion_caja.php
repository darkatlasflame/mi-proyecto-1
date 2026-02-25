<?php
// admin/api/gestion_caja.php
require_once '../../config/db.php';
header('Content-Type: application/json');

// Atrapamos la acción (puede venir por GET en Buscar/Cierre/Cliente, o por POST en Anular)
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

if (!$accion) {
    echo json_encode(['success' => false, 'msg' => 'No se especificó ninguna acción.']);
    exit;
}

// ==========================================
// 1. ACCIÓN: BUSCAR PARA REIMPRIMIR
// ==========================================
if ($accion === 'buscar') {
    $ticket = trim($_GET['ticket'] ?? '');
    
    if (empty($ticket)) {
        echo json_encode(['success' => false, 'msg' => 'Por favor ingrese un código o RUT válido.']);
        exit;
    }

    try {
        // Buscamos TODOS los pasajes que coincidan con ese RUT o código exacto
        $stmt = $pdo->prepare("SELECT * FROM ventas WHERE codigo_ticket = ? OR rut_pasajero = ? ORDER BY id DESC");
        $stmt->execute([$ticket, $ticket]);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($ventas) > 0) {
            echo json_encode(['success' => true, 'tickets' => $ventas]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'No se encontró ningún pasaje con ese código o RUT.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => 'Error de BD: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 2. ACCIÓN: ANULAR BOLETO
// ==========================================
if ($accion === 'anular') {
    $codigo_ticket = trim($_POST['codigo_ticket'] ?? '');
    $oficina = $_POST['oficina'] ?? 'Desconocida';

    if (empty($codigo_ticket)) {
        echo json_encode(['success' => false, 'msg' => 'Falta el código del ticket.']);
        exit;
    }

    try {
        // Primero verificamos si el ticket existe y si ya está anulado
        $stmt = $pdo->prepare("SELECT estado, nro_asiento, id_viaje FROM ventas WHERE codigo_ticket = ?");
        $stmt->execute([$codigo_ticket]);
        $venta = $stmt->fetch();

        if (!$venta) {
            echo json_encode(['success' => false, 'msg' => 'El boleto no existe.']);
            exit;
        }

        if ($venta['estado'] === 'ANULADO') {
            echo json_encode(['success' => false, 'msg' => 'Este boleto ya se encuentra anulado.']);
            exit;
        }

        // Anulamos el boleto y registramos qué oficina hizo la anulación
        $update = $pdo->prepare("UPDATE ventas SET estado = 'ANULADO', oficina_venta = ? WHERE codigo_ticket = ?");
        $update->execute([$oficina . ' (Anulación)', $codigo_ticket]);

        echo json_encode(['success' => true, 'msg' => 'Boleto anulado correctamente. El asiento ha sido liberado.']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => 'Error al anular: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 3. ACCIÓN: CIERRE DE CAJA
// ==========================================
if ($accion === 'cierre') {
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $oficina = $_GET['oficina'] ?? '';

    try {
        // Calcular Ingresos (Tickets Confirmados)
        $stmt_ingresos = $pdo->prepare("
            SELECT COALESCE(SUM(total_pagado), 0) as total, COUNT(id) as cantidad 
            FROM ventas 
            WHERE oficina_venta = ? AND DATE(fecha_venta) = ? AND estado = 'CONFIRMADO'
        ");
        $stmt_ingresos->execute([$oficina, $fecha]);
        $ingresos = $stmt_ingresos->fetch();

        // Calcular Egresos (Tickets Anulados)
        $stmt_egresos = $pdo->prepare("
            SELECT COALESCE(SUM(total_pagado), 0) as total, COUNT(id) as cantidad 
            FROM ventas 
            WHERE oficina_venta LIKE ? AND DATE(fecha_venta) = ? AND estado = 'ANULADO'
        ");
        $stmt_egresos->execute([$oficina . '%', $fecha]);
        $egresos = $stmt_egresos->fetch();

        $total_caja = $ingresos['total'] - $egresos['total'];

        echo json_encode([
            'success' => true,
            'ingresos' => (int)$ingresos['total'],
            'vendidos' => (int)$ingresos['cantidad'],
            'egresos' => (int)$egresos['total'],
            'anulados' => (int)$egresos['cantidad'],
            'total_caja' => (int)$total_caja
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => 'Error al calcular cierre: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 4. ACCIÓN NUEVA: BUSCAR CLIENTE POR RUT PARA AUTOCOMPLETAR
// ==========================================
if ($accion === 'buscar_cliente') {
    $rut = trim($_GET['rut'] ?? '');
    
    if (empty($rut)) {
        echo json_encode(['success' => false]);
        exit;
    }

    try {
        // Busca en la tabla optimizada de CLIENTES
        $stmt = $pdo->prepare("SELECT nombre, telefono FROM clientes WHERE rut = ?");
        $stmt->execute([$rut]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            echo json_encode([
                'success' => true, 
                'nombre' => $cliente['nombre'], 
                'telefono' => $cliente['telefono']
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Si llega hasta aquí, la acción no era válida
echo json_encode(['success' => false, 'msg' => 'Acción no reconocida.']);