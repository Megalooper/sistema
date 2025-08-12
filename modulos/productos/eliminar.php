<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_producto) {
    header("Location: productos.php?error=Producto no válido.");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmt->execute([$id_producto]);
    $success = "Producto eliminado correctamente.";
} catch (PDOException $e) {
    $success = "No se puede eliminar el producto porque está asociado a ventas.";
}

header("Location: productos.php?mensaje=" . urlencode($success));
exit;
?>