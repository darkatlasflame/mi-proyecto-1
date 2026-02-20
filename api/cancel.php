<?php
// api/cancel.php
header('Content-Type: application/json');
require_once 'db_conn.php';

// Importante: La anulación es una acción sensible.
// En un sistema real aquí validarías permisos de administrador.

$folio = $_POST['folio'] ?? '';

if(empty($folio)) {
    echo json_encode(['success' => false, 'error' => 'Debe indicar el folio de venta']);
    exit;
}

try {
    // Verificar si existe y si ya está anulada
    $stmt = $pdo->prepare("SELECT id, estado FROM ventas WHERE codigo_venta = ?");
    $stmt->execute([$folio]);
    $venta = $stmt->fetch();

    if(!$venta) {
        echo json_encode(['success' => false, 'error' => 'Folio no encontrado']);
        exit;
    }

    if($venta['estado'] === 'Anulado') {
        echo json_encode(['success' => false, 'error' => 'Esta venta ya fue anulada anteriormente']);
        exit;
    }

    // Proceder a anular (cambiar estado)
    // No borramos los registros para mantener trazabilidad contable, solo cambiamos el estado.
    // El sistema de asientos (seats.php) filtra por estado='Confirmado', así que estos asientos se liberan solos.
    $stmt = $pdo->prepare("UPDATE ventas SET estado = 'Anulado' WHERE id = ?");
    $stmt->execute([$venta['id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}