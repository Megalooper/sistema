<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_nota = filter_input(INPUT_POST, 'id_nota', FILTER_VALIDATE_INT);
$titulo = trim($_POST['titulo']);
$tipo = $_POST['tipo'];
$id_usuario = $_SESSION['usuario']['id_usuario'];

if (!$id_nota || empty($titulo) || !in_array($tipo, ['texto', 'tabla'])) {
    header("Location: editar_nota.php?id=$id_nota&error=Datos inválidos.");
    exit;
}

try {
    // Verificar que la nota pertenezca al usuario
    $stmt = $pdo->prepare("SELECT id_usuario FROM notas WHERE id_nota = ?");
    $stmt->execute([$id_nota]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota || $nota['id_usuario'] != $id_usuario) {
        header("Location: notas.php?error=Permiso denegado.");
        exit;
    }

    if ($tipo === 'texto') {
        $contenido = $_POST['contenido'] ?? '';
        $stmt = $pdo->prepare("UPDATE notas SET titulo = ?, tipo = ?, contenido = ? WHERE id_nota = ?");
        $stmt->execute([$titulo, $tipo, $contenido, $id_nota]);
    } 
    elseif ($tipo === 'tabla') {
        $contenido = $_POST['contenido_tabla'] ?? '[]';
        $stmt = $pdo->prepare("UPDATE notas SET titulo = ?, tipo = ?, contenido = ? WHERE id_nota = ?");
        $stmt->execute([$titulo, $tipo, $contenido, $id_nota]);
    }

    header("Location: notas.php?success=Nota actualizada correctamente.");
    exit;

} catch (PDOException $e) {
    error_log("Error al actualizar nota: " . $e->getMessage());
    header("Location: editar_nota.php?id=$id_nota&error=No se pudo actualizar.");
    exit;
}
?>