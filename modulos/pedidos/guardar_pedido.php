<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Obtener datos del formulario
$id_pedido = $_POST['id_pedido'] ?? null;
$accion = $_POST['accion'] ?? 'guardar';
$tipo_pedido = $_POST['tipo_pedido'];
$numero_mesa = $_POST['numero_mesa'] ?? null;
$direccion_delivery = $_POST['direccion_delivery'] ?? null;
$telefono_cliente = $_POST['telefono_cliente'] ?? null;
$turno = $_POST['turno'];
$id_usuario = $_SESSION['usuario']['id_usuario'];

// Carrito de productos
$productos_json = $_POST['productos'] ?? '[]';
$productos = json_decode($productos_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    header("Location: nuevo_pedido.php?error=Error en los datos del carrito.");
    exit;
}

if (empty($productos)) {
    header("Location: nuevo_pedido.php?error=El pedido está vacío.");
    exit;
}

// Estado del pedido
$estado = $accion === 'en_espera' ? 'en espera' : 'abierto';

// Tasa de cambio
$stmt_tasa = $pdo->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
$tasa = $stmt_tasa->fetch(PDO::FETCH_ASSOC);
$valor_dolar = $tasa ? $tasa['valor_dolar'] : 1;

try {
    $pdo->beginTransaction();

    // Insertar o actualizar el pedido
    if ($id_pedido) {
        $stmt = $pdo->prepare("UPDATE pedidos SET numero_mesa = ?, direccion_delivery = ?, telefono_cliente = ?, estado = ?, tipo_pedido = ? WHERE id_pedido = ?");
        $stmt->execute([$numero_mesa, $direccion_delivery, $telefono_cliente, $estado, $tipo_pedido, $id_pedido]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO pedidos (id_turno, id_usuario, numero_mesa, tipo_pedido, direccion_delivery, telefono_cliente, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$turno, $id_usuario, $numero_mesa, $tipo_pedido, $direccion_delivery, $telefono_cliente, $estado]);
        $id_pedido = $pdo->lastInsertId();
    }

    // Borrar detalles anteriores
    $pdo->prepare("DELETE FROM detalles_pedido WHERE id_pedido = ?")->execute([$id_pedido]);

    // Insertar nuevos detalles
    $total_usd = 0;
    $total_bs = 0;

    foreach ($productos as $p) {
        $id = intval($p['id']);
        $cantidad = intval($p['cantidad']);
        $tipo = $p['tipo']; // 'producto' o 'preparacion'

        if ($cantidad <= 0 || !in_array($tipo, ['producto', 'preparacion'])) {
            continue;
        }

        // Validar que el producto o preparación exista y obtener precio
        if ($tipo === 'producto') {
            $stmt_item = $pdo->prepare("SELECT id_producto, nombre, precio_usd FROM productos WHERE id_producto = ?");
        } else {
            $stmt_item = $pdo->prepare("SELECT id_preparacion, nombre, precio_usd FROM preparaciones WHERE id_preparacion = ?");
        }

        $stmt_item->execute([$id]);
        $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception("Producto o preparación no encontrado: ID $id");
        }

        $precio_usd = $item['precio_usd'];
        $subtotal_usd = $precio_usd * $cantidad;
        $subtotal_bs = $subtotal_usd * $valor_dolar;

        $total_usd += $subtotal_usd;
        $total_bs += $subtotal_bs;

        // Insertar en detalles_pedido
        $stmt_detalle = $pdo->prepare("INSERT INTO detalles_pedido (id_pedido, id_producto, id_preparacion, cantidad, precio_unitario, subtotal_usd, subtotal_bs, tipo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        // Asignar id_producto e id_preparacion según el tipo
        $id_producto = ($tipo === 'producto') ? $id : null;
        $id_preparacion = ($tipo === 'preparacion') ? $id : null;

        $stmt_detalle->execute([
            $id_pedido,
            $id_producto,
            $id_preparacion,
            $cantidad,
            $precio_usd,
            $subtotal_usd,
            $subtotal_bs,
            $tipo
        ]);
    }

    // Actualizar totales en el pedido
    $pdo->prepare("UPDATE pedidos SET total_usd = ?, total_bs = ? WHERE id_pedido = ?")
        ->execute([$total_usd, $total_bs, $id_pedido]);

    $pdo->commit();

    // Redirigir
    if ($accion === 'en_espera') {
        header("Location: gestionar_pedidos.php?mensaje=Pedido puesto en espera.");
    } else {
        header("Location: nuevo_pedido.php?id=$id_pedido&mensaje=Pedido actualizado.");
    }
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: nuevo_pedido.php?error=Error: " . $e->getMessage());
    exit;
}
?>