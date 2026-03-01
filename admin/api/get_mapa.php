<?php
// admin/api/get_mapa.php (UNIFICADO + LIMPIADOR AUTOMÁTICO)
require_once '../../config/db.php';
header('Content-Type: application/json');

$id_viaje = $_GET['id'] ?? 0;

if (!$id_viaje) { echo json_encode([]); exit; }

try {
    // -------------------------------------------------------------------------
    // 🧹 EL LIMPIADOR AUTOMÁTICO (MAGIC CLEANUP)
    // Libera los asientos de la web que quedaron "Pendientes" por más de 15 minutos
    // porque el cliente cerró la ventana del banco o se arrepintió.
    $pdo->exec("DELETE FROM ventas WHERE estado = 'PENDIENTE' AND fecha_venta < (NOW() - INTERVAL 15 MINUTE)");
    // -------------------------------------------------------------------------

    // 1. Obtener la capacidad exacta del bus
    $stmt_bus = $pdo->prepare("SELECT b.capacidad FROM viajes v JOIN buses b ON v.id_bus = b.id WHERE v.id = ?");
    $stmt_bus->execute([$id_viaje]);
    $bus = $stmt_bus->fetch(PDO::FETCH_ASSOC);

    if (!$bus) { echo json_encode([]); exit; }
    $capacidad = (int)$bus['capacidad'];

    // 2. Buscar asientos ocupados (Confirmados o Pendientes recientes)
    $stmt_ocupados = $pdo->prepare("SELECT nro_asiento FROM ventas WHERE id_viaje = ? AND estado IN ('CONFIRMADO', 'PENDIENTE')");
    $stmt_ocupados->execute([$id_viaje]);
    $asientos_ocupados = $stmt_ocupados->fetchAll(PDO::FETCH_COLUMN);

    // 3. Armar el mapa
    $mapa = [];
    for ($i = 1; $i <= $capacidad; $i++) {
        $estado = in_array($i, $asientos_ocupados) ? 'ocupado' : 'libre';
        $mapa[] = ['nro' => $i, 'estado' => $estado];
    }

    echo json_encode($mapa);

} catch (Exception $e) {
    echo json_encode([]);
}