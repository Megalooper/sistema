<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
date_default_timezone_set('America/Caracas');

// Obtener inicial del usuario
$nombre_usuario = $_SESSION['usuario']['nombre'];
$inicial_usuario = strtoupper(substr($nombre_usuario, 0, 1));

// Filtros
$tipo_filtro = $_GET['tipo'] ?? '';
$busqueda = $_GET['q'] ?? '';

// Consulta corregida: JOIN con usuarios para obtener el nombre
$sql = "
    SELECT 
        im.id_movimiento,
        im.tipo,
        im.cantidad,
        im.fecha_movimiento,
        im.descripcion,
        im.id_usuario,
        p.nombre AS nombre_producto,
        u.nombre AS nombre_usuario
    FROM inventario_movimientos im
    JOIN productos p ON im.id_producto = p.id_producto
    JOIN usuarios u ON im.id_usuario = u.id_usuario
    WHERE 1=1
";

$params = [];

if ($tipo_filtro) {
    $sql .= " AND im.tipo = ?";
    $params[] = $tipo_filtro;
}

if ($busqueda) {
    $sql .= " AND LOWER(p.nombre) LIKE ?";
    $params[] = '%' . strtolower($busqueda) . '%';
}

$sql .= " ORDER BY im.fecha_movimiento DESC LIMIT 200";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}

// Tipos de movimiento
$tipos = ['compra', 'produccion', 'merma'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Inventario - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filtros-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filtros-container input,
        .filtros-container select {
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
        .badge-success { background: #28a745; }
        .badge-info { background: #17a2b8; }
        .badge-danger { background: #dc3545; }
        .historial-table {
            width: 100%;
            border-collapse: collapse;
        }
        .historial-table th,
        .historial-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .historial-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .historial-table tr:hover {
            background: #f8f9fa;
        }
        .acciones-container {
            margin-top: 20px;
            text-align: right;
        }
        .btn-volver {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .btn-volver:hover {
            background: #5a6268;
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
        <li><a href="inventario.php"><i class="fas fa-database"></i><span class="nav-text">Inventario</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-history"></i><span class="nav-text">Historial</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-history"></i> Historial de Movimientos de Inventario</h1>
        <div class="user-info">
            <div class="user-initial"><?= $inicial_usuario ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-filter"></i> Filtros</h2>
            <form method="GET" class="filtros-container">
                <input type="text" name="q" placeholder="Buscar producto..." value="<?= htmlspecialchars($busqueda) ?>">
                <select name="tipo">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t ?>" <?= $tipo_filtro === $t ? 'selected' : '' ?>>
                            <?= ucfirst($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                <a href="historial.php" class="btn btn-secondary"><i class="fas fa-undo"></i> Limpiar</a>
            </form>
        </section>

        <section class="card">
            <h2><i class="fas fa-list"></i> Movimientos Registrados</h2>
            <?php if (empty($movimientos)): ?>
                <p>No se encontraron movimientos.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="historial-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td><?= $m['id_movimiento'] ?></td>
                                    <td><?= htmlspecialchars($m['nombre_producto']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $m['tipo'] === 'compra' ? 'badge-success' : '' ?>
                                            <?= $m['tipo'] === 'produccion' ? 'badge-info' : '' ?>
                                            <?= $m['tipo'] === 'merma' ? 'badge-danger' : '' ?>
                                        ">
                                            <?= ucfirst($m['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= ($m['tipo'] === 'merma' ? '-' : '+') . $m['cantidad'] ?></td>
                                    <td><?= htmlspecialchars($m['nombre_usuario']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($m['fecha_movimiento'])) ?></td>
                                    <td><?= htmlspecialchars($m['descripcion'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="acciones-container">
                <a href="inventario.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al Inventario</a>
            </div>
        </section>
    </main>
</div>

</body>
</html>