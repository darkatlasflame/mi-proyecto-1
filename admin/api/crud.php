<?php
// admin/api/crud.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$table  = $_REQUEST['table'] ?? '';

try {
    // 1. CARGAR CATÁLOGOS PARA LOS SELECTS
    if ($action === 'catalogos') {
        $buses = $pdo->query("SELECT id, numero_maquina, patente FROM buses WHERE estado = 'ACTIVO'")->fetchAll();
        $viajes = $pdo->query("SELECT id, origen, destino, DATE_FORMAT(fecha_hora, '%d/%m %H:%i') as fecha FROM viajes WHERE fecha_hora >= CURRENT_DATE")->fetchAll();
        echo json_encode(['buses' => $buses, 'viajes' => $viajes]);
        exit;
    }

    // 2. LEER DATOS (READ)
    if ($action === 'read') {
        if ($table === 'viajes') {
            $sql = "SELECT v.*, b.numero_maquina FROM viajes v JOIN buses b ON v.id_bus = b.id ORDER BY v.fecha_hora DESC LIMIT 50";
        } elseif ($table === 'matriz_precios') {
            $sql = "SELECT * FROM matriz_precios ORDER BY id_viaje DESC, orden_parada ASC LIMIT 100";
        } elseif ($table === 'ventas') {
            $sql = "SELECT id, codigo_ticket, nombre_pasajero, rut_pasajero, telefono_contacto, nro_asiento FROM ventas ORDER BY id DESC LIMIT 100";
        } else {
            $sql = "SELECT * FROM $table ORDER BY id DESC";
        }
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        exit;
    }

    // 3. CREAR DATOS (CREATE)
    if ($action === 'create') {
        if ($table === 'viajes') {
            $sql = "INSERT INTO viajes (origen, destino, fecha_hora, id_bus) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$_POST['origen'], $_POST['destino'], $_POST['fecha_hora'], $_POST['id_bus']]);
        } elseif ($table === 'buses') {
            $sql = "INSERT INTO buses (numero_maquina, patente, marca, capacidad, estado) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$_POST['numero_maquina'], $_POST['patente'], $_POST['marca'], $_POST['capacidad'], $_POST['estado']]);
        } elseif ($table === 'matriz_precios') {
            $sql = "INSERT INTO matriz_precios (id_viaje, origen_tramo, destino_tramo, precio_adulto, precio_mayor, precio_estudiante, orden_parada) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$_POST['id_viaje'], $_POST['origen_tramo'], $_POST['destino_tramo'], $_POST['precio_adulto'], $_POST['precio_mayor'], $_POST['precio_estudiante'], $_POST['orden_parada']]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // 4. ACTUALIZAR DATOS (UPDATE)
    if ($action === 'update') {
        $id = $_POST['id'];
        if ($table === 'ventas') {
            $sql = "UPDATE ventas SET nombre_pasajero = ?, rut_pasajero = ?, telefono_contacto = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$_POST['nombre_pasajero'], $_POST['rut_pasajero'], $_POST['telefono_contacto'], $id]);
        } elseif ($table === 'matriz_precios') {
            $sql = "UPDATE matriz_precios SET origen_tramo=?, destino_tramo=?, precio_adulto=?, precio_mayor=?, precio_estudiante=?, orden_parada=? WHERE id=?";
            $pdo->prepare($sql)->execute([$_POST['origen_tramo'], $_POST['destino_tramo'], $_POST['precio_adulto'], $_POST['precio_mayor'], $_POST['precio_estudiante'], $_POST['orden_parada'], $id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // 5. BORRAR DATOS (DELETE)
    if ($action === 'delete') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>