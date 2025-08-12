<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$inicial_usuario = strtoupper(substr($usuario['nombre'], 0, 1));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../../assets/img/favicon.ico" type="image/x-icon">
    <style>
        .reportes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .reporte-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .reporte-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 1rem;
        }
        .reporte-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .btn-reporte {
            background: #007bff;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
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
        <li class="active"><a href="#"><i class="fas fa-chart-bar"></i><span class="nav-text">Reportes</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<!-- Main Content -->
<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-chart-bar"></i> Generar Reportes</h1>
        <div class="user-info">
            <div class="user-initial"><?= $inicial_usuario ?></div>
            <span><?= htmlspecialchars($usuario['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-file-alt"></i> Seleccione el tipo de reporte</h2>
            <div class="reportes-grid">

                <!-- Reporte de Ventas -->
                <div class="reporte-card">
                    <div class="reporte-icon"><i class="fas fa-shopping-cart"></i></div>
                    <h3 class="reporte-title">Ventas</h3>
                    <form method="POST" action="generar_pdf.php" target="_blank">
                        <input type="hidden" name="tipo" value="ventas">
                        <div class="form-group">
                            <label>Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Fin</label>
                            <input type="date" name="fecha_fin" required>
                        </div>
                        <button type="submit" class="btn-reporte">
                            <i class="fas fa-file-pdf"></i> Generar PDF
                        </button>
                        <button type="button" class="btn-reporte" onclick="enviarPorCorreo(this)">
                            <i class="fas fa-envelope"></i> Enviar por Correo
                        </button>
                    </form>
                </div>

                <!-- Reporte de Inventario -->
                <div class="reporte-card">
                    <div class="reporte-icon"><i class="fas fa-database"></i></div>
                    <h3 class="reporte-title">Inventario</h3>
                    <form method="POST" action="generar_pdf.php" target="_blank">
                        <input type="hidden" name="tipo" value="inventario">
                        <div class="form-group">
                            <label>Tipo de Movimiento</label>
                            <select name="tipo_movimiento">
                                <option value="">Todos</option>
                                <option value="compra">Compra</option>
                                <option value="produccion">Producción</option>
                                <option value="merma">Merma</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha Inicio</label>
                            <input type="date" name="fecha_inicio">
                        </div>
                        <div class="form-group">
                            <label>Fecha Fin</label>
                            <input type="date" name="fecha_fin">
                        </div>
                        <button type="submit" class="btn-reporte">
                            <i class="fas fa-file-pdf"></i> Generar PDF
                        </button>
                        <button type="button" class="btn-reporte" onclick="enviarPorCorreo(this)">
                            <i class="fas fa-envelope"></i> Enviar por Correo
                        </button>
                    </form>
                </div>

                <!-- Reporte de Deliverys -->
                <div class="reporte-card">
                    <div class="reporte-icon"><i class="fas fa-truck"></i></div>
                    <h3 class="reporte-title">Deliverys</h3>
                    <form method="POST" action="generar_pdf.php" target="_blank">
                        <input type="hidden" name="tipo" value="deliverys">
                        <div class="form-group">
                            <label>Mes</label>
                            <select name="mes" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>"><?= date('F', mktime(0, 0, 0, $m)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Año</label>
                            <input type="number" name="anio" value="<?= date('Y') ?>" min="2020" required>
                        </div>
                        <button type="submit" class="btn-reporte">
                            <i class="fas fa-file-pdf"></i> Generar PDF
                        </button>
                        <button type="button" class="btn-reporte" onclick="enviarPorCorreo(this)">
                            <i class="fas fa-envelope"></i> Enviar por Correo
                        </button>
                    </form>
                </div>

                <!-- Reporte de Propinas -->
                <div class="reporte-card">
                    <div class="reporte-icon"><i class="fas fa-hand-holding-usd"></i></div>
                    <h3 class="reporte-title">Propinas</h3>
                    <form method="POST" action="generar_pdf.php" target="_blank">
                        <input type="hidden" name="tipo" value="propinas">
                        <div class="form-group">
                            <label>Mes</label>
                            <select name="mes" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>"><?= date('F', mktime(0, 0, 0, $m)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Año</label>
                            <input type="number" name="anio" value="<?= date('Y') ?>" min="2020" required>
                        </div>
                        <button type="submit" class="btn-reporte">
                            <i class="fas fa-file-pdf"></i> Generar PDF
                        </button>
                        <button type="button" class="btn-reporte" onclick="enviarPorCorreo(this)">
                            <i class="fas fa-envelope"></i> Enviar por Correo
                        </button>
                    </form>
                </div>

            </div>
        </section>
    </main>
</div>

</body>
<script>
function enviarPorCorreo(button) {
    const form = button.closest('form');
    const tipo = form.querySelector('input[name="tipo"]').value;
    const email = prompt("Ingrese el correo electrónico:");

    if (!email) return;

    const formData = new FormData(form);
    formData.append('email', email);

    // Mostrar cargando
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    button.disabled = true;

    fetch('enviar_pdf.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || (data.success ? 'Correo enviado' : 'Error'));
        button.innerHTML = originalText;
        button.disabled = false;
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error de red al enviar el correo.');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
</html>