<?php
// admin/api/buscar_viajes.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$origen = $_POST['origen'] ?? '';
$destino = $_POST['destino'] ?? '';
$fecha = $_POST['fecha'] ?? '';

if (!$origen || !$destino || !$fecha) {
    echo json_encode(['success' => false, 'msg' => 'Faltan parámetros de búsqueda.']);
    exit;
}

try {
    // 1. Buscar precio universal
    $stmt_precio = $pdo->prepare("SELECT precio_adulto, precio_estudiante, precio_mayor FROM matriz_precios WHERE origen_tramo = ? AND destino_tramo = ?");
    $stmt_precio->execute([$origen, $destino]);
    $precios = $stmt_precio->fetch(PDO::FETCH_ASSOC);

    if (!$precios) {
        echo json_encode(['success' => false, 'msg' => 'No hay tarifas configuradas para este tramo.', 'viajes' => []]);
        exit;
    }

    // 2. Buscar viajes filtrando por Origen y Destino reales
    date_default_timezone_set('America/Santiago'); 
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');
    
    if ($fecha === $fecha_actual) {
        $sql_viajes = "
            SELECT v.id, DATE_FORMAT(v.fecha_hora, '%H:%i') as hora, b.numero_maquina, b.patente, b.capacidad 
            FROM viajes v 
            JOIN buses b ON v.id_bus = b.id 
            WHERE DATE(v.fecha_hora) = ? AND TIME(v.fecha_hora) >= ? 
            AND v.origen = ? AND v.destino = ? AND v.estado = 'PROGRAMADO'
            ORDER BY v.fecha_hora ASC
        ";
        $stmt_viajes = $pdo->prepare($sql_viajes);
        $stmt_viajes->execute([$fecha, $hora_actual, $origen, $destino]);
    } else {
        $sql_viajes = "
            SELECT v.id, DATE_FORMAT(v.fecha_hora, '%H:%i') as hora, b.numero_maquina, b.patente, b.capacidad 
            FROM viajes v 
            JOIN buses b ON v.id_bus = b.id 
            WHERE DATE(v.fecha_hora) = ? 
            AND v.origen = ? AND v.destino = ? AND v.estado = 'PROGRAMADO'
            ORDER BY v.fecha_hora ASC
        ";
        $stmt_viajes = $pdo->prepare($sql_viajes);
        $stmt_viajes->execute([$fecha, $origen, $destino]);
    }
    
    $viajes_db = $stmt_viajes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Unir datos
    $viajes_result = [];
    foreach ($viajes_db as $v) {
        $viajes_result[] = [
            'id' => $v['id'],
            'hora' => $v['hora'],
            'numero_maquina' => $v['numero_maquina'],
            'patente' => $v['patente'],
            'capacidad' => $v['capacidad'],
            'precio_adulto' => $precios['precio_adulto'],
            'precio_estudiante' => $precios['precio_estudiante'],
            'precio_mayor' => $precios['precio_mayor']
        ];
    }

    echo json_encode(['success' => true, 'viajes' => $viajes_result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Error de BD: ' . $e->getMessage()]);
}