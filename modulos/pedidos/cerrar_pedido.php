<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
date_default_timezone_set('America/Caracas');

// === OBTENER ID DEL PEDIDO ===
$id_pedido = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);
if (!$id_pedido) {
    $id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}
if (!$id_pedido) {
    header("Location: gestionar_pedidos.php?error=ID de pedido no válido.");
    exit;
}

// === VERIFICAR QUE EL PEDIDO EXISTA Y ESTÉ ABIERTO O EN ESPERA ===
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id_pedido = ? AND estado IN ('abierto', 'en espera')");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pedido) {
    header("Location: gestionar_pedidos.php?error=Pedido no encontrado o ya cerrado.");
    exit;
}

// === OBTENER PRODUCTOS DEL PEDIDO ===
$stmt_productos = $pdo->prepare("
    SELECT 
        dp.cantidad,
        dp.precio_unitario,
        dp.tipo,
        COALESCE(p.nombre, prep.nombre) AS nombre_real
    FROM detalles_pedido dp
    LEFT JOIN productos p ON dp.id_producto = p.id_producto
    LEFT JOIN preparaciones prep ON dp.id_preparacion = prep.id_preparacion
    WHERE dp.id_pedido = ?
");
$stmt_productos->execute([$id_pedido]);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

if (empty($productos)) {
    header("Location: gestionar_pedidos.php?error=El pedido no tiene productos.");
    exit;
}

// === OBTENER TASA DE CAMBIO ===
$stmt_tasa = $pdo->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
$tasa = $stmt_tasa->fetch(PDO::FETCH_ASSOC);
$valor_dolar = $tasa ? $tasa['valor_dolar'] : 1;

// === CALCULAR TOTAL DEL PEDIDO ===
$total_pedido_usd = array_sum(array_map(function ($p) {
    return $p['precio_unitario'] * $p['cantidad'];
}, $productos));

// Valores iniciales
$delivery_usd = 0.00;
$propina_usd = 0.00;

// Si es delivery, asignar costo base
if ($pedido['tipo_pedido'] === 'delivery') {
    $delivery_usd = 2.00; // Puedes cambiar este valor
}

// Si se envían valores desde POST (por errores), mantenerlos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_usd = filter_input(INPUT_POST, 'delivery_usd', FILTER_VALIDATE_FLOAT) ?? $delivery_usd;
    $propina_usd = filter_input(INPUT_POST, 'propina_usd', FILTER_VALIDATE_FLOAT) ?? $propina_usd;
}

// Calcular totales finales
$total_usd = $total_pedido_usd + $delivery_usd + $propina_usd;
$total_bs = $total_usd * $valor_dolar;

// Manejo de errores y éxitos
$error = $success = "";
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerrar Pedido - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-group.inline {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }
        .form-group.inline input[type="number"] {
            width: 120px;
        }
        .resumen-total {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
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
        <li><a href="gestionar_pedidos.php"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-check-circle"></i><span class="nav-text">Cerrar Pedido</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial</span></a></li>
        <li><a href="propinas_mes.php"><i class="fas fa-hand-holding-usd"></i><span class="nav-text">Propinas del Mes</span></a></li>
        <li><a href="deudas_delivery.php"><i class="fas fa-truck"></i><span class="nav-text">Deudas Delivery</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-check-circle"></i> Cerrar Pedido #<?= $id_pedido ?></h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><i class="fas fa-receipt"></i> Productos del Pedido</h2>
            <?php if (empty($productos)): ?>
                <p>No hay productos en este pedido.</p>
            <?php else: ?>
                <table class="tabla-pedidos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nombre_real']) ?></td>
                                <td><?= $p['cantidad'] ?></td>
                                <td>$<?= number_format($p['precio_unitario'], 2) ?></td>
                                <td>$<?= number_format($p['precio_unitario'] * $p['cantidad'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="resumen-total">
                    <p><strong>Total Pedido:</strong> $<?= number_format($total_pedido_usd, 2) ?> (Bs <?= number_format($total_pedido_usd * $valor_dolar, 2) ?>)</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Formulario para cerrar pedido -->
        <section class="card">
            <h2><i class="fas fa-dollar-sign"></i> Detalles de Pago</h2>
            <form id="formCerrarPedido" method="POST" action="registrar_cierre.php">
                <input type="hidden" name="id_pedido" value="<?= $id_pedido ?>">

                <!-- Delivery (solo si es delivery) -->
                <?php if ($pedido['tipo_pedido'] === 'delivery'): ?>
                    <div class="form-group inline">
                        <label for="delivery_usd">🚚 Costo Delivery (USD)</label>
                        <input type="number" id="delivery_usd" name="delivery_usd" step="0.01" min="0"
                               value="<?= htmlspecialchars($delivery_usd) ?>" onchange="recalcularTotal()">
                    </div>
                <?php else: ?>
                    <input type="hidden" name="delivery_usd" value="0">
                <?php endif; ?>

                <!-- Propina -->
                <div class="form-group inline">
                    <label for="propina_usd">💡 Propina (USD)</label>
                    <input type="number" id="propina_usd" name="propina_usd" step="0.01" min="0"
                           value="<?= htmlspecialchars($propina_usd) ?>" onchange="recalcularTotal()">
                </div>

                <!-- Totales -->
                <div class="form-group">
                    <label>Total a Pagar (USD)</label>
                    <input type="text" id="total_usd" name="total_usd" readonly value="<?= number_format($total_usd, 2) ?>">
                </div>
                <div class="form-group">
                    <label>Total a Pagar (Bs)</label>
                    <input type="text" id="total_bs" name="total_bs" readonly value="<?= number_format($total_bs, 2) ?>">
                </div>

                <!-- Métodos de Pago -->
                <h3><i class="fas fa-credit-card"></i> Métodos de Pago</h3>
                <div id="metodosPago">
                    <div class="metodo-pago">
                        <select name="metodo_pago[]" class="form-control" required onchange="actualizarReferencia(this)">
                            <option value="">Selecciona método</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="efectivo_usd">Efectivo (USD)</option>
                            <option value="pago_movil">Pago Móvil</option>
                            <option value="zelle">Zelle</option>
                            <option value="tarjeta_debito">Tarjeta de Débito</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
                        </select>
                        <input type="text" name="referencia[]" class="form-control" placeholder="Referencia (opcional)">
                        <input type="number" name="monto_pago[]" class="form-control" placeholder="Monto en Bs" step="0.01" required>
                        <button type="button" class="btn btn-danger" onclick="eliminarMetodo(this)">Eliminar</button>
                    </div>
                </div>

                <button type="button" class="btn btn-secondary" onclick="agregarMetodoPago()">+ Agregar Método</button>

                <div class="form-actions">
                    <a href="gestionar_pedidos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Cerrar Pedido
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
    let valorDolar = <?= $valor_dolar ?>;

    function recalcularTotal() {
        const totalPedidoUsd = <?= $total_pedido_usd ?>;
        const deliveryUsd = parseFloat(document.getElementById('delivery_usd')?.value || 0);
        const propinaUsd = parseFloat(document.getElementById('propina_usd')?.value || 0);

        const totalUsd = totalPedidoUsd + deliveryUsd + propinaUsd;
        const totalBs = totalUsd * valorDolar;

        document.getElementById('total_usd').value = totalUsd.toFixed(2);
        document.getElementById('total_bs').value = totalBs.toFixed(2);
    }

    function agregarMetodoPago() {
        const contenedor = document.getElementById('metodosPago');
        const nuevo = document.createElement('div');
        nuevo.className = 'metodo-pago';
        nuevo.innerHTML = `
            <select name="metodo_pago[]" class="form-control" required onchange="actualizarReferencia(this)">
                <option value="">Selecciona método</option>
                <option value="efectivo">Efectivo</option>
                <option value="efectivo_usd">Efectivo (USD)</option>
                <option value="pago_movil">Pago Móvil</option>
                <option value="zelle">Zelle</option>
                <option value="tarjeta_debito">Tarjeta de Débito</option>
                <option value="transferencia">Transferencia</option>
                <option value="otro">Otro</option>
            </select>
            <input type="text" name="referencia[]" class="form-control" placeholder="Referencia (opcional)">
            <input type="number" name="monto_pago[]" class="form-control" placeholder="Monto en Bs" step="0.01" required>
            <button type="button" class="btn btn-danger" onclick="eliminarMetodo(this)">Eliminar</button>
        `;
        contenedor.appendChild(nuevo);
    }

    function eliminarMetodo(button) {
        if (document.querySelectorAll('.metodo-pago').length > 1) {
            button.parentElement.remove();
        } else {
            alert("Debe haber al menos un método de pago.");
        }
    }

    function actualizarReferencia(select) {
        const referenciaInput = select.parentElement.querySelector('input[name="referencia[]"]');
        const metodo = select.value;
        if (metodo === 'pago_movil' || metodo === 'zelle' || metodo === 'transferencia') {
            referenciaInput.placeholder = "Número de referencia";
            referenciaInput.required = true;
        } else {
            referenciaInput.placeholder = "Referencia (opcional)";
            referenciaInput.required = false;
        }
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function () {
        recalcularTotal();
    });
</script>

</body>
</html>