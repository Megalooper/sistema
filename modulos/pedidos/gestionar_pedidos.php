<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Obtener pedidos no cerrados (abierto o en espera)
$stmt = $pdo->prepare("SELECT p.id_pedido, p.numero_mesa, p.tipo_pedido, p.estado, p.fecha_apertura, SUM(dp.subtotal_bs) AS total_bs 
                       FROM pedidos p 
                       LEFT JOIN detalles_pedido dp ON p.id_pedido = dp.id_pedido 
                       WHERE p.estado IN ('abierto', 'en espera') 
                       GROUP BY p.id_pedido 
                       ORDER BY p.fecha_apertura DESC");
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejo de mensajes
$error = $success = "";
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);
if (isset($_GET['mensaje'])) $success = htmlspecialchars($_GET['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Pedidos - Comida Rápida</title>
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
        <li><a href="nuevo_pedido.php"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial de Pedidos</span></a></li>
        <li><a href="propinas_mes.php"><i class="fas fa-hand-holding-usd"></i><span class="nav-text">Propinas del Mes</span></a></li>
        <li><a href="deudas_delivery.php"><i class="fas fa-truck"></i><span class="nav-text">Deudas Delivery</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-tasks"></i> Gestionar Pedidos Activos</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-list"></i> Pedidos en Curso</h2>

            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>

            <?php if (empty($pedidos)): ?>
                <div class="no-resultados">
                    <i class="fas fa-box-open"></i>
                    <p>No hay pedidos activos.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="tabla-pedidos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mesa / Pedido</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Total Bs</th>
                                <th>Apertura</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <tr>
                                    <td><?= $p['id_pedido'] ?></td>
                                    <td><?= htmlspecialchars($p['numero_mesa']) ?></td>
                                    <td><?= ucfirst($p['tipo_pedido']) ?></td>
                                    <td class="estado-<?= strtolower($p['estado']) ?>">
                                        <?= ucfirst($p['estado']) ?>
                                    </td>
                                    <td>Bs <?= number_format($p['total_bs'] ?? 0, 2) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($p['fecha_apertura'])) ?></td>
                                    <td class="acciones">
                                        <!-- Botón Reanudar -->
                                        <a href="nuevo_pedido.php?id=<?= $p['id_pedido'] ?>" class="accion-btn editar" title="Reanudar Pedido">
                                            <i class="fas fa-play"></i>
                                        </a>
                                        <!-- Botón Cancelar -->
                                        <a href="cancelar_pedido.php?id=<?= $p['id_pedido'] ?>" class="accion-btn eliminar" title="Cancelar Pedido" onclick="return confirm('¿Estás seguro de cancelar este pedido? Esta acción no se puede deshacer.')">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>