<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../config/db.php';

// Obtener y validar el ID de la receta
$id_receta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_receta) {
    header("Location: gestion_receta.php?error=ID de receta no válido.");
    exit;
}

// Obtener información de la receta antes de eliminar (opcional, para logs)
$stmt_select = $pdo->prepare("
    SELECT r.id_receta, r.id_preparacion, r.id_producto, r.cantidad, p.nombre AS nombre_producto
    FROM recetas r
    JOIN productos p ON r.id_producto = p.id_producto
    WHERE r.id_receta = ?
");
$stmt_select->execute([$id_receta]);
$receta = $stmt_select->fetch(PDO::FETCH_ASSOC);

if (!$receta) {
    header("Location: gestion_receta.php?error=Receta no encontrada.");
    exit;
}

// Intentar eliminar
try {
    $stmt_delete = $pdo->prepare("DELETE FROM recetas WHERE id_receta = ?");
    $stmt_delete->execute([$id_receta]);

    $success = "Ingrediente '" . htmlspecialchars($receta['nombre_producto']) . "' eliminado de la receta.";
    header("Location: gestion_receta.php?id=" . $receta['id_preparacion'] . "&mensaje=" . urlencode($success));
    exit;

} catch (PDOException $e) {
    error_log("Error al eliminar receta (id_receta=$id_receta): " . $e->getMessage());
    $error = "No se pudo eliminar el ingrediente. Puede que esté protegido por una restricción del sistema.";
    header("Location: gestion_receta.php?id=" . $receta['id_preparacion'] . "&error=" . urlencode($error));
    exit;
}
?>