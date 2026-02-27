<?php
// public/api/iniciar_pago.php
require_once '../../config/db.php';
header('Content-Type: application/json');

// --- CREDENCIALES DE FLOW (Sandbox / Pruebas) ---
// Cuando pases a real, cambias estas llaves y la URL
$flow_api_key = '475EF3EC-A805-4287-9F9F-8289649DLD0B';
$flow_secret_key = '941676971089fb002474a016e59125c5da561125';
$flow_url_api = 'https://sandbox.flow.cl/api'; 
// ------------------------------------------------

$id_viaje = $_POST['id_viaje'] ?? '';
$asiento = $_POST['asiento'] ?? '';
$rut = trim($_POST['rut'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? 'correo@ejemplo.com'); // Flow exige un email
$total_pagado = $_POST['total_pagado'] ?? 0;
// (Asume que recibes origen, destino, tipo_pasajero igual que antes)

try {
    // 1. Verificar que el asiento siga libre
    $stmt_check = $pdo->prepare("SELECT id FROM ventas WHERE id_viaje = ? AND nro_asiento = ? AND estado IN ('CONFIRMADO', 'PENDIENTE')");
    $stmt_check->execute([$id_viaje, $asiento]);
    if ($stmt_check->fetch()) {
        echo json_encode(['status' => 'error', 'msg' => 'Asiento ocupado o en proceso de pago.']);
        exit;
    }

    // 2. Fotografía del viaje (Igual que en la caja)
    $stmt_info = $pdo->prepare("SELECT fecha_hora, numero_maquina, patente FROM viajes v JOIN buses b ON v.id_bus = b.id WHERE v.id = ?");
    $stmt_info->execute([$id_viaje]);
    $info_viaje = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $codigo_ticket = 'WEB-' . strtoupper(substr(uniqid(), -5));
    
    // 3. Insertar Venta como PENDIENTE
    $sql_venta = "INSERT INTO ventas (
        codigo_ticket, id_viaje, fecha_viaje_historico, bus_historico, nro_asiento, rut_pasajero, nombre_pasajero, 
        origen_boleto, destino_boleto, total_pagado, canal, oficina_venta, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'WEB', 'Venta Online', 'PENDIENTE')";
    
    $pdo->prepare($sql_venta)->execute([
        $codigo_ticket, $id_viaje, $info_viaje['fecha_hora'], $info_viaje['numero_maquina'], 
        $asiento, $rut, $nombre, $_POST['origen'], $_POST['destino'], $total_pagado
    ]);

    // 4. CREAR ORDEN EN FLOW
    $params = [
        "apiKey" => $flow_api_key,
        "commerceOrder" => $codigo_ticket, // Usamos tu código de ticket como ID de orden
        "subject" => "Pasaje JetBus: " . $_POST['origen'] . " - " . $_POST['destino'],
        "currency" => "CLP",
        "amount" => $total_pagado,
        "email" => $email,
        "paymentMethod" => 9, // 9 = Todos los medios de pago (Webpay, Mach, etc)
        // OJO: Aquí debes poner tu dominio real con el túnel de Cloudflare
        "urlConfirmation" => "https://plausible-unpredatory-kati.ngrok-free.dev/jetbus/public/api/confirmar_pago.php",
        "urlReturn" => "https://plausible-unpredatory-kati.ngrok-free.dev/jetbus/public/exito.php" 
    ];

    // Firmar la petición para Flow
    $keys = array_keys($params);
    sort($keys);
    $toSign = "";
    foreach ($keys as $key) { $toSign .= $key . $params[$key]; }
    $params["s"] = hash_hmac('sha256', $toSign, $flow_secret_key);

    // Enviar a Flow
    $ch = curl_init($flow_url_api . "/payment/create");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['url'])) {
        // Todo salió bien, devolvemos la URL de pago al frontend
        echo json_encode([
            'status' => 'success', 
            'url_pago' => $response['url'] . "?token=" . $response['token']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Error de Flow: ' . $response['message']]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}