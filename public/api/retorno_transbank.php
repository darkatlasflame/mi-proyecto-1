<?php
// public/api/retorno_transbank.php
require_once '../../config/db.php';

// Recibir el token falso
$token = $_GET['token_ws'] ?? $_POST['token_ws'] ?? null;
$es_rechazado = isset($_POST['rechazar']); // Saber si pinchaste el botón rojo

if (!$token) {
    die("<h2 style='text-align:center; margin-top:50px;'>Error: No hay token de transacción.</h2>");
}

try {
    // 1. Recuperar datos de la BD
    $stmt = $pdo->prepare("SELECT * FROM pagos_webpay WHERE token_ws = ?");
    $stmt->execute([$token]);
    $pago_db = $stmt->fetch();
    
    if (!$pago_db) {
        die("Token no encontrado en la base de datos.");
    }

    $d = json_decode($pago_db['datos_cliente'], true);

    if (!$es_rechazado) {
        // --- PAGO APROBADO SIMULADO ---
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE pagos_webpay SET estado='APROBADO' WHERE token_ws=?")->execute([$token]);

        $codigo_ticket = 'WEB-' . strtoupper(substr(uniqid(), -5));
        
        $sql_venta = "INSERT INTO ventas (
            codigo_ticket, id_viaje, nro_asiento, rut_pasajero, nombre_pasajero, telefono_contacto, 
            tipo_pasajero, origen_boleto, destino_boleto, total_pagado, canal, oficina_venta, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'WEB', 'ONLINE', 'CONFIRMADO')";
        
        $pdo->prepare($sql_venta)->execute([
            $codigo_ticket, $d['id_viaje'], $d['asiento'], $d['rut'], $d['nombre'], $d['telefono'], 
            $d['tipo_pasajero'], $d['origen'], $d['destino'], $pago_db['monto']
        ]);

        $pdo->prepare("DELETE FROM reservas_temporales WHERE id_viaje=? AND nro_asiento=?")->execute([$d['id_viaje'], $d['asiento']]);

        $pdo->commit();

        // Redirigir a página de éxito
        header("Location: ../exito.php?ticket=" . $codigo_ticket);

    } else {
        // --- PAGO RECHAZADO SIMULADO ---
        $pdo->prepare("UPDATE pagos_webpay SET estado='RECHAZADO' WHERE token_ws=?")->execute([$token]);
        // Liberar asiento
        $pdo->prepare("DELETE FROM reservas_temporales WHERE id_viaje=? AND nro_asiento=?")->execute([$d['id_viaje'], $d['asiento']]);
        
        echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>";
        echo "<h2 style='color:red;'>Pago Rechazado Simuladamente.</h2>";
        echo "<p>Tu tarjeta no fue cargada y el asiento ha sido liberado.</p>";
        echo "<a href='../index.php' style='padding:10px 20px; background:#003580; color:white; text-decoration:none; border-radius:5px;'>Volver al inicio</a>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "Error en la transacción simulada: " . $e->getMessage();
}
?>