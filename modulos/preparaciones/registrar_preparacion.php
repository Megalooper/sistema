<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';

// Obtener datos del formulario
$nombre = trim($_POST['nombre']);
$descripcion = trim($_POST['descripcion']);
$precio_usd = filter_input(INPUT_POST, 'precio_usd', FILTER_VALIDATE_FLOAT);
$id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);

$error = "";
$success = "";

if (empty($nombre) || $precio_usd === false || $precio_usd <= 0 || !$id_categoria) {
    $error = "Datos incompletos o inválidos.";
    header("Location: preparaciones.php?error=" . urlencode($error));
    exit;
}

try {
    // Insertar preparación
    $stmt = $pdo->prepare("INSERT INTO preparaciones (nombre, descripcion, precio_usd, id_categoria) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $descripcion, $precio_usd, $id_categoria]);

    $success = "Preparación registrada correctamente.";
    header("Location: preparaciones.php?mensaje=" . urlencode($success));
    exit;

} catch (PDOException $e) {
    $error = "Error al registrar la preparación.";
    header("Location: preparaciones.php?error=" . urlencode($error));
    exit;
}