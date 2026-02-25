<?php
// admin/api/vender.php
require_once '../../config/db.php';
header('Content-Type: application/json');

// Validar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.']);
    exit;
}

// Recibir los datos de la venta
$id_viaje = $_POST['id_viaje'] ?? '';
$asiento = $_POST['asiento'] ?? '';
$rut = trim($_POST['rut'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$oficina = $_POST['oficina'] ?? 'Oficina Central';
$origen = $_POST['origen'] ?? '';
$destino = $_POST['destino'] ?? '';
$tipo_pasajero = $_POST['tipo_pasajero'] ?? 'ADULTO';
$total_pagado = $_POST['total_pagado'] ?? 0;

// Validar campos obligatorios
if (!$id_viaje || !$asiento || !$rut || !$nombre) {
    echo json_encode(['status' => 'error', 'msg' => 'Faltan datos obligatorios (RUT y Nombre).']);
    exit;
}

try {
    // 1. Verificar que el asiento siga libre (por si lo compraron por internet 1 segundo antes)
    $stmt_check = $pdo->prepare("SELECT id FROM ventas WHERE id_viaje = ? AND nro_asiento = ? AND estado = 'CONFIRMADO'");
    $stmt_check->execute([$id_viaje, $asiento]);
    if ($stmt_check->fetch()) {
        echo json_encode(['status' => 'error', 'msg' => 'El asiento ya fue ocupado. Por favor, seleccione otro.']);
        exit;
    }

    // 2. Generar el código del pasaje
    $codigo_ticket = 'CAJA-' . strtoupper(substr(uniqid(), -5));
    
    // 3. Insertar la venta
    $sql_venta = "INSERT INTO ventas (
        codigo_ticket, id_viaje, nro_asiento, rut_pasajero, nombre_pasajero, telefono_contacto, 
        tipo_pasajero, origen_boleto, destino_boleto, total_pagado, canal, oficina_venta, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'CAJA', ?, 'CONFIRMADO')";
    
    $pdo->prepare($sql_venta)->execute([
        $codigo_ticket, $id_viaje, $asiento, $rut, $nombre, $telefono, 
        $tipo_pasajero, $origen, $destino, $total_pagado, $oficina
    ]);

    // 4. GUARDAR O ACTUALIZAR AL CLIENTE EN LA NUEVA TABLA
    $sql_cliente = "INSERT INTO clientes (rut, nombre, telefono) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), telefono = VALUES(telefono)";
    $pdo->prepare($sql_cliente)->execute([$rut, $nombre, $telefono]);

    // 5. Devolver éxito
    echo json_encode(['status' => 'success', 'ticket' => $codigo_ticket]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Error al guardar la venta: ' . $e->getMessage()]);
}