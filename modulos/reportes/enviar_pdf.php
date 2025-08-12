<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';
require_once '../../config/db.php';

$mail = new PHPMailer(true);

try {
    // Capturar datos del POST
    $tipo = $_POST['tipo'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $mes = $_POST['mes'] ?? null;
    $anio = $_POST['anio'] ?? null;
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? null;
    $email_destino = $_POST['email'] ?? '';

    if (!$tipo || !$email_destino) {
        throw new Exception("Datos incompletos.");
    }

    // Validar email
    if (!filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo no válido.");
    }

    // === OBTENER DATOS DE LA EMPRESA ===
    $stmt_empresa = $pdo->prepare("SELECT nombre_empresa, logo FROM usuarios WHERE id_usuario = ? LIMIT 1");
    $stmt_empresa->execute([$_SESSION['usuario']['id_usuario']]);
    $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);
    $nombre_empresa = $empresa ? ($empresa['nombre_empresa'] ?: 'Comida Rápida') : 'Comida Rápida';

    // === GENERAR EL PDF EN MEMORIA ===
    ob_start();
    include 'generar_pdf.php'; // Este archivo genera el PDF y lo imprime en output
    $pdfContent = ob_get_clean();

    // === CONFIGURAR PHPMailer ===
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';           // Cambia si usas otro proveedor
    $mail->SMTPAuth   = true;
    $mail->Username   = 'erwin.ricardo08@gmail.com';        // Tu correo
    $mail->Password   = 'uurj qsud aavh xuqi';          // Contraseña de app
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Configuración del correo
    $mail->setFrom('mamachula.ve@gmail.com', $nombre_empresa);
    $mail->addAddress($email_destino);

    $mail->isHTML(true);
    $mail->Subject = "Reporte de $tipo - $nombre_empresa";
    $mail->Body    = "
        <h2>Reporte de $tipo</h2>
        <p>Estimado usuario,</p>
        <p>Se adjunta el reporte solicitado generado el " . date('d/m/Y H:i') . ".</p>
        <p><strong>Empresa:</strong> $nombre_empresa</p>
        <p>Este es un mensaje automático. Por favor, no responda.</p>
    ";

    // Adjuntar el PDF generado en memoria
    $mail->addStringAttachment($pdfContent, "reporte_$tipo_" . date('Ymd') . ".pdf", 'base64', 'application/pdf');

    // Enviar
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente.']);
} catch (Exception $e) {
    error_log("Error al enviar correo: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo]);
}