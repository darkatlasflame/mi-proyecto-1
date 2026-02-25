<?php
// admin/api/vender.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$id_viaje = $_POST['id_viaje'] ?? null;
$asiento = $_POST['asiento'] ?? null;
$rut = $_POST['rut'] ?? null;
$nombre = $_POST['nombre'] ?? null;
$telefono = $_POST['telefono'] ?? '';
$origen = $_POST['origen'] ?? '';
$destino = $_POST['destino'] ?? '';
$tipo_pasajero = $_POST['tipo_pasajero'] ?? 'ADULTO';
$total_pagado = $_POST['total_pagado'] ?? 0;
$oficina = $_POST['oficina'] ?? 'Oficina Central';

if (!$id_viaje || !$asiento || !$rut || !$nombre) {
    echo json_encode(['status' => 'error', 'msg' => 'Faltan datos obligatorios del pasajero']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 1. Verificar si alguien más acaba de comprar el mismo asiento (Doble chequeo de seguridad)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE id_viaje = ? AND nro_asiento = ? AND estado = 'CONFIRMADO'");
    $stmt->execute([$id_viaje, $asiento]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("El asiento $asiento ya fue vendido. Actualice el mapa.");
    }
    
    // 2. Generar un Código de Ticket Único
    $ticket = 'TKT-' . strtoupper(substr(uniqid(), -6));
    
    // 3. Insertar la Venta Definitiva
    $sql = "INSERT INTO ventas (
                codigo_ticket, id_viaje, nro_asiento, rut_pasajero, nombre_pasajero, telefono_contacto, 
                tipo_pasajero, origen_boleto, destino_boleto, total_pagado, canal, oficina_venta, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'OFICINA', ?, 'CONFIRMADO')";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $ticket, $id_viaje, $asiento, $rut, $nombre, $telefono, 
        $tipo_pasajero, $origen, $destino, $total_pagado, $oficina
    ]);
    
    // 4. Borrar cualquier bloqueo temporal (el carrito web) que existiera para este asiento
    $pdo->prepare("DELETE FROM reservas_temporales WHERE id_viaje = ? AND nro_asiento = ?")->execute([$id_viaje, $asiento]);
    
    $pdo->commit();
    echo json_encode(['status' => 'success', 'ticket' => $ticket]);
    
} catch(Exception $e) {
    $pdo->rollBack(); // Si algo falla, no se guarda nada
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>