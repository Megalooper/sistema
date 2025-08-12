<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/db.php';
require_once '../../vendor/autoload.php'; // Para Spout

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Type;

$error = "";
$success = "";
$productos_importados = 0;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = "Error al subir el archivo.";
    } else {
        $tmp_path = $archivo['tmp_name'];
        $original_name = $archivo['name'];
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        try {
            // === CREAR LECTOR SEGÚN LA EXTENSIÓN ===
            $reader = null;

            if ($extension === 'xlsx' || $extension === 'xls') {
                $reader = ReaderEntityFactory::createXLSXReader();
            } elseif ($extension === 'csv') {
                $reader = ReaderEntityFactory::createCSVReader();
                // Opcional: configurar delimitador
                // $reader->setFieldDelimiter(',');
                // $reader->setFieldEnclosure('"');
            } else {
                $error = "Formato no soportado. Usa .xlsx o .csv.";
                throw new Exception("Formato no soportado");
            }

            // Abrir archivo
            $reader->open($tmp_path);

            $fila = 1;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if ($fila === 1) {
                        $fila++;
                        continue; // Saltar encabezado
                    }

                    $cells = $row->getCells();
                    $data = [];
                    foreach ($cells as $cell) {
                        $data[] = $cell->getValue();
                    }

                    if (empty(array_filter($data))) {
                        $fila++;
                        continue; // Fila vacía
                    }

                    // Mapeo de columnas
                    $codigo          = $data[0] ?? null;
                    $nombre          = trim($data[1] ?? '');
                    $descripcion     = trim($data[2] ?? '');
                    $precio_usd      = filter_var($data[3], FILTER_VALIDATE_FLOAT);
                    $stock           = filter_var($data[4], FILTER_VALIDATE_FLOAT);
                    $nombre_categoria = trim($data[5] ?? '');
                    $es_ingrediente  = filter_var($data[6], FILTER_VALIDATE_BOOLEAN);
                    $visible_venta   = !isset($data[7]) || filter_var($data[7], FILTER_VALIDATE_BOOLEAN);

                    // Validación
                    if (empty($nombre)) {
                        $errores[] = "Fila $fila: Nombre del producto es obligatorio.";
                        $fila++;
                        continue;
                    }
                    if ($precio_usd === false || $precio_usd <= 0) {
                        $errores[] = "Fila $fila: Precio USD inválido.";
                        $fila++;
                        continue;
                    }
                    if ($stock === false || $stock < 0) {
                        $errores[] = "Fila $fila: Stock inválido.";
                        $fila++;
                        continue;
                    }

                    // Obtener id_categoria
                    $id_categoria = null;
                    if (!empty($nombre_categoria)) {
                        $stmt_cat = $pdo->prepare("SELECT id_categoria FROM categorias WHERE nombre = ?");
                        $stmt_cat->execute([$nombre_categoria]);
                        $categoria = $stmt_cat->fetch();
                        if ($categoria) {
                            $id_categoria = $categoria['id_categoria'];
                        } else {
                            $errores[] = "Fila $fila: Categoría '$nombre_categoria' no encontrada.";
                            $fila++;
                            continue;
                        }
                    }

                    // Insertar producto
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO productos 
                            (codigo, id_categoria, nombre, descripcion, precio_usd, stock, es_ingrediente, visible_venta) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $codigo,
                            $id_categoria,
                            $nombre,
                            $descripcion,
                            $precio_usd,
                            $stock,
                            $es_ingrediente,
                            $visible_venta
                        ]);
                        $productos_importados++;
                    } catch (PDOException $e) {
                        $errores[] = "Fila $fila: Error al registrar '$nombre'.";
                    }

                    $fila++;
                }
            }

            $reader->close();

            if ($productos_importados > 0) {
                $success = "$productos_importados producto(s) importado(s) correctamente.";
            }
            if (!empty($errores)) {
                $error = "Errores:<br>" . implode("<br>", $errores);
            }

        } catch (Exception $e) {
            $error = "Error al leer el archivo: " . $e->getMessage();
        }
    }
}

// Redirigir con mensaje
if ($success) {
    header("Location: productos.php?mensaje=" . urlencode($success));
} else {
    header("Location: productos.php?error=" . urlencode($error));
}
exit;