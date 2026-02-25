<?php 
// public/resultados.php
require_once '../config/db.php'; 

$origen = $_GET['origen'] ?? '';
$destino = $_GET['destino'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

$sql = "SELECT v.id, v.fecha_hora, b.numero_maquina, b.marca,
               m.precio_adulto, m.precio_mayor, m.precio_estudiante
        FROM viajes v
        JOIN buses b ON v.id_bus = b.id
        JOIN matriz_precios m ON m.id_viaje = v.id
        WHERE m.origen_tramo = ? AND m.destino_tramo = ? AND DATE(v.fecha_hora) = ?
        ORDER BY v.fecha_hora ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$origen, $destino, $fecha]);
$viajes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JetBus | Horarios Disponibles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: #f4f7f6;">

<nav class="navbar navbar-dark" style="background-color: #003580;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-arrow-left"></i> Volver a buscar</a>
    </div>
</nav>

<div class="container py-5">
    <h2 class="fw-bold mb-4" style="color: #003580;">Salidas: <?php echo $origen; ?> <i class="fas fa-arrow-right text-warning"></i> <?php echo $destino; ?></h2>
    <p class="text-muted"><i class="far fa-calendar-alt"></i> Fecha: <?php echo date('d/m/Y', strtotime($fecha)); ?></p>

    <?php if(count($viajes) === 0): ?>
        <div class="alert alert-warning text-center p-4 shadow-sm">
            <h4><i class="fas fa-exclamation-triangle"></i> No hay salidas programadas para este día.</h4>
            <a href="index.php" class="btn btn-primary mt-3">Probar otra fecha</a>
        </div>
    <?php else: ?>
        
        <div class="row">
            <?php foreach($viajes as $v): ?>
            <div class="col-md-8 mx-auto mb-3">
                <div class="card shadow-sm border-0" style="border-left: 5px solid #003580;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="fw-bold text-primary mb-0"><?php echo date('H:i', strtotime($v['fecha_hora'])); ?> hrs</h3>
                            <small class="text-muted">Bus <?php echo $v['marca']; ?> (Nº <?php echo $v['numero_maquina']; ?>)</small>
                        </div>
                        <div class="text-end">
                            <span class="d-block text-muted small">Desde</span>
                            <h4 class="fw-bold text-success mb-2">$<?php echo number_format($v['precio_estudiante'], 0, ',', '.'); ?></h4>
                            <a href="seleccion.php?id_viaje=<?php echo $v['id']; ?>&origen=<?php echo urlencode($origen); ?>&destino=<?php echo urlencode($destino); ?>" class="btn btn-warning fw-bold px-4">
                                VER ASIENTOS <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

</body>
</html>