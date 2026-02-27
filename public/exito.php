<?php
// public/exito.php
require_once '../config/db.php';

// --- TUS CREDENCIALES DE FLOW SANDBOX ---
// ¡Recuerda poner las mismas que usaste en iniciar_pago.php!
$flow_api_key = '475EF3EC-A805-4287-9F9F-8289649DLD0B';
$flow_secret_key = '941676971089fb002474a016e59125c5da561125';
$flow_url_api = 'https://sandbox.flow.cl/api'; 
// ----------------------------------------

// Flow envía el token de vuelta normalmente por POST
$token = $_POST['token'] ?? $_GET['token'] ?? '';
$estado_pago = 0;
$datos_boleto = null;

if ($token) {
    // 1. Preguntarle a Flow cómo terminó esta transacción
    $params = [
        "apiKey" => $flow_api_key,
        "token" => $token
    ];

    $keys = array_keys($params);
    sort($keys);
    $toSign = "";
    foreach ($keys as $key) { $toSign .= $key . $params[$key]; }
    $params["s"] = hash_hmac('sha256', $toSign, $flow_secret_key);

    $url = $flow_url_api . "/payment/getStatus?" . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['status'])) {
        $estado_pago = $response['status'];
        $codigo_ticket = $response['commerceOrder'];

        // 2. Si el estado es 2 (Pagado), buscamos los datos en la base de datos para mostrarlos
        if ($estado_pago == 2) {
            $stmt = $pdo->prepare("SELECT * FROM ventas WHERE codigo_ticket = ?");
            $stmt->execute([$codigo_ticket]);
            $datos_boleto = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de tu Compra | JetBus</title>
    <style>
        :root { --primary: #003580; --accent: #006ce4; --success: #28a745; --danger: #dc3545; --bg: #f5f7fa; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; width: 90%; text-align: center; }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        .success-icon { color: var(--success); }
        .error-icon { color: var(--danger); }
        .ticket-info { background: #f8fafc; border: 2px dashed #cbd5e1; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left; }
        .ticket-info p { margin: 8px 0; font-size: 0.95rem; }
        .ticket-info strong { color: var(--primary); }
        .btn { background: var(--accent); color: white; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block; transition: 0.2s; margin-top: 10px; width: 100%; box-sizing: border-box; }
        .btn:hover { background: #0056b3; }
        .btn-outline { background: white; color: var(--primary); border: 2px solid var(--primary); }
    </style>
</head>
<body>

    <div class="card">
        <?php if (!$token): ?>
            <div class="icon error-icon">⚠️</div>
            <h1 style="margin-top:0;">Acceso Inválido</h1>
            <p style="color:gray;">No se encontraron datos de la transacción.</p>
            <a href="index.php" class="btn">Volver al inicio</a>

        <?php elseif ($estado_pago == 2 && $datos_boleto): ?>
            <div class="icon success-icon">✅</div>
            <h1 style="margin-top:0;">¡Pago Exitoso!</h1>
            <p style="color:gray;">Tu pasaje ha sido confirmado y tu asiento reservado.</p>
            
            <div class="ticket-info">
                <center><small>CÓDIGO DE RESERVA</small><br><strong style="font-size:1.5rem; color:var(--success);"><?= htmlspecialchars($datos_boleto['codigo_ticket']) ?></strong></center>
                <hr style="border-top:1px dashed #ccc; margin:15px 0;">
                <p><strong>Pasajero:</strong> <?= htmlspecialchars($datos_boleto['nombre_pasajero']) ?></p>
                <p><strong>Ruta:</strong> <?= htmlspecialchars($datos_boleto['origen_boleto']) ?> ➔ <?= htmlspecialchars($datos_boleto['destino_boleto']) ?></p>
                <p><strong>Fecha y Hora:</strong> <?= date('d/m/Y H:i', strtotime($datos_boleto['fecha_viaje_historico'])) ?> hrs</p>
                <p><strong>Asiento:</strong> N° <?= htmlspecialchars($datos_boleto['nro_asiento']) ?></p>
                <p><strong>Total Pagado:</strong> $<?= number_format($datos_boleto['total_pagado'], 0, '', '.') ?></p>
            </div>
            
            <p style="font-size:0.85rem; color:gray;">Por favor, toma una captura de pantalla de este boleto o anota tu código de reserva para presentarlo al subir al bus.</p>
            
            <a href="index.php" class="btn">Comprar otro pasaje</a>

        <?php else: ?>
            <div class="icon error-icon">❌</div>
            <h1 style="margin-top:0;">Pago No Realizado</h1>
            <p style="color:gray;">El pago fue rechazado por el banco o cancelaste la operación. Tu asiento ha sido liberado.</p>
            <a href="index.php" class="btn">Intentar nuevamente</a>
        <?php endif; ?>
    </div>

</body>
</html>