<?php 
// public/seleccion.php
require_once '../config/db.php'; 

// 1. Recibimos EXACTAMENTE el viaje que el cliente clickeó en resultados.php
$id_viaje = $_GET['id_viaje'] ?? 0;
$origen = $_GET['origen'] ?? '';
$destino = $_GET['destino'] ?? '';

if (!$id_viaje || !$origen || !$destino) {
    die("<div class='container text-center mt-5'><h3 class='text-danger'>Error: Faltan datos del viaje.</h3><a href='index.php' class='btn btn-primary mt-3'>Volver al inicio</a></div>");
}

// 2. Buscamos ESE viaje específico por su ID y cruzamos sus precios
$sql = "SELECT v.id, v.fecha_hora, b.numero_maquina, 
               m.precio_adulto, m.precio_mayor, m.precio_estudiante
        FROM viajes v
        JOIN buses b ON v.id_bus = b.id
        JOIN matriz_precios m ON m.id_viaje = v.id
        WHERE v.id = ? AND m.origen_tramo = ? AND m.destino_tramo = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_viaje, $origen, $destino]);
$viaje = $stmt->fetch();

if(!$viaje) {
    die("<div class='container text-center mt-5'><h3 class='text-danger'>Error: No se encontró la tarifa para este tramo en este bus.</h3><p>Asegúrate de haberle puesto precios a este viaje en el panel de administración.</p><a href='index.php' class='btn btn-warning mt-3'>Volver al inicio</a></div>");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JetBus | Selecciona tu Asiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        
        .bus-container {
            background: white; border-radius: 20px; padding: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            max-width: 350px; margin: 0 auto;
            border: 2px solid #e9ecef;
        }
        
        .seat-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 12px; 
            margin-top: 20px; 
        }
        
        .seat {
            width: 50px; height: 50px; background: #e2e8f0; border-radius: 8px 8px 15px 15px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-weight: bold; color: #64748b; border: 1px solid #cbd5e1;
            position: relative; transition: 0.2s;
        }
        
        .seat::before { 
            content: ''; position: absolute; top: -6px; width: 36px; height: 6px; 
            background: inherit; border-radius: 5px 5px 0 0; border: 1px solid #cbd5e1; border-bottom: none;
        }
        
        .seat:hover:not(.occupied):not(.bloqueado) { background: #cbd5e1; }
        .seat.selected { background: #003580; color: white; border-color: #002255; }
        .seat.occupied { background: #dc3545; color: white; cursor: not-allowed; opacity: 0.7; }
        .seat.bloqueado { background: #ffc107; color: black; cursor: not-allowed; }
        
        .summary-card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08); position: sticky; top: 20px;
        }
        
        .price-tag { font-size: 2.2rem; font-weight: 900; color: #003580; }
        
        .btn-next {
            background: #ffc107; color: #003580; font-weight: bold;
            padding: 15px; border-radius: 8px; border: none; width: 100%;
            font-size: 1.1rem; transition: 0.3s;
        }
        .btn-next:hover:not(:disabled) { background: #e0a800; transform: translateY(-2px); }
        .btn-next:disabled { background: #e9ecef; color: #adb5bd; cursor: not-allowed; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark" style="background-color: #003580;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Volver a horarios</a>
        <span class="navbar-text text-light"><i class="fas fa-lock"></i> Compra Segura</span>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <div class="col-md-7 mb-4">
            <h3 class="fw-bold mb-4" style="color: #003580;">1. Elige tu asiento</h3>
            <div class="bus-container">
                <div class="text-center mb-3 text-muted fw-bold p-2" style="background: #f8f9fa; border-radius: 8px;">
                    <i class="fas fa-steering-wheel"></i> FRENTE DEL BUS
                </div>
                
                <div class="seat-grid" id="bus-map">
                    <div class="text-center w-100 py-5">Cargando asientos...</div>
                </div>
                
                <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="font-size: 0.85rem;">
                    <span><div style="display:inline-block; width:15px; height:15px; background:#e2e8f0; border-radius:3px;"></div> Libre</span>
                    <span><div style="display:inline-block; width:15px; height:15px; background:#003580; border-radius:3px;"></div> Tu Asiento</span>
                    <span><div style="display:inline-block; width:15px; height:15px; background:#dc3545; border-radius:3px;"></div> Ocupado</span>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="summary-card">
                <h4 class="mb-3 fw-bold border-bottom pb-2" style="color: #003580;">2. Resumen de Viaje</h4>
                
                <div class="mb-3">
                    <p class="mb-0 text-muted small">Ruta:</p>
                    <h5 class="fw-bold"><?php echo htmlspecialchars($origen); ?> <i class="fas fa-arrow-right text-warning"></i> <?php echo htmlspecialchars($destino); ?></h5>
                    <p class="text-muted small mb-0">
                        <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($viaje['fecha_hora'])); ?> | 
                        <i class="far fa-clock"></i> <?php echo date('H:i', strtotime($viaje['fecha_hora'])); ?> hrs
                    </p>
                    <p class="text-muted small">Bus Nº <?php echo $viaje['numero_maquina']; ?></p>
                </div>

                <div class="mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                    <span class="text-muted">Asiento seleccionado:</span>
                    <h2 class="mb-0" id="lbl-asiento-elegido" style="color: #003580;">-</h2>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-muted">Tipo de Pasajero</label>
                    <select class="form-select border-primary" id="tipo_pasajero" onchange="calcularTotal()">
                        <option value="ADULTO" data-price="<?php echo $viaje['precio_adulto']; ?>">Adulto - $<?php echo number_format($viaje['precio_adulto'],0,',','.'); ?></option>
                        <option value="ESTUDIANTE" data-price="<?php echo $viaje['precio_estudiante']; ?>">Estudiante - $<?php echo number_format($viaje['precio_estudiante'],0,',','.'); ?></option>
                        <option value="MAYOR" data-price="<?php echo $viaje['precio_mayor']; ?>">Tercera Edad - $<?php echo number_format($viaje['precio_mayor'],0,',','.'); ?></option>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted fw-bold">Total a Pagar:</span>
                    <span class="price-tag" id="total-display">$0</span>
                </div>

                <form action="checkout.php" method="POST">
                    <input type="hidden" name="id_viaje" value="<?php echo $viaje['id']; ?>">
                    <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen); ?>">
                    <input type="hidden" name="destino" value="<?php echo htmlspecialchars($destino); ?>">
                    <input type="hidden" name="asiento" id="input_asiento" required>
                    <input type="hidden" name="precio" id="input_precio"> <input type="hidden" name="tipo_pasajero" id="input_tipo">
                    
                    <button type="submit" class="btn-next shadow" id="btn-pagar" disabled>
                        CONTINUAR AL PAGO <i class="fas fa-chevron-right ms-2"></i>
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    const ID_VIAJE = <?php echo $viaje['id']; ?>;
    let asientoSeleccionado = null;

    async function cargarBus() {
        try {
            const res = await fetch(`../admin/api/get_mapa.php?id=${ID_VIAJE}`);
            const asientos = await res.json();
            
            const grid = document.getElementById('bus-map');
            grid.innerHTML = ''; 
            
            asientos.forEach(a => {
                let div = document.createElement('div');
                div.className = `seat ${a.estado}`;
                div.innerText = a.nro;
                
                if (a.nro % 4 === 3) div.style.gridColumn = "3"; 

                if(a.estado === 'libre') {
                    div.onclick = () => seleccionarAsiento(div, a.nro);
                } else {
                    div.classList.add('occupied');
                }
                
                grid.appendChild(div);
            });
            
            calcularTotal();
            
        } catch (error) {
            document.getElementById('bus-map').innerHTML = "<p class='text-danger'>Error al cargar asientos.</p>";
        }
    }

    function seleccionarAsiento(elemento, nro) {
        document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
        elemento.classList.add('selected');
        asientoSeleccionado = nro;
        
        document.getElementById('lbl-asiento-elegido').innerText = nro;
        document.getElementById('input_asiento').value = nro;
        document.getElementById('btn-pagar').disabled = false;
        
        calcularTotal();
    }

    function calcularTotal() {
        const select = document.getElementById('tipo_pasajero');
        const precio = select.options[select.selectedIndex].getAttribute('data-price');
        const tipo = select.value;

        document.getElementById('total-display').innerText = '$' + new Intl.NumberFormat('es-CL').format(precio);
        
        document.getElementById('input_precio').value = precio;
        document.getElementById('input_tipo').value = tipo;
    }

    cargarBus();
</script>

</body>
</html>