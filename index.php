<?php
session_start();

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php");
    exit;
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);

    require_once 'config/db.php';

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            // Iniciar sesión
            $_SESSION['usuario'] = $usuario;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Correo o contraseña incorrectos.";
        }
    } catch (PDOException $e) {
        $error = "Error al iniciar sesión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DevSolutions POS - Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/estilo-login.css">
    <!-- En el <head> de todas tus páginas -->
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon"></head>
<body class="login-body">
    <div class="login-container">
        <!-- Logo y nombre del sistema -->
        <div class="logo-container">
        <!-- Aquí va el SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" width="200" height="60" viewBox="0 0 200 60">
        <!-- Fondo transparente -->
        <rect width="200" height="60" fill="transparent" />

        <!-- Icono: Terminal de código + Etiqueta de venta -->
        <!-- Terminal de código (izquierda) -->
        <rect x="10" y="15" width="24" height="30" rx="6" fill="#2c3e50" />
        <rect x="14" y="19" width="16" height="2" fill="#ecf0f1" />
        <rect x="14" y="24" width="10" height="2" fill="#ecf0f1" />
        <rect x="14" y="29" width="14" height="2" fill="#ecf0f1" />
        <circle cx="16" cy="40" r="2" fill="#e74c3c" />
        <circle cx="22" cy="40" r="2" fill="#f39c12" />
        <circle cx="28" cy="40" r="2" fill="#27ae60" />

        <!-- Símbolo POS (derecha) -->
        <path d="M60 15 L75 15 L80 25 L70 25 L60 25 Z" fill="#3498db"/>
        <path d="M65 30 L75 30 L75 35 L65 35 Z" fill="#2c3e50"/>
        <path d="M68 38 L72 38 L72 42 L68 42 Z" fill="#2c3e50"/>

        <!-- Texto: DevSolutions POS -->
        <text x="90" y="28" font-family="Arial, sans-serif" font-size="14" fill="#2c3e50" font-weight="bold">
            DevSolutions
        </text>
        <text x="90" y="44" font-family="Arial, sans-serif" font-size="12" fill="#3498db" font-weight="600">
            Sistema de Gestión POS
        </text>

        <!-- Línea decorativa -->
        <line x1="88" y1="50" x2="180" y2="50" stroke="#bdc3c7" stroke-width="1" />
        </svg>
    </div>

        <?php if (!empty($error)): ?>
            <p class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="correo"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                <input type="email" id="correo" class="form-control" name="correo" required value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="contrasena"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="contrasena" class="form-control" name="contrasena" required>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <p class="registro-link">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </p>
    </div>
</body>

</html>