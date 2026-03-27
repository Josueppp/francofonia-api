<?php
/**
 * Middleware de Autenticación - Protege los Endpoints verificando el Bearer Token en BD
 */
require_once __DIR__ . '/../config/database.php';

function verifyRequest() {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado. Token requerido.']);
        exit;
    }

    $token = $matches[1];
    $db = (new Database())->getConnection();

    // Buscar el token en la tabla sessions y validar que no haya expirado
    $stmt = $db->prepare('SELECT * FROM sessions WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([$token]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado. Token inválido o expirado.']);
        exit;
    }

    // OPCIONAL: Extender la vida del token aquí, si se desea sliding window sessions.
    
    // Inyectar user_id en el entorno global para que los endpoints posteriores puedan usarlo
    $_SERVER['AUTH_USER_ID'] = $session['user_id'];
    return $session;
}
