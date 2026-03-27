<?php
/**
 * Logger de Seguridad
 * Registra eventos de seguridad
 */

class SecurityLogger {
    private static $logFile = __DIR__ . '/../logs/security.log';
    
    /**
     * Registrar evento de seguridad
     */
    public static function log($type, $message, $data = []) {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'message' => $message,
            'data' => $data
        ];
        
        $logLine = json_encode($entry) . "\n";
        @file_put_contents(self::$logFile, $logLine, FILE_APPEND);
    }
    
    /**
     * Log de intento de login fallido
     */
    public static function loginFailed($email, $reason = '') {
        self::log('LOGIN_FAILED', "Intento de login fallido: $reason", ['email' => $email]);
    }
    
    /**
     * Log de login exitoso
     */
    public static function loginSuccess($userId, $role) {
        self::log('LOGIN_SUCCESS', 'Login exitoso', ['user_id' => $userId, 'role' => $role]);
    }
    
    /**
     * Log de acceso bloqueado por rate limiting
     */
    public static function rateLimited($endpoint) {
        self::log('RATE_LIMITED', 'Rate limit excedido', ['endpoint' => $endpoint]);
    }
    
    /**
     * Log de request sospechosa
     */
    public static function suspicious($reason, $details = []) {
        self::log('SUSPICIOUS', "Request sospechosa: $reason", $details);
    }
    
    /**
     * Log de acción de admin
     */
    public static function adminAction($action, $userId, $details = []) {
        self::log('ADMIN_ACTION', $action, array_merge(['user_id' => $userId], $details));
    }
    
    private static function getClientIp(): string {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return 'unknown';
    }
}
