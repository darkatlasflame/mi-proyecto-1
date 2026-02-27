<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JetBus Admin | Panel de Control</title>
    <style>
        :root { --p: #1e293b; --s: #3b82f6; --bg: #f1f5f9; --card: #ffffff; --text: #334155; }
        * { box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 0; }
        body { display: grid; grid-template-columns: 250px 1fr; height: 100vh; background: var(--bg); color: var(--text); }
        
        aside { background: var(--p); color: white; padding: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .brand { font-size: 1.5rem; font-weight: 800; margin-bottom: 2rem; color: white; display: flex; align-items: center; gap: 10px; }
        .brand span { color: #facc15; }
        .nav-btn { padding: 12px; border-radius: 8px; cursor: pointer; transition: 0.2s; background: transparent; border: none; color: #94a3b8; text-align: left; font-weight: 600; width: 100%; }
        .nav-btn:hover, .nav-btn.active { background: #334155; color: white; }
        .nav-btn.active { border-left: 4px solid var(--s); }
        
        main { padding: 2rem; overflow-y: auto; }
        .card { background: var(--card); border-radius: 10px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 700; color: #64748b; }
        
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; color: white; transition: 0.2s; }
        .btn-primary { background: var(--s); }
        .btn-warning { background: #f59e0b; }
        .btn-danger { background: #ef4444; }
        
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 2rem; border-radius: 12px; width: 450px; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;}
        .form-group input, .form-group select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; }
    </style>
</head>
<body>

    <aside>
        <div class="brand">JETBUS<span>ADMIN</span></div>
        <button class="nav-btn active" onclick="loadView('viajes')">📅 Programación Viajes</button>
        <button class="nav-btn" onclick="loadView('buses')">🚌 Flota de Buses</button>
        <button class="nav-btn" onclick="loadView('matriz_precios')">💲 Matriz de Precios</button>
        <button class="nav-btn" onclick="loadView('ventas')">👥 Corrección Pasajeros</button>
        <div style="flex-grow:1"></div>
        <a href="index.php" class="nav-btn" style="text-align:center; background:#facc15; color:#1e293b; text-decoration:none;">⬅ Volver a Ventas</a>
    </aside>

    <main>
        <header style="display:flex; justify-content:space-between; margin-bottom:2rem;">
            <h1 id="page-title">Cargando...</h1>
            <button id="btn-nuevo" class="btn btn-primary" onclick="openModal()">+ Nuevo Registro</button>
        </header>

        <div class="card" id="table-container"></div>
    </main>

    <div id="modal" class="modal">
        <div class="modal-content">
            <h2 id="modal-title" style="margin-bottom:1.5rem">Formulario</h2>
            <form id="dynamic-form"></form>
            <div style="margin-top:1.5rem; text-align:right; display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn" style="background:#e2e8f0; color:#333" onclick="closeModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveData()">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        let currentView = 'viajes';
        let editId = null;
        let catalogoBuses = [];
        let catalogoCiudades = [];

        // Configuración blindada de vistas y formularios
        const schemas = {
            viajes: {
                title: "Programación de Viajes",
                cols: ["ID", "Ruta Principal", "Fecha/Hora", "Bus Asignado", "Estado", "Acciones"],
                fields: [
                    { name: "origen", label: "Origen Principal", type: "select", source: "ciudades" },
                    { name: "destino", label: "Destino Final", type: "select", source: "ciudades" },
                    { name: "fecha_hora", label: "Fecha y Hora", type: "datetime-local" },
                    { name: "id_bus", label: "Bus Asignado", type: "select", source: "buses" },
                    { name: "estado", label: "Estado", type: "select", options: [{id:'PROGRAMADO', val:'Programado'}, {id:'FINALIZADO', val:'Finalizado'}, {id:'CANCELADO', val:'Cancelado'}] }
                ]
            },
            buses: {
                title: "Flota de Buses",
                cols: ["Máquina", "Patente", "Capacidad", "Estado", "Acciones"],
                fields: [
                    { name: "numero_maquina", label: "N° Máquina", type: "text" },
                    { name: "patente", label: "Patente", type: "text" },
                    { name: "capacidad", label: "Capacidad (Asientos)", type: "number" },
                    { name: "estado", label: "Estado", type: "select", options: [{id:'ACTIVO', val:'Activo'}, {id:'INACTIVO', val:'Inactivo'}, {id:'TALLER', val:'Taller'}] }
                ]
            },
            matriz_precios: {
                title: "Matriz de Precios Universal (Por Tramo)",
                cols: ["Origen", "Destino", "Adulto", "T. Edad", "Estudiante", "Acciones"],
                fields: [
                    { name: "origen_tramo", label: "Origen Tramo (Ciudad)", type: "text" },
                    { name: "destino_tramo", label: "Destino Tramo (Ciudad)", type: "text" },
                    { name: "precio_adulto", label: "Precio Adulto ($)", type: "number" },
                    { name: "precio_mayor", label: "Precio T. Edad ($)", type: "number" },
                    { name: "precio_estudiante", label: "Precio Estudiante ($)", type: "number" }
                ]
            },
            ventas: {
                title: "Corrección de Pasajeros",
                cols: ["Ticket", "Pasajero", "RUT", "Teléfono", "Asiento", "Acciones"],
                fields: [
                    { name: "nombre_pasajero", label: "Nombre Pasajero", type: "text" },
                    { name: "rut_pasajero", label: "RUT", type: "text" },
                    { name: "telefono_contacto", label: "Teléfono", type: "text" }
                ]
            }
        };

        document.addEventListener('DOMContentLoaded', async () => {
            await cargarCatalogos();
            loadView('viajes');
        });

        async function cargarCatalogos() {
            const res = await fetch('api/crud.php?action=catalogos');
            const data = await res.json();
            catalogoBuses = data.buses || [];
            catalogoCiudades = data.ciudades || [];
        }

        async function loadView(view) {
            currentView = view;
            editId = null;
            
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            event && event.target ? event.target.classList.add('active') : document.querySelector(`[onclick="loadView('${view}')"]`).classList.add('active');
            
            document.getElementById('page-title').innerText = schemas[view].title;
            document.getElementById('btn-nuevo').style.display = (view === 'ventas') ? 'none' : 'block';

            const res = await fetch(`api/crud.php?action=read&table=${view}`);
            const json = await res.json();
            renderTable(json.data || []);
        }

        function renderTable(data) {
            let html = `<table><thead><tr>`;
            schemas[currentView].cols.forEach(c => html += `<th>${c}</th>`);
            html += `</tr></thead><tbody>`;

            data.forEach(row => {
                html += `<tr>`;
                if(currentView === 'viajes') {
                    html += `<td>${row.id}</td><td><strong>${row.origen}</strong> ➔ ${row.destino}</td><td>${row.fecha_hora}</td><td>Máquina ${row.numero_maquina || '-'}</td><td>${row.estado || 'PROGRAMADO'}</td>`;
                } else if(currentView === 'buses') {
                    html += `<td>${row.numero_maquina}</td><td>${row.patente}</td><td>${row.capacidad}</td><td><span style="background:${row.estado==='ACTIVO'?'#dcfce3':'#fee2e2'}; color:${row.estado==='ACTIVO'?'#166534':'#991b1b'}; padding:2px 8px; border-radius:10px; font-size:0.8rem;">${row.estado}</span></td>`;
                } else if(currentView === 'matriz_precios') {
                    html += `<td>${row.origen_tramo}</td><td>${row.destino_tramo}</td><td>$${row.precio_adulto}</td><td>$${row.precio_mayor}</td><td>$${row.precio_estudiante}</td>`;
                } else if(currentView === 'ventas') {
                    html += `<td>${row.codigo_ticket}</td><td>${row.nombre_pasajero}</td><td>${row.rut_pasajero}</td><td>${row.telefono_contacto}</td><td>${row.nro_asiento}</td>`;
                }

                html += `<td style="display:flex; gap:5px;">`;
                html += `<button class="btn btn-warning" style="padding:4px 8px; font-size:0.8rem;" onclick='openModal(${JSON.stringify(row).replace(/'/g, "&apos;")})'>Editar</button>`;
                
                if(currentView !== 'ventas') {
                    html += `<button class="btn btn-danger" style="padding:4px 8px; font-size:0.8rem;" onclick="deleteRow(${row.id})">Borrar</button>`;
                }
                html += `</td></tr>`;
            });
            html += `</tbody></table>`;
            document.getElementById('table-container').innerHTML = html;
        }

        function openModal(data = null) {
            editId = data ? data.id : null;
            const form = document.getElementById('dynamic-form');
            form.innerHTML = '';
            
            schemas[currentView].fields.forEach(f => {
                let inputHtml = '';
                let val = data ? (data[f.name] || '') : '';

                if(f.type === 'select') {
                    let opts = '';
                    if(f.source === 'buses') {
                        catalogoBuses.forEach(b => opts += `<option value="${b.id}" ${val==b.id?'selected':''}>Máquina ${b.numero_maquina} (${b.patente})</option>`);
                    } else if (f.source === 'ciudades') {
                        if (catalogoCiudades.length === 0) opts = '<option value="">Crea un precio primero para registrar ciudades</option>';
                        catalogoCiudades.forEach(c => opts += `<option value="${c}" ${val===c?'selected':''}>${c}</option>`);
                    } else {
                        f.options.forEach(o => opts += `<option value="${o.id}" ${val==o.id?'selected':''}>${o.val}</option>`);
                    }
                    inputHtml = `<select name="${f.name}" required>${opts}</select>`;
                } else {
                    if(f.type === 'datetime-local' && val) val = val.replace(' ', 'T').substring(0, 16);
                    inputHtml = `<input type="${f.type}" name="${f.name}" value="${val}" required>`;
                }
                form.innerHTML += `<div class="form-group"><label>${f.label}</label>${inputHtml}</div>`;
            });
            
            document.getElementById('modal').style.display = 'flex';
        }

        function closeModal() { document.getElementById('modal').style.display = 'none'; }

        async function saveData() {
            const fd = new FormData(document.getElementById('dynamic-form'));
            fd.append('action', editId ? 'update' : 'create');
            fd.append('table', currentView);
            if(editId) fd.append('id', editId);

            try {
                const res = await fetch('api/crud.php', { method: 'POST', body: fd });
                const json = await res.json();
                
                if(json.success) {
                    closeModal();
                    if(currentView === 'matriz_precios' || currentView === 'buses') await cargarCatalogos();
                    loadView(currentView);
                } else alert("Error: " + json.error);
            } catch(e) { alert("Error de conexión al guardar."); }
        }

        async function deleteRow(id) {
            if(!confirm("¿Seguro que deseas borrar esto?")) return;
            const fd = new FormData();
            fd.append('action', 'delete'); fd.append('table', currentView); fd.append('id', id);
            await fetch('api/crud.php', { method: 'POST', body: fd });
            loadView(currentView);
        }
    </script>
</body>
</html>