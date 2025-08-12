<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$error = $success = "";

// Obtener ID de la preparación
$id_preparacion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_preparacion) {
    header("Location: preparaciones.php?error=ID no válido.");
    exit;
}

// Obtener preparación actual
$stmt = $pdo->prepare("SELECT * FROM preparaciones WHERE id_preparacion = ?");
$stmt->execute([$id_preparacion]);
$preparacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$preparacion) {
    header("Location: preparaciones.php?error=Preparación no encontrada.");
    exit;
}

// Obtener categorías para el formulario
$stmt_categorias = $pdo->query("SELECT c.id_categoria, c.nombre AS categoria, a.nombre AS area FROM categorias c LEFT JOIN areas a ON c.id_area = a.id_area ORDER BY a.nombre, c.nombre");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio_usd = filter_input(INPUT_POST, 'precio_usd', FILTER_VALIDATE_FLOAT);
    $id_categoria = $_POST['id_categoria'] ?? null;

    if (empty($nombre) || $precio_usd === false || $precio_usd < 0 || !$id_categoria) {
        $error = "Por favor, completa todos los campos obligatorios correctamente.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE preparaciones SET id_categoria = ?, nombre = ?, descripcion = ?, precio_usd = ? WHERE id_preparacion = ?");
            $stmt->execute([$id_categoria, $nombre, $descripcion, $precio_usd, $id_preparacion]);
            header("Location: preparaciones.php?mensaje=Preparación actualizada exitosamente.");
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar la preparación.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Preparación - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-preparaciones.css">
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
        <li><a href="../productos/productos.php"><i class="fas fa-boxes"></i><span class="nav-text">Productos</span></a></li>
        <li><a href="../productos/categorias/categorias.php"><i class="fas fa-layer-group"></i><span class="nav-text">Categorías</span></a></li>
        <li class="active"><a href="preparaciones.php"><i class="fas fa-hamburger"></i><span class="nav-text">Preparaciones</span></a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-hamburger"></i> Editar Preparación</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <!-- Formulario de edición -->
        <section class="card">
            <h2><i class="fas fa-edit"></i> Editar Preparación</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="editar_preparacion.php?id=<?= $id_preparacion ?>">
                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select id="categoria" name="id_categoria" class="form-control" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>" <?= $preparacion['id_categoria'] == $cat['id_categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['area'] . " - " . $cat['categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre de la Preparación *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required 
                           value="<?= htmlspecialchars($preparacion['nombre']) ?>" placeholder="Ej: Hamburguesa Sencilla">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción (opcional)</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($preparacion['descripcion']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="precio_usd">Precio en USD *</label>
                    <input type="number" id="precio_usd" name="precio_usd" step="0.01" min="0.01" class="form-control" required 
                           value="<?= htmlspecialchars($preparacion['precio_usd']) ?>">
                </div>
                <div class="form-actions">
                    <a href="preparaciones.php" class="btn btn-secondary">
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