<?php
// public/index.php
require_once '../config/db.php';

// Obtener dinámicamente todas las paradas (Concepción, Laraquete, Curanilahue, Cerro Alto, Cañete)
// Así no tienes que escribirlas a mano y se actualizan solas si agregas más en el admin.
$stmt = $pdo->query("
    SELECT DISTINCT origen_tramo as ciudad FROM matriz_precios 
    UNION 
    SELECT DISTINCT destino_tramo FROM matriz_precios 
    ORDER BY ciudad
");
$ciudades = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JetBus | Viaja por la Provincia de Arauco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        
        /* Banner Principal (Hero) */
        .hero-section {
            background: linear-gradient(rgba(0, 53, 128, 0.7), rgba(0, 53, 128, 0.9)), 
                        url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?q=80&w=1920');
            background-size: cover;
            background-position: center;
            height: 60vh;
            min-height: 400px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
        }

        /* Buscador Flotante */
        .search-widget {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 1000px;
            margin-top: -80px; /* Hace que el cuadro flote sobre el banner */
            position: relative;
            z-index: 10;
        }

        .btn-search {
            background-color: #ffc107; 
            color: #003580; 
            font-weight: bold;
            padding: 14px; 
            border: none; 
            width: 100%; 
            border-radius: 8px;
            font-size: 1.1rem;
            transition: 0.3s;
        }
        
        .btn-search:hover { background-color: #e0a800; transform: translateY(-2px); }

        .form-control, .form-select {
            padding: 14px; 
            border-radius: 8px; 
            border: 1px solid #dee2e6; 
            background-color: #f8f9fa;
        }
        
        .form-label { color: #003580; font-weight: 700; font-size: 0.95rem; }
        
        /* Tarjetas de Beneficios */
        .feature-box { text-align: center; padding: 20px; transition: 0.3s; }
        .feature-box:hover { transform: translateY(-5px); }
        .feature-icon { font-size: 3rem; color: #006ce4; margin-bottom: 15px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark" style="background-color: #003580;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-bus-alt text-warning"></i> JetBus Online
            </a>
            <span class="navbar-text text-light">
                <i class="fas fa-headset"></i> Soporte: +569 8784 0428
            </span>
        </div>
    </nav>

    <div class="hero-section text-center">
        <div class="px-3">
            <h1 class="display-4 fw-bold mb-3">Tu viaje comienza aquí</h1>
            <p class="lead fs-4">Conectando Concepción, Arauco y Cañete todos los días.</p>
        </div>
    </div>

    <div class="container d-flex justify-content-center">
        <div class="search-widget">
            <form action="resultados.php" method="GET" class="row g-3 align-items-end">
                
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-map-marker-alt text-danger"></i> Origen</label>
                    <select class="form-select" name="origen" required>
                        <option value="" selected disabled>¿Desde dónde viajas?</option>
                        <?php foreach($ciudades as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-location-arrow text-success"></i> Destino</label>
                    <select class="form-select" name="destino" required>
                        <option value="" selected disabled>¿Hacia dónde vas?</option>
                        <?php foreach($ciudades as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-calendar-alt text-primary"></i> Fecha de Ida</label>
                    <input type="date" class="form-control" name="fecha" required 
                           value="<?php echo date('Y-m-d'); ?>" 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn-search shadow">
                        BUSCAR PASAJES <i class="fas fa-search ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="container my-5 pt-5">
        <h2 class="text-center fw-bold mb-5" style="color: #003580;">¿Por qué viajar con nosotros?</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-wifi feature-icon"></i>
                    <h5 class="fw-bold">Conexión a Bordo</h5>
                    <p class="text-muted">Disfruta de WiFi gratuito y enchufes USB en todos nuestros recorridos hacia la provincia de Arauco.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-couch feature-icon"></i>
                    <h5 class="fw-bold">Máxima Comodidad</h5>
                    <p class="text-muted">Moderna flota de buses con asientos Salón Cama y Semi Cama para que tu viaje sea un agrado.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h5 class="fw-bold">Seguridad 24/7</h5>
                    <p class="text-muted">Nuestros buses cuentan con monitoreo GPS en ruta y conductores altamente capacitados.</p>
                </div>
            </div>
        </div>
    </div>

    <footer style="background: #1a1a1a; color: #888; padding: 30px 0; text-align: center; margin-top: 50px;">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> JetBus. Todos los derechos reservados.</p>
            <small>Desarrollado para la ruta Concepción - Cañete</small>
        </div>
    </footer>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const origen = document.querySelector('select[name="origen"]').value;
            const destino = document.querySelector('select[name="destino"]').value;
            
            if (origen === destino) {
                e.preventDefault();
                alert("El origen y el destino no pueden ser la misma ciudad.");
            }
        });
    </script>
</body>
</html>