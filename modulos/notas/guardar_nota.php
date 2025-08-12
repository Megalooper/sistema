<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$titulo = trim($_POST['titulo']);
$tipo = $_POST['tipo'];
$id_usuario = $_SESSION['usuario']['id_usuario'];

if (empty($titulo)) {
    header("Location: notas.php?error=Título es obligatorio");
    exit;
}

if ($tipo === 'texto') {
    $contenido = $_POST['contenido'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO notas (titulo, tipo, contenido, id_usuario) VALUES (?, 'texto', ?, ?)");
    $stmt->execute([$titulo, $contenido, $id_usuario]);
} 
elseif ($tipo === 'tabla') {
    $contenido = $_POST['contenido_tabla'] ?? '[]';
    $stmt = $pdo->prepare("INSERT INTO notas (titulo, tipo, contenido, id_usuario) VALUES (?, 'tabla', ?, ?)");
    $stmt->execute([$titulo, $contenido, $id_usuario]);
}

header("Location: notas.php?success=Nota guardada correctamente");
exit;
?>