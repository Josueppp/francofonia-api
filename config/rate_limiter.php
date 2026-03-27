<?php
/**
 * Rate Limiter - Previene ataques de fuerza bruta
 * Implementación simple basada en IP + acción
 */

class RateLimiter {
    private static $storage = [];
    private static $maxRequests = 30; // Máximo 30 requests
    private static $timeWindow = 60;   // Por minuto
    
    /**
     * Verificar si el request está dentro del límite
     */
    public static function check($action = 'default'): bool {
        $ip = self::getClientIp();
        $key = $ip . '_' . $action;
        $now = time();
        
        // Inicializar si no existe
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = ['requests' => [], 'blocked' => false];
        }
        
        // Limpiar requests viejos
        self::$storage[$key]['requests'] = array_filter(
            self::$storage[$key]['requests'],
            function($timestamp) use ($now) {
                return ($now - $timestamp) < self::$timeWindow;
            }
        );
        
        // Verificar si está bloqueado
        if (self::$storage[$key]['blocked']) {
            // Verificar si ya puede desbloquearse
            $firstRequest = reset(self::$storage[$key]['requests']);
            if ($firstRequest && ($now - $firstRequest) > (self::$timeWindow * 2)) {
                self::$storage[$key]['blocked'] = false;
                self::$storage[$key]['requests'] = [];
            } else {
                return false;
            }
        }
        
        // Contar requests
        $count = count(self::$storage[$key]['requests']);
        
        if ($count >= self::$maxRequests) {
            self::$storage[$key]['blocked'] = true;
            return false;
        }
        
        // Agregar request actual
        self::$storage[$key]['requests'][] = $now;
        
        return true;
    }
    
    /**
     * Obtener IP del cliente
     */
    private static function getClientIp(): string {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                   'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Enviar respuesta de rate limit excedido
     */
    public static function limitExceeded() {
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Demasiadas solicitudes. Intenta de nuevo en un minuto.',
            'code' => 429
        ]);
        exit;
    }
}
