<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
    $descripcion = trim($_POST['descripcion'] ?? '');

    $tipos_validos = ['compra', 'produccion', 'merma'];

    // Validar datos
    if (!$id_producto || !in_array($tipo, $tipos_validos) || $cantidad <= 0) {
        header("Location: inventario.php?error=Datos incompletos o inválidos.");
        exit;
    }

    $id_usuario = $_SESSION['usuario']['id_usuario'];

    try {
        $pdo->beginTransaction();

        // Registrar el movimiento
        $stmt = $pdo->prepare("
            INSERT INTO inventario_movimientos 
            (id_producto, tipo, cantidad, descripcion, id_usuario) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_producto, $tipo, $cantidad, $descripcion, $id_usuario]);

        // Actualizar stock
        // Si es merma, se resta; si es compra o producción, se suma
        $ajuste_stock = ($tipo === 'merma') ? -$cantidad : $cantidad;

        $stmt_stock = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
        $stmt_stock->execute([$ajuste_stock, $id_producto]);

        // Confirmar transacción
        $pdo->commit();

        // Redirigir con éxito
        header("Location: inventario.php?mensaje=Movimiento registrado. Stock actualizado.");
        exit;

    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Error en registrar_movimiento.php: " . $e->getMessage());
        header("Location: inventario.php?error=No se pudo registrar el movimiento.");
        exit;
    }
} else {
    // Si no es POST, redirigir
    header("Location: inventario.php");
    exit;
}
?>