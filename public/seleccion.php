<?php
$id_viaje = $_GET['id_viaje'] ?? '';
$origen = $_GET['origen'] ?? '';
$destino = $_GET['destino'] ?? '';
$fecha = $_GET['fecha'] ?? '';
$hora = $_GET['hora'] ?? '';

// Precios recibidos por URL
$precios = [
    'ADULTO' => $_GET['precio_adulto'] ?? 0,
    'MAYOR' => $_GET['precio_mayor'] ?? 0,
    'ESTUDIANTE' => $_GET['precio_estudiante'] ?? 0
];

if(!$id_viaje) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar Pasaje | JetBus</title>
    <style>
        :root { --primary: #003580; --accent: #006ce4; --success: #28a745; --bg: #f5f7fa; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg); }
        header { background: var(--primary); padding: 15px 20px; color: white; display:flex; justify-content:space-between; align-items:center;}
        .container { max-width: 1000px; margin: 20px auto; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 0 15px; }
        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
        
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }
        h3 { color: var(--primary); margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        /* Mapa del Bus */
        .bus-layout { background: white; padding: 2rem; border: 2px solid #e5e7eb; border-radius: 40px 40px 10px 10px; max-width: 250px; margin: 0 auto; display: grid; grid-template-columns: repeat(2, 1fr) 25px repeat(2, 1fr); gap: 10px; }
        .seat { aspect-ratio: 1; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; cursor: pointer; border: 1px solid rgba(0,0,0,0.05); transition: 0.2s; }
        .seat.libre { background: #e8f0fe; color: var(--primary); }
        .seat.seleccionado { background: var(--accent); color: white; transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,108,228,0.3); }
        .seat.ocupado { background: #dc3545; color: white; cursor: not-allowed; border: none; }
        .aisle { grid-column: 3; }
        .bus-nose { grid-column: 1/-1; background: #333; color: white; text-align: center; padding: 5px; border-radius: 4px; font-size: 0.7rem; margin-bottom: 10px; }

        /* Formulario */
        label { display: block; font-size: 0.85rem; font-weight: bold; color: gray; margin-top: 15px; margin-bottom: 5px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; outline: none; box-sizing: border-box;}
        .btn-pagar { background: var(--success); color: white; border: none; padding: 15px; width: 100%; border-radius: 6px; font-weight: bold; font-size: 1.1rem; margin-top: 20px; cursor: pointer; transition: 0.2s; }
        .btn-pagar:hover { background: #218838; }
        .btn-pagar:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>

    <header>
        <a href="javascript:history.back()" style="color:white; text-decoration:none; font-weight:bold;">⬅ Atrás</a>
        <div style="font-weight: bold;">Completar Compra</div>
    </header>

    <div class="container">
        <div class="card">
            <h3 style="text-align:center;">1. Elige tu Asiento</h3>
            <div id="bus-layout-grid" class="bus-layout">
                <div class="bus-nose">FRENTE DEL BUS</div>
                </div>
            <div style="text-align:center; margin-top:15px; font-size:0.9rem;">
                <span style="display:inline-block; width:15px; height:15px; background:#e8f0fe; border-radius:3px; vertical-align:middle;"></span> Libre 
                <span style="display:inline-block; width:15px; height:15px; background:#dc3545; border-radius:3px; vertical-align:middle; margin-left:10px;"></span> Ocupado
            </div>
        </div>

        <div class="card">
            <h3>2. Datos del Pasajero</h3>
            <div style="background:#f8fafc; padding:15px; border-radius:6px; margin-bottom:15px; border:1px solid #e2e8f0;">
                <strong>Resumen:</strong> <?= htmlspecialchars($origen) ?> a <?= htmlspecialchars($destino) ?><br>
                <small><?= date('d/m/Y', strtotime($fecha)) ?> a las <?= htmlspecialchars($hora) ?> hrs</small><br>
                <strong style="color:var(--accent)">Asiento seleccionado: <span id="lbl-asiento">-</span></strong>
            </div>

            <label>TIPO DE PASAJE</label>
            <select id="tipo-tarifa" onchange="actualizarPrecio()">
                <option value="ADULTO" data-price="<?= $precios['ADULTO'] ?>">Adulto - $<?= number_format($precios['ADULTO'], 0, '', '.') ?></option>
                <option value="MAYOR" data-price="<?= $precios['MAYOR'] ?>">Tercera Edad - $<?= number_format($precios['MAYOR'], 0, '', '.') ?></option>
                <option value="ESTUDIANTE" data-price="<?= $precios['ESTUDIANTE'] ?>">Estudiante - $<?= number_format($precios['ESTUDIANTE'], 0, '', '.') ?></option>
            </select>

            <label>RUT (Sin puntos ni guion)</label>
            <input type="text" id="p-rut" placeholder="Ej: 123456789">

            <label>NOMBRE COMPLETO</label>
            <input type="text" id="p-nombre" placeholder="Nombre de quien viaja">

            <div style="display:flex; gap:10px;">
                <div style="flex:1">
                    <label>TELÉFONO</label>
                    <input type="text" id="p-telefono" placeholder="+569...">
                </div>
                <div style="flex:1">
                    <label>CORREO ELECTRÓNICO</label>
                    <input type="email" id="p-email" placeholder="Para enviar el boleto" required>
                </div>
            </div>

            <div style="text-align:right; margin-top:20px; font-size:1.5rem; font-weight:bold; color:var(--success);">
                Total: <span id="lbl-total">$0</span>
            </div>

            <button id="btn-submit" class="btn-pagar" onclick="iniciarPago()" disabled>PAGAR CON WEBPAY / FLOW</button>
        </div>
    </div>

    <script>
        let asientoSeleccionado = null;
        let precioFinal = <?= $precios['ADULTO'] ?>;

        // Cargar mapa del bus usando la API del Admin
        async function cargarMapa() {
            const res = await fetch(`../admin/api/get_mapa.php?id=<?= $id_viaje ?>`);
            const asientos = await res.json();
            
            let html = '<div class="bus-nose">FRENTE DEL BUS</div>';
            asientos.forEach(a => {
                if(a.nro % 4 === 3) html += '<div class="aisle"></div>';
                let cls = a.estado; 
                html += `<div class="seat ${cls}" onclick="clickAsiento(this, ${a.nro}, '${cls}')">${a.nro}</div>`;
            });
            document.getElementById('bus-layout-grid').innerHTML = html;
            actualizarPrecio();
        }

        function clickAsiento(elemento, nro, estado) {
            if(estado !== 'libre') return;
            document.querySelectorAll('.seat.seleccionado').forEach(s => s.classList.replace('seleccionado', 'libre'));
            elemento.classList.replace('libre', 'seleccionado');
            
            asientoSeleccionado = nro;
            document.getElementById('lbl-asiento').innerText = nro;
            document.getElementById('btn-submit').disabled = false;
        }

        function actualizarPrecio() {
            const sel = document.getElementById('tipo-tarifa');
            precioFinal = sel.options[sel.selectedIndex].getAttribute('data-price');
            document.getElementById('lbl-total').innerText = '$' + new Intl.NumberFormat('es-CL').format(precioFinal);
        }

        // --- CONEXIÓN CON FLOW ---
        async function iniciarPago() {
            const rut = document.getElementById('p-rut').value;
            const nombre = document.getElementById('p-nombre').value;
            const telefono = document.getElementById('p-telefono').value;
            const email = document.getElementById('p-email').value;
            const tipoPax = document.getElementById('tipo-tarifa').value;

            if(!asientoSeleccionado) return alert("Por favor, selecciona un asiento en el mapa.");
            if(!rut || !nombre || !email) return alert("RUT, Nombre y Correo son obligatorios.");

            const btn = document.getElementById('btn-submit');
            btn.innerText = "Conectando con el banco... ⏳";
            btn.disabled = true;

            const fd = new FormData();
            fd.append('id_viaje', '<?= $id_viaje ?>');
            fd.append('asiento', asientoSeleccionado);
            fd.append('rut', rut);
            fd.append('nombre', nombre);
            fd.append('telefono', telefono);
            fd.append('email', email);
            fd.append('origen', '<?= $origen ?>');
            fd.append('destino', '<?= $destino ?>');
            fd.append('tipo_pasajero', tipoPax);
            fd.append('total_pagado', precioFinal);

            try {
                // Llama al archivo iniciar_pago.php que creamos antes
                const res = await fetch('api/iniciar_pago.php', { method: 'POST', body: fd });
                const json = await res.json();

                if(json.status === 'success') {
                    // ¡Todo salió bien! Redirigimos al cliente a la página segura de Flow
                    window.location.href = json.url_pago;
                } else {
                    alert("Error: " + json.msg);
                    btn.innerText = "PAGAR CON WEBPAY / FLOW";
                    btn.disabled = false;
                }
            } catch (error) {
                alert("Error de conexión. Intente nuevamente.");
                btn.innerText = "PAGAR CON WEBPAY / FLOW";
                btn.disabled = false;
            }
        }

        // Arrancar
        cargarMapa();
    </script>
</body>
</html>