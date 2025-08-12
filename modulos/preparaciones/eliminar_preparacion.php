<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_preparacion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_preparacion) {
    header("Location: preparaciones.php?error=ID de preparación inválido.");
    exit;
}

try {
    // Eliminar preparación
    $stmt = $pdo->prepare("DELETE FROM preparaciones WHERE id_preparacion = ?");
    $stmt->execute([$id_preparacion]);

    header("Location: preparaciones.php?mensaje=Preparación eliminada correctamente.");
    exit;
} catch (PDOException $e) {
    header("Location: preparaciones.php?error=No se puede eliminar la preparación porque tiene recetas o ventas asociadas.");
    exit;
}