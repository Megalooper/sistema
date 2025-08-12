<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

$id_venta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_venta) {
    die("Venta no válida");
}

// Obtener venta
$stmt_venta = $pdo->prepare("SELECT v.*, t.nombre AS turno, u.nombre_empresa, u.rif, u.direccion, u.telefono 
                            FROM ventas v 
                            JOIN turnos t ON v.id_turno = t.id_turno 
                            JOIN usuarios u ON v.id_usuario = u.id_usuario 
                            WHERE v.id_venta = ?");
$stmt_venta->execute([$id_venta]);
$venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    die("Venta no encontrada");
}

// Obtener detalles
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

// Generar PDF
require_once('../../fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 30);

// Logo y datos empresa
if (!empty($venta['logo']) && file_exists("../../assets/img/logos/" . $venta['logo'])) {
    $pdf->Image("../../assets/img/logos/" . $venta['logo'], 90, 10, 30);
    $pdf->SetY(45);
} else {
    $pdf->SetY(20);
}
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, utf8_decode($venta['nombre_empresa']), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, utf8_decode("RIF: " . $venta['rif']), 0, 1, 'C');
$pdf->Cell(0, 6, utf8_decode("Dirección: " . $venta['direccion']), 0, 1, 'C');
$pdf->Cell(0, 6, utf8_decode("Teléfono: " . $venta['telefono']), 0, 1, 'C');
$pdf->Ln(8);

// Datos de la venta
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Venta #' . $venta['id_venta']), 0, 1, 'L');
$pdf->Cell(0, 6, utf8_decode('Turno: ' . $venta['turno']), 0, 1, 'L');
$pdf->Cell(0, 6, utf8_decode('Fecha: ' . date('d/m/Y H:i', strtotime($venta['fecha_venta']))), 0, 1, 'L');
$pdf->Ln(8);

// Tabla de productos/preparaciones
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(100, 8, utf8_decode('Producto/Preparación'), 1, 0, 'C');
$pdf->Cell(25, 8, utf8_decode('Cantidad'), 1, 0, 'C');
$pdf->Cell(30, 8, utf8_decode('Precio Bs'), 1, 0, 'C');
$pdf->Cell(35, 8, utf8_decode('Subtotal Bs'), 1, 1, 'C');

$pdf->SetFont('Arial', '', 10);
foreach ($detalles as $d) {
    $pdf->Cell(100, 8, utf8_decode($d['nombre']), 1, 0, 'L');
    $pdf->Cell(25, 8, $d['cantidad'], 1, 0, 'C');
    $pdf->Cell(30, 8, 'Bs ' . number_format($d['precio_unitario_bs'], 2), 1, 0, 'R');
    $pdf->Cell(35, 8, 'Bs ' . number_format($d['subtotal_bs'], 2), 1, 1, 'R');
}

// Totales con IVA
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('Base Imponible: Bs ' . number_format($venta['base_imponible'], 2)), 0, 1, 'R');
$pdf->Cell(0, 8, utf8_decode('IVA (16%): Bs ' . number_format($venta['iva'], 2)), 0, 1, 'R');
$pdf->Cell(0, 8, utf8_decode('Total Bs: Bs ' . number_format($venta['total_bs'], 2)), 0, 1, 'R');

// Pie de página
$pdf->SetY(-30);
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(180, 180, 180);
$pdf->Cell(0, 10, utf8_decode('Desarrollado por DevSolutions'), 0, 0, 'C');

$pdf->Output('venta_' . $venta['id_venta'] . '.pdf', 'I');
exit;