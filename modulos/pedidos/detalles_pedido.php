<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pedido) {
    header("Location: historial.php?error=ID de pedido inválido.");
    exit;
}

// Obtener datos del pedido
$stmt = $pdo->prepare("SELECT 
    p.*,
    u.nombre as vendedor_nombre,
    t.nombre as turno_nombre
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    JOIN turnos t ON p.id_turno = t.id_turno
    WHERE p.id_pedido = ?");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header("Location: historial.php?error=Pedido no encontrado.");
    exit;
}

// Obtener productos
$stmt_detalle = $pdo->prepare("SELECT 
    dp.cantidad,
    dp.precio_unitario,
    dp.subtotal_usd,
    dp.subtotal_bs,
    COALESCE(prod.nombre, prep.nombre) AS nombre
    FROM detalles_pedido dp
    LEFT JOIN productos prod ON dp.id_producto = prod.id_producto
    LEFT JOIN preparaciones prep ON dp.id_preparacion = prep.id_preparacion
    WHERE dp.id_pedido = ?");
$stmt_detalle->execute([$id_pedido]);
$productos = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

// Obtener pagos
$stmt_pagos = $pdo->prepare("SELECT metodo_pago, referencia, monto_bs FROM pagos WHERE id_pedido = ?");
$stmt_pagos->execute([$id_pedido]);
$pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

// Obtener propina
$stmt_propina = $pdo->prepare("SELECT monto_bs, metodo_pago, referencia FROM propinas WHERE id_pedido = ?");
$stmt_propina->execute([$id_pedido]);
$propina = $stmt_propina->fetch(PDO::FETCH_ASSOC);

// Manejo de mensajes
$error = $success = "";
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);
if (isset($_GET['success'])) $success = htmlspecialchars($_GET['success']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Pedido #<?= $id_pedido ?></title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .info-item {
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .info-item strong {
            color: #2c3e50;
        }
        .acciones-container {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn-imprimir {
            background: #27ae60;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-volver {
            background: #95a5a6;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>

<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
        <span class="username"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
    </div>
    <ul class="menu">
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li><a href="nuevo_pedido.php"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li><a href="gestionar_pedidos.php"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-file-alt"></i><span class="nav-text">Detalles del Pedido</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial</span></a></li>
        <li><a href="propinas_mes.php"><i class="fas fa-hand-holding-usd"></i><span class="nav-text">Propinas del Mes</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-file-alt"></i> Detalles del Pedido #<?= $id_pedido ?></h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><i class="fas fa-info-circle"></i> Información General</h2>
            <div class="info-item">
                <strong>ID del Pedido:</strong> #<?= $id_pedido ?>
            </div>
            <div class="info-item">
                <strong>Vendedor:</strong> <?= htmlspecialchars($pedido['vendedor_nombre']) ?>
            </div>
            <div class="info-item">
                <strong>Turno:</strong> <?= htmlspecialchars($pedido['turno_nombre']) ?>
            </div>
            <div class="info-item">
                <strong>Tipo:</strong> <?= ucfirst($pedido['tipo_pedido']) ?>
            </div>
            <?php if ($pedido['tipo_pedido'] == 'mesa'): ?>
                <div class="info-item">
                    <strong>Mesa:</strong> <?= htmlspecialchars($pedido['numero_mesa']) ?>
                </div>
            <?php else: ?>
                <div class="info-item">
                    <strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion_delivery']) ?>
                </div>
                <div class="info-item">
                    <strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono_cliente']) ?>
                </div>
            <?php endif; ?>
            <div class="info-item">
                <strong>Apertura:</strong> <?= date('d/m/Y H:i', strtotime($pedido['fecha_apertura'])) ?>
            </div>
            <div class="info-item">
                <strong>Cierre:</strong> <?= date('d/m/Y H:i', strtotime($pedido['fecha_cierre'])) ?>
            </div>
        </section>

        <section class="card">
            <h2><i class="fas fa-boxes"></i> Productos</h2>
            <table class="tabla-pedidos">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal USD</th>
                        <th>Subtotal Bs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= $p['cantidad'] ?></td>
                            <td>$<?= number_format($p['precio_unitario'], 2) ?></td>
                            <td>$<?= number_format($p['subtotal_usd'], 2) ?></td>
                            <td>Bs<?= number_format($p['subtotal_bs'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td><strong>$<?= number_format($pedido['total_usd'], 2) ?></strong></td>
                        <td><strong>Bs<?= number_format($pedido['total_bs'], 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <section class="card">
            <h2><i class="fas fa-money-bill-wave"></i> Pagos</h2>
            <table class="tabla-pedidos">
                <thead>
                    <tr>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th>Monto Bs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td><?= ucfirst($p['metodo_pago']) ?></td>
                            <td><?= htmlspecialchars($p['referencia'] ?? '–') ?></td>
                            <td>Bs<?= number_format($p['monto_bs'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <?php if ($propina): ?>
            <section class="card">
                <h2><i class="fas fa-hand-holding-usd"></i> Propina</h2>
                <div class="info-item">
                    <strong>Monto:</strong> Bs<?= number_format($propina['monto_bs'], 2) ?>
                </div>
                <div class="info-item">
                    <strong>Método:</strong> <?= ucfirst($propina['metodo_pago']) ?>
                </div>
                <?php if ($propina['referencia']): ?>
                    <div class="info-item">
                        <strong>Referencia:</strong> <?= htmlspecialchars($propina['referencia']) ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div class="acciones-container">
            <a href="javascript:imprimirFactura()" class="btn-imprimir">
                <i class="fas fa-print"></i> Imprimir Factura
            </a>
            <a href="historial.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver al Historial
            </a>
        </div>
    </main>
</div>

<script>
function imprimirFactura() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Factura #<?= $id_pedido ?></title>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #f4f4f4; }
                .total { font-weight: bold; text-align: right; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Factura de Pedido #<?= $id_pedido ?></h2>
                <p><?= date('d/m/Y H:i') ?></p>
            </div>
            <h3>Productos</h3>
            <table>
                <tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr>
                <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= $p['cantidad'] ?></td>
                    <td>$<?= number_format($p['precio_unitario'], 2) ?></td>
                    <td>$<?= number_format($p['subtotal_usd'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr><td colspan="3" class="total">Total USD:</td><td>$<?= number_format($pedido['total_usd'], 2) ?></td></tr>
            </table>
            <p><strong>Propina:</strong> Bs<?= number_format($propina['monto_bs'] ?? 0, 2) ?></p>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>

</body>
</html>