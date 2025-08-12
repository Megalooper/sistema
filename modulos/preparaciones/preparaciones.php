<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$error = $success = "";

// Obtener categorías para el formulario
$stmt_categorias = $pdo->query("SELECT c.id_categoria, c.nombre AS categoria, a.nombre AS area FROM categorias c LEFT JOIN areas a ON c.id_area = a.id_area ORDER BY a.nombre, c.nombre");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener preparaciones con su categoría y área
$stmt = $pdo->query("SELECT p.id_preparacion, p.nombre, p.descripcion, p.precio_usd, c.nombre AS categoria, a.nombre AS area
                     FROM preparaciones p
                     JOIN categorias c ON p.id_categoria = c.id_categoria
                     JOIN areas a ON c.id_area = a.id_area
                     ORDER BY area, categoria, p.nombre");
$preparaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Preparaciones - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-preparaciones.css">
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
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li><a href="../productos/productos.php"><i class="fas fa-boxes"></i><span class="nav-text">Productos</span></a></li>
        <li><a href="../productos/categorias/categorias.php"><i class="fas fa-layer-group"></i><span class="nav-text">Categorías</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-hamburger"></i><span class="nav-text">Preparaciones</span></a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-hamburger"></i> Gestión de Preparaciones</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <!-- Registro de preparación -->
        <section class="card">
            <h2><i class="fas fa-plus-circle"></i> Registrar Preparación</h2>

            <?php
            if (isset($_GET['mensaje'])) {
                echo '<div class="success">' . htmlspecialchars($_GET['mensaje']) . '</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div class="error">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>

            <form method="POST" action="../preparaciones/registrar_preparacion.php">
                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select id="categoria" name="id_categoria" class="form-control" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>">
                                <?= htmlspecialchars($cat['area'] . " - " . $cat['categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre de la Preparación *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Hamburguesa Sencilla">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción (opcional)</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="precio_usd">Precio en USD *</label>
                    <input type="number" id="precio_usd" name="precio_usd" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Preparación
                    </button>
                </div>
            </form>
        </section>

        <!-- Listado de preparaciones -->
        <section class="card">
            <h2><i class="fas fa-list-ul"></i> Listado de Preparaciones</h2>
            <div class="table-container">
                <table class="tabla-preparaciones">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Área</th>
                            <th>Categoría</th>
                            <th>Precio USD</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preparaciones as $p): ?>
                            <tr>
                                <td><?= $p['id_preparacion'] ?></td>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><?= htmlspecialchars($p['area']) ?></td>
                                <td><?= htmlspecialchars($p['categoria']) ?></td>
                                <td>$<?= number_format($p['precio_usd'], 2) ?></td>
                                <td class="acciones">
                                    <a href="editar_preparacion.php?id=<?= $p['id_preparacion'] ?>" class="accion-btn editar">Editar</a>
                                    <a href="eliminar_preparacion.php?id=<?= $p['id_preparacion'] ?>" class="accion-btn eliminar" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
                                    <a href="recetas/gestion_receta.php?id=<?= $p['id_preparacion'] ?>" class="accion-btn gestionar">Gestionar Receta</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>