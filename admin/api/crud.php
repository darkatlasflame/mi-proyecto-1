<?php
// admin/api/crud.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$table = $_REQUEST['table'] ?? '';

try {
    // 1. Catálogos (Buses y Ciudades)
    if ($action === 'catalogos') {
        $buses = $pdo->query("SELECT id, numero_maquina, patente FROM buses WHERE estado = 'ACTIVO'")->fetchAll(PDO::FETCH_ASSOC);
        
        // Extraemos las ciudades directamente desde los precios configurados
        $ciudades_stmt = $pdo->query("SELECT DISTINCT origen_tramo as ciudad FROM matriz_precios UNION SELECT DISTINCT destino_tramo as ciudad FROM matriz_precios ORDER BY ciudad");
        $ciudades = $ciudades_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['success' => true, 'buses' => $buses, 'ciudades' => $ciudades]);
        exit;
    }

    // 2. Leer Tabla
    if ($action === 'read') {
        if ($table === 'viajes') {
            $sql = "SELECT v.*, b.numero_maquina FROM viajes v LEFT JOIN buses b ON v.id_bus = b.id ORDER BY v.fecha_hora DESC LIMIT 100";
        } elseif ($table === 'ventas') {
            $sql = "SELECT * FROM ventas ORDER BY id DESC LIMIT 50";
        } else {
            $sql = "SELECT * FROM $table ORDER BY id DESC";
        }
        $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // 3. Borrar
    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // 4. Crear o Actualizar
    if ($action === 'create' || $action === 'update') {
        $fields = $_POST;
        unset($fields['action'], $fields['table'], $fields['id']);

        $columns = array_keys($fields);
        $values = array_values($fields);

        if ($action === 'create') {
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $cols = implode(',', $columns);
            $sql = "INSERT INTO $table ($cols) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        } else {
            $id = $_POST['id'];
            $setClause = implode('=?,', $columns) . '=?';
            $sql = "UPDATE $table SET $setClause WHERE id = ?";
            $values[] = $id; 
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}