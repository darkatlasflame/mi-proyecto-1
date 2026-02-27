<?php
// public/api/confirmar_pago.php
require_once '../../config/db.php';

// Mismas credenciales que arriba
$flow_api_key = '475EF3EC-A805-4287-9F9F-8289649DLD0B';
$flow_secret_key = '941676971089fb002474a016e59125c5da561125';
$flow_url_api = 'https://sandbox.flow.cl/api'; 

// Flow nos manda un Token por POST
$token = $_POST['token'] ?? '';
if (!$token) { http_response_code(400); exit('No token'); }

// Preguntarle a Flow el estado real de este token
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
    $codigo_ticket = $response['commerceOrder'];
    
    if ($response['status'] == 2) {
        // STATUS 2 = PAGADO. ¡Confirmamos el pasaje!
        $stmt = $pdo->prepare("UPDATE ventas SET estado = 'CONFIRMADO' WHERE codigo_ticket = ? AND estado = 'PENDIENTE'");
        $stmt->execute([$codigo_ticket]);
        
        // Aquí podrías agregar el código para guardar al cliente en el directorio
        // ...
        
    } elseif ($response['status'] == 3 || $response['status'] == 4) {
        // RECHAZADO o ANULADO. Liberamos el asiento
        $stmt = $pdo->prepare("UPDATE ventas SET estado = 'ANULADO' WHERE codigo_ticket = ?");
        $stmt->execute([$codigo_ticket]);
    }
}

// SIEMPRE hay que responderle a Flow para que sepa que lo recibimos
http_response_code(200);
echo "OK";