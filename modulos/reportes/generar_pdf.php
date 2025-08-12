<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    die("Acceso no autorizado.");
}

require_once '../../config/db.php';
require_once '../../fpdf/fpdf.php'; // Asegúrate de que la ruta sea correcta

$tipo = $_POST['tipo'] ?? $_GET['tipo'];
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;
$mes = $_POST['mes'] ?? null;
$anio = $_POST['anio'] ?? null;
$tipo_movimiento = $_POST['tipo_movimiento'] ?? null;

// Validar tipo
$tipos_validos = ['ventas', 'inventario', 'deliverys', 'propinas'];
if (!in_array($tipo, $tipos_validos)) {
    die("Tipo de reporte no válido.");
}

// === OBTENER LOGO Y DATOS DE LA EMPRESA ===
$stmt_empresa = $pdo->prepare("SELECT nombre_empresa, logo FROM usuarios WHERE id_usuario = ? LIMIT 1");
$stmt_empresa->execute([$_SESSION['usuario']['id_usuario']]);
$empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

$nombre_empresa = $empresa ? ($empresa['nombre_empresa'] ?: 'Comida Rápida') : 'Comida Rápida';
$logo_url = null;

if ($empresa && $empresa['logo']) {
    $logo_path = "../../assets/img/logos/" . $empresa['logo'];
    if (file_exists($logo_path)) {
        $logo_url = $logo_path;
    }
}

// Fallback al logo genérico
if (!$logo_url) {
    $logo_path_default = "../../assets/img/logos/logo_empresa.png";
    if (file_exists($logo_path_default)) {
        $logo_url = $logo_path_default;
    }
}

// Preparar condiciones
$condiciones = [];
$params = [];

if ($tipo === 'ventas') {
    if ($fecha_inicio) {
        $condiciones[] = "p.fecha_cierre >= ?";
        $params[] = "$fecha_inicio 00:00:00";
    }
    if ($fecha_fin) {
        $condiciones[] = "p.fecha_cierre <= ?";
        $params[] = "$fecha_fin 23:59:59";
    }
} elseif ($tipo === 'inventario') {
    if ($tipo_movimiento) {
        $condiciones[] = "im.tipo = ?";
        $params[] = $tipo_movimiento;
    }
    if ($fecha_inicio) {
        $condiciones[] = "im.fecha_movimiento >= ?";
        $params[] = "$fecha_inicio 00:00:00";
    }
    if ($fecha_fin) {
        $condiciones[] = "im.fecha_movimiento <= ?";
        $params[] = "$fecha_fin 23:59:59";
    }
} elseif ($tipo === 'deliverys' || $tipo === 'propinas') {
    $mes = (int)$mes;
    $anio = (int)$anio;
    $condiciones[] = "MONTH(p.fecha_cierre) = ? AND YEAR(p.fecha_cierre) = ?";
    $params[] = $mes;
    $params[] = $anio;
}

$where = !empty($condiciones) ? "WHERE " . implode(" AND ", $condiciones) : "";

// Obtener datos
$datos = [];
$total_usd = 0;
$total_bs = 0;

if ($tipo === 'ventas') {
    $sql = "SELECT p.id_pedido, p.total_usd, p.total_bs, p.fecha_cierre FROM pedidos p $where AND p.estado = 'cerrado' ORDER BY p.fecha_cierre DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($datos as $d) {
        $total_usd += $d['total_usd'];
        $total_bs += $d['total_bs'];
    }
} elseif ($tipo === 'inventario') {
    $sql = "SELECT im.*, p.nombre AS nombre_producto, u.nombre AS nombre_usuario 
            FROM inventario_movimientos im 
            JOIN productos p ON im.id_producto = p.id_producto 
            JOIN usuarios u ON im.id_usuario = u.id_usuario $where 
            ORDER BY im.fecha_movimiento DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($tipo === 'deliverys') {
    $sql = "SELECT d.*, p.numero_mesa, p.direccion_delivery 
            FROM deliverys d 
            JOIN pedidos p ON d.id_pedido = p.id_pedido $where 
            ORDER BY d.fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($datos as $d) {
        $total_usd += $d['costo_usd'];
        $total_bs += $d['costo_bs'];
    }
} elseif ($tipo === 'propinas') {
    $sql = "SELECT pr.*, p.id_pedido, u.nombre AS nombre_usuario, t.nombre AS nombre_turno
            FROM propinas pr 
            JOIN pedidos p ON pr.id_pedido = p.id_pedido 
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            JOIN turnos t ON pr.id_turno = t.id_turno
            $where 
            ORDER BY pr.fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_tasa = $pdo->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
    $tasa = $stmt_tasa->fetch(PDO::FETCH_ASSOC);
    $valor_dolar = $tasa ? $tasa['valor_dolar'] : 1;

    foreach ($datos as $d) {
        $total_bs += $d['monto_bs'];
        $total_usd += $d['monto_bs'] / $valor_dolar;
    }
}

// Crear PDF
$pdf = new FPDF('P', 'mm', 'A4'); // 'P' = portrait
$pdf->AddPage();
$pdf->SetMargins(15, 25, 15); // Izquierda, arriba, derecha
$pdf->SetAutoPageBreak(true, 30); // Margen inferior

// === ENCABEZADO ===
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(44, 62, 80);

$y = 25;

// Logo centrado
if ($logo_url) {
    $width = 50;
    $x = (297 - $width) / 3.1; // A4 Landscape: 297mm
    $pdf->Image($logo_url, $x, $y, $width);
    $y += $width * 1;
}

// Nombre de la empresa
$pdf->SetY($y);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', $nombre_empresa), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(52, 152, 219);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', 'Reporte de ' . ucfirst($tipo)), 0, 1, 'C');

// Filtros
$pdf->SetFont('Arial', 'I', 11);
$pdf->SetTextColor(100, 100, 100);
$filtro_texto = "";
if ($fecha_inicio) $filtro_texto .= "Del $fecha_inicio ";
if ($fecha_fin) $filtro_texto .= "al $fecha_fin";
if ($mes) $filtro_texto .= "Mes: " . date('F', mktime(0, 0, 0, $mes)) . " $anio";

if ($filtro_texto) {
    $pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1', $filtro_texto), 0, 1, 'C');
}
$pdf->Ln(10);

// === TABLA DE DATOS ===
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(52, 152, 219);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(52, 152, 219);

if ($tipo === 'ventas') {
    $pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'ID Pedido'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', 'Total USD'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', 'Total Bs'), 1, 0, 'C', true);
    $pdf->Cell(60, 10, iconv('UTF-8', 'ISO-8859-1', 'Fecha'), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    foreach ($datos as $d) {
        $pdf->Cell(40, 10, $d['id_pedido'], 1, 0, 'C');
        $pdf->Cell(50, 10, '$' . number_format($d['total_usd'], 2), 1, 0, 'C');
        $pdf->Cell(50, 10, 'Bs' . number_format($d['total_bs'], 2), 1, 0, 'C');
        $pdf->Cell(60, 10, date('d/m/Y H:i', strtotime($d['fecha_cierre'])), 1, 1, 'C');
    }
} elseif ($tipo === 'inventario') {
    $pdf->Cell(30, 10, iconv('UTF-8', 'ISO-8859-1', 'ID'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', 'Producto'), 1, 0, 'C', true);
    $pdf->Cell(30, 10, iconv('UTF-8', 'ISO-8859-1', 'Tipo'), 1, 0, 'C', true);
    $pdf->Cell(20, 10, iconv('UTF-8', 'ISO-8859-1', 'Cant'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', 'Usuario'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', 'Fecha'), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    foreach ($datos as $d) {
        $pdf->Cell(30, 10, $d['id_movimiento'], 1, 0, 'C');
        $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', $d['nombre_producto']), 1, 0);
        $pdf->Cell(30, 10, iconv('UTF-8', 'ISO-8859-1', ucfirst($d['tipo'])), 1, 0, 'C');
        $pdf->Cell(20, 10, $d['cantidad'], 1, 0, 'C');
        $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', $d['nombre_usuario']), 1, 0);
        $pdf->Cell(50, 10, date('d/m/Y H:i', strtotime($d['fecha_movimiento'])), 1, 1, 'C');
    }
} elseif ($tipo === 'deliverys') {
    $pdf->Cell(20, 10, iconv('UTF-8', 'ISO-8859-1', 'ID Pedido'), 1, 0, 'C', true);
    $pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Costo USD'), 1, 0, 'C', true);
    $pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Costo Bs'), 1, 0, 'C', true);
    $pdf->Cell(70, 10, iconv('UTF-8', 'ISO-8859-1', 'Dirección'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', 'Fecha'), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    foreach ($datos as $d) {
        $pdf->Cell(30, 10, $d['id_pedido'], 1, 0, 'C');
        $pdf->Cell(40, 10, '$' . number_format($d['costo_usd'], 2), 1, 0, 'C');
        $pdf->Cell(40, 10, 'Bs' . number_format($d['costo_bs'], 2), 1, 0, 'C');
        $pdf->Cell(80, 10, iconv('UTF-8', 'ISO-8859-1', $d['direccion_delivery']), 1, 0);
        $pdf->Cell(50, 10, date('d/m/Y H:i', strtotime($d['fecha'])), 1, 1, 'C');
    }
} elseif ($tipo === 'propinas') {
    $pdf->Cell(20, 10, iconv('UTF-8', 'ISO-8859-1', 'ID Pedido'), 1, 0, 'C', true);
    $pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Monto Bs'), 1, 0, 'C', true);
    $pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Usuario'), 1, 0, 'C', true);
    $pdf->Cell(30, 10, iconv('UTF-8', 'ISO-8859-1', 'Turno'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', 'Fecha'), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    foreach ($datos as $d) {
        $pdf->Cell(30, 10, $d['id_pedido'], 1, 0, 'C');
        $pdf->Cell(40, 10, 'Bs' . number_format($d['monto_bs'], 2), 1, 0, 'C');
        $pdf->Cell(50, 10, iconv('UTF-8', 'ISO-8859-1', $d['nombre_usuario']), 1, 0);
        $pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', $d['nombre_turno']), 1, 0, 'C');
        $pdf->Cell(50, 10, date('d/m/Y', strtotime($d['fecha'])), 1, 1, 'C');
    }
}

$pdf->Ln(10);

// === TOTALES ===
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', 'Total USD: $' . number_format($total_usd, 2) . ' | Total Bs: Bs' . number_format($total_bs, 2)), 0, 1, 'R');

// === FOOTER ===
$pdf->SetY(257);
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', '© ' . date('Y') . ' DevSolutions. Todos los derechos reservados.'), 0, 1, 'C');

// Salida del PDF
$pdf->Output('reporte_' . $tipo . '_' . date('Ymd') . '.pdf', 'I');