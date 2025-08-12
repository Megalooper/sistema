<?php
// Paso 1: Limpiar cualquier salida previa (incluyendo errores)
ob_clean();
error_reporting(0); // Desactivar todos los errores (solo para producción)
ini_set('display_errors', 'Off');

// Paso 2: Asegurar que solo se devuelva JSON
header('Content-Type: application/json; charset=utf-8');

// Paso 3: Iniciar sesión
session_start();
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Paso 4: Ruta absoluta al autoload.php
// Ajusta esta ruta según la ubicación real de tu proyecto
$baseDir = $_SERVER['DOCUMENT_ROOT']; // Raíz del servidor (ej: C:/xampp/htdocs)

// Ajusta esta ruta según tu estructura
// Ej: Si tu proyecto se llama "Sistema_venta_comida_rapida"
$projectName = 'Sistema_venta_comida_rapida'; // Cambia esto si tu carpeta tiene otro nombre
$vendorPath = $baseDir . '/' . $projectName . '/vendor/autoload.php';

// Si no está ahí, prueba con una ruta relativa segura
if (!file_exists($vendorPath)) {
    // Alternativa: desde el directorio actual
    $vendorPath = __DIR__ . '/../../vendor/autoload.php'; // Ajusta según necesidad
}

// Paso 5: Intentar cargar el autoloader
if (!file_exists($vendorPath)) {
    echo json_encode([
        'success' => false,
        'error' => 'No se encontró autoload.php',
        'ruta' => $vendorPath,
        'document_root' => $baseDir,
        'proyecto' => $projectName
    ]);
    exit;
}

// Incluir sin mostrar errores
if (!@require_once $vendorPath) {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo cargar la librería escpos-php. Verifica que esté instalada con Composer.'
    ]);
    exit;
}

// Paso 6: Importar clases
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

// Paso 7: Recibir datos
$input = json_decode(file_get_contents('php://input'), true);
$puerto = $input['impresora'] ?? '';

if (empty($puerto)) {
    echo json_encode(['success' => false, 'error' => 'Puerto no especificado']);
    exit;
}

// Paso 8: Intentar imprimir
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

try {
    $connector = new WindowsPrintConnector("ImpresoraCaja"); // Nombre compartido
    $printer = new Printer($connector);
    $printer->text("✅ ¡Impresión por red!\n");
    $printer->cut();
    $printer->close();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>