<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$tipo = $_POST['tipo'] ?? $_GET['tipo'];
$fecha_inicio = $_POST['fecha_inicio'] ?? $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? $_GET['fecha_fin'] ?? null;
$mes = $_POST['mes'] ?? $_GET['mes'] ?? null;
$anio = $_POST['anio'] ?? $_GET['anio'] ?? null;
$tipo_movimiento = $_POST['tipo_movimiento'] ?? $_GET['tipo_movimiento'] ?? null;

// Validar tipo
$tipos_validos = ['ventas', 'inventario', 'deliverys', 'propinas'];
if (!in_array($tipo, $tipos_validos)) {
    die("Tipo de reporte no válido.");
}

// Generar condiciones
$condiciones = [];
$params = [];

if ($tipo === 'ventas') {
    if ($fecha_inicio) {
        $condiciones[] = "v.fecha_cierre >= ?";
        $params[] = "$fecha_inicio 00:00:00";
    }
    if ($fecha_fin) {
        $condiciones[] = "v.fecha_cierre <= ?";
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
    $sql = "SELECT p.id_pedido, p.total_usd, p.total_bs, p.fecha_cierre FROM pedidos p $where ORDER BY p.fecha_cierre DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($datos as $d) {
        $total_usd += $d['total_usd'];
        $total_bs += $d['total_bs'];
    }
} elseif ($tipo === 'inventario') {
    $sql = "SELECT im.*, p.nombre AS nombre_producto, u.nombre AS nombre_usuario FROM inventario_movimientos im JOIN productos p ON im.id_producto = p.id_producto JOIN usuarios u ON im.id_usuario = u.id_usuario $where ORDER BY im.fecha_movimiento DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($tipo === 'deliverys') {
    $sql = "SELECT d.*, p.numero_mesa, p.direccion_delivery FROM deliverys d JOIN pedidos p ON d.id_pedido = p.id_pedido $where ORDER BY d.fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($datos as $d) {
        $total_usd += $d['costo_usd'];
        $total_bs += $d['costo_bs'];
    }
} elseif ($tipo === 'propinas') {
    $sql = "SELECT pr.*, p.id_pedido, u.nombre AS nombre_usuario FROM propinas pr JOIN pedidos p ON pr.id_pedido = p.id_pedido JOIN usuarios u ON pr.id_turno = u.turno $where ORDER BY pr.fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($datos as $d) {
        $total_usd += $d['monto_bs'] / (new PDO('mysql:host=localhost;dbname=bd_comida_rapida', 'root', ''))->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1")->fetchColumn();
        $total_bs += $d['monto_bs'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de <?= ucfirst($tipo) ?></title>
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .reporte-header { text-align: center; margin-bottom: 2rem; }
        .reporte-header img { width: 80px; margin-bottom: 10px; }
        .reporte-title { margin: 0; color: #2c3e50; }
        .resumen { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .tabla-reporte { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .tabla-reporte th, .tabla-reporte td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .tabla-reporte th { background: #007bff; color: white; }
        .footer-reporte { text-align: center; margin-top: 3rem; color: #666; font-size: 0.9rem; }
        .acciones { text-align: center; margin: 2rem 0; }
        .btn { padding: 10px 20px; margin: 0 10px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-pdf { background: #d9534f; color: white; }
        .btn-email { background: #5cb85c; color: white; }
    </style>
</head>
<body>

<div class="reporte-header">
    <img src="../../assets/img/logo.png" alt="Logo"> <!-- Asegúrate de tener el logo -->
    <h1 class="reporte-title">Reporte de <?= ucfirst($tipo) ?></h1>
    <p><?= $fecha_inicio ? "Del $fecha_inicio al $fecha_fin" : ($mes ? "Mes: " . date('F', mktime(0, 0, 0, $mes)) . " $anio" : '') ?></p>
</div>

<div class="resumen">
    <strong>Total USD:</strong> $<?= number_format($total_usd, 2) ?> |
    <strong>Total Bs:</strong> Bs<?= number_format($total_bs, 2) ?> |
    <strong>Total Registros:</strong> <?= count($datos) ?>
</div>

<table class="tabla-reporte">
    <thead>
        <tr>
            <?php if ($tipo === 'ventas'): ?>
                <th>ID Pedido</th>
                <th>Total USD</th>
                <th>Total Bs</th>
                <th>Fecha</th>
            <?php elseif ($tipo === 'inventario'): ?>
                <th>Producto</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Usuario</th>
                <th>Fecha</th>
            <?php elseif ($tipo === 'deliverys'): ?>
                <th>ID Pedido</th>
                <th>Costo USD</th>
                <th>Costo Bs</th>
                <th>Dirección</th>
                <th>Fecha</th>
            <?php elseif ($tipo === 'propinas'): ?>
                <th>ID Pedido</th>
                <th>Monto Bs</th>
                <th>Usuario</th>
                <th>Fecha</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($datos as $d): ?>
        <tr>
            <?php if ($tipo === 'ventas'): ?>
                <td><?= $d['id_pedido'] ?></td>
                <td>$<?= number_format($d['total_usd'], 2) ?></td>
                <td>Bs<?= number_format($d['total_bs'], 2) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($d['fecha_cierre'])) ?></td>
            <?php elseif ($tipo === 'inventario'): ?>
                <td><?= htmlspecialchars($d['nombre_producto']) ?></td>
                <td><?= ucfirst($d['tipo']) ?></td>
                <td><?= $d['cantidad'] ?></td>
                <td><?= htmlspecialchars($d['nombre_usuario']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($d['fecha_movimiento'])) ?></td>
            <?php elseif ($tipo === 'deliverys'): ?>
                <td><?= $d['id_pedido'] ?></td>
                <td>$<?= number_format($d['costo_usd'], 2) ?></td>
                <td>Bs<?= number_format($d['costo_bs'], 2) ?></td>
                <td><?= htmlspecialchars($d['direccion_delivery']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($d['fecha'])) ?></td>
            <?php elseif ($tipo === 'propinas'): ?>
                <td><?= $d['id_pedido'] ?></td>
                <td>Bs<?= number_format($d['monto_bs'], 2) ?></td>
                <td><?= htmlspecialchars($d['nombre_usuario']) ?></td>
                <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="footer-reporte">
    <p>© <?= date('Y') ?> DevSolutions. Todos los derechos reservados.</p>
</div>

<div class="acciones">
    <button class="btn btn-pdf" onclick="generarPDF()">Generar PDF</button>
    <button class="btn btn-email" onclick="enviarPorCorreo()">Enviar por Correo</button>
</div>

<script>
function generarPDF() {
    const url = new URL('generar_pdf.php', window.location.origin + '<?= dirname($_SERVER['PHP_SELF']) ?>');
    <?php foreach ($_POST as $key => $value): ?>
    url.searchParams.append('<?= $key ?>', '<?= $value ?>');
    <?php endforeach; ?>
    window.open(url, '_blank');
}

function enviarPorCorreo() {
    const email = prompt("Ingrese el correo electrónico:");
    if (!email) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'enviar_pdf.php';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'email';
    input.value = email;
    <?php foreach ($_POST as $key => $value): ?>
    const input<?= $key ?> = document.createElement('input');
    input<?= $key ?>.type = 'hidden';
    input<?= $key ?>.name = '<?= $key ?>';
    input<?= $key ?>.value = '<?= $value ?>';
    form.appendChild(input<?= $key ?>);
    <?php endforeach; ?>
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>

</body>
</html>