<?php
// public/exito.php
require_once '../config/db.php';

$flow_api_key = 'TU_API_KEY_AQUI'; 
$flow_secret_key = 'TU_SECRET_KEY_AQUI'; 
$flow_url_api = 'https://sandbox.flow.cl/api'; 

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$estado_pago = 0; $datos_boleto = null; $error_debug = ""; 

if ($token) {
    $params = [ "apiKey" => $flow_api_key, "token" => $token ];
    $keys = array_keys($params); sort($keys); $toSign = "";
    foreach ($keys as $key) { $toSign .= $key . $params[$key]; }
    $params["s"] = hash_hmac('sha256', $toSign, $flow_secret_key);

    $url = $flow_url_api . "/payment/getStatus?" . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $respuesta_cruda = curl_exec($ch); $error_curl = curl_error($ch); curl_close($ch);

    if ($respuesta_cruda === false) {
        $error_debug = "Error cURL: " . $error_curl;
    } else {
        $response = json_decode($respuesta_cruda, true);
        if (isset($response['status'])) {
            $estado_pago = $response['status']; $codigo_ticket = $response['commerceOrder'];
            if ($estado_pago == 2) {
                $stmt = $pdo->prepare("SELECT * FROM ventas WHERE codigo_ticket = ?");
                $stmt->execute([$codigo_ticket]);
                $datos_boleto = $stmt->fetch(PDO::FETCH_ASSOC);
            } else $error_debug = "Estado devuelto por banco: " . $estado_pago;
        } else $error_debug = "Flow respondió error: " . ($response['message'] ?? 'Desconocido');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de tu Compra | Buses Cordillera</title>
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
        .debug-box { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 10px; border-radius: 6px; text-align: left; font-size: 0.8rem; font-family: monospace; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <?php if (!$token): ?>
            <div class="icon error-icon">⚠️</div><h1 style="margin-top:0;">Acceso Inválido</h1>
            <a href="index.php" class="btn">Volver al inicio</a>

        <?php elseif ($estado_pago == 2 && $datos_boleto): ?>
            <div class="icon success-icon">✅</div><h1 style="margin-top:0;">¡Pago Exitoso!</h1>
            <p style="color:gray;">Tu pasaje ha sido confirmado y tu asiento reservado.</p>
            <div class="ticket-info">
                <center><small>CÓDIGO DE RESERVA</small><br><strong style="font-size:1.5rem; color:var(--success);"><?= htmlspecialchars($datos_boleto['codigo_ticket']) ?></strong></center>
                <hr style="border-top:1px dashed #ccc; margin:15px 0;">
                <p><strong>Pasajero:</strong> <?= htmlspecialchars($datos_boleto['nombre_pasajero']) ?></p>
                <p><strong>Ruta:</strong> <?= htmlspecialchars($datos_boleto['origen_boleto']) ?> ➔ <?= htmlspecialchars($datos_boleto['destino_boleto']) ?></p>
                <p><strong>Fecha y Hora:</strong> <?= date('d/m/Y H:i', strtotime($datos_boleto['fecha_viaje_historico'])) ?> hrs</p>
                <p><strong>Asiento:</strong> N° <?= htmlspecialchars($datos_boleto['nro_asiento']) ?></p>
                
                <?php if (!empty($datos_boleto['anotaciones'])): ?>
                    <p style="margin-top:10px; color:#555;"><strong>Notas adicionales:</strong> <?= htmlspecialchars($datos_boleto['anotaciones']) ?></p>
                <?php endif; ?>
            </div>
            <a href="index.php" class="btn">Comprar otro pasaje</a>

        <?php else: ?>
            <div class="icon error-icon">❌</div><h1 style="margin-top:0;">Pago No Realizado</h1>
            <?php if ($error_debug): ?><div class="debug-box"><strong>Debug:</strong><br><?= htmlspecialchars($error_debug) ?></div><?php endif; ?>
            <a href="index.php" class="btn">Intentar nuevamente</a>
        <?php endif; ?>
    </div>
</body>
</html>