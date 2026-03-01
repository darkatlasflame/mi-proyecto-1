<?php
// public/api/iniciar_pago.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$flow_api_key = 'TU_API_KEY_AQUI';
$flow_secret_key = 'TU_SECRET_KEY_AQUI';
$flow_url_api = 'https://sandbox.flow.cl/api'; 

$id_viaje = $_POST['id_viaje'] ?? '';
$asiento = $_POST['asiento'] ?? '';
$rut = trim($_POST['rut'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? 'correo@ejemplo.com'); 
$anotaciones = trim($_POST['anotaciones'] ?? '');
$total_pagado = $_POST['total_pagado'] ?? 0;

try {
    $stmt_check = $pdo->prepare("SELECT id FROM ventas WHERE id_viaje = ? AND nro_asiento = ? AND estado IN ('CONFIRMADO', 'PENDIENTE')");
    $stmt_check->execute([$id_viaje, $asiento]);
    if ($stmt_check->fetch()) {
        echo json_encode(['status' => 'error', 'msg' => 'Asiento ocupado o en proceso de pago.']);
        exit;
    }

    $stmt_info = $pdo->prepare("SELECT fecha_hora, numero_maquina, patente FROM viajes v JOIN buses b ON v.id_bus = b.id WHERE v.id = ?");
    $stmt_info->execute([$id_viaje]);
    $info_viaje = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $codigo_ticket = 'WEB-' . strtoupper(substr(uniqid(), -5));
    
    // Agregada la columna 'anotaciones' al final
    $sql_venta = "INSERT INTO ventas (
        codigo_ticket, id_viaje, fecha_viaje_historico, bus_historico, nro_asiento, rut_pasajero, nombre_pasajero, 
        origen_boleto, destino_boleto, total_pagado, canal, oficina_venta, estado, anotaciones
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'WEB', 'Venta Online', 'PENDIENTE', ?)";
    
    $pdo->prepare($sql_venta)->execute([
        $codigo_ticket, $id_viaje, $info_viaje['fecha_hora'], $info_viaje['numero_maquina'], 
        $asiento, $rut, $nombre, $_POST['origen'], $_POST['destino'], $total_pagado, $anotaciones
    ]);

    $params = [
        "apiKey" => $flow_api_key,
        "commerceOrder" => $codigo_ticket, 
        "subject" => "Pasaje Buses Cordillera: " . $_POST['origen'] . " - " . $_POST['destino'],
        "currency" => "CLP",
        "amount" => $total_pagado,
        "email" => $email,
        "paymentMethod" => 9,
        "urlConfirmation" => "https://tusitio.ngrok-free.app/jetbus/public/api/confirmar_pago.php",
        "urlReturn" => "https://tusitio.ngrok-free.app/jetbus/public/exito.php" 
    ];

    $keys = array_keys($params); sort($keys); $toSign = "";
    foreach ($keys as $key) { $toSign .= $key . $params[$key]; }
    $params["s"] = hash_hmac('sha256', $toSign, $flow_secret_key);

    $ch = curl_init($flow_url_api . "/payment/create");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['url'])) {
        echo json_encode(['status' => 'success', 'url_pago' => $response['url'] . "?token=" . $response['token']]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Error de Flow: ' . $response['message']]);
    }

} catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]); }