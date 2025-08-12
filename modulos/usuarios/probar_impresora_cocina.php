<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

$input = json_decode(file_get_contents('php://input'), true);
$ip = $input['ip'] ?? '';

if (empty($ip)) {
    echo json_encode(['success' => false, 'error' => 'IP no especificada']);
    exit;
}

try {
    $connector = new NetworkPrintConnector($ip, 9100); // Puerto estándar ESC/POS
    $printer = new Printer($connector);
    
    $printer->text("=== PRUEBA DE COCINA ===\n");
    $printer->text("IP: $ip\n");
    $printer->text("Fecha: " . date('d/m/Y H:i:s') . "\n");
    $printer->text("✅ ¡Impresión exitosa!\n");
    $printer->feed(2);
    $printer->cut();
    $printer->close();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error al imprimir en cocina ($ip): " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>