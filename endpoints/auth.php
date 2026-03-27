<?php
/**
 * Endpoint: Auth - Login/Logout para staff y guests
 * Versión segura con validación y logging
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/validator.php';
require_once __DIR__ . '/../config/security_logger.php';

function handleAuth($method, $action) {
    $db = (new Database())->getConnection();

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'login':
                staffLogin($db, $data);
                break;
            case 'guest-login':
                guestLogin($db, $data);
                break;
            case 'logout':
                staffLogout($db);
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Acción no válida']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
    }
}

function staffLogin($db, $data) {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    // Validar inputs
    $emailError = InputValidator::validateEmail($email);
    if ($emailError) {
        http_response_code(400);
        echo json_encode(['error' => $emailError]);
        return;
    }
    
    $passError = InputValidator::validatePassword($password);
    if ($passError) {
        http_response_code(400);
        echo json_encode(['error' => $passError]);
        return;
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user) {
        SecurityLogger::loginFailed($email, 'Usuario no encontrado');
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales inválidas']);
        return;
    }

    // Validar Contraseña con Bcrypt
    // password_verify compara el texto plano ingresado con el string Hasheado
    // guardado en la base de datos (que incluye el salt) de forma segura.
    if (!password_verify($password, $user['password'])) {
        SecurityLogger::loginFailed($email, 'Password incorrecto');
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales inválidas']);
        return;
    }

    // Login exitoso
    // -------------------------------------------------------------------------------------
    // SISTEMA DE SESIONES POR TOKEN
    // Al autenticarse correctamente, se genera un token criptográficamente seguro.
    // Este token se guarda en la tabla `sessions` vinculándolo al usuario.
    // -------------------------------------------------------------------------------------
    $token = bin2hex(random_bytes(32)); // 64 caracteres hex
    
    // Insertar token en BD con vigencia de 8 horas
    $sessionStmt = $db->prepare('INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 8 HOUR))');
    $sessionStmt->execute([$user['id'], $token]);

    SecurityLogger::loginSuccess($user['id'], $user['role']);
    
    // Return user data (sin el password para que no viaje a la UI) y el nuevo token
    unset($user['password']);
    echo json_encode(['token' => $token, 'user' => $user]);
}

/**
 * Función de Logout
 * Borra el token enviado del registro de sesiones
 */
function staffLogout($db) {
    // Leer el token del Authorization Header
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $stmt = $db->prepare('DELETE FROM sessions WHERE token = ?');
        $stmt->execute([$token]);
    }
    
    echo json_encode(['message' => 'Sesión cerrada']);
}

function guestLogin($db, $data) {
    $correo = strtolower(trim($data['correo'] ?? ''));

    // Validar email
    $emailError = InputValidator::validateEmail($correo);
    if ($emailError) {
        http_response_code(400);
        echo json_encode(['error' => $emailError]);
        return;
    }

    $stmt = $db->prepare('SELECT * FROM participants WHERE correo = ?');
    $stmt->execute([$correo]);
    $participant = $stmt->fetch();

    if (!$participant) {
        SecurityLogger::loginFailed($correo, 'Participante no encontrado');
        http_response_code(404);
        echo json_encode(['found' => false, 'error' => 'Participante no encontrado']);
        return;
    }

    SecurityLogger::loginSuccess($participant['id'], 'guest');
    
    // No exponer datos sensibles
    $safeParticipant = [
        'id' => $participant['id'],
        'nombre' => $participant['nombre'],
        'apellido_paterno' => $participant['apellido_paterno'],
        'correo' => $participant['correo'],
        'correoEnviado' => (bool) $participant['correoEnviado']
    ];
    
    echo json_encode(['found' => true, 'participant' => $safeParticipant]);
}
