<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../config/db.php';

$id_categoria = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_categoria) {
    header("Location: categorias.php?error=Categoría no válida.");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id_categoria = ?");
    $stmt->execute([$id_categoria]);
    $success = "Categoría eliminada correctamente.";
} catch (PDOException $e) {
    $success = "No se puede eliminar la categoría porque tiene productos asociados.";
}

header("Location: categorias.php?mensaje=" . urlencode($success));
exit;
?>