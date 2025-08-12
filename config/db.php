<?php
// Configuración de la conexión a la base de datos
$host = "localhost";     // Servidor
$user = "root";          // Usuario de MySQL
$password = "";          // Contraseña (en tu caso está vacía)
$dbname = "bd_comida_rapida"; // Nombre de la nueva base de datos

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    // Configurar el manejo de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}