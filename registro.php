<?php
session_start();

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php");
    exit;
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);
    $confirmar_contrasena = trim($_POST['confirmar_contrasena']);

    if (empty($nombre) || empty($correo) || empty($contrasena) || empty($confirmar_contrasena)) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            require_once 'config/db.php';

            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            if ($stmt->rowCount() > 0) {
                $error = "El correo ya está registrado.";
            } else {
                // Registrar usuario
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, contrasena) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $correo, $hash]);

                $success = "Registro exitoso. Ahora puedes iniciar sesión.";
            }
        } catch (PDOException $e) {
            $error = "Error al registrar el usuario.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Comida Rápida</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css ">
    <link rel="stylesheet" href="assets/css/estilo-login.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="logo-container">
            <i class="fas fa-user-plus"></i>
            <h1>Registrar Cuenta</h1>
        </div>

        <?php if (!empty($error)): ?>
            <p class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="form-actions">
            <div class="form-group">
                <label for="nombre"><i class="fas fa-user"></i> Nombre Completo</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Juan Pérez">
            </div>

            <div class="form-group">
                <label for="correo"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                <input type="email" id="correo" name="correo" class="form-control" required placeholder="ejemplo@dominio.com">
            </div>

            <div class="form-group">
                <label for="contrasena"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" class="form-control" required placeholder="Ingrese su contraseña">
            </div>

            <div class="form-group">
                <label for="confirmar_contrasena"><i class="fas fa-key"></i> Confirmar Contraseña</label>
                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" class="form-control" required placeholder="Repita su contraseña">
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Registrarse
            </button>
        </form>

        <p class="registro-link">
            ¿Ya tienes cuenta? <a href="index.php">Inicia sesión aquí</a>
        </p>
    </div>
</body>
</html>