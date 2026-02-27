<?php
// admin/api/get_mapa.php (UNIFICADO: Sirve a la CAJA y a la WEB)
require_once '../../config/db.php';
header('Content-Type: application/json');

// Recibir el ID del viaje
$id_viaje = $_GET['id'] ?? 0;

if (!$id_viaje) {
    echo json_encode([]);
    exit;
}

try {
    // 1. Obtener la capacidad exacta del bus asignado a este viaje
    $stmt_bus = $pdo->prepare("
        SELECT b.capacidad 
        FROM viajes v 
        JOIN buses b ON v.id_bus = b.id 
        WHERE v.id = ?
    ");
    $stmt_bus->execute([$id_viaje]);
    $bus = $stmt_bus->fetch(PDO::FETCH_ASSOC);

    if (!$bus) {
        // Si por alguna razón el viaje no existe, devolvemos un mapa vacío
        echo json_encode([]);
        exit;
    }

    $capacidad = (int)$bus['capacidad'];

    // 2. Buscar qué asientos ya están vendidos o en proceso de pago online (Flow)
    // Es crucial incluir 'PENDIENTE' para que no vendan el mismo asiento en la oficina 
    // mientras alguien lo está pagando con Webpay en su casa.
    $stmt_ocupados = $pdo->prepare("
        SELECT nro_asiento 
        FROM ventas 
        WHERE id_viaje = ? AND estado IN ('CONFIRMADO', 'PENDIENTE')
    ");
    $stmt_ocupados->execute([$id_viaje]);
    
    // FETCH_COLUMN nos devuelve un arreglo simple, ej: [3, 4, 12]
    $asientos_ocupados = $stmt_ocupados->fetchAll(PDO::FETCH_COLUMN);

    // 3. Armar el mapa de asientos del 1 hasta la capacidad máxima del bus
    $mapa = [];
    for ($i = 1; $i <= $capacidad; $i++) {
        $estado = in_array($i, $asientos_ocupados) ? 'ocupado' : 'libre';
        
        $mapa[] = [
            'nro' => $i,
            'estado' => $estado
        ];
    }

    // 4. Enviar el JSON limpio a la pantalla
    echo json_encode($mapa);

} catch (Exception $e) {
    // Si hay un error, devolvemos un array vacío para que no colapse el sistema
    echo json_encode([]);
}