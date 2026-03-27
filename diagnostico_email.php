<?php
/**
 * Diagnosticar Problemas de Envío de Correos
 * Ejecutar desde navegador: /api/diagnostico_email.php
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 Diagnóstico de Envío de Correos</h1>";
echo "<hr>";

// ============= 1. Verificar Composer/ PHPMailer =============
echo "<h2>1. Estado de PHPMailer</h2>";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer instalado correctamente<br>";
    echo "📦 Versión: " . (defined('PHPMAILER_VERSION') ? PHPMailER_VERSION : 'Desconocida') . "<br>";
} else {
    echo "❌ PHPMailer NO encontrado<br>";
    echo "💡 Solución: Ejecutar <code>composer install</code> en la carpeta /api<br>";
    exit;
}

// ============= 2. Verificar Base de Datos =============
echo "<h2>2. Estado de Base de Datos</h2>";
try {
    $db = (new Database())->getConnection();
    echo "✅ Conexión a MySQL exitosa<br>";
    
    // Ver tabla participants
    $stmt = $db->query("SHOW TABLES LIKE 'participants'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabla 'participants' existe<br>";
        
        // Contar pendientes
        $stmt = $db->query("SELECT COUNT(*) as total FROM participants WHERE correoEnviado = 0");
        $pendientes = $stmt->fetch();
        echo "📧 Correos pendientes: " . $pendientes['total'] . "<br>";
        
        // Mostrar algunos pendientes
        if ($pendientes['total'] > 0) {
            $stmt = $db->query("SELECT id, nombre, correo, correoEnviado FROM participants WHERE correoEnviado = 0 LIMIT 3");
            echo "<ul>";
            while ($row = $stmt->fetch()) {
                echo "<li>• {$row['nombre']} ({$row['correo']}) - Enviado: " . ($row['correoEnviado'] ? 'Sí' : 'NO') . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "❌ Tabla 'participants' NO existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

// ============= 3. Probar Conexión SMTP =============
echo "<h2>3. Prueba de Conexión SMTP (Gmail)</h2>";

// Credenciales (las mismas del código)
$smtpHost = 'smtp.gmail.com';
$smtpUser = 'francofoniaevento2026@gmail.com';
$smtpPass = 'zofw mbsk sqll cdil'; // Password de aplicación
$smtpPort = 587;

echo "🔌 Host: $smtpHost<br>";
echo "👤 Usuario: $smtpUser<br>";
echo "🔐 Puerto: $smtpPort<br>";
echo "🔒 TLS: Sí<br><br>";

$mail = new PHPMailer(true);
$mail->SMTPDebug = SMTP::DEBUG_SERVER;
$mail->isSMTP();
$mail->Host       = $smtpHost;
$mail->SMTPAuth   = true;
$mail->Username   = $smtpUser;
$mail->Password   = $smtpPass;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = $smtpPort;
$mail->setFrom($smtpUser, 'Francofonía 2026');
$mail->addAddress($smtpUser); // Enviar a sí mismo para probar

$mail->Subject = '🧪 Prueba de FrancofoniApp';
$mail->Body    = 'Si recibes este correo, el sistema de envíos funciona correctamente.';

echo "<strong>Resultado de la conexión:</strong><br>";
echo "<pre style='background:#f4f4f4; padding:10px; overflow:auto; max-height:300px;'>";

try {
    if (!$mail->SMTPConnect()) {
        echo "❌ No se pudo conectar al servidor SMTP\n";
    } else {
        echo "✅ Conexión SMTP exitosa\n";
        
        if (!$mail->send()) {
            echo "❌ Error al enviar: " . $mail->ErrorInfo . "\n";
        } else {
            echo "✅ Correo de PRUEBA enviado correctamente\n";
            echo "📬 Revisa la bandeja de entrada de: $smtpUser\n";
        }
    }
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
}

echo "</pre>";

// ============= 4. Posibles Problemas =============
echo "<h2>4. Soluciones a Problemas Comunes</h2>";
echo "<ul>";
echo "<li><strong>❌ Error de autenticación:</strong> Verificar que la contraseña sea una 'Contraseña de Aplicación' de Gmail (no la contraseña normal de la cuenta)</li>";
echo "<li><strong>❌ Gmail bloqua envíos:</strong> Activar 'Acceso menos seguro de aplicaciones' O usar Password de Aplicación</li>";
echo "<li><strong>❌ Timeout:</strong> Verificar que XAMPP/Apache tenga acceso a internet</li>";
echo "<li><strong>❌ Puerto bloqueado:</strong> El puerto 587 debe estar abierto en el firewall</li>";
echo "</ul>";

echo "<h2>5. Enlaces de Ayuda</h2>";
echo "<ul>";
echo "<li><a href='https://support.google.com/accounts/answer/185833' target='_blank'>Cómo crear Password de Aplicación Gmail</a></li>";
echo "<li><a href='https://myaccount.google.com/lesssecureapps' target='_blank'>Activar acceso menos seguro (no recomendado)</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p style='color:#666;'>Generado: " . date('Y-m-d H:i:s') . "</p>";
