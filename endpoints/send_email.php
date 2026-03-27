<?php
/**
 * Endpoint: Envío de Correo Individual
 * Envía un correo inmediatamente a un participante específico
 * Útil para pruebas o reenvíos
 * 
 * POST /api/send-email
 * {
 *   "participantId": "p-123456"
 * }
 * 
 * Opcional:
 * {
 *   "participantId": "p-123456",
 *   "resend": true  // Para reenviar aunque ya tenga correoEnviado = 1
 * }
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use POST']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$participantId = $data['participantId'] ?? '';
$forceResend = $data['resend'] ?? false;

if (empty($participantId)) {
    http_response_code(400);
    echo json_encode(['error' => 'participantId es requerido']);
    exit;
}

// Cargar configuración
$config = require __DIR__ . '/../config/email_config.php';
$smtpConfig = $config[$config['provider']] ?? $config['gmail'];

// Conectar a BD
$db = (new Database())->getConnection();

// Buscar participante
$stmt = $db->prepare('SELECT * FROM participants WHERE id = ?');
$stmt->execute([$participantId]);
$participante = $stmt->fetch();

if (!$participante) {
    http_response_code(404);
    echo json_encode(['error' => 'Participante no encontrado']);
    exit;
}

// Verificar si ya se envió (a menos que sea reenvío forzado)
if (!$forceResend && $participante['correoEnviado']) {
    echo json_encode([
        'message' => 'El correo ya fue enviado anteriormente',
        'resend' => true,
        'hint' => 'Use {"participantId": "xxx", "resend": true} para forzar reenvío'
    ]);
    exit;
}

if (empty($participante['correo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'El participante no tiene correo electrónico']);
    exit;
}

// Configurar PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = $smtpConfig['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpConfig['username'];
    $mail->Password   = $smtpConfig['password'];
    $mail->SMTPSecure = $smtpConfig['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtpConfig['port'];
    $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;
    
    // Destinatario
    $mail->addAddress($participante['correo'], $participante['nombre']);
    
    // Asunto
    $mail->Subject = $config['subject'];
    
    // Generar QR
    $qrData = urlencode($participante['id']);
    $qrSize = $config['qr_size'] ?? 250;
    $qrUrl = "{$config['qr_api_url']}?size={$qrSize}x{$qrSize}&data={$qrData}";
    
    // Cuerpo del correo
    $body = buildEmailBody($participante, $qrUrl, $config);
    $mail->Body = $body;
    
    // Enviar
    $mail->send();
    
    // Actualizar BD
    $upd = $db->prepare('UPDATE participants SET correoEnviado = 1 WHERE id = ?');
    $upd->execute([$participantId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Correo enviado exitosamente',
        'correo' => $participante['correo'],
        'nombre' => $participante['nombre']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al enviar correo',
        'details' => $mail->ErrorInfo,
        'hint' => 'Verificar credenciales SMTP en config/email_config.php'
    ]);
}

/**
 * Construye el cuerpo del correo HTML (mismo que process_email_queue)
 */
function buildEmailBody($participante, $qrUrl, $config) {
    $nombre = htmlspecialchars($participante['nombre']);
    
    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background-color: #050A14; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #FFFFFF; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #050A14; padding: 40px 0; }
        .card { width: 600px; background-color: #0D1526; margin: 0 auto; border-radius: 15px; border: 1px solid #1E2D45; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { background: linear-gradient(135deg, #0A1A2F 0%, #162A47 100%); padding: 40px; text-align: center; border-bottom: 2px solid #FBC02D; }
        .content { padding: 40px; line-height: 1.8; text-align: center; color: #FFFFFF; }
        .footer { padding: 20px; text-align: center; font-size: 11px; color: #8A9BB3; background-color: #090F1B; }
        h1 { color: #FBC02D; margin: 0; font-size: 26px; letter-spacing: 1px; }
        h2 { color: #FFFFFF; font-weight: 300; margin-bottom: 20px; }
        p { margin-bottom: 20px; font-size: 16px; color: #CED4DA; }
        .qr-container { background: white; padding: 20px; border-radius: 15px; display: inline-block; box-shadow: 0 0 25px rgba(251, 192, 45, 0.2); margin: 20px 0; }
        .qr-img { width: 250px; height: 250px; display: block; }
        .badge { display: inline-block; padding: 5px 15px; background-color: rgba(251, 192, 45, 0.1); color: #FBC02D; border: 1px solid #FBC02D; border-radius: 20px; font-size: 12px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>🥐 FRANCOFONÍA 2026</h1>
                <div style="color: #8A9BB3; font-size: 13px; margin-top: 5px; letter-spacing: 2px;">INVITACIÓN EXCLUSIVA</div>
            </div>
            <div class="content">
                <div class="badge">Bienvenue</div>
                <h2>¡Hola, $nombre!</h2>
                <p>Te damos la bienvenida al evento más esperado de gastronomía y cultura francesa.</p>
                <p>Presenta este <strong>Código QR Digital</strong> en cada stand para registrar tu visita y disfrutar de las degustaciones exclusivas:</p>
                
                <div class="qr-container">
                    <img src="$qrUrl" alt="Tu Código QR" class="qr-img" />
                </div>
                
                <p style="color: #FBC02D; font-size: 13px; margin-top: 20px;">📌 Tip: Puedes tomarle una captura de pantalla para tenerlo siempre a la mano.</p>
            </div>
            <div class="footer">
                © 2026 Francofonía. La experiencia gourmet definitiva en tus manos.
            </div>
        </div>
    </div>
</body>
</html>
HTML;

    return $body;
}
