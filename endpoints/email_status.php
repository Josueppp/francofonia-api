<?php
/**
 * Endpoint: Estado de Correos
 * Muestra cuántos correos hay pendientes y permite procesarlos
 * GET /api/email-status
 * POST /api/email-status (para procesar)
 */

require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

// Contar pendientes
$stmt = $db->query('SELECT COUNT(*) as total FROM participants WHERE correoEnviado = 0 AND correo != ""');
$pendientes = $stmt->fetch()['total'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'pendientes' => (int)$pendientes,
        'mensaje' => $pendientes > 0 ? "Hay $pendientes correos pendientes" : 'No hay correos pendientes'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($pendientes == 0) {
        echo json_encode(['success' => true, 'message' => 'No hay correos pendientes', 'enviados' => 0]);
        exit;
    }
    
    // Procesar hasta 10 correos
    $config = require __DIR__ . '/../config/email_config.php';
    $smtpConfig = $config[$config['provider']] ?? $config['gmail'];
    
    $stmt = $db->query('SELECT * FROM participants WHERE correoEnviado = 0 AND correo != "" LIMIT 10');
    $lista = $stmt->fetchAll();
    
    $enviados = 0;
    $errores = [];
    
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
        $mail->SMTPKeepAlive = true;
        
        foreach ($lista as $p) {
            try {
                $mail->clearAddresses();
                $mail->addAddress($p['correo'], $p['nombre']);
                $mail->Subject = $config['subject'];
                
                $qrData = urlencode($p['id']);
                $qrSize = $config['qr_size'] ?? 250;
                $qrUrl = "{$config['qr_api_url']}?size={$qrSize}x{$qrSize}&data={$qrData}";
                
                $body = buildEmailBody($p, $qrUrl);
                $mail->Body = $body;
                $mail->send();
                
                $upd = $db->prepare('UPDATE participants SET correoEnviado = 1 WHERE id = ?');
                $upd->execute([$p['id']]);
                $enviados++;
                
            } catch (Exception $e) {
                $errores[] = $p['correo'] . ': ' . $mail->ErrorInfo;
            }
            $mail->getSMTPInstance()->reset();
        }
        $mail->smtpClose();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    
    // Contar restantes
    $stmt = $db->query('SELECT COUNT(*) as total FROM participants WHERE correoEnviado = 0 AND correo != ""');
    $restantes = $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'enviados' => $enviados,
        'pendientes' => $restantes,
        'errores' => $errores
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);

function buildEmailBody($participante, $qrUrl) {
    $nombre = htmlspecialchars($participante['nombre']);
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px;">
                <tr><td style="background: linear-gradient(135deg, #0A2342 0%, #2087C7 60%, #174A7C 100%); padding: 30px; text-align: center;">
                    <h1 style="color: #FBC02D; margin: 0; font-size: 28px;">🥐 Francofonía 2026</h1>
                    <p style="color: #ffffff; margin: 10px 0 0 0;">La gastronomía une al mundo</p>
                </td></tr>
                <tr><td style="padding: 30px;">
                    <h2 style="color: #722F37; margin-top: 0;">Bienvenue, $nombre!</h2>
                    <p style="color: #333333; font-size: 14px;">Bienvenido al evento exclusivo de cultura y gastronomía francesa.</p>
                    <p style="color: #333333; font-size: 14px;">Aquí está tu <strong>código QR digital</strong>:</p>
                    <div style="text-align: center; margin: 25px 0;">
                        <div style="background: white; padding: 15px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <img src="$qrUrl" alt="QR" style="width: 250px; height: 250px;" />
                        </div>
                    </div>
                    <p style="color: #666666; font-size: 12px; text-align: center;">Presenta este código en cada stand</p>
                </td></tr>
                <tr><td style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                    <p style="color: #888888; font-size: 12px; margin: 0;">© 2026 Francofonía</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
HTML;
}
