<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
date_default_timezone_set('America/Caracas');

// Obtener turnos
$stmt_turnos = $pdo->query("SELECT id_turno, nombre, hora_inicio, hora_fin FROM turnos ORDER BY id_turno ASC");
$turnos = $stmt_turnos->fetchAll(PDO::FETCH_ASSOC);

// Detectar turno actual
$hora_actual = date("H:i");
$turno_actual = null;
foreach ($turnos as $t) {
    $inicio = $t['hora_inicio'];
    $fin = $t['hora_fin'];
    if (($inicio <= $fin && $hora_actual >= $inicio && $hora_actual <= $fin) ||
        ($inicio > $fin && ($hora_actual >= $inicio || $hora_actual <= $fin))) {
        $turno_actual = $t['id_turno'];
        break;
    }
}

// Obtener áreas
$stmt_areas = $pdo->query("SELECT id_area, nombre FROM areas ORDER BY nombre ASC");
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías por área
$stmt_categorias = $pdo->query("SELECT id_categoria, nombre, id_area FROM categorias ORDER BY nombre ASC");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos
$stmt_productos = $pdo->query("SELECT id_producto, nombre, precio_usd, stock, id_categoria FROM productos WHERE visible_venta = 1 ORDER BY nombre ASC");
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener preparaciones
$stmt_preparaciones = $pdo->query("SELECT p.id_preparacion, p.nombre, p.precio_usd, c.id_categoria FROM preparaciones p JOIN categorias c ON p.id_categoria = c.id_categoria ORDER BY p.nombre ASC");
$preparaciones = $stmt_preparaciones->fetchAll(PDO::FETCH_ASSOC);

// Tasa de cambio
$stmt_tasa = $pdo->query("SELECT valor_dolar FROM tasas_cambio ORDER BY fecha_registro DESC LIMIT 1");
$tasa = $stmt_tasa->fetch(PDO::FETCH_ASSOC);
$valor_dolar = $tasa ? $tasa['valor_dolar'] : 1;

// Manejo de errores
$error = $success = "";
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);
if (isset($_GET['mensaje'])) $success = htmlspecialchars($_GET['mensaje']);

// Cargar pedido si existe
$id_pedido = $_GET['id'] ?? null;
$datos_pedido = null;
$seleccionados = [];
$productosOriginales = [];

if ($id_pedido) {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id_pedido = ? AND estado IN ('abierto', 'en espera')");
    $stmt->execute([$id_pedido]);
    $datos_pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($datos_pedido) {
        $stmt_detalle = $pdo->prepare("
            SELECT 
                dp.cantidad,
                dp.precio_unitario,
                dp.tipo,
                COALESCE(p.nombre, prep.nombre) AS nombre_real,
                COALESCE(dp.id_producto, dp.id_preparacion) AS id_real
            FROM detalles_pedido dp
            LEFT JOIN productos p ON dp.id_producto = p.id_producto
            LEFT JOIN preparaciones prep ON dp.id_preparacion = prep.id_preparacion
            WHERE dp.id_pedido = ?
        ");
        $stmt_detalle->execute([$id_pedido]);

        while ($p = $stmt_detalle->fetch(PDO::FETCH_ASSOC)) {
            if (empty($p['nombre_real'])) continue;

            $producto = [
                'id' => $p['id_real'],
                'nombre' => $p['nombre_real'],
                'precio' => $p['precio_unitario'],
                'cantidad' => $p['cantidad'],
                'tipo' => $p['tipo']
            ];

            // En el bucle que carga los productos originales
            $productosOriginales[] = $producto; // Sin esNuevo
            $seleccionados[] = $producto;       // Solo el original
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Pedido - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../../assets/img/favicon.ico" type="image/x-icon">
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
        <span class="username"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
    </div>
    <ul class="menu">
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li><a href="gestionar_pedidos.php"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial de Pedidos</span></a></li>
        <li><a href="propinas_mes.php"><i class="fas fa-hand-holding-usd"></i><span class="nav-text">Propinas del Mes</span></a></li>
        <li><a href="deudas_delivery.php"><i class="fas fa-truck"></i><span class="nav-text">Deudas Delivery</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-clipboard-list"></i> Nuevo Pedido</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card venta-form-container">
            <h2><i class="fas fa-info-circle"></i> Información del Pedido</h2>
            <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

            <form id="pedidoForm" method="POST" action="guardar_pedido.php">
                <input type="hidden" name="id_pedido" value="<?= $id_pedido ?>">
                <input type="hidden" name="turno" value="<?= $turno_actual ?>">

                <div class="form-group">
                    <label for="tipo_pedido">Tipo de Pedido *</label>
                    <select id="tipo_pedido" name="tipo_pedido" class="form-control" required onchange="mostrarCampos(this.value)">
                        <option value="">Selecciona...</option>
                        <option value="mesa" <?= ($datos_pedido['tipo_pedido'] ?? '') == 'mesa' ? 'selected' : '' ?>>Mesa</option>
                        <option value="delivery" <?= ($datos_pedido['tipo_pedido'] ?? '') == 'delivery' ? 'selected' : '' ?>>Delivery</option>
                    </select>
                </div>

                <div id="campo_mesa" style="<?= ($datos_pedido['tipo_pedido'] ?? '') == 'mesa' ? 'display:block;' : 'display:none;' ?>">
                    <div class="form-group">
                        <label for="numero_mesa">Número de Mesa *</label>
                        <input type="text" id="numero_mesa" name="numero_mesa" class="form-control" 
                               value="<?= htmlspecialchars($datos_pedido['numero_mesa'] ?? '') ?>" placeholder="Ej: Mesa 5" required>
                    </div>
                </div>

                <div id="campo_delivery" style="<?= ($datos_pedido['tipo_pedido'] ?? '') == 'delivery' ? 'display:block;' : 'display:none;' ?>">
                    <div class="form-group">
                        <label for="direccion_delivery">Dirección de Entrega *</label>
                        <textarea id="direccion_delivery" name="direccion_delivery" class="form-control" rows="2" 
                                  placeholder="Dirección completa" required><?= htmlspecialchars($datos_pedido['direccion_delivery'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="telefono_cliente">Teléfono del Cliente *</label>
                        <input type="text" id="telefono_cliente" name="telefono_cliente" class="form-control" 
                               value="<?= htmlspecialchars($datos_pedido['telefono_cliente'] ?? '') ?>" placeholder="0412-1234567" required>
                    </div>
                </div>

                <!-- Carrito de productos -->
                <div class="resumen-productos" id="seleccionadosList">
                    <h3><i class="fas fa-shopping-cart"></i> Productos Seleccionados</h3>
                    <p id="mensajeVacio">No hay productos seleccionados aún.</p>
                </div>

                <!-- Totales -->
                <div class="totales">
                    <h3><i class="fas fa-calculator"></i> Resumen del Pedido</h3>
                    <div class="form-group">
                        <label for="total_usd">Total en USD *</label>
                        <input type="text" id="total_usd" name="total_usd" readonly class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="total_bs">Total en Bs *</label>
                        <input type="text" id="total_bs" name="total_bs" readonly class="form-control">
                    </div>
                </div>

                <!-- Acciones -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="ponerEnEspera()">
                        <i class="fas fa-pause"></i> Poner en Espera
                    </button>
                    <button type="button" class="btn btn-primary" onclick="abrirModal()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                    <button type="button" class="btn btn-warning" onclick="imprimirComanda()">
                        <i class="fas fa-print"></i> Imprimir Comanda
                    </button>
                    <button type="button" class="btn btn-success" onclick="cerrarPedido()">
                        <i class="fas fa-check"></i> Cerrar Pedido
                    </button>
                </div>
            </form>
        </section>
    </main>
</div>

<!-- Botón flotante -->
<button class="btn-agregar-producto" onclick="abrirModal()" title="Agregar Producto">
    <i class="fas fa-plus"></i>
</button>

<!-- Modal de productos -->
<div class="overlay" id="overlay" onclick="cerrarModal()"></div>
<div id="modalProductos" class="modal-overlay">
    <div class="modal-header">
        <h3><i class="fas fa-shopping-cart"></i> Agregar Producto</h3>
        <button class="close-btn" onclick="cerrarModal()">&times;</button>
    </div>
    <div class="modal-body">
        <!-- Paso 1: Seleccionar Área -->
        <div id="stepArea" class="modal-step">
            <h4><i class="fas fa-layer-group"></i> Paso 1: Selecciona un Área</h4>
            <?php foreach ($areas as $a): ?>
                <div class="area-card" onclick="seleccionarArea(<?= $a['id_area'] ?>)">
                    <i class="fas fa-layer-group"></i>
                    <span><?= htmlspecialchars($a['nombre']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paso 2: Seleccionar Categoría -->
        <div id="stepCategoria" class="modal-step hidden">
            <h4><i class="fas fa-boxes"></i> Paso 2: Selecciona una Categoría</h4>
            <div class="categorias-lista" id="categoriasList">
                <!-- Aquí se cargarán las categorías -->
            </div>
        </div>

        <!-- Paso 3: Seleccionar Producto o Preparación -->
        <div id="stepProducto" class="modal-step hidden">
            
            <h4><i class="fas fa-boxes"></i> Paso 3: Selecciona un Producto o Preparación</h4>
            <div class="productos-lista" id="productosLista">
                <!-- Aquí se cargarán los productos -->
            </div>
        </div>

        <!-- Paso 4: Cantidad y Observaciones -->
        <div id="stepCantidad" class="modal-step hidden">
            <h4><i class="fas fa-sort-numeric-up"></i> Paso 4: Cantidad y Observaciones</h4>
            <p id="nombreProductoSeleccionado" style="font-weight: 600; font-size: 1.1rem; text-align: center;"></p>
            
            <div class="form-group">
                <label>Cantidad</label>
                <input type="number" id="cantidadSeleccionada" class="form-control" min="1" value="1" oninput="this.value = Math.max(1, this.value)">
            </div>
            
            <div class="form-group">
                <label>Observaciones (opcional)</label>
                <textarea id="observacionesProducto" class="form-control" placeholder="Ej: Sin cebolla, bien cocido..." rows="2"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="atras()">Atrás</button>
                <button type="button" class="btn btn-primary" onclick="agregarAlCarrito()">Agregar al Pedido</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- INICIALIZACIÓN ---
    let seleccionados = <?= json_encode($seleccionados) ?>;
    let areaSeleccionada = null;
    let categoriaSeleccionada = null;
    let productoSeleccionado = null;
    let productosOriginales = <?= json_encode($productosOriginales) ?>;

    // --- REFERENCIAS ---
    
    const seleccionadosList = document.getElementById('seleccionadosList');
    const mensajeVacio = document.getElementById('mensajeVacio');
    const totalUsdInput = document.getElementById('total_usd');
    const totalBsInput = document.getElementById('total_bs');
    const valorDolar = <?= $valor_dolar ?>;
    const usuario = <?= json_encode($_SESSION['usuario']) ?>;

    // --- FUNCIONES ---
    function mostrarCampos(tipo) {
        document.getElementById('campo_mesa').style.display = tipo === 'mesa' ? 'block' : 'none';
        document.getElementById('campo_delivery').style.display = tipo === 'delivery' ? 'block' : 'none';
    }

    function abrirModal() {
        document.getElementById('modalProductos').classList.add('active');
        document.getElementById('overlay').style.display = 'block';
        resetModal();
    }

    function cerrarModal(e) {
        if (e && e.target !== e.currentTarget) return;
        document.getElementById('modalProductos').classList.remove('active');
        document.getElementById('overlay').style.display = 'none';
    }
    // === FUNCIÓN DE BÚSQUEDA EN EL MODAL ===
    function filtrarProductos() {
        const input = document.getElementById('busquedaProducto');
        const filtro = input?.value.toLowerCase().trim() || '';
        const cards = document.querySelectorAll('.card-producto');

        cards.forEach(card => {
            const nombre = card.getAttribute('data-nombre') || '';
            const codigo = card.getAttribute('data-codigo') || '';
            
            if (nombre.includes(filtro) || codigo.includes(filtro)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function resetModal() {
        document.getElementById('stepArea').style.display = 'block';
        document.getElementById('stepCategoria').style.display = 'none';
        document.getElementById('stepProducto').style.display = 'none';
        document.getElementById('stepCantidad').style.display = 'none';
        document.getElementById('nombreProductoSeleccionado').innerText = "";
        document.getElementById('cantidadSeleccionada').value = 1;
        productoSeleccionado = null;
    }

    function seleccionarArea(id) {
        areaSeleccionada = id;
        document.getElementById('stepArea').style.display = 'none';
        document.getElementById('stepCategoria').style.display = 'block';

        const categoriasList = document.getElementById('categoriasList');
        categoriasList.innerHTML = "";

        <?php foreach ($categorias as $cat): ?>
            if (<?= $cat['id_area'] ?> == id) {
                const div = document.createElement("div");
                div.className = "categoria-card";
                div.setAttribute("onclick", `seleccionarCategoria(<?= $cat['id_categoria'] ?>)`);
                div.innerHTML = `<i class="fas fa-boxes"></i> <?= htmlspecialchars($cat['nombre']) ?>`;
                categoriasList.appendChild(div);
            }
        <?php endforeach; ?>
    }

    function seleccionarCategoria(id) {
        categoriaSeleccionada = id;
        document.getElementById('stepCategoria').style.display = 'none';
        document.getElementById('stepProducto').style.display = 'block';

        const productosLista = document.getElementById('productosLista');
        productosLista.innerHTML = "";

        // Productos
        <?php foreach ($productos as $p): ?>
            if (<?= $p['id_categoria'] ?> == id && <?= $p['stock'] ?> > 0) {
                const div = document.createElement("div");
                div.className = "producto-card";
                div.setAttribute("onclick", `seleccionarProducto(<?= $p['id_producto'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['precio_usd'] ?>, 'producto', <?= $p['stock'] ?>)`);
                div.innerHTML = `<strong><?= htmlspecialchars($p['nombre']) ?></strong><br><small>$<?= number_format($p['precio_usd'], 2) ?> | Stock: <?= $p['stock'] ?></small>`;
                productosLista.appendChild(div);
            }
        <?php endforeach; ?>

        // Preparaciones
        <?php foreach ($preparaciones as $prep): ?>
            if (<?= $prep['id_categoria'] ?> == id) {
                // Calcular stock real de la preparación
                <?php
                $stmt_ingredientes = $pdo->prepare("
                    SELECT r.cantidad AS cant_requerida, p.stock AS stock_producto
                    FROM recetas r
                    JOIN productos p ON r.id_producto = p.id_producto
                    WHERE r.id_preparacion = ?
                ");
                $stmt_ingredientes->execute([$prep['id_preparacion']]);
                $ingredientes = $stmt_ingredientes->fetchAll(PDO::FETCH_ASSOC);

                $puede_hacer = PHP_INT_MAX;
                foreach ($ingredientes as $ing) {
                    $cantidad_puede = floor($ing['stock_producto'] / $ing['cant_requerida']);
                    $puede_hacer = min($puede_hacer, $cantidad_puede);
                }
                $stock_preparacion = $puede_hacer;
                ?>

                const div = document.createElement("div");
                div.className = "producto-card";
                div.setAttribute("onclick", `seleccionarProducto(<?= $prep['id_preparacion'] ?>, '<?= addslashes($prep['nombre']) ?>', <?= $prep['precio_usd'] ?>, 'preparacion', <?= $stock_preparacion ?>)`);
                div.innerHTML = `<div class="producto-info">
                    <div class="nombre"><?= htmlspecialchars($prep['nombre']) ?></div>
                    <div class="precio">$<?= number_format($prep['precio_usd'], 2) ?></div>
                    <div class="stock">Stock disponible: <?= $stock_preparacion ?> unidades</div>
                </div>
                <i class="fas fa-chevron-right chevron"></i>`;
                productosLista.appendChild(div);
            }
        <?php endforeach; ?>
    }

    function seleccionarProducto(id, nombre, precio, tipo, stock = 10000) {
        productoSeleccionado = { id, nombre, precio, tipo, stock };
        document.getElementById('nombreProductoSeleccionado').innerText = `Producto: ${nombre}`;
        document.getElementById('cantidadSeleccionada').value = 1;
        document.getElementById('cantidadSeleccionada').max = stock;
        document.getElementById('stepProducto').style.display = 'none';
        document.getElementById('stepCantidad').style.display = 'block';
    }

    function atras() {
        document.getElementById('stepCategoria').style.display = 'block';
        document.getElementById('stepProducto').style.display = 'none';
        document.getElementById('stepCantidad').style.display = 'none';
    }

    function agregarAlCarrito() {
        const cantidad = parseInt(document.getElementById('cantidadSeleccionada').value);
        const observaciones = document.getElementById('observacionesProducto').value.trim();
        const prod = productoSeleccionado;

        if (isNaN(cantidad) || cantidad <= 0) {
            alert("Por favor, ingresa una cantidad válida.");
            return;
        }

        if (prod.tipo === 'producto' && prod.stock < cantidad) {
            alert("No hay suficiente stock para este producto.");
            return;
        }

        // === NUEVA LÓGICA: NO FUSIONAR CON PRODUCTOS ORIGINALES ===
        // Solo se busca duplicado entre productos que NO son originales
        const indexNuevo = seleccionados.findIndex(p => 
            p.id === prod.id && 
            p.tipo === prod.tipo && 
            p.esNuevo === true && 
            p.observaciones === (observaciones || null)
        );

        if (indexNuevo !== -1) {
            // Si ya existe un producto nuevo igual, se suma
            seleccionados[indexNuevo].cantidad += cantidad;
        } else {
            // Si es nuevo, se agrega como item independiente
            seleccionados.push({ 
                ...prod, 
                cantidad, 
                observaciones: observaciones || null,
                esNuevo: true  // Marcar como producto nuevo
            });
        }

        renderizarSeleccionados();
        cerrarModal();
    }

    function renderizarSeleccionados() {
        seleccionadosList.innerHTML = '<h3><i class="fas fa-shopping-cart"></i> Productos Seleccionados</h3>';
        mensajeVacio.style.display = 'none';

        if (seleccionados.length === 0) {
            mensajeVacio.style.display = 'block';
            totalUsdInput.value = "0.00";
            totalBsInput.value = "0.00";
            return;
        }

        let totalUsd = 0;
        seleccionados.forEach((p, index) => {
            const subtotal = p.precio * p.cantidad;
            totalUsd += subtotal;

            const esNuevo = p.esNuevo ? '<span class="nuevo-tag" style="color: #e74c3c; font-size: 0.8rem; margin-left: 5px;">(Nuevo)</span>' : '';

            const div = document.createElement("div");
            div.className = "producto-seleccionado";
            div.innerHTML = `
                <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span> - ${p.nombre} x ${p.cantidad} ${esNuevo}
                ${p.observaciones ? '<div class="observaciones">Obs: ' + p['observaciones'] + '</div>' : ''}
                <div class="acciones">
                    <button type="button" onclick="modificarCantidad(${index}, 1)">+</button>
                    <button type="button" onclick="modificarCantidad(${index}, -1)">-</button>
                    <button type="button" onclick="eliminarProducto(${index})"><i class="fas fa-trash"></i></button>
                </div>
                <div class="subtotal">$${subtotal.toFixed(2)}</div>
            `;
            seleccionadosList.appendChild(div);
        });

        totalUsdInput.value = totalUsd.toFixed(2);
        totalBsInput.value = (totalUsd * valorDolar).toFixed(2);
    }

    function modificarCantidad(index, cambio) {
        seleccionados[index].cantidad += cambio;
        if (seleccionados[index].cantidad <= 0) {
            seleccionados.splice(index, 1);
        }
        renderizarSeleccionados();
    }

    function eliminarProducto(index) {
        seleccionados.splice(index, 1);
        renderizarSeleccionados();
    }

    function imprimirComanda() {
        if (seleccionados.length === 0) {
            alert("No hay productos para imprimir.");
            return;
        }

        // Filtrar solo productos marcados como nuevos
        const nuevosProductos = seleccionados.filter(p => p.esNuevo === true);

        if (nuevosProductos.length === 0) {
            if (!confirm("No hay productos nuevos. ¿Imprimir todo el pedido?")) {
                return;
            }
            // Si dice que sí, imprime todo
        }

        const tipoPedido = document.getElementById('tipo_pedido').value;
        const numeroMesa = document.getElementById('numero_mesa')?.value || '';
        const direccionDelivery = document.getElementById('direccion_delivery')?.value || '';
        const telefonoCliente = document.getElementById('telefono_cliente')?.value || '';
        const id_pedido = document.querySelector('input[name="id_pedido"]').value || 'Nuevo';

        const productosAImprimir = nuevosProductos.length > 0 ? nuevosProductos : seleccionados;

        const datos = {
            id_pedido,
            tipo_pedido: tipoPedido,
            numero_mesa: numeroMesa,
            direccion_delivery: direccionDelivery,
            telefono_cliente: telefonoCliente,
            productos: productosAImprimir,
            turno: <?= $turno_actual ?>,
            ip_cocina: usuario.ip_cocina,
            impresora_barra: usuario.impresora_barra
        };

        fetch('imprimir_comanda.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('Error al imprimir: ' + data.error);
            } else {
                alert('Comanda enviada a cocina y barra.');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error de conexión con el servidor.');
        });
    }

    function ponerEnEspera() {
        if (seleccionados.length === 0) {
            alert("No puedes poner en espera un pedido vacío.");
            return;
        }

        if (confirm("¿Deseas poner este pedido en espera?")) {
            const form = document.getElementById('pedidoForm');
            form.action = 'guardar_pedido.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'productos';
            try {
                input.value = JSON.stringify(seleccionados);
            } catch (e) {
                alert("Error al procesar el carrito: " + e.message);
                return;
            }
            form.appendChild(input);

            const accionInput = document.createElement('input');
            accionInput.type = 'hidden';
            accionInput.name = 'accion';
            accionInput.value = 'en_espera';
            form.appendChild(accionInput);

            form.submit();
        }
    }

    function cerrarPedido() {
        if (seleccionados.length === 0) {
            alert("El pedido está vacío. No se puede cerrar.");
            return;
        }

        const form = document.getElementById('pedidoForm');
        form.action = 'cerrar_pedido.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'productos';
        try {
            input.value = JSON.stringify(seleccionados);
        } catch (e) {
            alert("Error al procesar el carrito: " + e.message);
            return;
        }
        form.appendChild(input);

        form.submit();
    }

    // --- INICIALIZAR ---
    document.addEventListener('DOMContentLoaded', function () {
        renderizarSeleccionados();
    });
</script>
</body>
</html>