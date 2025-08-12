<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../index.php");
    exit;
}

require_once '../../../config/db.php';

$id_preparacion = filter_input(INPUT_POST, 'id_preparacion', FILTER_VALIDATE_INT);
$id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
$cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_FLOAT); // Ahora es FLOAT

if (!$id_preparacion || !$id_producto || $cantidad === false || $cantidad <= 0) {
    header("Location: gestion_receta.php?id=$id_preparacion&error=Datos incompletos o inválidos.");
    exit;
}

// Verificar si ya existe
$stmt = $pdo->prepare("SELECT * FROM recetas WHERE id_preparacion = ? AND id_producto = ?");
$stmt->execute([$id_preparacion, $id_producto]);

if ($stmt->fetch()) {
    header("Location: gestion_receta.php?id=$id_preparacion&error=Este producto ya está en la receta.");
    exit;
}

// Agregar ingrediente
try {
    $stmt = $pdo->prepare("INSERT INTO recetas (id_preparacion, id_producto, cantidad) VALUES (?, ?, ?)");
    $stmt->execute([$id_preparacion, $id_producto, $cantidad]);

    header("Location: gestion_receta.php?id=$id_preparacion&mensaje=Ingrediente agregado correctamente.");
    exit;
} catch (PDOException $e) {
    header("Location: gestion_receta.php?id=$id_preparacion&error=No se pudo agregar el ingrediente.");
    exit;
}