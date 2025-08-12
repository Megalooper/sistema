<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_producto) {
    header("Location: productos.php");
    exit;
}

// Obtener producto
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
$stmt->execute([$id_producto]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header("Location: productos.php?error=Producto no encontrado.");
    exit;
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio_usd = filter_input(INPUT_POST, 'precio_usd', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_FLOAT); // Ahora es FLOAT
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $es_ingrediente = isset($_POST['es_ingrediente']) ? 1 : 0;
    $visible_venta = isset($_POST['visible_venta']) ? 1 : 0;

    if (empty($nombre) || $precio_usd === false || $stock === false || $stock < 0 || !$id_categoria) {
        $error = "Por favor, completa todos los campos correctamente.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE productos 
                SET nombre = ?, descripcion = ?, precio_usd = ?, stock = ?, id_categoria = ?, 
                    es_ingrediente = ?, visible_venta = ? 
                WHERE id_producto = ?");
            $stmt->execute([
                $nombre, $descripcion, $precio_usd, $stock, $id_categoria, 
                $es_ingrediente, $visible_venta, $id_producto
            ]);
            $success = "Producto actualizado correctamente.";
            header("Location: productos.php?mensaje=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar el producto.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
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
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-edit"></i><span class="nav-text">Editar Producto</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-edit"></i> Editar Producto</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-edit"></i> Editar Producto</h2>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre del Producto *</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="precio_usd">Precio en USD *</label>
                    <input type="number" id="precio_usd" name="precio_usd" step="0.01" min="0" 
                           value="<?= $producto['precio_usd'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock Actual *</label>
                    <input type="number" id="stock" name="stock" step="0.001" min="0" 
                           value="<?= number_format($producto['stock'], 3) ?>" required 
                           placeholder="Ej: 5.250">
                </div>

                <div class="form-group">
                    <label for="id_categoria">Categoría *</label>
                    <?php
                    $stmt_categorias = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
                    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <select id="id_categoria" name="id_categoria" required>
                        <option value="">Selecciona una categoría...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>" <?= $producto['id_categoria'] == $cat['id_categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="es_ingrediente" value="1" <?= $producto['es_ingrediente'] ? 'checked' : '' ?>>
                        Es ingrediente (materia prima)
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="visible_venta" value="1" <?= $producto['visible_venta'] ? 'checked' : '' ?>>
                        Visible en ventas
                    </label>
                </div>

                <div class="form-actions">
                    <a href="productos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>