<?php
// admin/api/get_mapa.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$id_viaje = $_GET['id'] ?? 0;

try {
    // 1. Obtener la capacidad total del bus asignado a este viaje
    $stmt = $pdo->prepare("
        SELECT b.capacidad 
        FROM viajes v 
        JOIN buses b ON v.id_bus = b.id 
        WHERE v.id = ?
    ");
    $stmt->execute([$id_viaje]);
    $capacidad = $stmt->fetchColumn();

    if (!$capacidad) {
        $capacidad = 40; // Valor por defecto por si hay un error
    }

    // 2. Obtener asientos VENDIDOS (Solo los CONFIRMADOS, si está anulado no cuenta)
    $stmt = $pdo->prepare("
        SELECT nro_asiento, 'VENDIDO' as estado 
        FROM ventas 
        WHERE id_viaje = ? AND estado = 'CONFIRMADO'
    ");
    $stmt->execute([$id_viaje]);
    $vendidos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna formato [asiento => 'VENDIDO']

    // 3. Obtener asientos BLOQUEADOS (Carritos de compra web activos)
    $stmt = $pdo->prepare("
        SELECT nro_asiento, 'BLOQUEADO' as estado 
        FROM reservas_temporales 
        WHERE id_viaje = ? AND fecha_expiracion > NOW()
    ");
    $stmt->execute([$id_viaje]);
    $bloqueados = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna formato [asiento => 'BLOQUEADO']

    // 4. Construir el mapa completo de asientos para dibujar en el HTML
    $mapa = [];
    for ($i = 1; $i <= $capacidad; $i++) {
        if (isset($vendidos[$i])) {
            $estado = 'ocupado';
        } elseif (isset($bloqueados[$i])) {
            $estado = 'bloqueado';
        } else {
            $estado = 'libre';
        }

        $mapa[] = [
            'nro' => $i,
            'estado' => $estado
        ];
    }

    echo json_encode($mapa);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>