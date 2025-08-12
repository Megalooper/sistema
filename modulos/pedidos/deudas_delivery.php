<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
date_default_timezone_set('America/Caracas');

// Obtener usuario
$nombre_usuario = $_SESSION['usuario']['nombre'];
$inicial_usuario = strtoupper(substr($nombre_usuario, 0, 1));

// Filtro por mes y año
$mes_filtro = $_GET['mes'] ?? date('m');
$anio_filtro = $_GET['anio'] ?? date('Y');

// Actualizar estado (marcar como pagado)
if (isset($_GET['pagar']) && isset($_GET['token']) && hash_equals($_SESSION['token'] ?? '', $_GET['token'])) {
    $id = filter_input(INPUT_GET, 'pagar', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("UPDATE deudas_delivery SET estado = 'pagado', fecha_pago = NOW() WHERE id_deuda = ?");
        $stmt->execute([$id]);
        $success = "Deuda marcada como pagada.";
    } else {
        $error = "ID de deuda no válido.";
    }
}

// Obtener deudas
$stmt = $pdo->prepare("
    SELECT * FROM deudas_delivery 
    WHERE mes = ? AND anio = ? 
    ORDER BY nombre_repartidor, estado DESC
");
$stmt->execute([$mes_filtro, $anio_filtro]);
$deudas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar token para seguridad
$_SESSION['token'] = bin2hex(random_bytes(16));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Deudas a Deliverys - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filtro-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filtro-container select, .filtro-container input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        .badge-pendiente { background: #dc3545; }
        .badge-pagado { background: #28a745; }
        .tabla-deudas {
            width: 100%;
            border-collapse: collapse;
        }
        .tabla-deudas th, .tabla-deudas td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .tabla-deudas th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .tabla-deudas tr:hover {
            background: #f8f9fa;
        }
        .acciones a {
            margin: 0 5px;
            color: #007bff;
            text-decoration: none;
        }
        .acciones a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= $inicial_usuario ?></div>
        <span class="username"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
    </div>
    <ul class="menu">
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li><a href="nuevo_pedido.php"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li><a href="gestionar_pedidos.php"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial</span></a></li>
        <li><a href="propinas_mes.php"><i class="fas fa-hand-holding-usd"></i><span class="nav-text">Propinas del Mes</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-truck"></i><span class="nav-text">Deudas Delivery</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-truck"></i> Deudas a Repartidores</h1>
        <div class="user-info">
            <div class="user-initial"><?= $inicial_usuario ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><i class="fas fa-filter"></i> Filtrar por Mes</h2>
            <form method="GET" class="filtro-container">
                <select name="mes">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $mes_filtro ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <input type="number" name="anio" min="2020" max="2030" value="<?= $anio_filtro ?>" placeholder="Año">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
            </form>
        </section>

        <section class="card">
            <h2>
                <i class="fas fa-money-bill-wave"></i>
                Deudas - <?= date('F Y', strtotime("$anio_filtro-$mes_filtro-01")) ?>
            </h2>

            <?php if (empty($deudas)): ?>
                <p>No hay deudas registradas para este mes.</p>
            <?php else: ?>
                <table class="tabla-deudas">
                    <thead>
                        <tr>
                            <th>Repartidor</th>
                            <th>Monto (Bs)</th>
                            <th>Monto (USD)</th>
                            <th>Estado</th>
                            <th>Fecha de Pago</th>
                            <th>Notas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deudas as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['nombre_repartidor']) ?></td>
                                <td>Bs <?= number_format($d['monto_bs'], 2) ?></td>
                                <td>$ <?= number_format($d['monto_usd'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $d['estado'] === 'pagado' ? 'badge-pagado' : 'badge-pendiente' ?>">
                                        <?= ucfirst($d['estado']) ?>
                                    </span>
                                </td>
                                <td><?= $d['fecha_pago'] ? date('d/m/Y', strtotime($d['fecha_pago'])) : '-' ?></td>
                                <td><?= htmlspecialchars($d['notas'] ?: '-') ?></td>
                                <td class="acciones">
                                    <?php if ($d['estado'] === 'pendiente'): ?>
                                        <a href="?pagar=<?= $d['id_deuda'] ?>&token=<?= $_SESSION['token'] ?>" 
                                           onclick="return confirm('¿Marcar como pagado?')">
                                            <i class="fas fa-check"></i> Pagar
                                        </a>
                                    <?php else: ?>
                                        <span style="color: green;">Pagado</span>
                                    <?php endif; ?>
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