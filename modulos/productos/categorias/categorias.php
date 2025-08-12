<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../config/db.php';

$error = $success = "";

// Obtener categorías con el nombre del área
$stmt_categorias = $pdo->query("SELECT c.id_categoria, c.nombre, a.nombre AS area FROM categorias c LEFT JOIN areas a ON c.id_area = a.id_area ORDER BY a.nombre, c.nombre");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener áreas para el formulario
$stmt_areas = $pdo->query("SELECT id_area, nombre FROM areas ORDER BY nombre ASC");
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

// Registrar categoría
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nombre = trim($_POST['nombre']);
    $id_area = filter_input(INPUT_POST, 'area', FILTER_VALIDATE_INT);

    if (empty($nombre) || !$id_area) {
        $error = "Nombre y área son obligatorios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre, id_area) VALUES (?, ?)");
            $stmt->execute([$nombre, $id_area]);
            $success = "Categoría registrada correctamente.";
            header("Location: categorias.php?mensaje=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Error al registrar la categoría.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorías - Comida Rápida</title>
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
        <li><a href="../productos.php"><i class="fas fa-boxes"></i><span class="nav-text">Productos</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-layer-group"></i><span class="nav-text">Categorías</span></a></li>
        <li><a href="../../preparaciones/preparaciones.php"><i class="fas fa-hamburger"></i><span class="nav-text">Preparaciones</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-layer-group"></i> Gestión de Categorías</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <!-- Registro de categoría -->
        <section class="card">
            <h2><i class="fas fa-plus-circle"></i> Registrar Categoría</h2>

            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="categorias.php">
                <input type="hidden" name="registrar" value="1">
                <div class="form-group">
                    <label for="nombre">Nombre de la Categoría *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Bebidas, Hamburguesas">
                </div>
                <div class="form-group">
                    <label for="area">Área *</label>
                    <select id="area" name="area" class="form-control" required>
                        <option value="">Seleccione un área</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id_area'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar Categoría</button>
                </div>
            </form>
        </section>

        <!-- Listado de categorías -->
        <section class="card">
            <h2><i class="fas fa-list-ul"></i> Listado de Categorías</h2>
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Área</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td><?= $cat['id_categoria'] ?></td>
                            <td><?= htmlspecialchars($cat['nombre']) ?></td>
                            <td><?= htmlspecialchars($cat['area']) ?></td>
                            <td class="acciones">
                                <a href="editar_categoria.php?id=<?= $cat['id_categoria'] ?>" class="editar">Editar</a>
                                <a href="eliminar_categoria.php?id=<?= $cat['id_categoria'] ?>" onclick="return confirm('¿Estás seguro?')" class="eliminar">Eliminar</a>
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