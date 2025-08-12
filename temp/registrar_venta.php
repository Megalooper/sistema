<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Obtener datos del formulario
$productos_id = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$tipos = $_POST['tipo'] ?? [];
$turno = filter_input(INPUT_POST, 'turno', FILTER_VALIDATE_INT);
$usuario = $_SESSION['usuario']['id_usuario'];

if (
    !$turno ||
    empty($productos_id) ||
    count($productos_id) !== count($cantidades) ||
    count($productos_id) !== count($tipos)
) {
    header("Location: nueva_venta.php?error=Datos incompletos.");
    exit;
}

try {
    $pdo->beginTransaction();

    // Insertar venta
    $total_usd = 0;
    $total_bs = 0;
    $stmt_tasa = $pdo->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
    $tasa = $stmt_tasa->fetch(PDO::FETCH_ASSOC);
    $valor_dolar = $tasa ? $tasa['valor_dolar'] : 1;

    $stmt_venta = $pdo->prepare("INSERT INTO ventas (id_vendedor, total_usd, total_bs, fecha_venta, id_turno) VALUES (?, ?, ?, NOW(), ?)");
    $stmt_venta->execute([$usuario, $total_usd, $total_bs, $turno]);
    $id_venta = $pdo->lastInsertId();

    // Insertar detalles de venta
    foreach ($productos_id as $i => $id) {
        $cantidad = intval($cantidades[$i]);
        $tipo = $tipos[$i];
        if ($cantidad <= 0) continue;

        if ($tipo === 'producto') {
            $stmt = $pdo->prepare("SELECT precio_usd FROM productos WHERE id_producto = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) continue;

            $precio = $item['precio_usd'];
            $subtotal_usd = $precio * $cantidad;
            $subtotal_bs = $subtotal_usd * $valor_dolar;

            // Insertar en detalles_venta (solo id_producto)
            $pdo->prepare("INSERT INTO detalles_venta (id_venta, id_producto, id_preparacion, cantidad, precio_unitario_usd, precio_unitario_bs, subtotal_usd, subtotal_bs)
                VALUES (?, ?, NULL, ?, ?, ?, ?, ?)")
                ->execute([$id_venta, $id, $cantidad, $precio, $precio * $valor_dolar, $subtotal_usd, $subtotal_bs]);

            // Actualizar stock del producto vendido
            $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?")
                ->execute([$cantidad, $id]);

        } elseif ($tipo === 'preparacion') {
            $stmt = $pdo->prepare("SELECT precio_usd FROM preparaciones WHERE id_preparacion = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) continue;

            $precio = $item['precio_usd'];
            $subtotal_usd = $precio * $cantidad;
            $subtotal_bs = $subtotal_usd * $valor_dolar;

            // Insertar en detalles_venta (solo id_preparacion)
            $pdo->prepare("INSERT INTO detalles_venta (id_venta, id_producto, id_preparacion, cantidad, precio_unitario_usd, precio_unitario_bs, subtotal_usd, subtotal_bs)
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?)")
                ->execute([$id_venta, $id, $cantidad, $precio, $precio * $valor_dolar, $subtotal_usd, $subtotal_bs]);

            // Descontar productos usados en la preparación (usando recetas)
            $stmtReceta = $pdo->prepare("SELECT id_producto, cantidad FROM recetas WHERE id_preparacion = ?");
            $stmtReceta->execute([$id]);
            while ($receta = $stmtReceta->fetch(PDO::FETCH_ASSOC)) {
                $idProd = $receta['id_producto'];
                $cantUsada = $receta['cantidad'] * $cantidad;
                $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?")
                    ->execute([$cantUsada, $idProd]);
            }
        }

        $total_usd += $subtotal_usd;
        $total_bs += $subtotal_bs;
    }

    // Actualizar totales en la venta
    $pdo->prepare("UPDATE ventas SET total_usd = ?, total_bs = ? WHERE id_venta = ?")
        ->execute([$total_usd, $total_bs, $id_venta]);

    $pdo->commit();

    $_SESSION['venta_id'] = $id_venta;
    header("Location: nueva_venta.php?exito=1&id=$id_venta");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: nueva_venta.php?error=No se pudo registrar la venta.");
    exit;
}