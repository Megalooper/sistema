<?php
session_start();
// No requiere login si es solo para ganancias (opcional: descomenta si quieres protección)
// if (!isset($_SESSION['usuario'])) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Acceso no autorizado']);
//     exit;
// }

header('Content-Type: application/json');

try {
    require_once 'config/db.php';

    $tipo = $_GET['tipo'] ?? 'mensual';

    $datos = ['labels' => [], 'data' => []];

    if ($tipo === 'diaria') {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(fecha_cierre) as dia,
                SUM(total_usd) as ganancia
            FROM pedidos 
            WHERE estado = 'cerrado'
            AND fecha_cierre >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(fecha_cierre)
            ORDER BY dia ASC;
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $datos['labels'][] = date('d/m', strtotime($row['fecha']));
            $datos['data'][] = round($row['ganancia'], 2);
        }

    } elseif ($tipo === 'anual') {
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(fecha_cierre, '%Y-%m') as mes,
                SUM(total_usd) as ganancia
            FROM pedidos 
            WHERE estado = 'cerrado'
            AND fecha_cierre >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY mes
            ORDER BY mes ASC;
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $datos['labels'][] = date('M Y', strtotime($row['mes'] . '-01'));
            $datos['data'][] = round($row['ganancia'], 2);
        }

    } else { // mensual (por defecto)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(fecha_cierre, '%Y-%m') as mes,
                SUM(total_usd) as ganancia
            FROM pedidos 
            WHERE estado = 'cerrado'
            AND fecha_cierre >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY mes
            ORDER BY mes ASC
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $datos['labels'][] = date('M Y', strtotime($row['mes'] . '-01'));
            $datos['data'][] = round($row['ganancia'], 2);
        }
    }

    echo json_encode($datos);

} catch (Exception $e) {
    error_log("Error en obtener_ganancias.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['labels' => [], 'data' => [], 'error' => 'Error interno del servidor']);
}
?>