<?php
/**
 * Endpoint: Process Email Queue
 * Envía correos pendientes (correoEnviado = 0) en lotes asíncronos
 * 
 * Mejorado con:
 * - Archivo de configuración separado
 * - Logs estructurados
 * - Manejo de errores detallado
 * - Reintentos automáticos
 * - Diagnóstico automático de problemas
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

// Validar que se haga mediante POST o llamado local seguro
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Cargar configuración
$config = require __DIR__ . '/../config/email_config.php';
$smtpConfig = $config[$config['provider']] ?? $config['gmail'];

// Función de logging
function logEmail($mensaje, $tipo = 'INFO') {
    global $config;
    if ($config['enable_logs']) {
        $logDir = dirname($config['log_file']);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $fecha = date('Y-m-d H:i:s');
        $logEntry = "[$fecha] [$tipo] $mensaje\n";
        @file_put_contents($config['log_file'], $logEntry, FILE_APPEND);
    }
}

logEmail("=== Inicio de procesamiento de cola de correos ===");

$db = (new Database())->getConnection();

// Buscar participantes pendientes de envío
$stmt = $db->query('SELECT * FROM participants WHERE correoEnviado = 0 AND correo != "" AND correo IS NOT NULL LIMIT ' . ($config['batch_size'] ?? 5));
$pendientes = $stmt->fetchAll();

if (count($pendientes) === 0) {
    logEmail("No hay correos pendientes en cola");
    echo json_encode(['message' => 'Sin correos en cola', 'procesados' => 0]);
    exit;
}

logEmail("Encontrados " . count($pendientes) . " correos pendientes");

$mail = new PHPMailer(true);
$procesados = 0;
$errores = 0;
$detalles = [];

try {
    // Configuración SMTP
    $mail->SMTPDebug = SMTP::DEBUG_OFF; // Cambiar a DEBUG_SERVER para debuggear
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
    
    // Configuración de tiempo de espera
    $mail->Timeout = 30;
    
    logEmail("Conexión SMTP configurada hacia {$smtpConfig['host']}");

    // Mantener la conexión KeepAlive abierta para mandar múltiples rápido
    $mail->SMTPKeepAlive = true;

    foreach ($pendientes as $p) {
        $intentos = 0;
        $enviado = false;
        
        while (!$enviado && $intentos < ($config['max_retries'] ?? 3)) {
            $intentos++;
            
            try {
                $mail->clearAddresses();
                $mail->addAddress($p['correo'], $p['nombre']);
                
                $mail->Subject = $config['subject'];
                
                // Generar URL del QR
                $qrData = urlencode($p['id']);
                $qrSize = $config['qr_size'] ?? 250;
                $qrUrl = "{$config['qr_api_url']}?size={$qrSize}x{$qrSize}&data={$qrData}";
                
                // Construir cuerpo del correo
                $body = buildEmailBody($p, $qrUrl, $config);
                $mail->Body = $body;
                
                $mail->send();
                
                // Marcar como completado en DB
                $upd = $db->prepare('UPDATE participants SET correoEnviado = 1 WHERE id = ?');
                $upd->execute([$p['id']]);
                
                $procesados++;
                $enviado = true;
                $detalles[] = [
                    'correo' => $p['correo'],
                    'nombre' => $p['nombre'],
                    'status' => 'enviado',
                    'intentos' => $intentos
                ];
                logEmail("Correo enviado a {$p['correo']} (intento $intentos)", 'SUCCESS');
                
            } catch (Exception $e) {
                logEmail("Intento $intentos fallido para {$p['correo']}: " . $mail->ErrorInfo, 'ERROR');
                
                if ($intentos < ($config['max_retries'] ?? 3)) {
                    // Esperar un poco antes de reintentar
                    sleep(2);
                }
            }
        }
        
        if (!$enviado) {
            $errores++;
            $detalles[] = [
                'correo' => $p['correo'],
                'nombre' => $p['nombre'],
                'status' => 'fallido',
                'intentos' => $intentos,
                'error' => $mail->ErrorInfo
            ];
            logEmail("Fallo definitivo para {$p['correo']} después de $intentos intentos", 'ERROR');
        }
        
        // Reset SMTP para siguiente correo
        $mail->getSMTPInstance()->reset();
    }

    $mail->smtpClose();

} catch (Exception $e) {
    logEmail("Error crítico: " . $e->getMessage(), 'CRITICAL');
    echo json_encode([
        'error' => 'Fallo al iniciar el servidor SMTP', 
        'details' => $e->getMessage(),
        'hint' => 'Verificar credenciales SMTP en config/email_config.php'
    ]);
    exit;
}

logEmail("=== Fin de procesamiento: $procesados enviados, $errores fallidos ===");

echo json_encode([
    'message' => 'Cola procesada', 
    'exitos' => $procesados, 
    'fallos' => $errores,
    'detalles' => $detalles
]);

/**
 * Construye el cuerpo del correo HTML
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
