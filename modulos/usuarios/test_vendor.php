<?php
$autoload = __DIR__ . '..\vendor\autoload.php';
echo "Ruta: $autoload<br>";
echo "¿Existe? " . (file_exists($autoload) ? '✅ Sí' : '❌ No') . "<br>";
echo "¿Es legible? " . (is_readable($autoload) ? '✅ Sí' : '❌ No') . "<br>";

if (file_exists($autoload)) {
    require_once $autoload;
    echo "✅ Autoload cargado correctamente.";
}
?>