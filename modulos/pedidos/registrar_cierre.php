<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require '../../vendor/autoload.php';
require_once '../../config/db.php';
date_default_timezone_set('America/Caracas');

// Obtener ID del pedido
$id_pedido = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);
if (!$id_pedido) {
    header("Location: gestionar_pedidos.php?error=ID de pedido no válido.");
    exit;
}

// Verificar que el pedido exista y esté abierto o en espera
$stmt = $pdo->prepare("SELECT estado, tipo_pedido, direccion_delivery, telefono_cliente FROM pedidos WHERE id_pedido = ? AND estado IN ('abierto', 'en espera')");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pedido) {
    header("Location: gestionar_pedidos.php?error=Pedido no encontrado o ya cerrado.");
    exit;
}

// === OBTENER TASA DE CAMBIO ===
$stmt_tasa = $pdo->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
$tasa = $stmt_tasa->fetch(PDO::FETCH_ASSOC);
$valor_dolar = $tasa ? $tasa['valor_dolar'] : 1;

// === OBTENER TOTALES DEL PEDIDO ===
$total_usd = filter_input(INPUT_POST, 'total_usd', FILTER_VALIDATE_FLOAT);
$total_bs = filter_input(INPUT_POST, 'total_bs', FILTER_VALIDATE_FLOAT);
$delivery_usd = filter_input(INPUT_POST, 'delivery_usd', FILTER_VALIDATE_FLOAT) ?: 0;
$propina_usd = filter_input(INPUT_POST, 'propina_usd', FILTER_VALIDATE_FLOAT) ?: 0;

if ($total_usd === false || $total_bs === false) {
    header("Location: cerrar_pedido.php?id=$id_pedido&error=Datos de total no válidos.");
    exit;
}

// === OBTENER PRODUCTOS DESDE LA BASE DE DATOS ===
$stmt_productos = $pdo->prepare("
    SELECT 
        dp.cantidad,
        dp.precio_unitario,
        dp.tipo,
        COALESCE(dp.id_producto, dp.id_preparacion) AS id_item
    FROM detalles_pedido dp
    WHERE dp.id_pedido = ?
");
$stmt_productos->execute([$id_pedido]);
$productos_db = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

if (empty($productos_db)) {
    header("Location: gestionar_pedidos.php?error=El pedido no tiene productos.");
    exit;
}

// === VALIDAR STOCK ANTES DE CERRAR ===
$errores_stock = [];

foreach ($productos_db as $p) {
    $cantidad = (int)$p['cantidad'];

    if ($p['tipo'] === 'producto') {
        $stmt_check = $pdo->prepare("SELECT nombre, stock FROM productos WHERE id_producto = ?");
        $stmt_check->execute([$p['id_item']]);
        $producto = $stmt_check->fetch();

        if (!$producto) {
            $errores_stock[] = "Producto no encontrado: ID {$p['id_item']}";
            continue;
        }

        if ($producto['stock'] < $cantidad) {
            $errores_stock[] = "Stock insuficiente para '{$producto['nombre']}': {$producto['stock']} disponible, se necesitan $cantidad";
        }
    } 
    elseif ($p['tipo'] === 'preparacion') {
        $stmt_ingredientes = $pdo->prepare("
            SELECT r.id_producto, p.nombre, p.stock, r.cantidad 
            FROM recetas r 
            JOIN productos p ON r.id_producto = p.id_producto 
            WHERE r.id_preparacion = ?
        ");
        $stmt_ingredientes->execute([$p['id_item']]);
        $ingredientes = $stmt_ingredientes->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ingredientes as $ing) {
            $necesita = $ing['cantidad'] * $cantidad;
            if ($ing['stock'] < $necesita) {
                $errores_stock[] = "Stock insuficiente para preparar '{$p['id_item']}': '{$ing['nombre']}' disponible {$ing['stock']}, necesario $necesita";
            }
        }
    }
}

if (!empty($errores_stock)) {
    $error_msg = urlencode("No se puede cerrar el pedido: " . implode(" | ", $errores_stock));
    header("Location: cerrar_pedido.php?id=$id_pedido&error=$error_msg");
    exit;
}

// === VALIDAR PAGOS ===
$metodos_pago = $_POST['metodo_pago'] ?? [];
$monto_pago = $_POST['monto_pago'] ?? [];

$total_pagado_bs = 0;

foreach ($metodos_pago as $index => $metodo) {
    $monto = floatval($monto_pago[$index] ?? 0);
    if ($monto <= 0) continue;

    if ($metodo === 'efectivo_usd') {
        $total_pagado_bs += $monto * $valor_dolar;
    } else {
        $total_pagado_bs += $monto;
    }
}

if ($total_pagado_bs < $total_bs) {
    $diferencia = $total_bs - $total_pagado_bs;
    $error_msg = urlencode("El monto pagado es insuficiente. Faltan Bs " . number_format($diferencia, 2));
    header("Location: cerrar_pedido.php?id=$id_pedido&error=$error_msg");
    exit;
}

// === DETECCIÓN AUTOMÁTICA DEL TURNO ACTUAL ===
$hora_actual = date("H:i:s");
$stmt_turnos = $pdo->query("SELECT id_turno, hora_inicio, hora_fin FROM turnos WHERE activo = 1");
$turnos = $stmt_turnos->fetchAll(PDO::FETCH_ASSOC);

$id_turno_actual = null;
foreach ($turnos as $t) {
    $inicio = $t['hora_inicio'];
    $fin = $t['hora_fin'];
    if (($inicio <= $fin && $hora_actual >= $inicio && $hora_actual <= $fin) ||
        ($inicio > $fin && ($hora_actual >= $inicio || $hora_actual <= $fin))) {
        $id_turno_actual = $t['id_turno'];
        break;
    }
}

if (!$id_turno_actual) {
    $error_msg = urlencode("No hay ningún turno activo en este momento.");
    header("Location: cerrar_pedido.php?id=$id_pedido&error=$error_msg");
    exit;
}

// === INICIAR TRANSACCIÓN ===
$pdo->beginTransaction();

try {
    // Actualizar pedido a "cerrado"
    $stmt_update = $pdo->prepare("
        UPDATE pedidos 
        SET estado = 'cerrado', 
            total_usd = ?, 
            total_bs = ?, 
            fecha_cierre = NOW(),
            id_turno = ? 
        WHERE id_pedido = ?
    ");
    $stmt_update->execute([$total_usd, $total_bs, $id_turno_actual, $id_pedido]);

    // Registrar propina (si hay)
    if ($propina_usd > 0) {
        $stmt_propina = $pdo->prepare("
            INSERT INTO propinas (id_pedido, id_turno, monto_bs, metodo_pago, referencia, fecha) 
            VALUES (?, ?, ?, 'efectivo', 'Propina del pedido', CURDATE())
        ");
        $stmt_propina->execute([$id_pedido, $id_turno_actual, $propina_usd * $valor_dolar]);
    }

    // === REGISTRAR DEUDA AL REPARTIDOR (Automática) ===
if ($delivery_usd > 0 && $pedido['tipo_pedido'] === 'delivery') {
    $mes_actual = (int)date('m');
    $anio_actual = (int)date('Y');

    // Define qué porcentaje del costo del delivery va al repartidor
    // Ej: 80% del costo del delivery en USD
    $porcentaje_repartidor = 0.80;
    $deuda_usd = $delivery_usd * $porcentaje_repartidor;
    $deuda_bs = $deuda_usd * $valor_dolar;

    // Verificar si ya existe una deuda para este mes
    $stmt_check = $pdo->prepare("
        SELECT id_deuda, monto_usd, monto_bs 
        FROM deudas_delivery 
        WHERE mes = ? AND anio = ? AND estado = 'pendiente'
    ");
    $stmt_check->execute([$mes_actual, $anio_actual]);
    $deuda_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($deuda_existente) {
        // Si existe, suma al existente
        $nuevo_monto_usd = $deuda_existente['monto_usd'] + $deuda_usd;
        $nuevo_monto_bs = $deuda_existente['monto_bs'] + $deuda_bs;

        $stmt_update = $pdo->prepare("
            UPDATE deudas_delivery 
            SET monto_usd = ?, monto_bs = ? 
            WHERE id_deuda = ?
        ");
        $stmt_update->execute([$nuevo_monto_usd, $nuevo_monto_bs, $deuda_existente['id_deuda']]);
    } else {
        // Si no existe, crea una nueva
        $stmt_insert = $pdo->prepare("
            INSERT INTO deudas_delivery 
            (nombre_repartidor, mes, anio, monto_bs, monto_usd, estado, notas) 
            VALUES (?, ?, ?, ?, ?, 'pendiente', 'Acumulado de deliverys del mes')
        ");
        $stmt_insert->execute([
            'Repartidores Delivery', // Puedes cambiarlo por un nombre dinámico si lo deseas
            $mes_actual,
            $anio_actual,
            $deuda_bs,
            $deuda_usd
        ]);
    }
}

    // === DESCUENTO DE STOCK ===
    foreach ($productos_db as $p) {
        $cantidad = (int)$p['cantidad'];

        if ($p['tipo'] === 'producto') {
            $stmt_update_stock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
            $stmt_update_stock->execute([$cantidad, $p['id_item']]);
        } 
        elseif ($p['tipo'] === 'preparacion') {
            $stmt_ingredientes = $pdo->prepare("
                SELECT id_producto, cantidad FROM recetas WHERE id_preparacion = ?
            ");
            $stmt_ingredientes->execute([$p['id_item']]);
            $ingredientes = $stmt_ingredientes->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ingredientes as $ing) {
                $cantidad_a_descontar = $ing['cantidad'] * $cantidad;
                $stmt_update_stock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
                $stmt_update_stock->execute([$cantidad_a_descontar, $ing['id_producto']]);
            }
        }
    }

    // Registrar pagos
    $stmt_pago = $pdo->prepare("
        INSERT INTO pagos (id_pedido, metodo_pago, monto_bs, fecha_pago) 
        VALUES (?, ?, ?, NOW())
    ");

    // === 4. Registrar pagos ===
    foreach ($metodos_pago as $index => $metodo) {
        $monto = filter_var($monto_bs[$index], FILTER_VALIDATE_FLOAT);
        if ($monto === false || $monto <= 0) continue;

        // Validar método de pago
        $metodo_pago_db = in_array($metodo, ['efectivo', 'zelle', 'pago_movil', 'tarjeta_debito', 'transferencia', 'otro']) 
            ? $metodo : 'otro';

        // Capturar la referencia si fue enviada
        $referencia = $_POST['referencia'][$index] ?? null;
        $referencia = !empty($referencia) ? trim($referencia) : null;

        // Insertar pago con referencia
        $stmt_pago = $pdo->prepare("
            INSERT INTO pagos (id_pedido, metodo_pago, monto_bs, referencia) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt_pago->execute([$id_pedido, $metodo_pago_db, $monto, $referencia]);
    }

    // Confirmar transacción
    $pdo->commit();

    // === ENVIAR NOTIFICACIÓN POR CORREO (SOLO ESTE BLOQUE SE AGREGA) ===
    try {

        $mail = new PHPMailer(true);

        // Configuración del servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'erwin.ricardo08@gmail.com';        // Tu correo
        $mail->Password   = 'uurj qsud aavh xuqi';          // Contraseña de app
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Destinatario predefinido
        $mail->setFrom('mamachula.ve@gmail.com', 'Sistema Comida Rapida');
        $mail->addAddress('elysaul.app@gmail.com');        // Correo fijo de notificación

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = "Pedido Cerrado - #{$id_pedido}";
        $mail->Body    = "
            <h2>Pedido Cerrado Exitosamente</h2>
            <p><strong>ID del Pedido:</strong> #{$id_pedido}</p>
            <p><strong>Total en USD:</strong> \${$total_usd}</p>
            <p><strong>Total en Bs:</strong> Bs {$total_bs}</p>
            <p><strong>Fecha de Cierre:</strong> " . date('d/m/Y H:i:s') . "</p>
            <hr>
            <p><em>Este es un mensaje automatico del sistema de gestion.</em></p>
            <p><em>DevSolutions. Todos los derechos reservados.</em></p>
        ";
        $mail->AltBody = "
            Pedido Cerrado - #{$id_pedido}
            Total en USD: \${$total_usd}
            Total en Bs: Bs {$total_bs}
            Fecha: " . date('d/m/Y H:i:s') . "
            Mensaje automático del sistema.
        ";

        $mail->send();
        error_log("Correo de notificación enviado para el pedido #{$id_pedido}");
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo para el pedido #{$id_pedido}: " . $mail->ErrorInfo);
        // No detenemos el flujo: el pedido ya se cerró
    }
    // === FIN DEL BLOQUE AÑADIDO ==
    // Redirigir con éxito
    header("Location: gestionar_pedidos.php?success=Pedido cerrado, stock actualizado, pagos, propina y delivery registrados correctamente.");
    exit;

} catch (Exception $e) {
    $pdo->rollback();
    error_log("Error al registrar cierre: " . $e->getMessage());
    $error_msg = urlencode("Error al cerrar el pedido: " . $e->getMessage());
    header("Location: cerrar_pedido.php?id=$id_pedido&error=$error_msg");
    exit;
}
?>