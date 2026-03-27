<?php
/**
 * Endpoint para procesar el restablecimiento de contraseña (el usuario guardando su nueva password)
 */
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$new_password = $data['new_password'] ?? '';

if (empty($token) || empty($new_password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Token y nueva contraseña requeridos']);
    exit;
}

if (strlen($new_password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}

$db = (new Database())->getConnection();

// 1. Verificar si el token existe y sigue vigente
$stmt = $db->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()');
$stmt->execute([$token]);
$resetRecord = $stmt->fetch();

if (!$resetRecord) {
    http_response_code(400);
    echo json_encode(['error' => 'El enlace es inválido o ha expirado. Por favor, solicita uno nuevo.']);
    exit;
}

// 2. Aplicar BCRYPT a la nueva contraseña
$hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);

// 3. Actualizar la nueva contraseña en el usuario
$stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
$stmt->execute([$hashedPassword, $resetRecord['email']]);

// 4. Invalidad el token para que no se re-utilice
$stmt = $db->prepare('DELETE FROM password_resets WHERE token = ?');
$stmt->execute([$token]);

// También podríamos elegir eliminar todos los tokens existentes para ese correo 
// DELETE FROM password_resets WHERE email = ?

echo json_encode(['message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.']);
