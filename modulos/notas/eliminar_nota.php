<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_nota = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_usuario = $_SESSION['usuario']['id_usuario'];

if (!$id_nota) {
    header("Location: notas.php?error=ID de nota no válido.");
    exit;
}

try {
    // Verificar que la nota pertenezca al usuario
    $stmt = $pdo->prepare("SELECT id_usuario FROM notas WHERE id_nota = ?");
    $stmt->execute([$id_nota]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota || $nota['id_usuario'] != $id_usuario) {
        header("Location: notas.php?error=No tienes permiso para eliminar esta nota.");
        exit;
    }

    // Eliminar la nota
    $stmt = $pdo->prepare("DELETE FROM notas WHERE id_nota = ?");
    $stmt->execute([$id_nota]);

    header("Location: notas.php?success=Nota eliminada correctamente.");
    exit;

} catch (PDOException $e) {
    error_log("Error al eliminar nota: " . $e->getMessage());
    header("Location: notas.php?error=No se pudo eliminar la nota.");
    exit;
}
?>