<?php
// api/cash_close.php
header('Content-Type: application/json');
require_once 'db_conn.php';

// CORRECCIÓN: Zona horaria Chile
date_default_timezone_set('America/Santiago');

$oficina_id = $_POST['oficina_id'] ?? '';
$fecha = $_POST['fecha'] ?? date('Y-m-d'); // Si no envían fecha, usa hoy (Chile)

if(!$oficina_id) {
    echo json_encode(['success' => false, 'error' => 'Seleccione una oficina']);
    exit;
}

try {
    // Sumar ventas filtrando por oficina Y fecha específica
    $sql = "SELECT metodo_pago, SUM(total_pagado) as total 
            FROM ventas 
            WHERE oficina_id = ? 
              AND DATE(fecha_venta) = ? 
              AND estado = 'Confirmado'
            GROUP BY metodo_pago";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$oficina_id, $fecha]);
    $detalles = $stmt->fetchAll();

    // Calcular totales generales
    $total_general = 0;
    $resumen = [];
    
    foreach($detalles as $d) {
        $total_general += $d['total'];
        $resumen[] = $d;
    }

    // Obtener nombre de la oficina
    $stmt = $pdo->prepare("SELECT nombre FROM oficinas WHERE id = ?");
    $stmt->execute([$oficina_id]);
    $nomOficina = $stmt->fetchColumn();

    echo json_encode([
        'success' => true, 
        'oficina' => $nomOficina,
        'fecha' => date('d-m-Y', strtotime($fecha)), // Devolver fecha formateada
        'detalles' => $resumen,
        'total_general' => $total_general
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}