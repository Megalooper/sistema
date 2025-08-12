<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Obtener propinas del mes actual
$mes = date('Y-m');
$stmt = $pdo->prepare("SELECT 
    p.fecha,
    t.nombre AS turno,
    SUM(p.monto_bs) AS total_bs
FROM propinas p
JOIN turnos t ON p.id_turno = t.id_turno
WHERE DATE_FORMAT(p.fecha, '%Y-%m') = ?
GROUP BY p.fecha, p.id_turno
ORDER BY p.fecha DESC, t.id_turno");
$stmt->execute([$mes]);
$propinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total del mes
$stmt_total = $pdo->prepare("SELECT SUM(monto_bs) AS total FROM propinas WHERE DATE_FORMAT(fecha, '%Y-%m') = ?");
$stmt_total->execute([$mes]);
$total_mes = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Propinas del Mes - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
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
        <li><a href="../pedidos/nuevo_pedido.php"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li><a href="../pedidos/gestionar_pedidos.php"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial de Pedidos</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-hand-holding-usd"></i><span class="nav-text">Propinas del Mes</span></a></li>
        <li><a href="deudas_delivery.php"><i class="fas fa-truck"></i><span class="nav-text">Deudas Delivery</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-hand-holding-usd"></i> Propinas del Mes</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-hand-holding-usd"></i> Propinas - <?= date('F Y') ?></h2>
            <div class="totales">
                <h3>Total del Mes: Bs <?= number_format($total_mes, 2) ?></h3>
            </div>

            <?php if (empty($propinas)): ?>
                <div class="no-resultados">
                    <i class="fas fa-box-open"></i>
                    <p>No hay propinas registradas este mes.</p>
                </div>
            <?php else: ?>
                <table class="tabla-pedidos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Total Bs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($propinas as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                                <td><?= htmlspecialchars($p['turno']) ?></td>
                                <td>Bs <?= number_format($p['total_bs'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>