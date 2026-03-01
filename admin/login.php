<?php
// admin/login.php
session_start();
require_once '../config/db.php';

// Si ya está logueado, lo mandamos a la boletería
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// -------------------------------------------------------------------
// 🛠️ AUTO-INSTALADOR DE SEGURIDAD (Se ejecuta solo la primera vez)
// -------------------------------------------------------------------
try {
    // Crea la tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        rol VARCHAR(20) DEFAULT 'CAJERO'
    )");

    // Revisa si hay usuarios. Si está vacío, crea el Admin maestro.
    $stmt_check = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt_check->fetchColumn() == 0) {
        $hash = password_hash('cordillera123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO usuarios (usuario, password, nombre, rol) VALUES ('admin', '$hash', 'Administrador Principal', 'ADMIN')");
    }
} catch (Exception $e) {}
// -------------------------------------------------------------------

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificamos la contraseña encriptada
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_rol'] = $user['rol'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Privado | Buses Cordillera</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .logo { font-size: 1.8rem; font-weight: 900; color: #003580; margin-bottom: 5px; }
        .logo span { color: #febb02; }
        .subtitle { color: gray; font-size: 0.9rem; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: bold; color: #334155; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; outline: none; }
        .btn { background: #006ce4; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: #0056b3; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">Buses<span>Cordillera</span></div>
        <div class="subtitle">Sistema de Boletería y Administración</div>

        <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" required autofocus autocomplete="off">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">INGRESAR AL SISTEMA</button>
        </form>
        <p style="margin-top:20px; font-size:0.8rem; color:#94a3b8;">Acceso exclusivo para personal autorizado.</p>
    </div>
</body>
</html>