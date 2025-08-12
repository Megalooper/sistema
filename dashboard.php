<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

require_once 'config/db.php';
$nombre_usuario = $_SESSION['usuario']['nombre'];
$inicial_usuario = strtoupper(substr($nombre_usuario, 0, 1));

// === Cargar datos para el dashboard ===
try {
    // Total productos
    $stmt = $pdo->query("SELECT COUNT(*) FROM productos");
    $total_productos = $stmt->fetchColumn();

    // Productos fuera de stock
    $stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock = 0");
    $fuera_de_stock = $stmt->fetchColumn();

    // Pedidos cerrados
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'cerrado'");
    $total_pedidos = $stmt->fetchColumn();

    // Últimos pedidos
    $stmt = $pdo->prepare("SELECT p.id_pedido, p.fecha_cierre, p.total_usd, u.nombre as mesero 
                          FROM pedidos p 
                          JOIN usuarios u ON p.id_usuario = u.id_usuario 
                          WHERE p.estado = 'cerrado' 
                          ORDER BY p.fecha_cierre DESC 
                          LIMIT 5");
    $stmt->execute();
    $ultimos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los productos con sus categorías
    $stmt_productos = $pdo->query("
        SELECT p.id_producto, p.nombre, p.stock, c.nombre as categoria_nombre 
        FROM productos p 
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
        ORDER BY p.stock ASC
    ");
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // === 3. Ganancias por mes (últimos 6 meses) ===
    $ganancias_por_mes = $pdo->prepare("
        SELECT 
            DATE_FORMAT(p.fecha_cierre, '%Y-%m') AS mes,
            SUM(p.total_usd) AS total_usd
        FROM pedidos p
        WHERE p.estado = 'cerrado'
        AND p.fecha_cierre >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY mes
        ORDER BY mes ASC
    ");
    $ganancias_por_mes->execute();
    $datos_grafica = $ganancias_por_mes->fetchAll(PDO::FETCH_ASSOC);

    // Preparar datos para Chart.js
    $meses = [];
    $ganancias = [];
    foreach ($datos_grafica as $fila) {
        $meses[] = date('M Y', strtotime($fila['mes'] . '-01'));
        $ganancias[] = round($fila['total_usd'], 2);
    }
    // Si no hay datos, mostrar al menos un mes
    if (empty($meses)) {
        $meses = ['Sin datos'];
        $ganancias = [0];
    }

} catch (PDOException $e) {
    $mensaje = "Error al cargar los datos: " . $e->getMessage();
}
$total_stock = $pdo->query("SELECT SUM(stock) FROM productos")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Comida Rápida</title>
    <link rel="stylesheet" href="assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === Estilos para el modal de stock === */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            display: none;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .modal-header {
            background: #e74c3c;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
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
        .alert-section {
            margin-bottom: 1.5rem;
        }
        .alert-section h4 {
            color: #2c3e50;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-section ul {
            list-style: none;
            padding: 0;
        }
        .alert-section li {
            padding: 8px 12px;
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
            margin-bottom: 6px;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        .alert-section li span.stock {
            font-weight: bold;
            color: #e74c3c;
        }
        .overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 999;
            display: none;
        }
        section.card:last-of-type {
    margin-top: 2rem;
}

section.card h2 {
    margin: 0 0 1rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e0e0e0;
    color: #2c3e50;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tabla-dashboard {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-top: 1rem;
}

.tabla-dashboard thead {
    background: #3498db;
    color: white;
}

.tabla-dashboard thead th {
    padding: 0.8rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.95rem;
}

.tabla-dashboard tbody td {
    padding: 0.8rem 1rem;
    border-bottom: 1px solid #eee;
    font-size: 0.95rem;
    color: #34495e;
}

.tabla-dashboard tbody tr:hover {
    background: #f8f9fa;
    transition: background 0.2s;
}

.tabla-dashboard tbody tr:last-child td {
    border-bottom: none;
}

.tabla-dashboard td strong {
    color: #2c3e50;
    font-size: 1rem;
}

.tabla-dashboard td small {
    color: #7f8c8d;
    font-size: 0.85rem;
}

/* Iconos en encabezado */
.tabla-dashboard th i,
section.card h2 i {
    font-size: 1.1rem;
}

/* Mensaje cuando no hay datos */
.tabla-dashboard + p {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    margin-top: 1rem;
}
    </style>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= $inicial_usuario ?></div>
        <span class="username"><?= htmlspecialchars($nombre_usuario) ?></span>
    </div>
    <ul class="menu">
        <li class="active">
            <a href="dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a>
        </li>
        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'productos') !== false) ? 'active' : '' ?>">
            <a href="modulos/productos/productos.php"><i class="fas fa-boxes"></i><span class="nav-text">Productos</span></a>
        </li>
        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'nueva_venta') !== false) ? 'active' : '' ?>">
            <a href="modulos/pedidos/nuevo_pedido.php"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a>
        </li>
        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'inventario') !== false) ? 'active' : '' ?>">
            <a href="modulos/inventario/inventario.php"><i class="fas fa-database"></i><span class="nav-text">Inventario</span></a>
        </li>
        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'reportes') !== false) ? 'active' : '' ?>">
            <a href="modulos/reportes/reportes.php"><i class="fas fa-chart-bar"></i><span class="nav-text">Reportes</span></a>
        </li>
        <li><a href="modulos/tasa_cambio/configurar_tasa.php"><i class="fas fa-dollar-sign"></i><span class="nav-text">Tasa de Cambio</span></a></li>
        <li><a href="modulos/notas/notas.php"><i class="fas fa-sticky-note"></i><span class="nav-text">Mis Notas</span></a></li>
        <li>
            <a href="modulos/usuarios/perfil.php"><i class="fas fa-user-cog"></i><span class="nav-text">Perfil</span></a>
        </li>
        <li>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a>
        </li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-home"></i> Dashboard</h1>
        <div class="user-info">
            <div class="user-initial"><?= $inicial_usuario ?></div>
            <span><?= htmlspecialchars($nombre_usuario) ?></span>
        </div>
    </header>

    <main id="dashboard-content">
        <!-- Sección 1: Resumen General -->
        <section class="card">
            <h2><i class="fas fa-chart-pie"></i> Resumen General</h2>
            <div class="overview">
                <div class="metric">
                    <i class="fas fa-cubes"></i>
                    <div>
                        <span>Total Productos</span>
                        <strong><?= number_format($total_productos, 0) ?></strong>
                    </div>
                </div>
                <div class="metric">
                    <i class="fas fa-clipboard-check"></i>
                    <div>
                        <span>Pedidos Cerrados</span>
                        <strong><?= number_format($total_pedidos, 0) ?></strong>
                    </div>
                </div>
                <div class="metric">
                    <i class="fas fa-box-open"></i>
                    <div>
                        <span>Total Stock</span>
                        <strong><?= number_format($total_stock, 0) ?></strong>
                    </div>
                </div>
                <!-- === MODIFICACIÓN: Fuera de Stock (clickeable) === -->
                <div class="metric alert" style="cursor: pointer;" title="Haz clic para ver productos agotados">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <span>Fuera de Stock</span>
                        <strong><?= number_format($fuera_de_stock, 0) ?></strong>
                    </div>
                </div>
            </div>

            <section class="grafica">
                <h2><i class="fas fa-chart-line"></i> Ganancias (USD)</h2>
                
                <!-- Botones de vista -->
                <div class="btn-group">
                    <button type="button" class="btn-view" onclick="cambiarVista('diaria')">Diaria</button>
                    <button type="button" class="btn-view" onclick="cambiarVista('mensual')">Mensual</button>
                    <button type="button" class="btn-view" onclick="cambiarVista('anual')">Anual</button>
                </div>

                <!-- Canvas para Chart.js -->
                <canvas id="graficaGanancias"></canvas>
            </section>

        <!-- Sección 2: Últimos Pedidos -->
        <section class="card">
            <h2><i class="fas fa-clock"></i> Últimos Pedidos Cerrados</h2>
            <?php if (empty($ultimos_pedidos)): ?>
                <p>No hay pedidos cerrados recientemente.</p>
            <?php else: ?>
                <table class="tabla-dashboard">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mesero</th>
                            <th>Total USD</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= $pedido['id_pedido'] ?></td>
                                <td><?= htmlspecialchars($pedido['mesero']) ?></td>
                                <td>$<?= number_format($pedido['total_usd'], 2) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido['fecha_cierre'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>

<!-- === MODAL DE STOCK === -->
<div id="modalStock" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Alertas de Stock</h3>
            <button class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert-section">
                <h4><i class="fas fa-times-circle"></i> Fuera de Stock (0)</h4>
                <ul id="listaFueraStock"></ul>
            </div>
            <div class="alert-section">
                <h4><i class="fas fa-exclamation-triangle"></i> Stock Bajo</h4>
                <ul id="listaStockBajo"></ul>
            </div>
        </div>
    </div>
</div>
<div id="overlayStock" class="overlay" onclick="cerrarModalStock()"></div>

<!-- === JAVASCRIPT CORREGIDO === -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Datos de productos (inyectados desde PHP)
    const productos = <?= json_encode($productos) ?>;

    // Referencias a elementos
    const btnFueraStock = document.querySelector('.metric.alert');
    const modal = document.getElementById('modalStock');
    const overlay = document.getElementById('overlayStock');
    const listaFuera = document.getElementById('listaFueraStock');
    const listaBajo = document.getElementById('listaStockBajo');

    if (!btnFueraStock || !modal || !overlay || !listaFuera || !listaBajo) {
        console.error('No se encontraron elementos necesarios para el modal.');
        return;
    }

    // Abrir modal
    function abrirModalStock() {
        listaFuera.innerHTML = '';
        listaBajo.innerHTML = '';

        let tieneFuera = false;
        let tieneBajo = false;

        productos.forEach(p => {
            // Fuera de stock
            if (p.stock <= 0) {
                const li = document.createElement('li');
                li.textContent = `${p.nombre} (Stock: ${p.stock})`;
                listaFuera.appendChild(li);
                tieneFuera = true;
            }
            // Stock bajo
            else if (
                (p.categoria_nombre === 'Aguas Frescas' && p.stock < 0.5) ||
                (p.categoria_nombre === 'Bebidas' && p.stock <= 5) ||
                (p.categoria_nombre !== 'Aguas Frescas' && p.categoria_nombre !== 'Bebidas' && p.stock <= 15)
            ) {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${p.nombre}</strong> - Stock: <span class="stock">${p.stock}</span>`;
                listaBajo.appendChild(li);
                tieneBajo = true;
            }
        });

        // Mensaje si no hay alertas
        if (!tieneFuera) {
            const li = document.createElement('li');
            li.textContent = 'No hay productos agotados.';
            li.style.color = '#27ae60';
            listaFuera.appendChild(li);
        }

        if (!tieneBajo) {
            const li = document.createElement('li');
            li.textContent = 'No hay productos con stock bajo.';
            li.style.color = '#27ae60';
            listaBajo.appendChild(li);
        }

        modal.style.display = 'flex';
        overlay.style.display = 'block';
    }

    // Cerrar modal
    function cerrarModalStock() {
        modal.style.display = 'none';
        overlay.style.display = 'none';
    }

    // Asignar evento al botón
    btnFueraStock.addEventListener('click', abrirModalStock);
    modal.addEventListener('click', cerrarModalStock);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Datos iniciales (mensual) - desde PHP
const datosMensual = {
    labels: <?= json_encode($meses) ?>,
    data: <?= json_encode($ganancias) ?>
};

let datosDiario = null;
let datosAnual = null;

let chart = null;
let vistaActual = 'mensual';

// Función para cargar datos diarios
async function cargarDatosDiarios() {
    if (datosDiario) return;
    try {
        const response = await fetch('obtener_ganancias.php?tipo=diaria');
        datosDiario = await response.json();
    } catch (err) {
        console.error('Error al cargar datos diarios:', err);
        alert('No se pudieron cargar los datos diarios.');
    }
}

// Función para cargar datos anuales
async function cargarDatosAnuales() {
    if (datosAnual) return;
    try {
        const response = await fetch('obtener_ganancias.php?tipo=anual');
        datosAnual = await response.json();
    } catch (err) {
        console.error('Error al cargar datos anuales:', err);
        alert('No se pudieron cargar los datos anuales.');
    }
}

// Cambiar vista
async function cambiarVista(tipo) {
    document.querySelectorAll('.btn-view').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');

    let labels = [];
    let data = [];
    let titulo = '';

    if (tipo === 'diaria') {
        await cargarDatosDiarios();
        if (datosDiario) {
            labels = datosDiario.labels;
            data = datosDiario.data;
            titulo = 'Últimos 30 días';
        }
    } else if (tipo === 'anual') {
        await cargarDatosAnuales();
        if (datosAnual) {
            labels = datosAnual.labels;
            data = datosAnual.data;
            titulo = 'Últimos 12 meses';
        }
    } else {
        labels = datosMensual.labels;
        data = datosMensual.data;
        titulo = 'Mensual';
    }

    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    chart.options.plugins.title.text = titulo;
    chart.update();
}

// Inicializar gráfica
document.addEventListener('DOMContentLoaded', async () => {
    const ctx = document.getElementById('graficaGanancias').getContext('2d');
    
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: datosMensual.labels,
            datasets: [{
                label: 'Ganancias (USD)',
                data: datosMensual.data,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: '#007bff',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                tooltip: { mode: 'index', intersect: false },
                title: { display: true, text: 'Mensual' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Activar botón "Mensual" por defecto
    document.querySelector('button[onclick="cambiarVista(\'mensual\')"]').classList.add('active');
});
</script>
<style>
    .btn-group {
    display: flex;
    gap: 8px;
    margin-bottom: 1rem;
    justify-content: center;
}

.btn-view {
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-view:hover {
    background: #e9ecef;
}

.btn-view.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.grafica {
    max-width: 800px;
    margin: 2rem auto;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>
</body>
</html>