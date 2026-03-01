<?php
$origen = $_GET['origen'] ?? ''; $destino = $_GET['destino'] ?? ''; $fecha = $_GET['fecha'] ?? '';
if(!$origen || !$destino || !$fecha) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados | Buses Cordillera</title>
    <style>
        :root { --primary: #003580; --accent: #006ce4; --bg: #f5f7fa; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg); }
        header { background: var(--primary); padding: 15px 20px; color: white; display:flex; justify-content:space-between; align-items:center;}
        .container { max-width: 800px; margin: 20px auto; padding: 0 15px; }
        .resumen-busqueda { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #ddd; }
        .bus-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #eee; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .bus-time { font-size: 1.5rem; font-weight: 900; color: var(--primary); }
        .btn-comprar { background: var(--accent); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; transition: 0.2s; }
        .btn-comprar:hover { background: #0056b3; }
        .loader { text-align: center; padding: 40px; color: gray; }
    </style>
</head>
<body>

    <header>
        <a href="index.php" style="color:white; text-decoration:none; font-weight:bold;">⬅ Volver</a>
        <div style="font-weight: bold;">Selecciona tu viaje</div>
    </header>

    <div class="container">
        <div class="resumen-busqueda">
            <h2 style="margin:0; font-size:1.2rem;"><?= htmlspecialchars($origen) ?> ➔ <?= htmlspecialchars($destino) ?></h2>
            <p style="margin:5px 0 0 0; color:gray;">Fecha: <?= date('d/m/Y', strtotime($fecha)) ?></p>
        </div>
        <div id="lista-viajes"><div class="loader">Buscando buses disponibles... 🚌</div></div>
    </div>

    <script>
        async function cargarViajes() {
            const fd = new FormData(); fd.append('origen', '<?= $origen ?>'); fd.append('destino', '<?= $destino ?>'); fd.append('fecha', '<?= $fecha ?>');
            try {
                const res = await fetch('../admin/api/buscar_viajes.php', { method: 'POST', body: fd });
                const json = await res.json();
                const contenedor = document.getElementById('lista-viajes');

                if(!json.success || json.viajes.length === 0) {
                    contenedor.innerHTML = `<div style="text-align:center; padding:30px; background:white; border-radius:8px;">
                        <h3>Lo sentimos 😔</h3><p>${json.msg || 'No hay buses programados para esta fecha y ruta.'}</p>
                        <a href="index.php" class="btn-comprar" style="display:inline-block; margin-top:10px;">Buscar otra fecha</a>
                    </div>`;
                    return;
                }

                let html = '';
                json.viajes.forEach(v => {
                    const link = `seleccion.php?id_viaje=${v.id}&origen=<?= urlencode($origen) ?>&destino=<?= urlencode($destino) ?>&fecha=<?= $fecha ?>&hora=${v.hora}&precio_adulto=${v.precio_adulto}&precio_estudiante=${v.precio_estudiante}&precio_mayor=${v.precio_mayor}`;
                    html += `
                    <div class="bus-card">
                        <div>
                            <div class="bus-time">${v.hora} hrs</div>
                            <div style="color:gray; font-size:0.9rem; margin-top:5px;">Directo • Bus ${v.numero_maquina}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size:0.8rem; color:gray; margin-bottom:5px;">Tarifa $${v.precio_adulto}</div>
                            <a href="${link}" class="btn-comprar">Elegir Asiento</a>
                        </div>
                    </div>`;
                });
                contenedor.innerHTML = html;
            } catch (error) { document.getElementById('lista-viajes').innerHTML = '<p style="color:red; text-align:center;">Error al conectar con el servidor.</p>'; }
        }
        cargarViajes();
    </script>
</body>
</html>