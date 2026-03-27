<?php
/**
 * Endpoint: Participants - CRUD completo con Cola de Correos Asíncrona
 * Versión segura con validación
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/validator.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';

// Proteger el endpoint - Detiene la ejecución si no hay token válido
verifyRequest();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function handleParticipants($method, $id) {
    $db = (new Database())->getConnection();

    switch ($method) {
        case 'GET':
            if ($id) {
                getParticipantById($db, $id);
            } else {
                // Check for query params
                $correo = $_GET['correo'] ?? null;
                if ($correo) {
                    getParticipantByEmail($db, $correo);
                } else {
                    getAllParticipants($db);
                }
            }
            break;
        case 'POST':
            createParticipant($db, true);
            break;
        case 'PUT':
            if ($id) updateParticipant($db, $id);
            else { http_response_code(400); echo json_encode(['error' => 'ID requerido']); }
            break;
        case 'DELETE':
            if ($id) deleteParticipant($db, $id);
            else { http_response_code(400); echo json_encode(['error' => 'ID requerido']); }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
}

function getAllParticipants($db) {
    $stmt = $db->query('SELECT * FROM participants ORDER BY createdAt DESC');
    $participants = $stmt->fetchAll();
    foreach ($participants as &$p) {
        $p['correoEnviado'] = (bool) $p['correoEnviado'];
    }
    echo json_encode($participants);
}

function getParticipantById($db, $id) {
    $stmt = $db->prepare('SELECT * FROM participants WHERE id = ?');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
        $p['correoEnviado'] = (bool) $p['correoEnviado'];
        echo json_encode($p);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Participante no encontrado']);
    }
}

function getParticipantByEmail($db, $correo) {
    $stmt = $db->prepare('SELECT * FROM participants WHERE LOWER(correo) = ?');
    $stmt->execute([strtolower(trim($correo))]);
    $p = $stmt->fetch();
    if ($p) {
        $p['correoEnviado'] = (bool) $p['correoEnviado'];
        echo json_encode($p);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No encontrado']);
    }
}

function createParticipant($db, $autoSendEmail = false) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    $nombreError = InputValidator::validateName($data['nombre'] ?? '', 'El nombre');
    if ($nombreError) {
        http_response_code(400);
        echo json_encode(['error' => $nombreError]);
        return;
    }
    
    $emailError = InputValidator::validateEmail($data['correo'] ?? '');
    if ($emailError) {
        http_response_code(400);
        echo json_encode(['error' => $emailError]);
        return;
    }
    
    // Validar otros campos opcionales si se proporcionan
    if (!empty($data['apellido_paterno'])) {
        $apError = InputValidator::validateName($data['apellido_paterno'], 'El apellido paterno');
        if ($apError) {
            http_response_code(400);
            echo json_encode(['error' => $apError]);
            return;
        }
    }
    
    if (!empty($data['sexo'])) {
        $sexoError = InputValidator::validateSexo($data['sexo']);
        if ($sexoError) {
            http_response_code(400);
            echo json_encode(['error' => $sexoError]);
            return;
        }
    }
    
    $id = 'p-' . uniqid();

    $stmt = $db->prepare('INSERT INTO participants (id, nombre, apellido_paterno, apellido_materno, ciudad, municipio, sexo, correo, qrCode, correoEnviado, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $id,
        InputValidator::sanitizeHtml($data['nombre'] ?? ''),
        InputValidator::sanitizeHtml($data['apellido_paterno'] ?? ''),
        InputValidator::sanitizeHtml($data['apellido_materno'] ?? ''),
        InputValidator::sanitizeHtml($data['ciudad'] ?? ''),
        InputValidator::sanitizeHtml($data['municipio'] ?? ''),
        $data['sexo'] ?? '',
        strtolower(trim($data['correo'] ?? '')),
        $data['qrCode'] ?? null,
        0 // Siempre 0 al crear - se enviará después
    ]);

    $correoEnviado = false;
    $correoMessage = 'El correo se enviará en segundo plano.';
    
    // Si autoSendEmail es true, intentar enviar inmediatamente
    if ($autoSendEmail && !empty($data['correo'])) {
        // Llamar al endpoint de envío de correo directamente
        $emailResult = sendEmailNow($db, $id);
        $correoEnviado = $emailResult['success'];
        $correoMessage = $emailResult['message'];
    }

    echo json_encode([
        'id' => $id, 
        'message' => 'Participante registrado. ' . $correoMessage,
        'correoEnviado' => $correoEnviado,
        'correoStatus' => $correoEnviado ? 'Enviado' : 'En Cola'
    ]);
}

function sendEmailNow($db, $participantId) {
    // Buscar el participante
    $stmt = $db->prepare('SELECT * FROM participants WHERE id = ?');
    $stmt->execute([$participantId]);
    $participante = $stmt->fetch();
    
    if (!$participante || empty($participante['correo'])) {
        return ['success' => false, 'message' => 'Participante sin correo'];
    }
    
    // Usar la configuración de email
    $config = require __DIR__ . '/../config/email_config.php';
    $smtpConfig = $config[$config['provider']] ?? $config['gmail'];
    
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
        
        $mail->addAddress($participante['correo'], $participante['nombre']);
        $mail->Subject = $config['subject'];
        
        $qrData = urlencode($participante['id']);
        $qrSize = $config['qr_size'] ?? 250;
        $qrUrl = "{$config['qr_api_url']}?size={$qrSize}x{$qrSize}&data={$qrData}";
        
        $body = buildEmailBody($participante, $qrUrl);
        $mail->Body = $body;
        
        $mail->send();
        
        // Actualizar estado
        $upd = $db->prepare('UPDATE participants SET correoEnviado = 1 WHERE id = ?');
        $upd->execute([$participantId]);
        
        return ['success' => true, 'message' => 'Correo enviado'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo];
    }
}

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

function updateParticipant($db, $id) {
    $data = json_decode(file_get_contents('php://input'), true);

    $fields = [];
    $values = [];

    $allowed = ['nombre', 'apellido_paterno', 'apellido_materno', 'ciudad', 'municipio', 'sexo', 'correo', 'qrCode', 'correoEnviado'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $val = $data[$field];
            if ($field === 'correoEnviado') $val = $val ? 1 : 0;
            if ($field === 'correo') $val = strtolower(trim($val));
            $values[] = $val;
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        return;
    }

    $values[] = $id;
    $sql = 'UPDATE participants SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $db->prepare($sql)->execute($values);

    echo json_encode(['message' => 'Participante actualizado']);
}

function deleteParticipant($db, $id) {
    $stmt = $db->prepare('DELETE FROM participants WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['message' => 'Participante eliminado']);
}
