<?php
// admin/api/buscar_viajes.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$origen = $_POST['origen'] ?? '';
$destino = $_POST['destino'] ?? '';
$fecha = $_POST['fecha'] ?? '';

if (!$origen || !$destino || !$fecha) {
    echo json_encode(['success' => false, 'msg' => 'Faltan datos de búsqueda']);
    exit;
}

try {
    // Buscar viajes programados para esa fecha cruzados con el tramo específico
    $sql = "SELECT 
                v.id, 
                DATE_FORMAT(v.fecha_hora, '%H:%i') as hora, 
                b.numero_maquina, 
                b.patente,
                m.precio_adulto, 
                m.precio_mayor, 
                m.precio_estudiante
            FROM viajes v
            JOIN buses b ON v.id_bus = b.id
            JOIN matriz_precios m ON m.id_viaje = v.id
            WHERE DATE(v.fecha_hora) = ? 
              AND m.origen_tramo = ? 
              AND m.destino_tramo = ?
            ORDER BY v.fecha_hora ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha, $origen, $destino]);
    $viajes = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'viajes' => $viajes]);

} catch(Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Error BD: ' . $e->getMessage()]);
}
?>