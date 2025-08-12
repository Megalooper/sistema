<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../config/db.php';

$id_categoria = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_categoria) {
    header("Location: categorias.php");
    exit;
}

// Obtener categoría
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id_categoria = ?");
$stmt->execute([$id_categoria]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header("Location: categorias.php?error=Categoría no encontrada.");
    exit;
}

// Obtener áreas
$stmt_areas = $pdo->query("SELECT id_area, nombre FROM areas");
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $id_area = filter_input(INPUT_POST, 'area', FILTER_VALIDATE_INT);

    if (empty($nombre) || !$id_area) {
        $error = "Nombre y área son obligatorios.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, id_area = ? WHERE id_categoria = ?");
            $stmt->execute([$nombre, $id_area, $id_categoria]);
            $success = "Categoría actualizada correctamente.";
            header("Location: categorias.php?mensaje=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar la categoría.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoría - Comida Rápida</title>
    <link rel="stylesheet" href="../../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../../assets/css/estilo-producto.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css ">
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
        <li class="active"><a href="#"><i class="fas fa-layer-group"></i><span class="nav-text">Categorías</span></a></li>
        <li><a href="../productos.php"><i class="fas fa-boxes"></i><span class="nav-text">Productos</span></a></li>
        <li><a href="../../ventas/nueva_venta.php"><i class="fas fa-shopping-cart"></i><span class="nav-text">Nueva Venta</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-edit"></i> Editar Categoría</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-edit"></i> Editar Categoría</h2>

            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="editar_categoria.php?id=<?= $id_categoria ?>">
                <div class="form-group">
                    <label for="nombre">Nombre de la Categoría *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($categoria['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="area">Área *</label>
                    <select id="area" name="area" class="form-control" required>
                        <option value="">Seleccione un área</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id_area'] ?>"<?= $categoria['id_area'] == $a['id_area'] ? ' selected' : '' ?>>
                                <?= htmlspecialchars($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                    <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>