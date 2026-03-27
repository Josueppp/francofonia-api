<?php
// Endpoint que devuelve la IP de red local del servidor (para mostrar al usuario la ruta del celular)

$ip = 'Localhost';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $output = [];
    exec('ipconfig', $output);
    foreach ($output as $line) {
        if (preg_match('/IPv4.*?:\s*([\d\.]+)/i', $line, $matches) || preg_match('/Direcci.*?IPv4.*?:\s*([\d\.]+)/i', $line, $matches)) {
            // Priorizamos la primera IP de red local clásica (192.168.x.x o 10.x.x.x)
            if(strpos($matches[1], '192.168.') === 0 || strpos($matches[1], '10.') === 0) {
                $ip = $matches[1];
                break;
            }
        }
    }
} else {
    // Si fuera Linux/Mac
    $ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
}

echo json_encode([
    'ip' => $ip,
    'full_url' => 'http://' . $ip . ':8100'
]);
