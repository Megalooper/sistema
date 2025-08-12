<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../config/db.php';

$id_receta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_receta) {
    header("Location: gestion_receta.php");
    exit;
}

// Obtener receta
$stmt = $pdo->prepare("SELECT r.*, p.nombre AS nombre_producto FROM recetas r JOIN productos p ON r.id_producto = p.id_producto WHERE r.id_receta = ?");
$stmt->execute([$id_receta]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receta) {
    header("Location: gestion_receta.php?error=Receta no encontrada.");
    exit;
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_FLOAT);

    if ($cantidad === false || $cantidad <= 0) {
        $error = "Cantidad inválida.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE recetas SET cantidad = ? WHERE id_receta = ?");
            $stmt->execute([$cantidad, $id_receta]);
            $success = "Cantidad actualizada.";
            header("Location: gestion_receta.php?id=" . $receta['id_preparacion'] . "&mensaje=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Receta</title>
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
        <li class="active"><a href="#"><i class="fas fa-edit"></i><span class="nav-text">Editar Receta</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-edit"></i> Editar Receta</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2>Editar Cantidad de Ingrediente</h2>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Producto</label>
                    <p><strong><?= htmlspecialchars($receta['nombre_producto']) ?></strong></p>
                </div>
                <div class="form-group">
                    <label for="cantidad">Cantidad *</label>
                    <input type="number" id="cantidad" name="cantidad" class="form-control" required min="0.001" step="0.001" value="<?= $receta['cantidad'] ?>" placeholder="Ej: 0.250">
                </div>
                <div class="form-actions">
                    <a href="gestion_receta.php?id=<?= $receta['id_preparacion'] ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>