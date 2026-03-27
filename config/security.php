<?php
/**
 * Sistema de Seguridad - API Key + CSRF
 * Protege contra ataques automatizados y CSRF
 */

class Security {
    private static $apiKey = 'francofonia_2026_secure_key'; // Cambiar en producción
    
    /**
     * Verificar API Key
     */
    public static function verifyApiKey($headers): bool {
        if (!isset($headers['X-API-Key']) && !isset($headers['x-api-key'])) {
            return false;
        }
        
        $key = $headers['X-API-Key'] ?? $headers['x-api-key'];
        return hash_equals(self::$apiKey, $key);
    }
    
    /**
     * Generar token CSRF
     */
    public static function generateCsrfToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verificar token CSRF
     */
    public static function verifyCsrfToken($token): bool {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Obtener token para el cliente
     */
    public static function getCsrfTokenForClient(): string {
        return self::generateCsrfToken();
    }
    
    /**
     * Verificar request completa
     */
    public static function verifyRequest($headers, $data = null): bool {
        // Para endpoints públicos (health, login), no requerimos API Key completa
        // Pero sí verificamos enmutodies básicos
        
        // Verificar que no sea un bot básico
        if (self::isBotRequest()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Detectar requests automatizadas/bots
     */
    private static function isBotRequest(): bool {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ua = strtolower($userAgent);
        
        // En desarrollo, solo bloquear sqlmap y herramientas de pentesting obvias
        if ($ua === 'curl' || empty($ua)) {
            return false;
        }
        
        // User agents peligrosos - solo en producción
        $botPatterns = [
            'sqlmap', 'nikto', 'nmap', 'metasploit', 'burp' // , 'zap' -> Comentado para permitir el reporte de ataque
        ];
        
        foreach ($botPatterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitizar respuesta para no exponer información sensible
     */
    public static function sanitizeResponse(&$data, $sensitiveKeys = []) {
        $defaultSensitive = [
            'password', 'password_hash', 'token', 'api_key', 'secret',
            'createdAt', 'updatedAt', 'ip', 'ip_address', 'last_login'
        ];
        
        $keys = array_merge($defaultSensitive, $sensitiveKeys);
        
        if (is_array($data)) {
            foreach ($keys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = '***';
                }
            }
        }
    }
    
    /**
     * Generar respuesta de error segura
     */
    public static function error($message, $code = 400) {
        http_response_code($code);
        
        // No exponer detalles técnicos
        $safeMessage = $message;
        if ($code >= 500) {
            $safeMessage = 'Error interno del servidor';
        }
        
        echo json_encode([
            'error' => $safeMessage,
            'code' => $code
        ]);
        exit;
    }
    
    /**
     * Verificar que la solicitud venga de origen Allowed
     */
    public static function verifyOrigin($allowedOrigins): bool {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // En desarrollo, permitir cualquier origin
        if (in_array('*', $allowedOrigins)) {
            return true;
        }
        
        return in_array($origin, $allowedOrigins);
    }
}
