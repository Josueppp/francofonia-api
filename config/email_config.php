<?php
/**
 * Configuración del Sistema de Correos
 * Este archivo debe configurarse según el proveedor SMTP utilizado
 * 
 * NO COMMITEAR este archivo si contiene credenciales reales
 */

// ==================== CONFIGURACIÓN SMTP ====================

return [
    // Proveedor: 'gmail', 'outlook', 'smtp', 'mailgun', 'sendgrid'
    'provider' => 'gmail',
    
    // URL Base para enlaces del Frontend (Reset Password, etc.)
    'frontend_url' => 'http://localhost:8100',
    
    // Configuración Gmail
    'gmail' => [
        'host' => 'smtp.gmail.com',
        'username' => 'francofoniaevento2026@gmail.com',
        'password' => 'zofw mbsk sqll cdil', // ⚠️ Password de APLICACIÓN, no la contraseña normal
        'port' => 587,
        'encryption' => 'tls', // tls o ssl
        'from_email' => 'francofoniaevento2026@gmail.com',
        'from_name' => 'Francofonía 2026'
    ],
    
    // Configuración alternativa (Outlook/Hotmail)
    'outlook' => [
        'host' => 'smtp-mail.outlook.com',
        'username' => 'tu@correo.com',
        'password' => 'tu_password',
        'port' => 587,
        'encryption' => 'tls',
        'from_email' => 'tu@correo.com',
        'from_name' => 'Francofonía 2026'
    ],
    
    // Configuración SMTP genérico
    'smtp' => [
        'host' => 'smtp.tuserver.com',
        'username' => 'tu@correo.com',
        'password' => 'tu_password',
        'port' => 587,
        'encryption' => 'tls',
        'from_email' => 'tu@correo.com',
        'from_name' => 'Francofonía 2026'
    ],
    
    // Configuración Mailgun (ejemplo)
    'mailgun' => [
        'domain' => 'tu-dominio.mailgun.org',
        'api_key' => 'tu-api-key',
        'from_email' => 'noreply@tu-dominio.com',
        'from_name' => 'Francofonía 2026'
    ],
    
    // ==================== CONFIGURACIÓN DE COLA ====================
    
    // Cuántos correos procesar por ejecución
    'batch_size' => 5,
    
    // Intervalo entre ejecuciones (en segundos) - para el scheduler
    'interval_seconds' => 30,
    
    // Intentos máximos si falla el envío
    'max_retries' => 3,
    
    // ==================== CONFIGURACIÓN DE CORREOS ====================
    
    // Asunto del correo
    'subject' => 'Bienvenido a Francofonía 2026 🥐 - Tu Acceso QR',
    
    // URL del servicio de QR (puede cambiarse por uno local)
    'qr_api_url' => 'https://api.qrserver.com/v1/create-qr-code/',
    
    // Tamaño del QR
    'qr_size' => 250,
    
    // Habilitar logs
    'enable_logs' => true,
    
    // Ruta del archivo de log
    'log_file' => __DIR__ . '/logs/email.log'
];
