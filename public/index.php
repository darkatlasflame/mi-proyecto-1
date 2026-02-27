<?php
require_once '../config/db.php';

// Cargar las paradas únicas desde el tarifario universal
try {
    $stmt = $pdo->query("
        SELECT ciudad FROM (
            SELECT DISTINCT origen_tramo as ciudad FROM matriz_precios 
            UNION 
            SELECT DISTINCT destino_tramo as ciudad FROM matriz_precios
        ) AS lista_ciudades ORDER BY ciudad
    ");
    $paradas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $paradas = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JetBus | Compra tus pasajes online</title>
    <style>
        :root { --primary: #003580; --accent: #006ce4; --bg: #f5f7fa; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: #333; }
        header { background: var(--primary); padding: 15px 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 900; }
        .logo span { color: #febb02; }
        .hero { background: linear-gradient(rgba(0,53,128,0.8), rgba(0,53,128,0.8)), url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?q=80&w=1000') center/cover; padding: 60px 20px; text-align: center; color: white; }
        .search-box { background: white; padding: 25px; border-radius: 12px; max-width: 800px; margin: -40px auto 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; gap: 15px; flex-wrap: wrap; }
        .input-group { flex: 1; min-width: 200px; text-align: left; }
        .input-group label { display: block; font-size: 0.8rem; font-weight: bold; color: gray; margin-bottom: 5px; }
        .input-group select, .input-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; outline: none; box-sizing: border-box; }
        .btn-search { background: var(--accent); color: white; border: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 1rem; cursor: pointer; flex: 1; min-width: 200px; margin-top: auto; transition: 0.2s; }
        .btn-search:hover { background: #0056b3; }
    </style>
</head>
<body>

    <header>
        <div class="logo">JetBus<span>Pro</span></div>
        <div style="font-weight: bold;">Pasajes Online</div>
    </header>

    <div class="hero">
        <h1 style="margin:0; font-size:2.5rem;">Viaja seguro, viaja cómodo</h1>
        <p style="font-size:1.2rem; opacity:0.9;">Compra tus pasajes en menos de 2 minutos.</p>
    </div>

    <form action="resultados.php" method="GET" class="search-box">
        <div class="input-group">
            <label>ORIGEN</label>
            <select name="origen" required>
                <option value="">¿Desde dónde viajas?</option>
                <?php foreach($paradas as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-group">
            <label>DESTINO</label>
            <select name="destino" required>
                <option value="">¿Hacia dónde vas?</option>
                <?php foreach($paradas as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="input-group">
            <label>FECHA DE IDA</label>
            <input type="date" name="fecha" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
        </div>
        <button type="submit" class="btn-search">BUSCAR PASAJES</button>
    </form>

</body>
</html>