<?php
/**
 * Endpoint para solicitar el restablecimiento de contraseña
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email requerido']);
    exit;
}

$db = (new Database())->getConnection();

// Verificar si el correo pertenece a un usuario Staff (solo staff tiene password)
$stmt = $db->prepare('SELECT id, email FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Por seguridad, siempre devolvemos un 200 aunque el correo no exista, 
// para prevenir ataques de "enumeración de usuarios".
if (!$user) {
    echo json_encode(['message' => 'Si el correo existe, se han enviado las instrucciones.']);
    exit;
}

// 1. Generar token criptográfico único
$token = bin2hex(random_bytes(32));

// 2. Guardar token en BD con expiración de 1 HORA
$stmt = $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
$stmt->execute([$user['email'], $token]);

// 3. Enviar correo a través de PHPMailer
$config = require __DIR__ . '/../config/email_config.php';
$smtpConfig = $config[$config['provider']] ?? $config['gmail'];

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $smtpConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpConfig['username'];
    $mail->Password = $smtpConfig['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpConfig['port'];
    $mail->setFrom($smtpConfig['from_email'], 'Soporte Francofonia');

    $mail->addAddress($user['email']);
    $mail->Subject = 'Instrucciones para restablecer tu contrasena';
    $mail->isHTML(true);

    // URL dinámica basada en configuración o en el origen de la petición
    $frontendBase = $config['frontend_url'] ?? $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:8100';
    $frontendUrl = rtrim($frontendBase, '/') . "/#/reset-password?token=" . $token;

    $mail->Body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { margin: 0; padding: 0; background-color: #050A14; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #FFFFFF; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #050A14; padding: 40px 0; }
        .card { width: 600px; background-color: #0D1526; margin: 0 auto; border-radius: 15px; border: 1px solid #1E2D45; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .header { background: linear-gradient(135deg, #0A1A2F 0%, #162A47 100%); padding: 40px; text-align: center; border-bottom: 2px solid #FBC02D; }
        .content { padding: 40px; line-height: 1.8; text-align: center; color: #FFFFFF; }
        .footer { padding: 20px; text-align: center; font-size: 11px; color: #8A9BB3; background-color: #090F1B; }
        h1 { color: #FBC02D; margin: 0; font-size: 24px; letter-spacing: 1px; }
        h2 { color: #FFFFFF; font-weight: 300; margin-bottom: 25px; }
        p { margin-bottom: 20px; font-size: 16px; color: #CED4DA; }
        .btn { display: inline-block; padding: 15px 35px; background-color: #FBC02D; color: #050A14; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(251, 192, 45, 0.3); }
    </style>
</head>
<body>
    <div class='wrapper'>
        <div class='card'>
            <div class='header'>
                <h1>🥐 FRANCOFONIA 2026</h1>
                <div style='color: #8A9BB3; font-size: 13px; margin-top: 5px; letter-spacing: 2px;'>SEGURIDAD Y ACCESO</div>
            </div>
            <div class='content'>
                <h2>Recuperar tu acceso</h2>
                <p>Has solicitado restablecer tu contraseña para la aplicación de Francofonia.</p>
                <p>Haz clic en el botón de abajo para configurar una nueva clave. Por tu seguridad, este enlace expirará en 1 hora.</p>
                <div style='margin: 35px 0;'>
                    <a href='{$frontendUrl}' class='btn'>Establecer Nueva Contraseña</a>
                </div>
                <p style='color: #8A9BB3; font-size: 13px;'>Si no solicitaste este cambio, simplemente ignora este correo electrónico.</p>
            </div>
            <div class='footer'>
                © 2026 Francofonía Eventos. Protección de Identidad Digital.
            </div>
        </div>
    </div>
</body>
</html>
";

    $mail->send();
    echo json_encode(['message' => 'Si el correo existe, se han enviado las instrucciones.']);
}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo enviar el correo de recuperación.']);
}
