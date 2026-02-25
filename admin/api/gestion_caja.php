<?php
// admin/api/gestion_caja.php
require_once '../../config/db.php';
header('Content-Type: application/json');

$accion = $_REQUEST['accion'] ?? '';

try {
    // --------------------------------------------------------
    // 1. ANULAR UN BOLETO (Devolver dinero)
    // --------------------------------------------------------
    if ($accion === 'anular') {
        $codigo_ticket = $_POST['codigo_ticket'] ?? '';
        $oficina = $_POST['oficina'] ?? 'Oficina Central'; // Quién devuelve la plata

        if (!$codigo_ticket) throw new Exception("Falta el código del ticket.");

        // Marcamos como ANULADO y guardamos la FECHA y OFICINA que hizo la devolución
        $sql = "UPDATE ventas 
                SET estado = 'ANULADO', fecha_anulacion = NOW(), oficina_anulacion = ? 
                WHERE codigo_ticket = ? AND estado = 'CONFIRMADO'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$oficina, $codigo_ticket]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'msg' => '✅ Pasaje anulado. Dinero devuelto y asiento liberado.']);
        } else {
            echo json_encode(['success' => false, 'msg' => '❌ Ticket no encontrado o ya estaba anulado.']);
        }
        exit;
    }

    // --------------------------------------------------------
    // 2. GENERAR CIERRE DE CAJA (Ingresos menos Devoluciones)
    // --------------------------------------------------------
    if ($accion === 'cierre') {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $oficina = $_GET['oficina'] ?? 'Todas';

        // 1. Calcular INGRESOS (Plata que entró hoy a esta sucursal)
        $sql_in = "SELECT SUM(total_pagado) as total_ingresos, COUNT(*) as cant_vendidos 
                   FROM ventas WHERE DATE(fecha_venta) = ?";
        $params_in = [$fecha];
        if ($oficina !== 'Todas') {
            $sql_in .= " AND oficina_venta = ?";
            $params_in[] = $oficina;
        }
        $stmt_in = $pdo->prepare($sql_in);
        $stmt_in->execute($params_in);
        $ingresos = $stmt_in->fetch();

        // 2. Calcular EGRESOS (Plata que se devolvió hoy en esta sucursal, por pasajes anulados)
        $sql_out = "SELECT SUM(total_pagado) as total_egresos, COUNT(*) as cant_anulados 
                    FROM ventas WHERE estado = 'ANULADO' AND DATE(fecha_anulacion) = ?";
        $params_out = [$fecha];
        if ($oficina !== 'Todas') {
            $sql_out .= " AND oficina_anulacion = ?";
            $params_out[] = $oficina;
        }
        $stmt_out = $pdo->prepare($sql_out);
        $stmt_out->execute($params_out);
        $egresos = $stmt_out->fetch();

        // Limpiar nulos
        $total_in = $ingresos['total_ingresos'] ?? 0;
        $total_out = $egresos['total_egresos'] ?? 0;
        
        // Efectivo Real en Caja
        $total_caja = $total_in - $total_out;

        echo json_encode([
            'success' => true,
            'ingresos' => $total_in,
            'vendidos' => $ingresos['cant_vendidos'] ?? 0,
            'egresos' => $total_out,
            'anulados' => $egresos['cant_anulados'] ?? 0,
            'total_caja' => $total_caja
        ]);
        exit;
    }

    throw new Exception("Acción no válida.");

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>