<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pedido) {
    header("Location: gestionar_pedidos.php?error=ID de pedido inválido.");
    exit;
}

// Verificar que el pedido esté abierto o en espera
$stmt = $pdo->prepare("SELECT estado FROM pedidos WHERE id_pedido = ? AND estado IN ('abierto', 'en espera')");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    header("Location: gestionar_pedidos.php?error=El pedido no se puede cancelar.");
    exit;
}

// Actualizar estado a cancelado
try {
    $pdo->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id_pedido = ?")->execute([$id_pedido]);
    header("Location: gestionar_pedidos.php?mensaje=Pedido cancelado correctamente.");
    exit;
} catch (PDOException $e) {
    header("Location: gestionar_pedidos.php?error=No se pudo cancelar el pedido.");
    exit;
}
?>