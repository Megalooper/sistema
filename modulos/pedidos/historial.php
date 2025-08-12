<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Consulta
$stmt = $pdo->prepare("SELECT 
    p.id_pedido,
    p.tipo_pedido,
    p.numero_mesa,
    p.total_usd,
    p.total_bs,
    p.fecha_apertura,
    u.nombre as vendedor
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    WHERE p.estado = 'cerrado'
    AND DATE(p.fecha_apertura) BETWEEN ? AND ?
    ORDER BY p.fecha_apertura DESC");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = $success = "";
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);
if (isset($_GET['success'])) $success = htmlspecialchars($_GET['success']);

// Obtener inicial del usuario
$usuario = $_SESSION['usuario'];
$inicial_usuario = strtoupper(substr($usuario['nombre'], 0, 1));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pedidos - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= $inicial_usuario ?></div>
        <span class="username"><?= htmlspecialchars($usuario['nombre']) ?></span>
    </div>
    <ul class="menu">
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li><a href="nuevo_pedido.php"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li><a href="gestionar_pedidos.php"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-history"></i><span class="nav-text">Historial de Pedidos</span></a></li>
        <li><a href="propinas_mes.php"><i class="fas fa-hand-holding-usd"></i><span class="nav-text">Propinas del Mes</span></a></li>
        <li><a href="deudas_delivery.php"><i class="fas fa-truck"></i><span class="nav-text">Deudas Delivery</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-history"></i> Historial de Pedidos</h1>
        <div class="user-info">
            <div class="user-initial"><?= $inicial_usuario ?></div>
            <span><?= htmlspecialchars($usuario['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-filter"></i> Filtros</h2>
            <form method="GET">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div>
                        <label>Desde</label>
                        <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" required>
                    </div>
                    <div>
                        <label>Hasta</label>
                        <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </section>

        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><i class="fas fa-list"></i> Pedidos Cerrados</h2>
            <?php if (empty($pedidos)): ?>
                <p>No hay pedidos en este periodo.</p>
            <?php else: ?>
                <table class="tabla-pedidos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Mesa / Pedido</th>
                            <th>Vendedor</th>
                            <th>Total USD</th>
                            <th>Total Bs</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td><?= $p['id_pedido'] ?></td>
                                <td><?= ucfirst($p['tipo_pedido']) ?></td>
                                <td><?= htmlspecialchars($p['numero_mesa']) ?></td>
                                <td><?= htmlspecialchars($p['vendedor']) ?></td>
                                <td>$<?= number_format($p['total_usd'], 2) ?></td>
                                <td>Bs<?= number_format($p['total_bs'], 2) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($p['fecha_apertura'])) ?></td>
                                <td class="acciones">
                                    <a href="detalles_pedido.php?id=<?= $p['id_pedido'] ?>" class="accion-btn editar" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
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