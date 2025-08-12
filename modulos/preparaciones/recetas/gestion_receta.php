<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../config/db.php';

$id_preparacion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_preparacion) {
    header("Location: preparaciones.php");
    exit;
}

// Obtener preparación
$stmt = $pdo->prepare("SELECT nombre FROM preparaciones WHERE id_preparacion = ?");
$stmt->execute([$id_preparacion]);
$preparacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$preparacion) {
    header("Location: preparaciones.php?error=Preparación no encontrada.");
    exit;
}

// Obtener productos (ingredientes)
$stmt_productos = $pdo->query("SELECT id_producto, nombre, precio_usd, stock FROM productos WHERE es_ingrediente = 1 AND stock > 0 ORDER BY nombre ASC");
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener receta actual
$stmt_receta = $pdo->prepare("SELECT r.id_receta, r.cantidad, p.nombre FROM recetas r JOIN productos p ON r.id_producto = p.id_producto WHERE r.id_preparacion = ?");
$stmt_receta->execute([$id_preparacion]);
$receta = $stmt_receta->fetchAll(PDO::FETCH_ASSOC);

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_FLOAT); // Ahora es FLOAT

    if (!$id_producto || $cantidad === false || $cantidad <= 0) {
        $error = "Producto y cantidad son obligatorios.";
    } else {
        try {
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT * FROM recetas WHERE id_preparacion = ? AND id_producto = ?");
            $stmt->execute([$id_preparacion, $id_producto]);
            if ($stmt->fetch()) {
                $success = "Ya existe este producto en la receta. Usa el módulo de edición para modificar la cantidad.";
                header("Location: gestion_receta.php?id=" . $id_preparacion . "&mensaje=" . urlencode($success));
                exit;
            } else {
                $stmt = $pdo->prepare("INSERT INTO recetas (id_preparacion, id_producto, cantidad) VALUES (?, ?, ?)");
                $stmt->execute([$id_preparacion, $id_producto, $cantidad]);
                $success = "Ingrediente agregado a la receta.";
                header("Location: gestion_receta.php?id=" . $id_preparacion . "&mensaje=" . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error al registrar el ingrediente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta - <?= htmlspecialchars($preparacion['nombre']) ?></title>
    <link rel="stylesheet" href="../../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../../assets/css/estilo-preparaciones.css">
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
        <li><a href="../../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li><a href="../../ventas/nueva_venta.php"><i class="fas fa-shopping-cart"></i><span class="nav-text">Nueva Venta</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-clipboard-list"></i><span class="nav-text">Gestionar Receta</span></a></li>
        <li><a href="../preparaciones.php"><i class="fas fa-layer-group"></i><span class="nav-text">Preparaciones</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-clipboard-list"></i> Receta - <?= htmlspecialchars($preparacion['nombre']) ?></h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <!-- Agregar ingrediente -->
        <section class="card">
            <h2><i class="fas fa-plus-circle"></i> Agregar Ingrediente</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['mensaje'])): ?>
                <div class="success"><?= htmlspecialchars($_GET['mensaje']) ?></div>
            <?php endif; ?>

            <form method="POST" action="gestion_receta.php?id=<?= $id_preparacion ?>">
                <input type="hidden" name="id_preparacion" value="<?= $id_preparacion ?>">
                <div class="form-group">
                    <label for="id_producto">Producto (Ingrediente)</label>
                    <select id="id_producto" name="id_producto" class="form-control" required>
                        <option value="">Seleccione un producto</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id_producto'] ?>">
                                <?= htmlspecialchars($p['nombre']) ?> - Stock: <?= number_format($p['stock'], 3) ?> - Precio: $<?= number_format($p['precio_usd'], 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cantidad">Cantidad consumida *</label>
                    <input type="number" id="cantidad" name="cantidad" class="form-control" required min="0.001" step="0.001" placeholder="Ej: 0.250">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Agregar a Receta
                    </button>
                </div>
            </form>
        </section>

        <!-- Listado de receta -->
        <section class="card">
            <h2><i class="fas fa-list-ul"></i> Ingredientes de <?= htmlspecialchars($preparacion['nombre']) ?></h2>
            <table class="tabla-preparaciones">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cantidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receta as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nombre']) ?></td>
                            <td><?= number_format($r['cantidad'], 3) ?></td>
                            <td class="acciones">
                                <a href="editar_receta.php?id=<?= $r['id_receta'] ?>" class="accion-btn">Editar</a>
                                <a href="eliminar_receta.php?id=<?= $r['id_receta'] ?>" class="accion-btn eliminar" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html>