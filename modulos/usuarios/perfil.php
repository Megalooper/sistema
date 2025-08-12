<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

$usuario = $_SESSION['usuario'];
$id_usuario = $usuario['id_usuario'];
$error = $success = "";

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);
    $nombre_empresa = trim($_POST['nombre_empresa']);
    $rif = trim($_POST['rif']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $ip_cocina = trim($_POST['ip_cocina'] ?? '');

    try {
        // Actualizar datos
        if (!empty($contrasena)) {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ?, contrasena = ?, nombre_empresa = ?, rif = ?, direccion = ?, telefono = ?, ip_cocina = ? WHERE id_usuario = ?");
            $stmt->execute([$nombre, $correo, $hash, $nombre_empresa, $rif, $direccion, $telefono, $ip_cocina, $id_usuario]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ?, nombre_empresa = ?, rif = ?, direccion = ?, telefono = ?, ip_cocina = ? WHERE id_usuario = ?");
            $stmt->execute([$nombre, $correo, $nombre_empresa, $rif, $direccion, $telefono, $ip_cocina, $id_usuario]);
        }

        // Subir logo si se envió
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($ext, $allowed)) {
                $logo_name = "logo_empresa_" . $id_usuario . "." . $ext;
                $logo_destino = "../../assets/img/logos/" . $logo_name;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_destino)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET logo = ? WHERE id_usuario = ?");
                    $stmt->execute([$logo_name, $id_usuario]);
                } else {
                    $error = "Error al subir el logo.";
                }
            } else {
                $error = "Formato de logo no permitido.";
            }
        }

        // Actualizar sesión
        $_SESSION['usuario'] = $pdo->query("SELECT * FROM usuarios WHERE id_usuario = $id_usuario")->fetch(PDO::FETCH_ASSOC);
        $success = "Datos actualizados correctamente.";
    } catch (PDOException $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// Recargar datos del usuario
$usuario = $_SESSION['usuario'];
$logo_url = $usuario['logo'] ? "../../assets/img/logos/" . $usuario['logo'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil - Comida Rápida</title>
    <link rel="stylesheet" href="../../assets/css/estilo-dashboard.css">
    <link rel="stylesheet" href="../../assets/css/estilo-perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../../assets/img/favicon.ico" type="image/x-icon">
    <style>
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn-test {
            background: #f39c12;
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .test-result {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 8px;
            display: none;
        }
        .test-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        .test-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        .logo-preview {
            margin: 1rem 0;
            text-align: center;
        }
        .logo-preview img {
            max-width: 150px;
            max-height: 100px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .logo-preview p {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
<aside id="sidebar">
    <div class="logo">
        <div class="user-initial"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
        <span class="username"><?= htmlspecialchars($usuario['nombre']) ?></span>
    </div>
    <ul class="menu">
        <li class="active"><a href="#"><i class="fas fa-user-cog"></i><span class="nav-text">Perfil</span></a></li>
        <li><a href="../../dashboard.php"><i class="fas fa-home"></i><span class="nav-text">Inicio</span></a></li>
        <li><a href="../pedidos/nuevo_pedido.php"><i class="fas fa-clipboard-list"></i><span class="nav-text">Nuevo Pedido</span></a></li>
        <li><a href="../pedidos/gestionar_pedidos.php"><i class="fas fa-tasks"></i><span class="nav-text">Gestionar Pedidos</span></a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i><span class="nav-text">Cerrar Sesión</span></a></li>
    </ul>
</aside>

<div id="content">
    <header id="navbar">
        <h1><i class="fas fa-user-cog"></i> Configuración del Perfil</h1>
        <div class="user-info">
            <div class="user-initial"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($usuario['nombre']) ?></span>
        </div>
    </header>

    <main>
        <section class="card">
            <h2><i class="fas fa-user-edit"></i> Datos de Usuario y Empresa</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="perfil.php" enctype="multipart/form-data">
                <!-- Datos de Usuario -->
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($usuario['correo']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="contrasena">Contraseña (dejar vacía para no cambiar)</label>
                    <input type="password" id="contrasena" name="contrasena">
                </div>

                <hr>

                <!-- Datos de la Empresa -->
                <h3><i class="fas fa-building"></i> Datos de la Empresa</h3>
                <div class="form-group">
                    <label for="nombre_empresa">Nombre de la Empresa</label>
                    <input type="text" id="nombre_empresa" name="nombre_empresa" value="<?= htmlspecialchars($usuario['nombre_empresa'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="rif">RIF</label>
                    <input type="text" id="rif" name="rif" value="<?= htmlspecialchars($usuario['rif'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($usuario['direccion'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                </div>

                <!-- Subir Logo -->
                <div class="form-group">
                    <label for="logo">Logo de la Empresa</label>
                    <input type="file" id="logo" name="logo" accept="image/*">
                    <div class="logo-preview">
                        <?php if ($logo_url): ?>
                            <img src="<?= $logo_url ?>" alt="Logo actual">
                            <p>Logo actual</p>
                        <?php else: ?>
                            <p>No hay logo subido</p>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>

                <!-- Impresora de Cocina -->
                <h3><i class="fas fa-print"></i> Impresora de Cocina</h3>
                <div class="form-group">
                    <label for="ip_cocina">IP de la Impresora de Cocina</label>
                    <input type="text" id="ip_cocina" name="ip_cocina" placeholder="192.168.1.100" value="<?= htmlspecialchars($usuario['ip_cocina'] ?? '') ?>">
                    <button type="button" class="btn-test" onclick="probarImpresora()">
                        <i class="fas fa-print"></i> Probar Impresora
                    </button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>

            <div id="testResult" class="test-result">
                <span id="testMessage"></span>
            </div>
        </section>
    </main>
</div>

<script>
function probarImpresora() {
    const ip = document.getElementById('ip_cocina').value;
    if (!ip) {
        alert("Por favor, ingresa la IP de la impresora de cocina.");
        return;
    }

    fetch('probar_impresora_cocina.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ip: ip })
    })
    .then(r => r.json())
    .then(data => {
        const resultDiv = document.getElementById('testResult');
        const messageDiv = document.getElementById('testMessage');
        resultDiv.style.display = 'block';

        if (data.success) {
            resultDiv.className = 'test-result test-success';
            messageDiv.textContent = `✅ ¡Prueba exitosa en ${ip}!`;
        } else {
            resultDiv.className = 'test-result test-error';
            messageDiv.textContent = `❌ Error: ${data.error}`;
        }
    })
    .catch(err => {
        console.error('Error:', err);
        document.getElementById('testResult').style.display = 'block';
        document.getElementById('testMessage').textContent = '❌ Error de conexión con el servidor.';
    });
}
</script>
</body>
</html>