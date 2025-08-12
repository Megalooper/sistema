<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
$usuario = $_SESSION['usuario'];
$inicial_usuario = strtoupper(substr($usuario['nombre'], 0, 1));

// Obtener notas del usuario
$stmt = $pdo->prepare("SELECT * FROM notas WHERE id_usuario = ? ORDER BY fecha_actualizacion DESC");
$stmt->execute([$usuario['id_usuario']]);
$notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notas - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../../assets/img/favicon.ico" type="image/x-icon">
    <style>
        .nota-card {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .nota-titulo {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .nota-fecha {
            font-size: 0.8rem;
            color: #666;
        }
        .tabla-nota {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }
        .tabla-nota th, .tabla-nota td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 0.9rem;
        }
        .tabla-nota th {
            background: #f8f9fa;
        }
        .acciones-nota {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        .btn-nota {
            margin-right: 8px;
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        #modalNotas {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
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
        <li class="active"><a href="#"><i class="fas fa-sticky-note"></i><span class="nav-text">Mis Notas</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-sticky-note"></i> Mis Notas</h1>
        <button onclick="abrirModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Nota</button>
    </header>

    <main>
        <div class="card">
            <h2>Tus Notas</h2>
            <?php if (empty($notas)): ?>
                <p>No tienes notas aún. ¡Crea una nueva!</p>
            <?php else: ?>
                <?php foreach ($notas as $nota): ?>
                    <div class="nota-card">
                        <div class="nota-titulo"><?= htmlspecialchars($nota['titulo']) ?></div>
                        <div class="nota-tipo"><strong>Tipo:</strong> <?= ucfirst($nota['tipo']) ?></div>
                        <div class="nota-fecha">Creada: <?= date('d/m/Y H:i', strtotime($nota['fecha_creacion'])) ?></div>

                        <?php if ($nota['tipo'] === 'texto'): ?>
                            <p style="margin: 10px 0;"><?= nl2br(htmlspecialchars($nota['contenido'])) ?></p>
                        <?php elseif ($nota['tipo'] === 'tabla'): ?>
                            <?php
                            $filas = json_decode($nota['contenido'], true);
                            if (is_array($filas) && !empty($filas)):
                            ?>
                            <table class="tabla-nota">
                                <thead>
                                    <tr>
                                        <?php foreach ($filas[0] as $key => $value): ?>
                                            <th><?= htmlspecialchars(ucfirst($key)) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filas as $fila): ?>
                                        <tr>
                                            <?php foreach ($fila as $value): ?>
                                                <td><?= htmlspecialchars($value) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                                <p>Sin datos en la tabla.</p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="acciones-nota">
                            <a href="editar_nota.php?id=<?= $nota['id_nota'] ?>" class="btn btn-primary btn-nota">Editar</a>
                            <a href="eliminar_nota.php?id=<?= $nota['id_nota'] ?>" class="btn btn-danger btn-nota" 
                               onclick="return confirm('¿Eliminar esta nota?')">Eliminar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal para nueva nota -->
<div id="modalNotas">
    <div class="modal-content">
        <h2>Crear Nueva Nota</h2>
        <form method="POST" action="guardar_nota.php">
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Tipo de Nota</label>
                <select name="tipo" class="form-control" onchange="cambiarTipo(this.value)" required>
                    <option value="texto">Texto</option>
                    <option value="tabla">Tabla</option>
                </select>
            </div>

            <!-- Nota de texto -->
            <div id="seccionTexto">
                <div class="form-group">
                    <label>Contenido</label>
                    <textarea name="contenido" class="form-control" rows="5" placeholder="Escribe tu nota..."></textarea>
                </div>
            </div>

            <!-- Nota tipo tabla -->
            <div id="seccionTabla" style="display: none;">
                <p>Agrega filas a tu tabla:</p>
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
                        <!-- Filas se agregarán aquí -->
                    </tbody>
                </table>
                <button type="button" onclick="agregarFila()" class="btn btn-secondary">+ Agregar Fila</button>
                <input type="hidden" name="contenido_tabla" id="contenido_tabla">
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Nota</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModal() {
        document.getElementById('modalNotas').style.display = 'flex';
    }
    function cerrarModal() {
        document.getElementById('modalNotas').style.display = 'none';
    }

    function cambiarTipo(tipo) {
        document.getElementById('seccionTexto').style.display = tipo === 'texto' ? 'block' : 'none';
        document.getElementById('seccionTabla').style.display = tipo === 'tabla' ? 'block' : 'none';
    }

    let filas = [];
    function agregarFila() {
        const nombre = prompt("Nombre del producto:");
        const cantidad = parseFloat(prompt("Cantidad:")) || 0;
        const precio = parseFloat(prompt("Precio (USD):")) || 0;
        const total = cantidad * precio;

        if (!nombre) return;

        filas.push({ nombre, cantidad, precio: precio.toFixed(2), total: total.toFixed(2) });
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

    function eliminarFila(index) {
        filas.splice(index, 1);
        actualizarTabla();
    }
</script>

</body>
</html>