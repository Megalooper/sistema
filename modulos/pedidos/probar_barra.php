<?php
session_start();
if (!isset($_SESSION['usuario'])) exit;

require_once '../../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

$impresora = $_SESSION['usuario']['impresora_barra'] ?? 'XP-58';

try {
    $connector = new WindowsPrintConnector("smb://localhost/$impresora");
    $printer = new Printer($connector);
    $printer->text("✅ Prueba de impresora: $impresora\n");
    $printer->text("Fecha: " . date('Y-m-d H:i:s') . "\n");
    $printer->cut();
    $printer->close();
    echo "✅ Impresión exitosa en $impresora";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>