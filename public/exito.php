<?php
// public/exito.php
require_once '../config/db.php';

$codigo_ticket = $_GET['ticket'] ?? '';

if (!$codigo_ticket) {
    die("<div style='text-align:center; padding-top:50px; font-family:sans-serif;'><h2>Error: No se proporcionó un código de ticket.</h2><a href='index.php'>Volver al inicio</a></div>");
}

try {
    // Buscar todos los datos de la venta, el viaje y el bus
    $sql = "SELECT v.*, vj.fecha_hora, b.numero_maquina, b.patente 
            FROM ventas v
            JOIN viajes vj ON v.id_viaje = vj.id
            JOIN buses b ON vj.id_bus = b.id
            WHERE v.codigo_ticket = ? AND v.estado = 'CONFIRMADO'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$codigo_ticket]);
    $boleto = $stmt->fetch();

    if (!$boleto) {
        die("<div style='text-align:center; padding-top:50px; font-family:sans-serif;'><h2>El ticket no existe o fue anulado.</h2><a href='index.php'>Volver al inicio</a></div>");
    }

} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JetBus | Compra Exitosa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        
        /* Diseño del Boleto Digital */
        .ticket-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 40px auto;
            overflow: hidden;
            position: relative;
        }

        .ticket-header {
            background: #28a745;
            color: white;
            text-align: center;
            padding: 30px 20px 20px;
        }

        .ticket-header i { font-size: 3rem; margin-bottom: 10px; }
        
        .ticket-body { padding: 30px; }
        
        .ticket-row { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
        .ticket-row:last-child { border-bottom: none; }
        
        .label { font-size: 0.85rem; color: #6c757d; font-weight: bold; text-transform: uppercase; }
        .value { font-size: 1.1rem; color: #343a40; font-weight: bold; text-align: right; }
        
        .seat-badge {
            background: #003580; color: white;
            padding: 5px 15px; border-radius: 8px; font-size: 1.5rem;
        }

        .barcode {
            text-align: center; padding: 20px; background: #f8f9fa;
            border-top: 2px dashed #ccc; font-family: 'Courier New', Courier, monospace;
        }

        /* Ocultar botones e interfaz extra al momento de imprimir o generar el PDF */
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .ticket-container { box-shadow: none; border: 2px solid #000; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container pb-5">
    
    <div class="ticket-container">
        <div class="ticket-header">
            <i class="fas fa-check-circle"></i>
            <h2 class="fw-bold mb-0">¡Pago Exitoso!</h2>
            <p class="mb-0 mt-2 opacity-75">Tu pasaje está confirmado y listo para viajar.</p>
        </div>

        <div class="ticket-body">
            
            <div class="ticket-row align-items-center">
                <div class="label">Asiento</div>
                <div class="value seat-badge"><?php echo $boleto['nro_asiento']; ?></div>
            </div>

            <div class="ticket-row">
                <div class="label">Ruta</div>
                <div class="value text-primary">
                    <?php echo htmlspecialchars($boleto['origen_boleto']); ?> <i class="fas fa-arrow-right mx-1"></i> <?php echo htmlspecialchars($boleto['destino_boleto']); ?>
                </div>
            </div>

            <div class="ticket-row">
                <div class="label">Salida</div>
                <div class="value text-end">
                    <?php echo date('d/m/Y', strtotime($boleto['fecha_hora'])); ?><br>
                    <span class="text-danger fs-5"><?php echo date('H:i', strtotime($boleto['fecha_hora'])); ?> hrs</span>
                </div>
            </div>

            <div class="ticket-row">
                <div class="label">Pasajero</div>
                <div class="value text-end">
                    <?php echo htmlspecialchars($boleto['nombre_pasajero']); ?><br>
                    <small class="text-muted">RUT: <?php echo htmlspecialchars($boleto['rut_pasajero']); ?></small>
                </div>
            </div>

            <div class="ticket-row">
                <div class="label">Tarifa / Total</div>
                <div class="value">
                    <?php echo $boleto['tipo_pasajero']; ?> - $<?php echo number_format($boleto['total_pagado'], 0, ',', '.'); ?>
                </div>
            </div>

            <div class="ticket-row">
                <div class="label">Bus</div>
                <div class="value">Máquina <?php echo $boleto['numero_maquina']; ?></div>
            </div>

        </div>

        <div class="barcode">
            <i class="fas fa-barcode" style="font-size: 3rem; color: #333; letter-spacing: 5px;"></i><br>
            <strong>FOLIO: <?php echo $boleto['codigo_ticket']; ?></strong>
            <p class="text-muted small mt-2 mb-0">Presenta este boleto desde tu celular al subir al bus.</p>
        </div>
    </div>

    <div class="text-center no-print mt-4">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-bold shadow-sm me-2">
            <i class="fas fa-print"></i> GUARDAR PDF / IMPRIMIR
        </button>
        <a href="index.php" class="btn btn-outline-secondary px-4 py-2 fw-bold shadow-sm">
            Volver al Inicio
        </a>
    </div>

</div>

</body>
</html>