<?php
// admin/index.php
require_once '../config/db.php';

// Cargar las paradas únicas desde la matriz de precios para los selectores
$stmt = $pdo->query("SELECT DISTINCT origen_tramo as nombre FROM matriz_precios UNION SELECT DISTINCT destino_tramo FROM matriz_precios");
$paradas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Cargar los viajes programados para hoy o el futuro
$stmt = $pdo->query("
    SELECT v.id, v.fecha_hora, b.numero_maquina, b.patente, b.capacidad
    FROM viajes v 
    JOIN buses b ON v.id_bus = b.id 
    WHERE v.fecha_hora >= CURRENT_DATE 
    ORDER BY v.fecha_hora ASC
");
$viajes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JetBus Pro | Punto de Venta Oficina</title>
    <style>
        :root {
            --primary: #003580; --accent: #006ce4; --success: #28a745;
            --danger: #dc3545; --warning: #ffc107; --bg: #f5f7fa;
            --card: #ffffff; --text: #1a1a1a; --gray: #6b7280;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); padding-bottom: 50px; }

        header { background: var(--primary); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .logo { font-size: 1.5rem; font-weight: 800; letter-spacing: -1px; }
        .logo span { color: #febb02; }
        .office-selector { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
        .office-selector option { color: black; }
        .container { max-width: 1200px; margin: 2rem auto; display: grid; grid-template-columns: 1fr 350px; gap: 2rem; padding: 0 1rem; }
        @media (max-width: 900px) { .container { grid-template-columns: 1fr; } }
        .card { background: var(--card); border-radius: 12px; padding: 1.5rem; border: 1px solid #e5e7eb; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        h2 { font-size: 1rem; color: var(--primary); margin-bottom: 1.2rem; text-transform: uppercase; letter-spacing: 0.5px; border-left: 4px solid var(--accent); padding-left: 10px; }
        .hidden { display: none !important; }
        .btn { padding: 0.6rem 1rem; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; transition: 0.2s; text-align: center; font-size: 0.9rem; }
        .btn-primary { background: var(--accent); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: #333; }
        .btn-dark { background: #343a40; color: white; }
        .search-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 1rem; }
        select, input, textarea { width: 100%; padding: 0.7rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; outline: none; }
        .steps { display: flex; justify-content: space-between; margin-bottom: 1.5rem; background: white; padding: 0.8rem; border-radius: 50px; border: 1px solid #e5e7eb; }
        .step { font-size: 0.8rem; font-weight: 700; color: #9ca3af; padding: 0 1rem; }
        .step.active { color: var(--accent); }
        
        /* Mapa del bus */
        .bus-layout { background: white; padding: 2rem; border: 2px solid #e5e7eb; border-radius: 40px 40px 10px 10px; max-width: 320px; margin: auto; display: grid; grid-template-columns: repeat(2, 1fr) 25px repeat(2, 1fr); gap: 10px; }
        .seat { aspect-ratio: 1; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; cursor: pointer; border: 1px solid rgba(0,0,0,0.05); transition: 0.2s; }
        .seat.libre { background: #e8f0fe; color: var(--primary); }
        .seat.seleccionado { background: var(--accent); color: white; transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,108,228,0.3); }
        .seat.ocupado { background: #dc3545; color: white; cursor: not-allowed; border: none; }
        .seat.bloqueado { background: #ffc107; color: black; cursor: not-allowed; border: none; }
        .aisle { grid-column: 3; }
        .bus-nose { grid-column: 1/-1; background: #333; color: white; text-align: center; padding: 5px; border-radius: 4px; font-size: 0.7rem; margin-bottom: 10px; }
        
        /* Pasajeros */
        .pax-form-block { background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid var(--accent); border: 1px solid #eee; }
        .pax-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 10px; }
        
        /* Modales e Impresión */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-card { background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 95%; max-height: 90vh; overflow-y: auto; }

        @media print {
            @page { margin: 0; size: auto; } 
            body { margin: 0; padding: 0; background: white; }
            body * { visibility: hidden; height: 0; overflow: hidden; } 
            #printable-area, #printable-area * { visibility: visible; height: auto; overflow: visible; }
            #printable-area { position: absolute; left: 0; top: 0; width: 74mm; padding: 2mm; font-family: 'Courier New', monospace; font-size: 12px; color: black; }
            .modal-card { box-shadow: none; padding: 0; width: auto; max-width: none; position: static; }
            .ticket { border: none; border-bottom: 2px dashed black; margin-bottom: 15px; padding-bottom: 15px; page-break-inside: avoid; }
            .ticket h3 { font-size: 16px; margin: 5px 0; text-align: center; }
            .ticket p { margin: 3px 0; font-size: 12px; }
            .ticket hr { border-top: 1px dashed black; margin: 5px 0; }
            .ticket .total { font-size: 16px; font-weight: bold; text-align: right; margin-top: 5px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">JetBus<span>Pro</span></div>
        <div style="display:flex; align-items:center; gap:10px">
            <label for="current-office" style="font-size:0.9rem">Oficina:</label>
            <select id="current-office" class="office-selector">
                <option value="Oficina Concepcion">📍 Concepción</option>
                <option value="Oficina Laraquete">📍 Laraquete</option>
                <option value="Oficina Curanilahue">📍 Curanilahue</option>
                <option value="Oficina Cerro Alto">📍 Cerro Alto</option>
                <option value="Oficina Canete">📍 Cañete</option>
            </select>
        </div>
    </header>

    <div class="container">
        <main>
            <div class="steps">
                <div class="step active" id="st-1">1. Viaje</div>
                <div class="step" id="st-2">2. Asiento</div>
                <div class="step" id="st-3">3. Pasajero</div>
                <div class="step" id="st-4">4. Emisión</div>
            </div>

            <section class="card">
                <h2>1. Búsqueda de Viaje</h2>
                <div class="search-grid">
                    <div>
                        <label style="font-size:0.8rem; font-weight:bold; color:gray">ORIGEN</label>
                        <select id="origen">
                            <option value="">Seleccione...</option>
                            <?php foreach($paradas as $p): ?>
                                <option value="<?= $p ?>"><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:bold; color:gray">DESTINO</label>
                        <select id="destino">
                            <option value="">Seleccione...</option>
                            <?php foreach($paradas as $p): ?>
                                <option value="<?= $p ?>"><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:0.8rem; font-weight:bold; color:gray">FECHA</label>
                        <input type="date" id="fecha" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <button id="btn-buscar" class="btn btn-primary" style="width:100%" onclick="buscarViajes()">BUSCAR DISPONIBILIDAD</button>
            </section>

            <section id="results-area" class="card hidden">
                <h2>2. Horarios Disponibles</h2>
                <div id="schedule-list"></div>
            </section>

            <section id="seat-area" class="card hidden">
                <h2>3. Mapa de Asientos <span id="seat-badge" style="background:var(--accent); color:white; padding:2px 8px; border-radius:10px; font-size:0.8rem">-</span></h2>
                <div class="bus-layout">
                    <div class="bus-nose">CABINA DEL CONDUCTOR</div>
                    <div id="bus-layout-grid" style="display:contents"></div>
                </div>
            </section>

            <section id="confirm-area" class="card hidden">
                <h2>4. Datos del Pasajero</h2>
                <div id="passenger-forms">
                    <div class="pax-form-block">
                        <h4 id="lbl-asiento-elegido">Asiento: -</h4>
                        <div style="margin: 10px 0;">
                            <label style="font-size:0.8rem; font-weight:bold;">TIPO TARIFA</label>
                            <select id="tipo-tarifa" onchange="actualizarPrecio()"></select>
                        </div>
                        <div class="pax-grid">
                            <input type="text" id="p-rut" placeholder="RUT (Sin puntos)">
                            <input type="text" id="p-nombre" placeholder="Nombre Completo">
                        </div>
                        <input type="text" id="p-telefono" placeholder="Teléfono" style="margin-top:10px;">
                    </div>
                </div>
                <div style="margin-top:20px; border-top:1px solid #eee; padding-top:15px">
                    <h3 style="text-align:right; color:var(--success); margin-bottom:15px;">TOTAL: <span id="lbl-total">$0</span></h3>
                    <button id="btn-finalizar" class="btn btn-success" style="width:100%" onclick="emitirBoleto()">CONFIRMAR Y EMITIR BOLETO</button>
                </div>
            </section>
        </main>

        <aside>
            <div class="card" style="background: #334155; color:white; text-align:center;">
                <a href="panel.php" style="color:white; text-decoration:none; font-weight:bold;">⚙️ IR AL PANEL DE ADMINISTRACIÓN</a>
            </div>
            
            <div class="card">
                <h3>💰 Cierre de Caja</h3>
                <p style="font-size:0.8rem; margin-bottom:5px; color:#666">Reporte de ventas de la oficina actual.</p>
                <input type="date" id="cierre-fecha" value="<?= date('Y-m-d') ?>" style="margin-bottom:5px">
                <button class="btn btn-dark" style="width:100%" onclick="cerrarCaja()">GENERAR CIERRE</button>
            </div>
            
            <div class="card">
                <h3>🖨️ Reimpresión / 🚫 Anulación</h3>
                <input type="text" id="gestion-ticket" placeholder="Código (Ej: TKT-1234) o RUT" style="margin-bottom:5px">
                <div style="display:flex; gap:5px;">
                    <button class="btn btn-warning" style="flex:1" onclick="buscarParaImprimir()">BUSCAR</button>
                    <button class="btn btn-danger" style="flex:1" onclick="anularBoleto()">ANULAR</button>
                </div>
                <div id="resultado-gestion" style="margin-top:10px; font-size:0.85rem;"></div>
            </div>
        </aside>
    </div>

    <div id="print-overlay" class="modal-overlay hidden">
        <div class="modal-card">
            <div id="printable-area"></div>
            <div style="display:flex; gap:10px; margin-top:20px; justify-content: flex-end; padding: 20px;">
                <button onclick="window.print()" class="btn btn-primary">IMPRIMIR</button>
                <button onclick="document.getElementById('print-overlay').classList.add('hidden')" class="btn btn-danger">CERRAR</button>
            </div>
        </div>
    </div>

    <script>
        let datosViaje = { id: null, origen: null, destino: null, asiento: null, precioFinal: 0 };
        let preciosTramo = {};

        // 1. BUSCAR VIAJES DISPONIBLES
        async function buscarViajes() {
            const o = document.getElementById('origen').value;
            const d = document.getElementById('destino').value;
            const f = document.getElementById('fecha').value;

            if(!o || !d || !f) return alert("Complete Origen, Destino y Fecha");

            // Llamamos a la API para ver si hay viajes y obtener los precios de este tramo
            const fd = new FormData(); fd.append('origen', o); fd.append('destino', d); fd.append('fecha', f);
            const res = await fetch('api/buscar_viajes.php', { method: 'POST', body: fd });
            const json = await res.json();

            document.getElementById('results-area').classList.remove('hidden');
            
            if(!json.success || json.viajes.length === 0) {
                document.getElementById('schedule-list').innerHTML = "<p style='color:red;'>No hay salidas programadas para este tramo.</p>";
                return;
            }

            let html = '';
            json.viajes.forEach(v => {
                html += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; border:1px solid #ddd; border-radius:8px; margin-bottom:10px; background:#f8fafc;">
                    <div>
                        <strong style="font-size:1.1rem; color:var(--primary)">${v.hora} hrs</strong><br>
                        <small>Bus ${v.numero_maquina} (${v.patente})</small>
                    </div>
                    <div style="text-align:right;">
                        <small>Desde $${v.precio_estudiante}</small><br>
                        <button class="btn btn-primary btn-sm" onclick='seleccionarViaje(${JSON.stringify(v)})'>SELECCIONAR</button>
                    </div>
                </div>`;
            });
            document.getElementById('schedule-list').innerHTML = html;
            document.getElementById('st-2').classList.add('active');
        }

        // 2. SELECCIONAR VIAJE Y MOSTRAR MAPA
        async function seleccionarViaje(viaje) {
            datosViaje.id = viaje.id;
            datosViaje.origen = document.getElementById('origen').value;
            datosViaje.destino = document.getElementById('destino').value;
            datosViaje.asiento = null;

            // Guardar precios del tramo
            preciosTramo = {
                'ADULTO': viaje.precio_adulto,
                'MAYOR': viaje.precio_mayor,
                'ESTUDIANTE': viaje.precio_estudiante
            };

            // Llenar selector de tarifas
            document.getElementById('tipo-tarifa').innerHTML = `
                <option value="ADULTO" data-price="${preciosTramo.ADULTO}">Adulto - $${preciosTramo.ADULTO}</option>
                <option value="MAYOR" data-price="${preciosTramo.MAYOR}">Tercera Edad - $${preciosTramo.MAYOR}</option>
                <option value="ESTUDIANTE" data-price="${preciosTramo.ESTUDIANTE}">Estudiante - $${preciosTramo.ESTUDIANTE}</option>
            `;

            document.getElementById('seat-area').classList.remove('hidden');
            document.getElementById('st-3').classList.add('active');

            // Cargar mapa (Usamos la API get_mapa que creamos en el paso anterior)
            const res = await fetch(`api/get_mapa.php?id=${viaje.id}`);
            const asientos = await res.json();
            
            let h = '';
            asientos.forEach(a => {
                // Dibujar pasillo
                if(a.nro % 4 === 3) h += '<div class="aisle"></div>';
                
                let cls = a.estado; // libre, ocupado, bloqueado
                h += `<div class="seat ${cls}" data-nro="${a.nro}" onclick="clickAsiento(this, ${a.nro}, '${cls}')">${a.nro}</div>`;
            });
            document.getElementById('bus-layout-grid').innerHTML = h;
            
            document.getElementById('confirm-area').classList.add('hidden'); // Ocultar form si cambia de bus
        }

        // 3. ELEGIR ASIENTO
        function clickAsiento(elemento, nro, estado) {
            if(estado !== 'libre') return;

            // Quitar seleccion previa
            document.querySelectorAll('.seat.seleccionado').forEach(s => s.classList.replace('seleccionado', 'libre'));
            
            elemento.classList.replace('libre', 'seleccionado');
            datosViaje.asiento = nro;
            
            document.getElementById('seat-badge').innerText = "Asiento " + nro;
            document.getElementById('lbl-asiento-elegido').innerText = "Asiento: " + nro;
            
            document.getElementById('confirm-area').classList.remove('hidden');
            document.getElementById('st-4').classList.add('active');
            actualizarPrecio();
        }

        function actualizarPrecio() {
            const sel = document.getElementById('tipo-tarifa');
            datosViaje.precioFinal = sel.options[sel.selectedIndex].getAttribute('data-price');
            document.getElementById('lbl-total').innerText = '$' + new Intl.NumberFormat('es-CL').format(datosViaje.precioFinal);
        }

        // 4. EMITIR BOLETO (VENDER)
        async function emitirBoleto() {
            if(!datosViaje.asiento) return alert("Seleccione un asiento");
            
            const rut = document.getElementById('p-rut').value;
            const nombre = document.getElementById('p-nombre').value;
            const telefono = document.getElementById('p-telefono').value;
            const oficina = document.getElementById('current-office').value;
            const tipoPax = document.getElementById('tipo-tarifa').value;

            if(!rut || !nombre) return alert("RUT y Nombre son obligatorios");

            const fd = new FormData();
            fd.append('id_viaje', datosViaje.id);
            fd.append('asiento', datosViaje.asiento);
            fd.append('rut', rut);
            fd.append('nombre', nombre);
            fd.append('telefono', telefono);
            fd.append('oficina', oficina);
            fd.append('origen', datosViaje.origen);
            fd.append('destino', datosViaje.destino);
            fd.append('tipo_pasajero', tipoPax);
            fd.append('total_pagado', datosViaje.precioFinal);

            const res = await fetch('api/vender.php', { method: 'POST', body: fd });
            const json = await res.json();

            if(json.status === 'success') {
                generarTicketImpresion({
                    folio: json.ticket,
                    origen: datosViaje.origen,
                    destino: datosViaje.destino,
                    asiento: datosViaje.asiento,
                    pasajero: nombre,
                    rut: rut,
                    tarifa: tipoPax,
                    total: datosViaje.precioFinal,
                    fecha: new Date().toLocaleString()
                });
                // Recargar mapa para marcarlo rojo
                seleccionarViaje({id: datosViaje.id, precio_adulto: preciosTramo.ADULTO, precio_mayor: preciosTramo.MAYOR, precio_estudiante: preciosTramo.ESTUDIANTE});
                
                // Limpiar form
                document.getElementById('p-rut').value = '';
                document.getElementById('p-nombre').value = '';
                document.getElementById('p-telefono').value = '';
            } else {
                alert("Error al vender: " + json.msg);
            }
        }

        // 5. RENDERIZAR TICKET TÉRMICO
        function generarTicketImpresion(t) {
            const html = `
                <div class="ticket">
                    <h3>JETBUS PRO</h3>
                    <center>Oficina Venta<br>Folio: ${t.folio}</center>
                    <hr>
                    <p><strong>FECHA EMISIÓN:</strong> ${t.fecha}</p>
                    <p><strong>RUTA:</strong> ${t.origen} -> ${t.destino}</p>
                    <p><strong>ASIENTO:</strong> <span style="font-size:16px">${t.asiento}</span></p>
                    <hr>
                    <p>PAX: ${t.pasajero}</p>
                    <p>RUT: ${t.rut}</p>
                    <p>TARIFA: ${t.tarifa}</p>
                    <div class="total">TOTAL: $${new Intl.NumberFormat('es-CL').format(t.total)}</div>
                    <center><small>Conserve este boleto</small></center>
                </div>`;
            
            document.getElementById('printable-area').innerHTML = html;
            document.getElementById('print-overlay').classList.remove('hidden');
        }

        // 6. GESTIÓN: ANULAR
        async function anularBoleto() {
            const ticket = document.getElementById('gestion-ticket').value;
            const oficina = document.getElementById('current-office').value; // Oficina que devuelve la plata
    
            if(!ticket) return alert("Ingrese el código del ticket");
            if(!confirm("¿Desea ANULAR el ticket " + ticket + "? Se descontará el dinero de esta caja.")) return;

            const fd = new FormData(); 
            fd.append('accion', 'anular'); 
            fd.append('codigo_ticket', ticket);
            fd.append('oficina', oficina);

            const res = await fetch('api/gestion_caja.php', { method: 'POST', body: fd });
            const json = await res.json();
    
            alert(json.msg);
            if(json.success && datosViaje.id) {
            seleccionarViaje({id: datosViaje.id, precio_adulto: preciosTramo.ADULTO, precio_mayor: preciosTramo.MAYOR, precio_estudiante: preciosTramo.ESTUDIANTE});
            }
        }

        // 7. CIERRE DE CAJA
        async function cerrarCaja() {
            const fecha = document.getElementById('cierre-fecha').value;
            const oficina = document.getElementById('current-office').value;

            const res = await fetch(`api/gestion_caja.php?accion=cierre&fecha=${fecha}&oficina=${oficina}`);
            const json = await res.json();

            if(!json.success) return alert("Error al generar cierre");

            let html = `
                <div class="ticket">
                    <h3>CIERRE DE CAJA</h3>
                    <center>${oficina}<br>Fecha Operación: ${fecha}</center>
                    <hr>
                     <table style="width:100%; font-size:12px;">
                        <tr><td>(+) Ventas (${json.vendidos} boletos)</td><td align="right">$${new Intl.NumberFormat('es-CL').format(json.ingresos)}</td></tr>
                         <tr><td>(-) Devoluciones (${json.anulados} anulados)</td><td align="right" style="color:red">-$${new Intl.NumberFormat('es-CL').format(json.egresos)}</td></tr>
                    </table>
                    <hr>
                <div class="total">EFECTIVO EN CAJA: $${new Intl.NumberFormat('es-CL').format(json.total_caja)}</div>
                </div>`;
    
            document.getElementById('printable-area').innerHTML = html;
            document.getElementById('print-overlay').classList.remove('hidden');
        }
    </script>
</body>
</html>