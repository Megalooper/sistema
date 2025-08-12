<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$error = $success = "";

// Obtener categorías para el formulario
$stmt_categorias = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Registrar producto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio_usd = filter_input(INPUT_POST, 'precio_usd', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_FLOAT);
    $id_categoria = $_POST['categoria'] === '' ? null : intval($_POST['categoria']);
    $codigo = trim($_POST['codigo']);
    $es_ingrediente = isset($_POST['es_ingrediente']) ? 1 : 0;
    $visible_venta = isset($_POST['visible_venta']) ? 1 : 0;

    if (empty($nombre) || $precio_usd === false || $stock === false) {
        $error = "Por favor, completa todos los campos obligatorios correctamente.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO productos (codigo, id_categoria, nombre, descripcion, precio_usd, stock, es_ingrediente, visible_venta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $id_categoria, $nombre, $descripcion, $precio_usd, $stock, $es_ingrediente, $visible_venta]);
            $success = "Producto registrado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al registrar el producto.";
        }
    }
}

// Obtener productos para mostrar
$stmt_productos = $pdo->query("SELECT p.*, c.nombre AS categoria_nombre FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria ORDER BY p.fecha_registro DESC");
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-producto.css">
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
        <li class="active"><a href="#"><i class="fas fa-boxes"></i><span class="nav-text">Productos</span></a></li>
        <li><a href="categorias/categorias.php"><i class="fas fa-layer-group"></i><span class="nav-text">Categorías</span></a></li>
        <li><a href="../preparaciones/preparaciones.php"><i class="fas fa-hamburger"></i><span class="nav-text">Preparaciones</span></a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-boxes"></i> Gestión de Productos</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
        </div>
    </header>

    <main>
        <!-- Registro de producto -->
        <section class="card">
            <h2><i class="fas fa-plus-circle"></i> Registrar Nuevo Producto</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="productos.php">
                <input type="hidden" name="registrar" value="1">
                <div class="form-group">
                    <label for="codigo">Código (opcional)</label>
                    <input type="text" id="codigo" name="codigo" class="form-control" placeholder="Ej: PRD-001">
                </div>
                <div class="form-group">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria" class="form-control">
                        <option value="">Sin Categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['categoria_nombre'] ?? $cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre del Producto *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Hamburguesa Sencilla">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción (opcional)</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Ej: Hamburguesa de 150gr con queso y tomate"></textarea>
                </div>
                <div class="form-group">
                    <label for="precio_usd">Precio USD *</label>
                    <input type="number" id="precio_usd" name="precio_usd" step="0.01" min="0.01" class="form-control" required placeholder="Ej: 2.50">
                </div>
                <div class="form-group">
                    <label for="stock">Stock Inicial *</label>
                    <input type="number" id="stock" name="stock" class="form-control" step="0.001" min="0" required placeholder="Ej: 10.500">
                </div>
                <div class="form-group checkbox-group">
                    <label><input type="checkbox" name="es_ingrediente" value="1"> Es ingrediente</label>
                </div>
                <div class="form-group checkbox-group">
                    <label><input type="checkbox" name="visible_venta" value="1" checked> Mostrar en ventas</label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar Producto</button>
                </div>
            </form>
        </section>

        <!-- Botón para importar desde Excel -->
        <div class="form-actions" style="margin-top: 2rem;">
            <button type="button" class="btn btn-secondary" onclick="abrirModalImportar()">
                <i class="fas fa-file-excel"></i> Importar desde Excel/CSV
            </button>
        </div>

        <!-- Listado de productos -->
        <section class="card">
            <h2><i class="fas fa-list-ul"></i> Listado de Productos</h2>
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio USD</th>
                        <th>Stock</th>
                        <th>Visible</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><?= $p['id_producto'] ?></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría') ?></td>
                            <td>$<?= number_format($p['precio_usd'], 2) ?></td>
                            <td><?= number_format($p['stock'], 3) ?></td>
                            <td><?= $p['visible_venta'] ? '✅' : '❌' ?></td>
                            <td class="acciones">
                                <a href="editar_producto.php?id=<?= $p['id_producto'] ?>" class="editar">Editar</a>
                                <a href="eliminar.php?id=<?= $p['id_producto'] ?>" onclick="return confirm('¿Estás seguro?')" class="eliminar">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<!-- Modal de Importación -->
<div id="modalImportar" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 90%; max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-excel"></i> Importar Productos</h3>
            <button class="close-btn" onclick="cerrarModalImportar()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Sube un archivo Excel (.xlsx, .xls) o CSV con los productos.</p>
            <p><strong>Campos requeridos:</strong> Nombre, Precio USD, Stock</p>
            <p><strong>Opcionales:</strong> Código, Descripción, Categoría, Es ingrediente (0/1), Visible (0/1)</p>
            <form id="formImportar" method="POST" action="importar_productos.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="archivo">Archivo Excel o CSV</label>
                    <input type="file" id="archivo" name="archivo" accept=".xlsx,.xls,.csv" required>
                </div>
                <p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                    <a href="plantilla_importacion.xlsx" style="color: #3498db;">📥 Descargar plantilla de ejemplo</a>
                </p>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalImportar()">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="overlayImportar" class="overlay" style="display: none;" onclick="cerrarModalImportar()"></div>

<script>
function abrirModalImportar() {
    document.getElementById('modalImportar').style.display = 'flex';
    document.getElementById('overlayImportar').style.display = 'block';
}

function cerrarModalImportar() {
    document.getElementById('modalImportar').style.display = 'none';
    document.getElementById('overlayImportar').style.display = 'none';
}
</script>

<style>
/* Reutilizamos estilos del modal de stock */
.modal-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.modal-header {
    background: #27ae60;
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}
.modal-body {
    padding: 1.5rem;
}
.overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    z-index: 999;
    display: none;
}
</style>

</body>
</html>