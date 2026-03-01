<?php
// public/api/confirmar_pago.php
require_once '../../config/db.php';

// Importar clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/Exception.php';
require '../../PHPMailer/PHPMailer.php';
require '../../PHPMailer/SMTP.php';

// ---------------- CONFIGURACIÓN ----------------
$flow_api_key = 'TU_API_KEY_AQUI'; // <--- Llave de Flow
$flow_secret_key = 'TU_SECRET_KEY_AQUI'; // <--- Secreta de Flow
$flow_url_api = 'https://sandbox.flow.cl/api'; 

$smtp_user = 'tu_correo@gmail.com'; // <--- Tu correo Gmail
$smtp_pass = 'TU_CONTRASEÑA_DE_16_LETRAS'; // <--- La contraseña amarilla de Google (sin espacios)
// -----------------------------------------------

$token = $_POST['token'] ?? '';
if (!$token) exit;

try {
    // 1. Preguntarle a Flow cómo salió el pago
    $params = [ "apiKey" => $flow_api_key, "token" => $token ];
    $keys = array_keys($params); sort($keys); $toSign = "";
    foreach ($keys as $key) { $toSign .= $key . $params[$key]; }
    $params["s"] = hash_hmac('sha256', $toSign, $flow_secret_key);

    $url = $flow_url_api . "/payment/getStatus?" . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // 2. Si el estado es 2 (PAGADO)
    if (isset($response['status']) && $response['status'] == 2) {
        
        $codigo_ticket = $response['commerceOrder'];
        $email_cliente = $response['payer']; // Flow nos devuelve el correo que usó el cliente para pagar

        // 3. Confirmar en la Base de Datos (Importante: Solo actualizar si estaba PENDIENTE)
        $stmt_update = $pdo->prepare("UPDATE ventas SET estado = 'CONFIRMADO' WHERE codigo_ticket = ? AND estado = 'PENDIENTE'");
        $stmt_update->execute([$codigo_ticket]);

        // Si se actualizó correctamente (evita mandar 2 correos si Flow manda el aviso 2 veces por error de red)
        if ($stmt_update->rowCount() > 0) {
            
            // 4. Buscar los datos del boleto para armar el correo
            $stmt_ticket = $pdo->prepare("SELECT * FROM ventas WHERE codigo_ticket = ?");
            $stmt_ticket->execute([$codigo_ticket]);
            $ticket = $stmt_ticket->fetch(PDO::FETCH_ASSOC);

            if ($ticket && $email_cliente) {
                // 5. ENVIAR EL CORREO ELECTRÓNICO
                $mail = new PHPMailer(true);
                try {
                    // Configuración del servidor de Google
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtp_user;
                    $mail->Password   = $smtp_pass;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet    = 'UTF-8';

                    // Remitente y Destinatario
                    $mail->setFrom($smtp_user, 'Buses Cordillera');
                    $mail->addAddress($email_cliente, $ticket['nombre_pasajero']);

                    // Contenido del Correo (Diseño HTML)
                    $mail->isHTML(true);
                    $mail->Subject = '🚌 Tu pasaje confirmado: ' . $ticket['origen_boleto'] . ' a ' . $ticket['destino_boleto'];
                    
                    $notasHtml = !empty($ticket['anotaciones']) ? "<p style='color:#dc3545;'><strong>Anotaciones:</strong> {$ticket['anotaciones']}</p>" : "";

                    $html = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px; background-color: #ffffff;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <h2 style='color: #003580; margin: 0;'>Buses<span style='color: #febb02;'>Cordillera</span></h2>
                            <p style='color: #28a745; font-weight: bold;'>¡Tu compra ha sido exitosa!</p>
                        </div>
                        
                        <p>Hola <strong>{$ticket['nombre_pasajero']}</strong>,</p>
                        <p>Tu pasaje ha sido emitido y tu asiento está asegurado. Aquí tienes los detalles de tu viaje:</p>
                        
                        <div style='background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 5px solid #006ce4; margin: 20px 0;'>
                            <h3 style='margin-top: 0; color: #003580;'>🎟️ Boleto N° {$ticket['codigo_ticket']}</h3>
                            <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                                <tr>
                                    <td style='padding: 5px 0; color: #555;'><strong>Ruta:</strong></td>
                                    <td style='padding: 5px 0; text-align: right;'>{$ticket['origen_boleto']} ➔ {$ticket['destino_boleto']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 5px 0; color: #555;'><strong>Fecha y Hora:</strong></td>
                                    <td style='padding: 5px 0; text-align: right;'>" . date('d/m/Y', strtotime($ticket['fecha_viaje_historico'])) . " a las " . date('H:i', strtotime($ticket['fecha_viaje_historico'])) . " hrs</td>
                                </tr>
                                <tr>
                                    <td style='padding: 5px 0; color: #555;'><strong>Asiento:</strong></td>
                                    <td style='padding: 5px 0; text-align: right; font-size: 16px; font-weight: bold; color: #006ce4;'>N° {$ticket['nro_asiento']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 5px 0; color: #555;'><strong>Total Pagado:</strong></td>
                                    <td style='padding: 5px 0; text-align: right;'>$" . number_format($ticket['total_pagado'], 0, '', '.') . "</td>
                                </tr>
                            </table>
                            {$notasHtml}
                        </div>
                        
                        <p style='color: #666; font-size: 0.9em; text-align: center;'>Por favor, presenta este correo desde tu celular al momento de subir al bus.</p>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='color: #999; font-size: 0.8em; text-align: center;'>Este es un mensaje automático, por favor no respondas a este correo.</p>
                    </div>";

                    $mail->Body = $html;
                    $mail->send();
                } catch (Exception $e) {
                    // Si falla el correo, lo registramos pero no le decimos a Flow que falló, porque el pasaje sí se vendió.
                    error_log("Error de correo: {$mail->ErrorInfo}");
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("Error en Webhook de Flow: " . $e->getMessage());
}