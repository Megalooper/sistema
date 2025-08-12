<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
require_once('../../fpdf/fpdf.php');

$id_venta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_venta) {
    die("Venta no vįlida.");
}

// Obtener venta
$stmt_venta = $pdo->prepare("SELECT v.id_venta, v.fecha_venta, v.total_usd, v.total_bs, t.nombre AS turno FROM ventas v JOIN turnos t ON v.id_turno = t.id_turno WHERE v.id_venta = ?");
$stmt_venta->execute([$id_venta]);
$venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

// Obtener detalles de venta (productos y preparaciones)
$stmt_detalles = $pdo->prepare("
    SELECT 
        COALESCE(p.nombre, pr.nombre) AS nombre,
        dv.cantidad,
        dv.precio_unitario_usd,
        dv.precio_unitario_bs,
        dv.subtotal_usd,
        dv.subtotal_bs
    FROM detalles_venta dv
    LEFT JOIN productos p ON dv.id_producto = p.id_producto
    LEFT JOIN preparaciones pr ON dv.id_preparacion = pr.id_preparacion
    WHERE dv.id_venta = ?
");
$stmt_detalles->execute([$id_venta]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos del negocio
$usuario = $_SESSION['usuario'];
$nombre_empresa = $usuario['nombre_empresa'] ?? 'Mi Empresa';
$rif = $usuario['rif'] ?? '';
$direccion = $usuario['direccion'] ?? '';
$telefono = $usuario['telefono'] ?? '';
$logo = $usuario['logo'] ?? '';

// Calcular valores del IVA
$IVA_PERCENT = 16; // 16% de IVA
$base_imponible_bs = $venta['total_bs'] / (1 + ($IVA_PERCENT / 100));
$iva_bs = $base_imponible_bs * ($IVA_PERCENT / 100);

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 30);

// Logo y datos empresa centrados
if ($logo && file_exists("../../assets/img/logos/" . $logo)) {
    $pdf->Image("../../assets/img/logos/" . $logo, 90, 10, 30); // centrado
    $pdf->SetY(45);
} else {
    $pdf->SetY(20);
}
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, utf8_decode($nombre_empresa), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, utf8_decode("RIF: " . $rif), 0, 1, 'C');
$pdf->Cell(0, 6, utf8_decode("Dirección: " . $direccion), 0, 1, 'C');
$pdf->Cell(0, 6, utf8_decode("Teléfono: " . $telefono), 0, 1, 'C');
$pdf->Ln(8);

// Datos de la venta
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Venta #' . $venta['id_venta']), 0, 1, 'L');
$pdf->Cell(0, 6, utf8_decode('Turno: ' . $venta['turno']), 0, 1, 'L');
$pdf->Cell(0, 6, utf8_decode('Fecha: ' . date('d/m/Y H:i', strtotime($venta['fecha_venta']))), 0, 1, 'L');
$pdf->Ln(8);

// Tabla de productos/preparaciones
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 8, utf8_decode('Producto/Preparación'), 1, 0, 'C');
$pdf->Cell(20, 8, utf8_decode('Cantidad'), 1, 0, 'C');
$pdf->Cell(30, 8, utf8_decode('Precio USD'), 1, 0, 'C');
$pdf->Cell(30, 8, utf8_decode('Precio Bs'), 1, 0, 'C');
$pdf->Cell(25, 8, utf8_decode('Subtotal USD'), 1, 0, 'C');
$pdf->Cell(25, 8, utf8_decode('Subtotal Bs'), 1, 1, 'C');

$pdf->SetFont('Arial', '', 10);
foreach ($detalles as $d) {
    $pdf->Cell(60, 8, utf8_decode($d['nombre']), 1, 0, 'L');
    $pdf->Cell(20, 8, $d['cantidad'], 1, 0, 'C');
    $pdf->Cell(30, 8, '$' . number_format($d['precio_unitario_usd'], 2), 1, 0, 'R');
    $pdf->Cell(30, 8, 'Bs ' . number_format($d['precio_unitario_bs'], 2), 1, 0, 'R');
    $pdf->Cell(25, 8, '$' . number_format($d['subtotal_usd'], 2), 1, 0, 'R');
    $pdf->Cell(25, 8, 'Bs ' . number_format($d['subtotal_bs'], 2), 1, 1, 'R');
}

// Totales con IVA
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Subtotal USD: $' . number_format($venta['total_usd'], 2)), 0, 1, 'R');
$pdf->Cell(0, 8, utf8_decode('Base Imponible: Bs ' . number_format($base_imponible_bs, 2)), 0, 1, 'R');
$pdf->Cell(0, 8, utf8_decode('IVA (' . $IVA_PERCENT . '%): Bs ' . number_format($iva_bs, 2)), 0, 1, 'R');
$pdf->Cell(0, 8, utf8_decode('Total Bs: Bs ' . number_format($venta['total_bs'], 2)), 0, 1, 'R');

// Pie de pįgina con marca de agua DevSolutions
$pdf->SetY(-30);
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(180, 180, 180);
$pdf->Cell(0, 10, utf8_decode('Desarrollado por DevSolutions'), 0, 0, 'C');

$pdf->Output('venta_' . $venta['id_venta'] . '.pdf', 'I');
exit;