<?php
/**
 * Script de configuración inicial
 * Ejecutar UNA VEZ para crear la DB y los usuarios con passwords hasheados
 * 
 * Abre en navegador: http://localhost/FRANCOFONIA/FrancofoniApp1/api/setup.php
 */

echo "<h1>🇫🇷 FrancofoníaApp - Setup Local</h1>";
echo "<pre>";

// Step 1: Connect to MySQL (without database)
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Conexión a MySQL exitosa\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Asegúrate de que XAMPP MySQL esté corriendo.\n";
    exit;
}

// Step 2: Create database
$pdo->exec('CREATE DATABASE IF NOT EXISTS francofonia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "✅ Base de datos 'francofonia_db' creada/verificada\n";

$pdo->exec('USE francofonia_db');

// Step 3: Create tables
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(50) PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'supervisor', 'usuario') NOT NULL DEFAULT 'usuario',
        standId VARCHAR(50) DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
echo "✅ Tabla 'users' creada\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS participants (
        id VARCHAR(50) PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        apellido_paterno VARCHAR(100) NOT NULL,
        apellido_materno VARCHAR(100) NOT NULL,
        ciudad VARCHAR(100) NOT NULL,
        municipio VARCHAR(100) NOT NULL,
        sexo VARCHAR(20) NOT NULL,
        correo VARCHAR(255) NOT NULL,
        qrCode TEXT DEFAULT NULL,
        correoEnviado TINYINT(1) DEFAULT 0,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
echo "✅ Tabla 'participants' creada\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS stands (
        id VARCHAR(50) PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT,
        usuarioId VARCHAR(50) DEFAULT '',
        activo TINYINT(1) DEFAULT 1,
        responsable VARCHAR(150) DEFAULT NULL
    )
");
echo "✅ Tabla 'stands' creada\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS visits (
        id VARCHAR(50) PRIMARY KEY,
        participantId VARCHAR(50) NOT NULL,
        standId VARCHAR(50) NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_participant (participantId),
        INDEX idx_stand (standId)
    )
");
echo "✅ Tabla 'visits' creada\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS surveys (
        id VARCHAR(50) PRIMARY KEY,
        participantId VARCHAR(50) NOT NULL,
        standId VARCHAR(50) NOT NULL,
        p1 VARCHAR(10) NOT NULL,
        p2 VARCHAR(10) NOT NULL,
        p3 VARCHAR(10) NOT NULL,
        p4 VARCHAR(10) NOT NULL,
        p5 VARCHAR(10) NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_survey_stand (standId)
    )
");
echo "✅ Tabla 'surveys' creada\n";

// Step 4: Create default users with proper bcrypt hashes
$adminHash = password_hash('admin123', PASSWORD_BCRYPT);
$superHash = password_hash('super123', PASSWORD_BCRYPT);
$userHash = password_hash('user123', PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (id, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");

$stmt->execute(['admin-local-001', 'admin@francofonia.com', $adminHash, 'admin']);
echo "✅ Admin creado: admin@francofonia.com / admin123\n";

$stmt->execute(['supervisor-local-001', 'supervisor@francofonia.com', $superHash, 'supervisor']);
echo "✅ Supervisor creado: supervisor@francofonia.com / super123\n";

$stmt->execute(['usuario-local-001', 'usuario@francofonia.com', $userHash, 'usuario']);
echo "✅ Usuario creado: usuario@francofonia.com / user123\n";

// Step 5: Create default stands
$stmtStand = $pdo->prepare("INSERT INTO stands (id, nombre, responsable, descripcion, activo, usuarioId) VALUES (?, ?, ?, ?, 1, '') ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");

$stands = [
    ['stand-001', 'Crepê', 'Adriana García', 'Deliciosas crepas francesas'],
    ['stand-002', 'Quiche Lorraine', 'Mildred Zoé', 'Clásico quiche de Lorena'],
    ['stand-003', 'Croquembouche', 'José Emilio', 'Torre de profitroles con caramelo'],
    ['stand-004', 'Crème Brûlée', 'Selina Maldonado', 'Postre de crema con azúcar quemada'],
    ['stand-005', 'Croissant', 'Ivan Atzin', 'Pan de hojaldre mantecoso'],
];

foreach ($stands as $s) {
    $stmtStand->execute($s);
}
echo "✅ 5 stands por defecto creados\n";

echo "\n=============================================\n";
echo "🎉 ¡Setup completado exitosamente!\n";
echo "=============================================\n";
echo "\n📋 Credenciales:\n";
echo "   Admin:      admin@francofonia.com / admin123\n";
echo "   Supervisor: supervisor@francofonia.com / super123\n";
echo "   Usuario:    usuario@francofonia.com / user123\n";
echo "\n🔗 API: http://localhost/FRANCOFONIA/FrancofoniApp1/api/\n";
echo "   Health: http://localhost/FRANCOFONIA/FrancofoniApp1/api/health\n";
echo "</pre>";
