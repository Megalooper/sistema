<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

$input = json_decode(file_get_contents('php://input'), true);

$productos = $input['productos'] ?? [];
if (empty($productos)) {
    echo json_encode(['success' => false, 'error' => 'No hay productos para cocina']);
    exit;
}

$ip = $input['ip'] ?? '';
if (empty($ip)) {
    echo json_encode(['success' => false, 'error' => 'No hay IP de impresora']);
    exit;
}

try {
    $connector = new NetworkPrintConnector($ip, 9100);
    $printer = new Printer($connector);

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("=== PEDIDO COCINA ===\n");
    $printer->setJustification(Printer::JUSTIFY_LEFT);

    $printer->text("Turno: {$input['turno_nombre']}\n");
    $printer->text("Fecha: " . date('d/m/Y H:i:s') . "\n");

    if ($input['tipo_pedido'] === 'mesa') {
        $printer->text("Mesa: {$input['numero_mesa']}\n");
    } else {
        $printer->text("Delivery\n");
        if (!empty($input['direccion_delivery'])) $printer->text("Dir: {$input['direccion_delivery']}\n");
        if (!empty($input['telefono_cliente'])) $printer->text("Tel: {$input['telefono_cliente']}\n");
    }
    $printer->text(str_repeat("-", 32) . "\n");

    foreach ($productos as $p) {
        $printer->text("  {$p['cantidad']} x {$p['nombre']}\n");
        if (!empty($p['observaciones'])) {
            $printer->text("    Obs: {$p['observaciones']}\n");
        }
        $printer->text("||\n||\n");
    }

    $printer->feed(2);
    $printer->cut();
    $printer->close();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error imprimir cocina: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>