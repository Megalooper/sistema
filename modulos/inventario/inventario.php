<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Obtener categorías y áreas
$stmt_categorias = $pdo->query("SELECT c.id_categoria, c.nombre AS categoria, a.nombre AS area FROM categorias c LEFT JOIN areas a ON c.id_area = a.id_area ORDER BY a.nombre, c.nombre");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos agrupados por categoría
$productosPorCategoria = [];
foreach ($categorias as $categoria) {
    $stmt_productos = $pdo->prepare("SELECT p.id_producto, p.codigo, p.nombre, p.precio_usd, p.stock, p.es_ingrediente FROM productos p WHERE p.id_categoria = ?");
    $stmt_productos->execute([$categoria['id_categoria']]);
    $productosPorCategoria[$categoria['id_categoria']] = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
}

// Productos sin categoría
$stmt_sin_categoria = $pdo->query("SELECT p.id_producto, p.codigo, p.nombre, p.precio_usd, p.stock FROM productos p WHERE p.id_categoria IS NULL OR p.id_categoria = 0");
$productosSinCategoria = $stmt_sin_categoria->fetchAll(PDO::FETCH_ASSOC);

// Tipos de movimiento
$tipos_movimiento = ['compra' => 'Compra', 'produccion' => 'Producción', 'merma' => 'Merma'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css ">
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
        <li class="active"><a href="#"><i class="fas fa-database"></i><span class="nav-text">Inventario</span></a></li>
        <li><a href="historial.php"><i class="fas fa-history"></i><span class="nav-text">Historial de Movimientos</span></a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <!-- Navbar -->
    <header id="navbar">
        <h1><i class="fas fa-database"></i> Gestión de Inventario</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <!-- Filtros -->
        <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <input type="text" id="busquedaProducto" placeholder="Buscar por nombre o código" class="form-control" onkeyup="filtrarProductos()" style="flex: 1; max-width: 300px;">
            <select id="filtroCategoria" class="form-control" onchange="filtrarProductos()">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>">
                        <?= htmlspecialchars($cat['area'] . " - " . $cat['categoria']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Listado de productos -->
        <?php foreach ($categorias as $categoria): ?>
            <div class="card categoria-card" data-categoria="<?= $categoria['id_categoria'] ?>">
                <h2><?= htmlspecialchars($categoria['area'] . " - " . $categoria['categoria']) ?></h2>
                <table class="tabla-productos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Precio USD</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="productos-container">
                        <?php foreach ($productosPorCategoria[$categoria['id_categoria']] as $p): ?>
                            <tr class="producto-row" data-nombre="<?= strtolower($p['nombre']) ?>" data-codigo="<?= strtolower($p['codigo'] ?: '') ?>">
                                <td><?= $p['id_producto'] ?></td>
                                <td><?= htmlspecialchars($p['codigo'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td>$<?= number_format($p['precio_usd'], 2) ?></td>
                                <td><?= $p['stock'] ?></td>
                                <td class="acciones">
                                    <button class="btn-movimiento" onclick="abrirModal(<?= $p['id_producto'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['stock'] ?>)">
                                        Registrar Movimiento
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <!-- Productos sin categoría -->
        <?php if (!empty($productosSinCategoria)): ?>
            <div class="card categoria-card" data-categoria="0">
                <h2>Sin Categoría</h2>
                <table class="tabla-productos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Precio USD</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="productos-container">
                        <?php foreach ($productosSinCategoria as $p): ?>
                            <tr class="producto-row" data-nombre="<?= strtolower($p['nombre']) ?>" data-codigo="<?= strtolower($p['codigo'] ?: '') ?>">
                                <td><?= $p['id_producto'] ?></td>
                                <td><?= htmlspecialchars($p['codigo'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td>$<?= number_format($p['precio_usd'], 2) ?></td>
                                <td><?= $p['stock'] ?></td>
                                <td class="acciones">
                                    <button class="btn-movimiento" onclick="abrirModal(<?= $p['id_producto'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['stock'] ?>)">
                                        Registrar Movimiento
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>


<!-- Modal lateral derecho -->
<div class="modal-overlay" id="modalMovimiento">
    <div class="modal-header">
        <h3 id="tituloModal">Registrar Movimiento</h3>
        <button class="close-btn" onclick="cerrarModal()">&times;</button>
    </div>
    <div class="modal-body">
        <form id="formMovimiento" method="POST" action="registrar_movimiento.php">
            <input type="hidden" id="productoId" name="id_producto">

            <div class="form-group">
                <label for="tipo">Tipo de Movimiento *</label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tipos_movimiento as $key => $label): ?>
                        <option value="<?= $key ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cantidad">Cantidad *</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" required min="1" data-stock="">
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción (opcional)</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Movimiento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="cerrarModal()"></div>

<!-- JS -->
<script>
    // Filtrar productos
    function filtrarProductos() {
        const filtroNombre = document.getElementById('busquedaProducto').value.toLowerCase().trim();
        const filtroCategoria = document.getElementById('filtroCategoria').value;

        document.querySelectorAll('.categoria-card').forEach(card => {
            const productos = card.querySelectorAll('.producto-row');
            let mostrarCategoria = false;

            productos.forEach(producto => {
                const nombre = producto.getAttribute('data-nombre');
                const codigo = producto.getAttribute('data-codigo');
                const coincideNombre = nombre.includes(filtroNombre);
                const coincideCodigo = codigo.includes(filtroNombre);
                const coincideCategoria = filtroCategoria === "" || card.getAttribute('data-categoria') === filtroCategoria;

                if (coincideNombre || coincideCodigo) {
                    producto.style.display = coincideCategoria ? 'table-row' : 'none';
                    if (coincideCategoria && (coincideNombre || coincideCodigo)) mostrarCategoria = true;
                } else {
                    producto.style.display = 'none';
                }
            });

            card.style.display = (mostrarCategoria || filtroCategoria === card.getAttribute('data-categoria')) ? 'block' : 'none';
        });
    }

    // Abrir modal con datos del producto
    function abrirModal(id, nombre, stock) {
        document.getElementById('productoId').value = id;
        document.getElementById('tituloModal').innerText = `Registrar movimiento - ${nombre}`;
        document.getElementById('cantidad').max = stock;
        document.getElementById('modalMovimiento').classList.add('active');
        document.getElementById('overlay').style.display = 'block';
    }

    // Cerrar modal
    function cerrarModal() {
        document.getElementById('modalMovimiento').classList.remove('active');
        document.getElementById('overlay').style.display = 'none';
    }

    // Validar cantidad
    document.getElementById('tipo').addEventListener('change', function () {
        const tipo = this.value;
        const cantidadInput = document.getElementById('cantidad');
        if (tipo === 'merma') {
            cantidadInput.max = cantidadInput.getAttribute('data-stock');
        } else {
            cantidadInput.removeAttribute('max');
        }
    });

    document.getElementById('cantidad').addEventListener('input', function () {
        const tipo = document.getElementById('tipo').value;
        const val = parseInt(this.value);
        if (val < 1) this.value = 1;
        if (tipo === 'merma') {
            const max = parseInt(this.getAttribute('data-stock'));
            if (val > max) this.value = max;
        }
    });
</script>

</body>
</html>