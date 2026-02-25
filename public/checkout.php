<?php
// public/checkout.php
session_start();

// Leemos los datos seguros que vienen por POST desde el mapa de asientos
$id_viaje = $_POST['id_viaje'] ?? 0;
$asiento = $_POST['asiento'] ?? 0;
$precio = $_POST['precio'] ?? 0;
$tipo_pasajero = $_POST['tipo_pasajero'] ?? 'ADULTO';
$origen = $_POST['origen'] ?? 'Concepcion';
$destino = $_POST['destino'] ?? 'Cañete';

// Si falta algo vital, detenemos el proceso
if (!$id_viaje || !$asiento || !$precio) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h3>Error: Faltan datos del viaje.</h3><a href='index.php'>Volver a buscar</a></div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JetBus | Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .card-resumen { background: #003580; color: white; border-radius: 15px; padding: 20px; }
        .form-control { border-radius: 8px; padding: 12px; }
        .btn-webpay { background: #28a745; color: white; font-weight: bold; border-radius: 8px; padding: 15px; border: none; width: 100%; transition: 0.3s; }
        .btn-webpay:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(40,167,69,0.3); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4" style="background-color: #003580;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Volver a los asientos</a>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        
        <div class="col-md-4 mb-4">
            <div class="card-resumen shadow">
                <h4 class="mb-4"><i class="fas fa-ticket-alt"></i> Tu Viaje</h4>
                <p class="mb-1 text-light">Tramo:</p>
                <h5><?php echo htmlspecialchars($origen); ?> <i class="fas fa-arrow-right text-warning"></i> <?php echo htmlspecialchars($destino); ?></h5>
                <hr style="border-color: rgba(255,255,255,0.2);">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Asiento:</span>
                    <strong class="fs-2 text-warning"><?php echo $asiento; ?></strong>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span>Pasajero:</span>
                    <span class="fw-bold"><?php echo $tipo_pasajero; ?></span>
                </div>
                <hr style="border-color: rgba(255,255,255,0.2);">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Total a pagar:</span>
                    <strong class="fs-2">$<?php echo number_format($precio, 0, ',', '.'); ?></strong>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow border-0" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4" style="color:#003580; font-weight:bold;">Datos del Pasajero</h4>
                    
                    <form action="api/iniciar_pago.php" method="POST">
                        <input type="hidden" name="id_viaje" value="<?php echo $id_viaje; ?>">
                        <input type="hidden" name="asiento" value="<?php echo $asiento; ?>">
                        <input type="hidden" name="precio" value="<?php echo $precio; ?>">
                        <input type="hidden" name="tipo_pasajero" value="<?php echo $tipo_pasajero; ?>">
                        <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen); ?>">
                        <input type="hidden" name="destino" value="<?php echo htmlspecialchars($destino); ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">RUT (Sin puntos, con guion)</label>
                            <input type="text" name="rut" class="form-control" required placeholder="12345678-9">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Juan Pérez">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">Teléfono Móvil (Para avisos)</label>
                            <input type="text" name="telefono" class="form-control" required placeholder="+569...">
                        </div>

                        <button type="submit" class="btn-webpay shadow">
                            <i class="fas fa-lock me-2"></i> IR A PAGAR
                        </button>
                        
                    </form>

                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>