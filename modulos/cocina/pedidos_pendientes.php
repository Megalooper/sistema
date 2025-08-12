<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

// Obtener área (1 = Cocina, 2 = Barra)
$id_area = isset($_GET['area']) ? intval($_GET['area']) : 0;

if (!in_array($id_area, [1, 2])) {
    die("Área no válida");
}

// Obtener pedidos pendientes para esta área
$stmt = $pdo->prepare("
    SELECT dp.id_detalle, p.nombre_pedido, 
           COALESCE(pr.nombre, prod.nombre) AS nombre_producto,
           dp.cantidad, dp.fecha_registro
    FROM detalles_pedido dp
    JOIN pedidos ped ON dp.id_pedido = ped.id_pedido
    LEFT JOIN productos prod ON dp.id_producto = prod.id_producto
    LEFT JOIN preparaciones pr ON dp.id_preparacion = pr.id_preparacion
    WHERE ped.estado = 'espera'
      AND COALESCE(prod.id_area, pr.id_area) = ?
    ORDER BY dp.fecha_registro ASC
");
$stmt->execute([$id_area]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombre_area = $id_area == 1 ? 'Cocina' : 'Barra';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos Pendientes - <?= $nombre_area ?></title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pedidos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .pedido-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            position: relative;
        }
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f0f2f5;
        }
        .pedido-nombre {
            font-weight: bold;
            font-size: 1.1rem;
            color: #2c3e50;
        }
        .pedido-fecha {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        .producto-item {
            padding: 0.5rem 0;
            border-bottom: 1px dashed #f0f2f5;
        }
        .producto-nombre {
            font-weight: 500;
        }
        .producto-cantidad {
            background: #3498db;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
        }
        .marcar-completado {
            margin-top: 1rem;
            width: 100%;
        }
    </style>
</head>
<body>

<!-- Sidebar simplificada para cocina/barra -->
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= strtoupper(substr($nombre_area, 0, 1)) ?></div>
        <span class="username"><?= $nombre_area ?></span>
    </div>
    <ul class="menu">
        <li class="active"><a href="?area=<?= $id_area ?>"><i class="fas fa-utensils"></i><span class="nav-text">Pedidos Pendientes</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-utensils"></i> Pedidos Pendientes - <?= $nombre_area ?></h1>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-list"></i> Órdenes por Preparar</h2>
            
            <?php if (empty($pedidos)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No hay pedidos pendientes</h3>
                    <p>Todas las órdenes están completas</p>
                </div>
            <?php else: ?>
                <div class="pedidos-container">
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="pedido-card">
                            <div class="pedido-header">
                                <div class="pedido-nombre"><?= htmlspecialchars($pedido['nombre_pedido']) ?></div>
                                <div class="pedido-fecha"><?= date('H:i', strtotime($pedido['fecha_registro'])) ?></div>
                            </div>
                            
                            <div class="producto-item">
                                <span class="producto-cantidad"><?= $pedido['cantidad'] ?></span>
                                <span class="producto-nombre"><?= htmlspecialchars($pedido['nombre_producto']) ?></span>
                            </div>
                            
                            <button class="btn btn-success marcar-completado">
                                <i class="fas fa-check"></i> Marcar como Completado
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>