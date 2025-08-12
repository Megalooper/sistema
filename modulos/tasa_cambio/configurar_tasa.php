<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$error = $success = "";

// Obtener tasa actual
$stmt = $pdo->query("SELECT * FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
$tasa_actual = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valor_dolar = filter_input(INPUT_POST, 'valor_dolar', FILTER_VALIDATE_FLOAT);

    if ($valor_dolar === false || $valor_dolar <= 0) {
        $error = "Por favor, ingresa un valor válido para el dólar (mayor a 0).";
    } else {
        try {
            // Opcional: eliminar tasas anteriores
            $pdo->exec("DELETE FROM tasas_cambio");

            // Insertar nueva tasa
            $stmt = $pdo->prepare("INSERT INTO tasas_cambio (valor_dolar) VALUES (?)");
            $stmt->execute([$valor_dolar]);

            $success = "Tasa de cambio actualizada correctamente.";

            // Recargar la tasa actual
            $stmt = $pdo->query("SELECT * FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
            $tasa_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error al guardar la tasa de cambio.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configurar Tasa de Cambio - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-producto.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css ">
    <link rel="icon" href="../../assets/img/favicon.ico" type="image/x-icon">
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
        <li><a href="../productos/productos.php"><i class="fas fa-boxes"></i><span class="nav-text">Productos</span></a></li>
        <li><a href="../inventario/inventario.php"><i class="fas fa-database"></i><span class="nav-text">Inventario</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-dollar-sign"></i><span class="nav-text">Tasa de Cambio</span></a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-dollar-sign"></i> Configurar Tasa de Cambio</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-edit"></i> Configuración de Tasa de Cambio</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="configurar_tasa.php">
                <div class="form-group">
                    <label for="valor_dolar">Valor del Dólar (Bs) *</label>
                    <input type="number" id="valor_dolar" name="valor_dolar" step="0.01" min="0.01"
                           class="form-control" required
                           value="<?= $tasa_actual ? number_format($tasa_actual['valor_dolar'], 2) : '' ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Tasa
                    </button>
                </div>
            </form>

            <?php if ($tasa_actual): ?>
                <div class="current-rate">
                    <h3><i class="fas fa-info-circle"></i> Tasa Actual</h3>
                    <p><strong>Valor del dólar:</strong> Bs <?= number_format($tasa_actual['valor_dolar'], 2) ?></p>
                    <p><strong>Última actualización:</strong> <?= date('d/m/Y H:i', strtotime($tasa_actual['fecha_registro'])) ?></p>
                </div>
            <?php else: ?>
                <div class="current-rate">
                    <h3><i class="fas fa-exclamation-triangle"></i> Información</h3>
                    <p>No hay una tasa de cambio registrada actualmente.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Resumen General -->
        <section class="card">
            <h2>Resumen General</h2>
            <div class="overview">
                <div class="metric">
                    <i class="fas fa-cubes"></i>
                    <div>
                        <span>Total Productos</span>
                        <strong><?= number_format($pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn(), 0) ?></strong>
                    </div>
                </div>
                <div class="metric">
                    <i class="fas fa-list-ul"></i>
                    <div>
                        <span>Total Ventas</span>
                        <strong><?= number_format($pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn(), 0) ?></strong>
                    </div>
                </div>
                <div class="metric">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <span>Total Stock</span>
                        <strong><?= number_format($pdo->query("SELECT SUM(stock) FROM productos")->fetchColumn(), 0) ?></strong>
                    </div>
                </div>
                <div class="metric alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <span>Fuera de Stock</span>
                        <strong><?= number_format($pdo->query("SELECT COUNT(*) FROM productos WHERE stock = 0")->fetchColumn(), 0) ?></strong>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>