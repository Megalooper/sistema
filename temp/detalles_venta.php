<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_venta = filter_input(INPUT_GET, 'id_venta', FILTER_VALIDATE_INT);
if (!$id_venta) {
    header("Location: nueva_venta.php");
    exit;
}

// Obtener venta
$stmt_venta = $pdo->prepare("SELECT v.id_venta, v.fecha_venta, v.total_usd, v.total_bs, t.nombre AS turno FROM ventas v JOIN turnos t ON v.id_turno = t.id_turno WHERE v.id_venta = ?");
$stmt_venta->execute([$id_venta]);
$venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    header("Location: nueva_venta.php?error=Venta no encontrada.");
    exit;
}

// Obtener detalles (productos y preparaciones)
$stmt_detalles = $pdo->prepare("
    SELECT 
        COALESCE(p.nombre, pr.nombre) AS producto,
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de Venta #<?= $venta['id_venta'] ?></title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-ventas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
        <span class="username"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
    </div>
    <ul class="menu">
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-receipt"></i><span class="nav-text">Detalles de Venta</span></a></li>
        <li><a href="nueva_venta.php"><i class="fas fa-shopping-cart"></i><span class="nav-text">Nueva Venta</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial de Ventas</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-receipt"></i> Detalles de Venta #<?= $venta['id_venta'] ?></h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-info-circle"></i> Información de la Venta</h2>
            <div class="venta-info" style="display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 2rem;">
                <div class="info-item"><i class="fas fa-clock"></i><p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></p></div>
                <div class="info-item"><i class="fas fa-layer-group"></i><p><strong>Turno:</strong> <?= htmlspecialchars($venta['turno']) ?></p></div>
                <div class="info-item"><i class="fas fa-money-bill"></i><p><strong>Total USD:</strong> $<?= number_format($venta['total_usd'], 2) ?></p></div>
                <div class="info-item"><i class="fas fa-money-bill-wave"></i><p><strong>Total Bs:</strong> Bs <?= number_format($venta['total_bs'], 2) ?></p></div>
            </div>

            <h3><i class="fas fa-boxes"></i> Productos y Preparaciones Vendidos</h3>
            <table class="tabla-ventas" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Producto/Preparación</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario USD</th>
                        <th>Precio Unitario Bs</th>
                        <th>Subtotal USD</th>
                        <th>Subtotal Bs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['producto']) ?></td>
                            <td><?= $d['cantidad'] ?></td>
                            <td>$<?= number_format($d['precio_unitario_usd'], 2) ?></td>
                            <td>Bs <?= number_format($d['precio_unitario_bs'], 2) ?></td>
                            <td>$<?= number_format($d['subtotal_usd'], 2) ?></td>
                            <td>Bs <?= number_format($d['subtotal_bs'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong>Total</strong></td>
                        <td><strong>$<?= number_format($venta['total_usd'], 2) ?></strong></td>
                        <td><strong>Bs <?= number_format($venta['total_bs'], 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="form-actions" style="margin-top:2rem;">
                <a href="imprimir_venta.php?id=<?= $venta['id_venta'] ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-print"></i> Imprimir
                </a>
                <a href="nueva_venta.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Nueva Venta
                </a>
            </div>
        </section>
    </main>
</div>

</body>
</html>