<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

// Obtener historial de ventas
$stmt = $pdo->prepare("
    SELECT v.*, t.nombre AS turno, u.nombre AS usuario, p.nombre_pedido
    FROM ventas v
    JOIN turnos t ON v.id_turno = t.id_turno
    JOIN usuarios u ON v.id_usuario = u.id_usuario
    JOIN pedidos p ON v.id_pedido = p.id_pedido
    ORDER BY v.fecha_venta DESC
    LIMIT 50
");
$stmt->execute();
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ventas - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #f0f2f5;
        }
        th {
            background-color: #2c3e50;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }
        .filtros {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .filtros .form-group {
            margin-bottom: 0;
            flex: 1;
        }
    </style>
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
        <li><a href="nuevo_pedido.php"><i class="fas fa-plus-circle"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li><a href="pedidos_espera.php"><i class="fas fa-list"></i><span class="nav-text">Pedidos en Espera</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-history"></i><span class="nav-text">Historial de Ventas</span></a></li>
        <li><a href="../../inventario/inventario.php"><i class="fas fa-database"></i><span class="nav-text">Inventario</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-history"></i> Historial de Ventas</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-receipt"></i> Ventas Registradas</h2>
            
            <div class="filtros">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha Inicio</label>
                    <input type="date" id="fecha_inicio" class="form-control">
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha Fin</label>
                    <input type="date" id="fecha_fin" class="form-control">
                </div>
                <div class="form-group">
                    <label for="busqueda">Buscar</label>
                    <input type="text" id="busqueda" class="form-control" placeholder="Nombre de pedido...">
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Pedido</th>
                            <th>Total USD</th>
                            <th>Total Bs</th>
                            <th>Turno</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><?= $venta['id_venta'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></td>
                                <td><?= htmlspecialchars($venta['nombre_pedido']) ?></td>
                                <td>$<?= number_format($venta['total_usd'], 2) ?></td>
                                <td>Bs <?= number_format($venta['total_bs'], 2) ?></td>
                                <td><?= htmlspecialchars($venta['turno']) ?></td>
                                <td><?= htmlspecialchars($venta['usuario']) ?></td>
                                <td>
                                    <a href="imprimir_pedido.php?id=<?= $venta['id_venta'] ?>" class="btn btn-primary btn-small" target="_blank">
                                        <i class="fas fa-print"></i> Imprimir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>