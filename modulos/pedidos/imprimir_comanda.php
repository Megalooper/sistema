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
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

$input = json_decode(file_get_contents('php://input'), true);

$id_pedido = $input['id_pedido'] ?? '';
$tipo_pedido = $input['tipo_pedido'] ?? '';
$numero_mesa = $input['numero_mesa'] ?? '';
$direccion_delivery = $input['direccion_delivery'] ?? '';
$telefono_cliente = $input['telefono_cliente'] ?? '';
$productos = $input['productos'] ?? [];
$turno_id = $input['turno'] ?? '';
$ip_cocina = $input['ip_cocina'] ?? '';
$impresora_barra = $input['impresora_barra'] ?? '';

// Validar que haya productos
if (empty($productos)) {
    echo json_encode(['success' => false, 'error' => 'No hay productos para imprimir']);
    exit;
}

// Conexión a la base de datos
try {
    require_once __DIR__ . '/../../config/db.php';
} catch (Exception $e) {
    error_log("Error de conexión a BD: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
    exit;
}

// Obtener nombre del turno
try {
    $stmt = $pdo->prepare("SELECT nombre FROM turnos WHERE id_turno = ?");
    $stmt->execute([$turno_id]);
    $turno_nombre = $stmt->fetchColumn() ?? 'Desconocido';
} catch (Exception $e) {
    error_log("Error al obtener turno: " . $e->getMessage());
    $turno_nombre = 'Desconocido';
}

// === OBTENER ÁREA DE CADA PRODUCTO/ELABORADO DESDE LA BASE DE DATOS ===
$productos_con_area = [];
$i1 = 0;
$i2 = 0;
$i3 = 0;
$i4 = 0;
foreach ($productos as $p) {
    $area = null;

    if ($p['tipo'] === 'producto' && isset($p['id'])) {
        $stmt = $pdo->prepare("
            SELECT a.id_area 
            FROM productos p 
            JOIN categorias c ON p.id_categoria = c.id_categoria 
            JOIN areas a ON c.id_area = a.id_area 
            WHERE p.id_producto = ?
        ");
        $stmt->execute([$p['id']]);
        $area = $stmt->fetchColumn();
        $i1++;
    } 
    elseif ($p['tipo'] === 'preparacion' && isset($p['id'])) {
        $stmt = $pdo->prepare("
            SELECT a.id_area 
            FROM preparaciones prep 
            JOIN categorias c ON prep.id_categoria = c.id_categoria 
            JOIN areas a ON c.id_area = a.id_area 
            WHERE prep.id_preparacion = ?
        ");
        $stmt->execute([$p['id']]);
        $area = $stmt->fetchColumn();
    }

    // Si no se encontró área, asignar por defecto (ej: cocina)
    $area = $area ?: 1;

    $productos_con_area[] = [
        'cantidad' => $p['cantidad'],
        'nombre' => $p['nombre'],
        'precio' => $p['precio'],
        'tipo' => $p['tipo'],
        'observaciones' => $p['observaciones'] ?? null,
        'area' => (int)$area
    ];
}

// === DIVIDIR PRODUCTOS POR ÁREA ===
$productos_area_1 = array_filter($productos_con_area, fn($p) => $p['area'] == 1); // Barra
$productos_area_2 = array_filter($productos_con_area, fn($p) => $p['area'] == 2); // Cocina

$respuestas = [];

// === 1. IMPRIMIR EN COCINA (Área 2) ===
if (!empty($productos_area_2) && !empty($ip_cocina)) {
    try {
        $connector = new NetworkPrintConnector($ip_cocina, 9100);
        $printer = new Printer($connector);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("=== PEDIDO COCINA ===\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);

        $printer->text("Turno: $turno_nombre\n");
        $printer->text("Fecha: " . date('d/m/Y H:i:s') . "\n");

        if ($tipo_pedido === 'mesa') {
            $printer->text("Mesa: $numero_mesa\n");
        } else {
            $printer->text("Delivery\n");
            if (!empty($direccion_delivery)) $printer->text("Dir: $direccion_delivery\n");
            if (!empty($telefono_cliente)) $printer->text("Tel: $telefono_cliente\n");
        }
        $printer->text(str_repeat("-", 96) . "\n");

        foreach ($productos_area_2 as $p) {
            $printer->text("  {$p['cantidad']} x {$p['nombre']}\n");
            if (!empty($p['observaciones'])) {
                $printer->text("    Obs: {$p['observaciones']}\n");
            }
            $printer->text("||\n");
        }
        $printer->text(str_repeat("-", 96) . "\n\n\n\n");

        $printer->feed(2);
        $printer->cut();
        $printer->close();

        $respuestas[] = "Cocina: OK";
    } catch (Exception $e) {
        error_log("Error imprimir cocina: " . $e->getMessage());
        $respuestas[] = "Cocina: ERROR - " . $e->getMessage();
    }
}

// === 2. IMPRIMIR EN BARRA (Área 1) ===

if (!empty($productos_area_1) && !empty($impresora_barra)) {
    try {
        $connector = new WindowsPrintConnector("smb://localhost/$impresora_barra");
        $printer = new Printer($connector);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("=== PEDIDO BARRA ===\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);

        $printer->text("Turno: $turno_nombre\n");
        $printer->text("Fecha: " . date('d/m/Y H:i:s') . "\n");

        if ($tipo_pedido === 'mesa') {
            $printer->text("Mesa: $numero_mesa\n");
        } else {
            $printer->text("Delivery\n");
            if (!empty($direccion_delivery)) $printer->text("Dir: $direccion_delivery\n");
            if (!empty($telefono_cliente)) $printer->text("Tel: $telefono_cliente\n");
        }
        $printer->text(str_repeat("-", 64) . "\n");

        foreach ($productos_area_1 as $p) {
            $printer->text("  {$p['cantidad']} x {$p['nombre']}\n");
            if (!empty($p['observaciones'])) {
                $printer->text("    Obs: {$p['observaciones']}\n");
            }
                $printer->text("||\n");
            
        }
        $printer->text(str_repeat("-", 64) . "\n\n\n\n\n");

        $printer->feed(2);
        $printer->cut();
        $printer->close();

        $respuestas[] = "Barra: OK";
    } catch (Exception $e) {
        error_log("Error imprimir barra: " . $e->getMessage());
        $respuestas[] = "Barra: ERROR - " . $e->getMessage();
    }
}

// === RESPUESTA FINAL ===
if (empty($respuestas)) {
    echo json_encode([
        'success' => false,
        'error' => 'No se encontraron productos para imprimir o no hay impresoras configuradas.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Comanda enviada a cocina y barra.',
        'detalles' => $respuestas,
        'productos_cocina' => count($productos_area_2),
        'productos_barra' => count($productos_area_1)
    ]);
}
?>