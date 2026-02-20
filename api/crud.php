<?php
header('Content-Type: application/json');
require_once 'db_conn.php';

$action = $_POST['action'] ?? '';
$table  = $_POST['table'] ?? '';

// Mapeo seguro de nombres de tabla para evitar inyección
$whitelist = [
    'viajes' => 'viajes', 
    'buses' => 'buses', 
    'pasajeros' => 'pasajeros', 
    'tramos' => 'precios_tramos', 
    'tarifas' => 'tarifas'
];

if (!array_key_exists($table, $whitelist)) {
    echo json_encode(['success' => false, 'error' => 'Tabla no válida']);
    exit;
}

$dbTable = $whitelist[$table];
$pk = ($table === 'pasajeros') ? 'rut' : 'id';

try {
    // --- READ (LEER) ---
    if ($action === 'read') {
        $sql = "SELECT * FROM $dbTable";
        
        // Ordenamientos específicos para que se vea bonito
        if($table === 'viajes') $sql .= " ORDER BY fecha_hora_salida DESC";
        if($table === 'buses') $sql .= " ORDER BY numero_bus ASC";
        
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } 
    
    // --- CREATE (CREAR) ---
    elseif ($action === 'create') {
        // Filtrar campos que no sean 'action', 'table', 'id'
        $fields = array_filter($_POST, fn($k) => !in_array($k, ['action', 'table', 'id']), ARRAY_FILTER_USE_KEY);
        
        $cols = implode(", ", array_keys($fields));
        $vals = implode(", ", array_fill(0, count($fields), "?"));
        
        $sql = "INSERT INTO $dbTable ($cols) VALUES ($vals)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($fields));
        
        echo json_encode(['success' => true]);
    } 
    
    // --- UPDATE (EDITAR) ---
    elseif ($action === 'update') {
        $id = $_POST['id'];
        $fields = array_filter($_POST, fn($k) => !in_array($k, ['action', 'table', 'id']), ARRAY_FILTER_USE_KEY);
        
        $set = implode("=?, ", array_keys($fields)) . "=?";
        $sql = "UPDATE $dbTable SET $set WHERE $pk = ?";
        
        $params = array_values($fields);
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true]);
    } 
    
    // --- DELETE (BORRAR) ---
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM $dbTable WHERE $pk = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } 

    else {
        echo json_encode(['success' => false, 'error' => 'Acción desconocida']);
    }

} catch (Exception $e) {
    // Mensaje amigable si hay error de llave foránea
    if(strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
        $msg = "No se puede eliminar porque este registro está siendo usado en una venta activa.";
    } else {
        $msg = $e->getMessage();
    }
    echo json_encode(['success' => false, 'error' => $msg]);
}