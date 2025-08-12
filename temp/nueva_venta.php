<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Establecer zona horaria de Venezuela
date_default_timezone_set('America/Caracas');

// Obtener turnos desde la base de datos
$stmt_turnos = $pdo->query("SELECT id_turno, nombre, hora_inicio, hora_fin FROM turnos ORDER BY id_turno ASC");
$turnos = $stmt_turnos->fetchAll(PDO::FETCH_ASSOC);

// Detectar turno actual
$hora_actual = date("H:i");
$turno_actual = null;

foreach ($turnos as $t) {
    $inicio = $t['hora_inicio'];
    $fin = $t['hora_fin'];

    // Convertir a formato 24h
    if ($inicio <= $fin) {
        if ($hora_actual >= $inicio && $hora_actual <= $fin) {
            $turno_actual = $t['id_turno'];
            break;
        }
    } else {
        if ($hora_actual >= $inicio || $hora_actual <= $fin) {
            $turno_actual = $t['id_turno'];
            break;
        }
    }
}

// Obtener áreas
$stmt_areas = $pdo->query("SELECT id_area, nombre FROM areas ORDER BY nombre ASC");
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías por área
$stmt_categorias = $pdo->query("SELECT id_categoria, nombre, id_area FROM categorias ORDER BY nombre ASC");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos
$stmt_productos = $pdo->query("SELECT id_producto, nombre, precio_usd, stock, id_categoria FROM productos WHERE visible_venta = 1 ORDER BY nombre ASC");
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener preparaciones
$stmt_preparaciones = $pdo->query("SELECT p.id_preparacion, p.nombre, p.precio_usd, c.id_categoria FROM preparaciones p JOIN categorias c ON p.id_categoria = c.id_categoria ORDER BY p.nombre ASC");
$preparaciones = $stmt_preparaciones->fetchAll(PDO::FETCH_ASSOC);

$preparaciones_stock = [];
foreach ($preparaciones as &$prep) {
    $stmt_receta = $pdo->prepare("SELECT r.id_producto, r.cantidad, p.stock FROM recetas r JOIN productos p ON r.id_producto = p.id_producto WHERE r.id_preparacion = ?");
    $stmt_receta->execute([$prep['id_preparacion']]);
    $min_stock = null;
    while ($receta = $stmt_receta->fetch(PDO::FETCH_ASSOC)) {
        if ($receta['cantidad'] > 0) {
            $possible = floor($receta['stock'] / $receta['cantidad']);
            if ($min_stock === null || $possible < $min_stock) {
                $min_stock = $possible;
            }
        }
    }
    $prep['stock_preparacion'] = ($min_stock !== null && $min_stock > 0) ? $min_stock : 0;
    $preparaciones_stock[$prep['id_preparacion']] = $prep['stock_preparacion'];
}
unset($prep);

// Obtener tasa de cambio actual
$stmt_tasa = $pdo->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
$tasa = $stmt_tasa->fetch(PDO::FETCH_ASSOC);
$valor_dolar = $tasa ? $tasa['valor_dolar'] : 1;

// Manejo de errores
$error = "";
$success = "";
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
if (isset($_GET['mensaje'])) {
    $success = htmlspecialchars($_GET['mensaje']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-ventas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --dark-bg: #0A192F;
            --border-color: #e1e5eb;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        /* Estilos generales */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }
        
        h1, h2, h3, h4 {
            color: var(--secondary-color);
        }
        
        /* Botones */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: var(--primary-color);
        }
        
        .btn-success {
            background: var(--success-color);
        }
        
        .btn-danger {
            background: var(--danger-color);
        }
        
        .btn-warning {
            background: var(--warning-color);
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        /* Botón flotante para abrir modal */
        .btn-agregar-producto {
            position: fixed;
            right: 30px;
            bottom: 30px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
            z-index: 1000;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-agregar-producto:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        /* MODAL MEJORADO */
    .modal-overlay {
        position: fixed;
        top: 0;
        right: 0;
        width: 420px;
        height: 100%;
        background: #fff;
        box-shadow: -8px 0 32px rgba(52,152,219,0.12);
        border-top-left-radius: 32px;
        border-bottom-left-radius: 32px;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(.4,0,.2,1);
        z-index: 1000;
        display: flex;
        flex-direction: column;
        overflow: auto;
    }

    .modal-overlay.active {
        transform: translateX(0);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        background: var(--primary-color);
        color: white;
        border-top-left-radius: 32px;
    }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .close-btn:hover {
            transform: scale(1.1);
        }
        
        .modal-body {
            padding: 2rem;
            background-color: var(--light-bg);
            flex-grow: 1;
            border-bottom-left-radius: 32px;
        }
        
        .modal-step {
            display: block;
        }
        
        .modal-step.hidden {
            display: none;
        }
        
        .step-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
            color: var(--secondary-color);
        }
        
        .step-title i {
            color: var(--primary-color);
        }
        
        /* Tarjetas de área y categoría más redondeadas */
        .area-card, .categoria-card {
            background: linear-gradient(90deg, #f8f9fa 80%, #eaf6fb 100%);
            padding: 1.2rem 1.5rem;
            border-radius: 24px;
            margin: 0.7rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: box-shadow 0.2s, transform 0.2s;
            border: none;
            box-shadow: 0 2px 12px rgba(52,152,219,0.08);
            position: relative;
        }

        .area-card:hover, .categoria-card:hover {
            box-shadow: 0 6px 24px rgba(52,152,219,0.18);
            transform: translateY(-2px) scale(1.03);
            background: linear-gradient(90deg, #eaf6fb 80%, #f8f9fa 100%);
        }

        .area-card i, .categoria-card i {
            color: var(--primary-color);
            font-size: 2rem;
            background: #eaf6fb;
            padding: 0.7rem;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s;
        }

        .area-card:hover i, .categoria-card:hover i {
            background: #d0eafd;
        }

        .area-card span, .categoria-card span {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--secondary-color);
        }
        
        /* Tarjetas de producto/preparación más redondeadas */
        .producto-card {
            background: #fff;
            border-radius: 20px;
            padding: 1.2rem 1.5rem;
            margin: 0.7rem 0;
            cursor: pointer;
            transition: box-shadow 0.2s, transform 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            box-shadow: 0 2px 12px rgba(52,152,219,0.08);
            position: relative;
        }

        .producto-card:hover {
            box-shadow: 0 8px 32px rgba(52,152,219,0.18);
            transform: translateY(-2px) scale(1.03);
            background: #eaf6fb;
        }

        .producto-info {
            flex: 1;
        }

        .producto-card .nombre {
            font-weight: 700;
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
            color: var(--secondary-color);
        }

        .producto-card .precio {
            color: var(--success-color);
            font-weight: 600;
            font-size: 1rem;
        }

        .producto-card .stock {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }

        .producto-card .chevron {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-left: 1rem;
            transition: transform 0.2s;
        }

        .producto-card:hover .chevron {
            transform: translateX(4px) scale(1.2);
        }
        
        /* Buscador */
        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        /* Cantidad */
        .cantidad-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            justify-content: center;
        }
        
        .cantidad-input {
            width: 80px;
            padding: 0.8rem;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .cantidad-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cantidad-btn:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        /* Carrito */
        .carrito-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .carrito-header {
            padding: 1rem 1.5rem;
            background: var(--dark-bg);
            color: white;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .carrito-body {
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .producto-seleccionado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .producto-seleccionado:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .producto-seleccionado .info {
            flex: 1;
        }
        
        .producto-seleccionado .nombre {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .producto-seleccionado .precio {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .producto-seleccionado .acciones {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .producto-seleccionado .cantidad-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--light-bg);
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
        }
        
        .acciones button {
            background: none;
            border: none;
            font-size: 1.1rem;
            color: var(--primary-color);
            cursor: pointer;
            transition: var(--transition);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .acciones button:hover {
            background: rgba(52, 152, 219, 0.1);
            color: #2980b9;
        }
        
        .acciones .btn-eliminar {
            color: var(--danger-color);
        }
        
        .acciones .btn-eliminar:hover {
            background: rgba(231, 76, 60, 0.1);
        }
        
        /* Totales */
        .totales {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }
        
        .total-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .total-item:last-child {
            border-bottom: none;
        }
        
        .total-label {
            font-weight: 500;
        }
        
        .total-value {
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .total-value.bs {
            color: var(--success-color);
        }
        
        .total-value.usd {
            color: var(--primary-color);
        }
        
        /* Nuevos estilos para impuestos */
        .tax-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        
        .tax-label {
            font-weight: 500;
            color: #7f8c8d;
        }
        
        .tax-value {
            font-weight: 600;
            color: #e74c3c;
        }
        
        /* Formulario */
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        /* Mensajes */
        .success,
        .error {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border-color: #2e7d32;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            border-color: #c62828;
        }
        
        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-overlay {
                width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        /* Responsive para modal */
        @media (max-width: 600px) {
            .modal-overlay {
                width: 100%;
                border-radius: 0;
            }
            .modal-header, .modal-body {
                border-radius: 0;
                padding: 1rem;
            }
            .area-card, .categoria-card, .producto-card {
                border-radius: 16px;
                padding: 1rem;
            }
        }
        
        /* Mensaje vacío */
        .text-center {
            text-align: center;
        }
        
        .empty-cart-message {
            padding: 2rem;
            color: #718096;
            font-style: italic;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .empty-cart-message i {
            font-size: 3rem;
            color: #cbd5e0;
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
        <li class="active"><a href="#"><i class="fas fa-shopping-cart"></i><span class="nav-text">Nueva Venta</span></a></li>
        <li><a href="../ventas/historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial de Ventas</span></a></li>
        <li><a href="../../inventario/inventario.php"><i class="fas fa-database"></i><span class="nav-text">Inventario</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-shopping-cart"></i> Registrar Nueva Venta</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card venta-form-container">
            <h2><i class="fas fa-info-circle"></i> Información de la Venta</h2>

            <?php if (isset($_GET['exito']) && isset($_GET['id'])): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    ¡Venta registrada correctamente!
                </div>
                <div class="form-actions" style="margin-bottom:2rem;">
                    <a href="imprimir_venta.php?id=<?= intval($_GET['id']) ?>" class="btn btn-primary" target="_blank">
                        <i class="fas fa-print"></i> Imprimir Factura
                    </a>
                    <a href="nueva_venta.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Nueva Venta
                    </a>
                </div>
            <?php endif; ?>

            <form id="ventaForm" method="POST" action="registrar_venta.php">
                <input type="hidden" name="turno" value="<?= $turno_actual ?>">
                <div class="form-group">
                    <label for="turnoSelect">Turno *</label>
                    <select id="turnoSelect" name="turno" class="form-control" required>
                        <?php foreach ($turnos as $t): ?>
                            <option value="<?= $t['id_turno'] ?>"<?= $t['id_turno'] == $turno_actual ? ' selected' : '' ?>>
                                <?= htmlspecialchars($t['nombre']) ?> (<?= $t['hora_inicio'] ?> - <?= $t['hora_fin'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Carrito de productos -->
                <div class="carrito-container" id="seleccionadosList">
                    <div class="carrito-header">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Productos Seleccionados</h3>
                    </div>
                    <div class="carrito-body" id="carrito-body">
                        <div id="mensajeVacio" class="empty-cart-message" style="display: flex;">
                            <i class="fas fa-shopping-basket"></i>
                            <p>No hay productos seleccionados aún</p>
                        </div>
                        <!-- Aquí se agregan los productos con JS -->
                    </div>
                </div>

                <!-- Totales con IVA -->
                <div class="totales">
                    <div class="total-item">
                        <span class="total-label">Subtotal en USD</span>
                        <span class="total-value usd" id="subtotal_usd_text">$0.00</span>
                        <input type="hidden" id="subtotal_usd" name="subtotal_usd" value="0.00">
                    </div>
                    
                    <!-- Subtotal en Bs (base imponible sin IVA) -->
                    <div class="total-item">
                        <span class="total-label">Subtotal en Bs</span>
                        <span class="total-value" id="base_imponible_text">Bs 0.00</span>
                        <input type="hidden" id="base_imponible" name="base_imponible" value="0.00">
                    </div>
                    
                    <!-- Sección de impuestos -->
                    <div class="tax-item">
                        <span class="tax-label">IVA (16%)</span>
                        <span class="tax-value" id="iva_text">Bs 0.00</span>
                        <input type="hidden" id="iva" name="iva" value="0.00">
                    </div>
                    
                    <div class="total-item" style="border-top: 2px solid #e1e5eb; padding-top: 1rem; margin-top: 0.5rem;">
                        <span class="total-label" style="font-weight: 700;">Total en Bs</span>
                        <span class="total-value bs" id="total_bs_text" style="font-size: 1.2rem;">Bs 0.00</span>
                        <input type="hidden" id="total_bs" name="total_bs" value="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="efectivo_recibido">Efectivo Recibido (Bs)</label>
                        <input type="number" id="efectivo_recibido" name="efectivo_recibido" step="0.01" class="form-control" placeholder="Ej: 500.00" oninput="calcularVuelto()">
                    </div>
                    <div class="form-group">
                        <label for="vuelto">Vuelto</label>
                        <input type="text" id="vuelto" name="vuelto" readonly class="form-control" value="Bs 0.00">
                    </div>
                </div>

                <!-- Acciones -->
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="limpiarVenta()">
                        <i class="fas fa-broom"></i> Limpiar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="abrirModal()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Venta
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

<!-- Botón flotante para abrir modal -->
<button class="btn-agregar-producto" onclick="abrirModal()" title="Agregar Producto">
    <i class="fas fa-plus"></i>
</button>

<!-- Modal de productos -->
<div class="overlay" id="overlay" onclick="cerrarModal()"></div>
<div id="modalProductos" class="modal-overlay">
    <div class="modal-header">
        <h3><i class="fas fa-boxes"></i> Agregar Producto</h3>
        <button class="close-btn" onclick="cerrarModal()">&times;</button>
    </div>
    <div class="modal-body">
        <!-- Paso 1: Seleccionar Área -->
        <div id="stepArea" class="modal-step">
            <div class="step-title">
                <i class="fas fa-layer-group"></i>
                <h4>Paso 1: Selecciona un Área</h4>
            </div>
            <?php foreach ($areas as $a): ?>
                <div class="area-card" onclick="seleccionarArea(<?= $a['id_area'] ?>)">
                    <i class="fas fa-<?= $a['id_area'] == 1 ? 'cocktail' : 'utensils' ?>"></i>
                    <span><?= htmlspecialchars($a['nombre']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paso 2: Seleccionar Categoría -->
        <div id="stepCategoria" class="modal-step hidden">
            <div class="step-title">
                <i class="fas fa-boxes"></i>
                <h4>Paso 2: Selecciona una Categoría</h4>
            </div>
            <div class="categorias-lista" id="categoriasList">
                <!-- Aquí se cargarán las categorías -->
            </div>
        </div>

        <!-- Paso 3: Seleccionar Producto o Preparación -->
        <div id="stepProducto" class="modal-step hidden">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="busquedaProducto" onkeyup="filtrarProductos()" placeholder="Buscar producto...">
            </div>
            <div class="step-title">
                <i class="fas fa-boxes"></i>
                <h4>Paso 3: Selecciona un Producto o Preparación</h4>
            </div>
            <div class="productos-lista" id="productosLista">
                <!-- Aquí se cargarán los productos -->
            </div>
        </div>

        <!-- Paso 4: Ingresar cantidad y confirmar -->
        <div id="stepCantidad" class="modal-step hidden">
            <div class="step-title">
                <i class="fas fa-sort-numeric-up"></i>
                <h4>Paso 4: Cantidad</h4>
            </div>
            <p id="nombreProductoSeleccionado" class="product-name" style="font-weight:600; text-align:center; font-size:1.1rem;"></p>
            
            <div class="cantidad-controls">
                <button class="cantidad-btn" onclick="cambiarCantidad(-1)">-</button>
                <input type="number" id="cantidadSeleccionada" class="cantidad-input" min="1" value="1">
                <button class="cantidad-btn" onclick="cambiarCantidad(1)">+</button>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="volverAProductos()">
                    <i class="fas fa-arrow-left"></i> Atrás
                </button>
                <button type="button" class="btn btn-primary" onclick="agregarAlCarrito()">
                    <i class="fas fa-plus"></i> Agregar al Carrito
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let seleccionados = [];
    let areaSeleccionada = null;
    let categoriaSeleccionada = null;
    let productoSeleccionado = null;
    const IVA_PERCENT = 16; // 16% de IVA

    // --- Cambios para asegurar funcionalidad ---

    // Evitar duplicidad de eventos y asegurar renderizado
    document.addEventListener('DOMContentLoaded', function() {
        renderizarSeleccionados();
    });

    function abrirModal() {
        document.getElementById('modalProductos').classList.add('active');
        document.getElementById('overlay').style.display = 'block';
        document.getElementById('stepArea').style.display = 'block';
        document.getElementById('stepCategoria').style.display = 'none';
        document.getElementById('stepProducto').style.display = 'none';
        document.getElementById('stepCantidad').style.display = 'none';
    }

    function cerrarModal(e) {
        document.getElementById('modalProductos').classList.remove('active');
        document.getElementById('overlay').style.display = 'none';
    }

    function seleccionarArea(id) {
        areaSeleccionada = id;
        document.getElementById('stepArea').style.display = 'none';
        document.getElementById('stepCategoria').style.display = 'block';

        const categoriasList = document.getElementById('categoriasList');
        categoriasList.innerHTML = "";

        <?php foreach ($categorias as $cat): ?>
            if (<?= $cat['id_area'] ?> == id) {
                const div = document.createElement("div");
                div.className = "categoria-card";
                div.onclick = function() { seleccionarCategoria(<?= $cat['id_categoria'] ?>); };
                div.innerHTML = `
                    <i class="fas fa-<?= $cat['id_area'] == 1 ? 'glass-martini-alt' : 'utensil-spoon' ?>"></i>
                    <span><?= htmlspecialchars($cat['nombre']) ?></span>
                `;
                categoriasList.appendChild(div);
            }
        <?php endforeach; ?>
    }

    function seleccionarCategoria(id) {
        categoriaSeleccionada = id;
        document.getElementById('stepCategoria').style.display = 'none';
        document.getElementById('stepProducto').style.display = 'block';

        const productosLista = document.getElementById('productosLista');
        productosLista.innerHTML = "";

        // Cargar productos de esta categoría
        <?php foreach ($productos as $p): ?>
            if (<?= $p['id_categoria'] ?> == id) {
                const div = document.createElement("div");
                div.className = "producto-card";
                div.onclick = function() { seleccionarProducto(<?= $p['id_producto'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['precio_usd'] ?>, 'producto', <?= $p['stock'] ?>); };
                div.innerHTML = `
                    <div class="producto-info">
                        <div class="nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div class="precio">$<?= number_format($p['precio_usd'], 2) ?></div>
                        <div class="stock">Stock: <?= $p['stock'] ?></div>
                    </div>
                    <i class="fas fa-chevron-right chevron"></i>
                `;
                productosLista.appendChild(div);
            }
        <?php endforeach; ?>

        // Cargar preparaciones de esta categoría
        <?php foreach ($preparaciones as $prep): ?>
            if (<?= $prep['id_categoria'] ?> == id) {
                const div = document.createElement("div");
                div.className = "producto-card";
                div.onclick = function() { seleccionarProducto(<?= $prep['id_preparacion'] ?>, '<?= addslashes($prep['nombre']) ?>', <?= $prep['precio_usd'] ?>, 'preparacion', <?= $prep['stock_preparacion'] ?>); };
                div.innerHTML = `
                    <div class="producto-info">
                        <div class="nombre"><?= htmlspecialchars($prep['nombre']) ?></div>
                        <div class="precio">$<?= number_format($prep['precio_usd'], 2) ?></div>
                        <div class="stock">Stock: <?= $prep['stock_preparacion'] ?></div>
                    </div>
                    <i class="fas fa-chevron-right chevron"></i>
                `;
                productosLista.appendChild(div);
            }
        <?php endforeach; ?>
    }

    function seleccionarProducto(id, nombre, precio, tipo, stock = 10000) {
        productoSeleccionado = {
            id: id,
            nombre: nombre,
            precio: precio,
            tipo: tipo,
            stock: stock
        };
        
        document.getElementById('nombreProductoSeleccionado').innerText = nombre;
        document.getElementById('cantidadSeleccionada').value = 1;
        document.getElementById('cantidadSeleccionada').max = stock;
        document.getElementById('stepProducto').style.display = 'none';
        document.getElementById('stepCantidad').style.display = 'block';
    }

    function cambiarCantidad(delta) {
        const input = document.getElementById('cantidadSeleccionada');
        let value = parseInt(input.value) || 0;
        value += delta;
        
        if (value < 1) value = 1;
        if (value > productoSeleccionado.stock && productoSeleccionado.tipo === 'producto') {
            value = productoSeleccionado.stock;
        }
        
        input.value = value;
    }

    function volverAProductos() {
        document.getElementById('stepCantidad').style.display = 'none';
        document.getElementById('stepProducto').style.display = 'block';
    }

    function agregarAlCarrito() {
        const cantidad = parseInt(document.getElementById('cantidadSeleccionada').value);
        const prod = productoSeleccionado;

        if (isNaN(cantidad) || cantidad <= 0) {
            alert("Por favor, ingresa una cantidad válida.");
            return;
        }

        if (prod.tipo === 'producto' && prod.stock < cantidad) {
            alert("No hay suficiente stock para este producto.");
            return;
        }

        // Verificar si el producto ya está en el carrito
        const index = seleccionados.findIndex(p => 
            p.id === prod.id && p.tipo === prod.tipo
        );

        if (index !== -1) {
            // Actualizar cantidad si ya existe
            seleccionados[index].cantidad += cantidad;
        } else {
            // Agregar nuevo producto al carrito
            seleccionados.push({
                id: prod.id,
                nombre: prod.nombre,
                precio: prod.precio,
                tipo: prod.tipo,
                cantidad: cantidad
            });
        }

        renderizarSeleccionados();
        cerrarModal(); // <-- Asegura que el modal se cierre
    }

    function eliminarProducto(index) {
        seleccionados.splice(index, 1);
        renderizarSeleccionados();
    }

    function modificarCantidad(index, cantidad) {
        seleccionados[index].cantidad += cantidad;
        if (seleccionados[index].cantidad <= 0) {
            seleccionados.splice(index, 1);
        }
        renderizarSeleccionados();
    }

    function renderizarSeleccionados() {
        const carritoBody = document.getElementById('carrito-body');
        const mensajeVacio = document.getElementById('mensajeVacio');
        const subtotalUsdInput = document.getElementById('subtotal_usd');
        const subtotalUsdText = document.getElementById('subtotal_usd_text');
        const baseImponibleInput = document.getElementById('base_imponible');
        const baseImponibleText = document.getElementById('base_imponible_text');
        const ivaInput = document.getElementById('iva');
        const ivaText = document.getElementById('iva_text');
        const totalBsInput = document.getElementById('total_bs');
        const totalBsText = document.getElementById('total_bs_text');
        const valorDolar = <?= $valor_dolar ?>;

        // Elimina solo los productos, no el mensaje vacío
        carritoBody.querySelectorAll('.producto-seleccionado').forEach(e => e.remove());

        if (seleccionados.length === 0) {
            mensajeVacio.style.display = 'flex';
            subtotalUsdInput.value = "0.00";
            subtotalUsdText.innerText = "$0.00";
            baseImponibleInput.value = "0.00";
            baseImponibleText.innerText = "Bs 0.00";
            ivaInput.value = "0.00";
            ivaText.innerText = "Bs 0.00";
            totalBsInput.value = "0.00";
            totalBsText.innerText = "Bs 0.00";
            document.getElementById('vuelto').value = "Bs 0.00";
            return;
        } else {
            mensajeVacio.style.display = 'none';
        }

        let subtotalUsd = 0;
        seleccionados.forEach((p, index) => {
            const subtotal = p.precio * p.cantidad;
            subtotalUsd += subtotal;

            const div = document.createElement("div");
            div.className = "producto-seleccionado";
            div.innerHTML = `
                <div class="info">
                    <div class="nombre">${p.nombre}</div>
                    <div class="precio">$${(p.precio).toFixed(2)}</div>
                </div>
                <div class="acciones">
                    <div class="cantidad-control">
                        <button type="button" onclick="modificarCantidad(${index}, -1)">-</button>
                        <span>${p.cantidad}</span>
                        <button type="button" onclick="modificarCantidad(${index}, 1)">+</button>
                    </div>
                    <button type="button" class="btn-eliminar" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="subtotal">$${subtotal.toFixed(2)}</div>
            `;
            carritoBody.appendChild(div);
        });

        // Calcular valores con impuestos (nueva lógica)
        const totalBs = subtotalUsd * valorDolar;
        const baseImponible = totalBs / (1 + (IVA_PERCENT / 100));
        const iva = baseImponible * (IVA_PERCENT / 100);

        // Actualizar campos ocultos
        subtotalUsdInput.value = subtotalUsd.toFixed(2);
        baseImponibleInput.value = baseImponible.toFixed(2);
        ivaInput.value = iva.toFixed(2);
        totalBsInput.value = totalBs.toFixed(2);

        // Actualizar la interfaz
        subtotalUsdText.innerText = `$${subtotalUsd.toFixed(2)}`;
        baseImponibleText.innerText = `Bs ${baseImponible.toFixed(2)}`;
        ivaText.innerText = `Bs ${iva.toFixed(2)}`;
        totalBsText.innerText = `Bs ${totalBs.toFixed(2)}`;

        // Actualizar vuelto
        calcularVuelto();
    }

    function limpiarVenta() {
        if (confirm("¿Estás seguro de limpiar la venta? Se eliminarán todos los productos del carrito.")) {
            seleccionados = [];
            renderizarSeleccionados();
        }
    }

    function calcularVuelto() {
        const efectivo = parseFloat(document.getElementById('efectivo_recibido').value) || 0;
        const totalBs = parseFloat(document.getElementById('total_bs').value) || 0;
        const vuelto = efectivo - totalBs;
        document.getElementById('vuelto').value = `Bs ${vuelto >= 0 ? vuelto.toFixed(2) : '0.00'}`;
    }

    function filtrarProductos() {
        const filtro = document.getElementById('busquedaProducto').value.toLowerCase().trim();
        document.querySelectorAll('.producto-card').forEach(card => {
            const nombre = card.querySelector('.nombre').innerText.toLowerCase();
            card.style.display = nombre.includes(filtro) ? 'flex' : 'none';
        });
    }

    document.getElementById('ventaForm').addEventListener('submit', function(e) {
        e.preventDefault();

        document.querySelectorAll('input[name="producto_id[]"], input[name="cantidad[]"], input[name="tipo[]"]').forEach(el => el.remove());

        seleccionados.forEach(p => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'producto_id[]';
            idInput.value = p.id;
            this.appendChild(idInput);

            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo[]';
            tipoInput.value = p.tipo;
            this.appendChild(tipoInput);

            const cantidadInput = document.createElement('input');
            cantidadInput.type = 'hidden';
            cantidadInput.name = 'cantidad[]';
            cantidadInput.value = p.cantidad;
            this.appendChild(cantidadInput);
        });

        this.submit();
    });

    // Inicializar carrito
    renderizarSeleccionados();
</script>

</body>
</html>