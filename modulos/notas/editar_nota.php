<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
$usuario = $_SESSION['usuario'];
$inicial_usuario = strtoupper(substr($usuario['nombre'], 0, 1));

$id_nota = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_usuario = $usuario['id_usuario'];

if (!$id_nota) {
    header("Location: notas.php?error=ID de nota no válido.");
    exit;
}

// Obtener nota
$stmt = $pdo->prepare("SELECT * FROM notas WHERE id_nota = ? AND id_usuario = ?");
$stmt->execute([$id_nota, $id_usuario]);
$nota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nota) {
    header("Location: notas.php?error=Nota no encontrada o no tienes permiso.");
    exit;
}

// Manejo de errores y éxitos
$error = $success = "";
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Nota - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .tabla-nota {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .tabla-nota th, .tabla-nota td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .tabla-nota th {
            background: #f8f9fa;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .acciones {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= $inicial_usuario ?></div>
        <span class="username"><?= htmlspecialchars($usuario['nombre']) ?></span>
    </div>
    <ul class="menu">
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li><a href="notas.php"><i class="fas fa-sticky-note"></i><span class="nav-text">Mis Notas</span></a></li>
        <li class="active"><a href="#"><i class="fas fa-edit"></i><span class="nav-text">Editar Nota</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-edit"></i> Editar Nota</h1>
        <a href="notas.php" class="btn btn-secondary">Volver</a>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>

        <section class="card">
            <h2>Editar Nota</h2>
            <form method="POST" action="actualizar_nota.php">
                <input type="hidden" name="id_nota" value="<?= $nota['id_nota'] ?>">

                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($nota['titulo']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" id="tipo" onchange="cambiarTipo()" required>
                        <option value="texto" <?= $nota['tipo'] === 'texto' ? 'selected' : '' ?>>Texto</option>
                        <option value="tabla" <?= $nota['tipo'] === 'tabla' ? 'selected' : '' ?>>Tabla</option>
                    </select>
                </div>

                <!-- Nota de texto -->
                <div id="seccionTexto" style="display: <?= $nota['tipo'] === 'texto' ? 'block' : 'none' ?>;">
                    <div class="form-group">
                        <label>Contenido</label>
                        <textarea name="contenido" rows="6" class="form-control"><?= htmlspecialchars($nota['contenido']) ?></textarea>
                    </div>
                </div>

                <!-- Nota tipo tabla -->
                <div id="seccionTabla" style="display: <?= $nota['tipo'] === 'tabla' ? 'block' : 'none' ?>;">
                    <p>Edita las filas de la tabla:</p>
                    <table id="tablaFilas" class="tabla-nota">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filas se cargarán con JavaScript -->
                        </tbody>
                    </table>
                    <button type="button" onclick="agregarFila()" class="btn btn-secondary">+ Agregar Fila</button>
                    <input type="hidden" name="contenido_tabla" id="contenido_tabla">
                </div>

                <div class="acciones">
                    <a href="notas.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
    let filas = <?= $nota['tipo'] === 'tabla' && !empty($nota['contenido']) ? $nota['contenido'] : '[]' ?>;

    function cambiarTipo() {
        const tipo = document.getElementById('tipo').value;
        document.getElementById('seccionTexto').style.display = tipo === 'texto' ? 'block' : 'none';
        document.getElementById('seccionTabla').style.display = tipo === 'tabla' ? 'block' : 'none';
        if (tipo === 'tabla') actualizarTabla();
    }

    function agregarFila() {
        const nombre = prompt("Nombre del producto:");
        const cantidad = parseFloat(prompt("Cantidad:")) || 0;
        const precio = parseFloat(prompt("Precio (USD):")) || 0;
        const total = cantidad * precio;

        if (!nombre) return;

        filas.push({ nombre, cantidad, precio: precio.toFixed(2), total: total.toFixed(2) });
        actualizarTabla();
    }

    function eliminarFila(index) {
        filas.splice(index, 1);
        actualizarTabla();
    }

    function actualizarTabla() {
        const tbody = document.querySelector('#tablaFilas tbody');
        tbody.innerHTML = '';
        filas.forEach((fila, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${fila.nombre}</td>
                <td>${fila.cantidad}</td>
                <td>$${fila.precio}</td>
                <td>$${fila.total}</td>
                <td><button type="button" onclick="eliminarFila(${index})">Eliminar</button></td>
            `;
            tbody.appendChild(tr);
        });
        document.getElementById('contenido_tabla').value = JSON.stringify(filas);
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function () {
        if (<?= $nota['tipo'] === 'tabla' ? 'true' : 'false' ?>) {
            actualizarTabla();
        }
    });
</script>

</body>
</html>