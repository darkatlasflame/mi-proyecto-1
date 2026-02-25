<?php
// public/api/iniciar_pago.php
require_once '../../config/db.php';
session_start();

// 1. BLINDAJE: Si alguien entra a esta página sin enviar el formulario, lo devolvemos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_viaje'])) {
    die("<div style='text-align:center; padding-top:50px; font-family:sans-serif;'><h3>Error: No se recibieron los datos del viaje.</h3><a href='../index.php' style='padding:10px 20px; background:#003580; color:white; text-decoration:none; border-radius:5px;'>Volver al inicio</a></div>");
}

$session_id = session_id() ?: 'SES-' . time();

// 2. RECEPCIÓN SEGURA: Usamos ?? para evitar Warnings si falta algún dato
$id_viaje = (int)($_POST['id_viaje'] ?? 0);
$asiento  = (int)($_POST['asiento'] ?? 0);
$precio   = (int)($_POST['precio'] ?? 0);
$tipo_pasajero = $_POST['tipo_pasajero'] ?? 'ADULTO';
$origen   = $_POST['origen'] ?? '';
$destino  = $_POST['destino'] ?? '';
$rut      = $_POST['rut'] ?? '';
$nombre   = $_POST['nombre'] ?? 'Pasajero Web';
$telefono = $_POST['telefono'] ?? '';

try {
    // 3. BLOQUEAR EL ASIENTO
    try {
        $stmt = $pdo->prepare("INSERT INTO reservas_temporales (token_sesion, id_viaje, nro_asiento, fecha_expiracion) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $stmt->execute([$session_id, $id_viaje, $asiento]);
    } catch (PDOException $e) {
        die("<div style='text-align:center; padding-top:50px; font-family:sans-serif;'><h3 style='color:red;'>Error: El asiento $asiento acaba de ser ocupado.</h3><a href='../index.php'>Volver a buscar</a></div>");
    }

    // 4. CREAR UN TOKEN FALSO Y GUARDAR DATOS
    $token_simulado = 'SIMULADO-' . time() . '-' . rand(1000, 9999);

    $datos_cliente = json_encode([
        'rut' => $rut, 'nombre' => $nombre, 'telefono' => $telefono,
        'id_viaje' => $id_viaje, 'asiento' => $asiento, 
        'tipo_pasajero' => $tipo_pasajero, 'origen' => $origen, 'destino' => $destino
    ]);

    $sql = "INSERT INTO pagos_webpay (token_ws, monto, estado, datos_cliente) VALUES (?, ?, 'PENDIENTE', ?)";
    $pdo->prepare($sql)->execute([$token_simulado, $precio, $datos_cliente]);

    // 5. MOSTRAR PANTALLA DE SIMULACIÓN
    echo "<!DOCTYPE html><html lang='es'><head><title>Simulador de Pago</title></head>";
    echo "<body style='background:#f4f7f6; font-family:sans-serif; text-align:center; padding-top:100px;'>";
    echo "<div style='background:white; max-width:400px; margin:auto; padding:30px; border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,0.1);'>";
    echo "<h2>💳 Pasarela de Prueba</h2>";
    echo "<p>Pasajero: <b>$nombre</b><br>Monto a cobrar: <b style='color:#003580; font-size:1.2rem;'>$" . number_format($precio, 0, ',', '.') . "</b></p>";
    
    // Botón APROBAR
    echo "<form action='retorno_transbank.php' method='POST' style='margin-bottom:15px;'>";
    echo "<input type='hidden' name='token_ws' value='$token_simulado'>";
    echo "<button type='submit' style='background:#28a745; color:white; padding:15px; border:none; border-radius:8px; width:100%; font-size:1.1rem; font-weight:bold; cursor:pointer;'>✅ Aprobar Pago Exitoso</button>";
    echo "</form>";

    // Botón RECHAZAR
    echo "<form action='retorno_transbank.php' method='POST'>";
    echo "<input type='hidden' name='token_ws' value='$token_simulado'>";
    echo "<input type='hidden' name='rechazar' value='1'>";
    echo "<button type='submit' style='background:#dc3545; color:white; padding:10px; border:none; border-radius:8px; width:100%; font-weight:bold; cursor:pointer;'>❌ Simular Rechazo de Tarjeta</button>";
    echo "</form>";

    echo "</div></body></html>";

} catch (Exception $e) {
    echo "Error en BD: " . $e->getMessage();
}
?>