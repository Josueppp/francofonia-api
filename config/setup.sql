-- ============================================
-- FrancofoníaApp - Base de Datos Local
-- Ejecutar en phpMyAdmin o MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS francofonia_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE francofonia_db;

-- ============================================
-- Tabla: users (staff del sistema)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(50) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'usuario') NOT NULL DEFAULT 'usuario',
    standId VARCHAR(50) DEFAULT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Tabla: participants (asistentes al evento)
-- ============================================
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
);

-- ============================================
-- Tabla: stands
-- ============================================
CREATE TABLE IF NOT EXISTS stands (
    id VARCHAR(50) PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    usuarioId VARCHAR(50) DEFAULT '',
    activo TINYINT(1) DEFAULT 1,
    responsable VARCHAR(150) DEFAULT NULL
);

-- ============================================
-- Tabla: visits
-- ============================================
CREATE TABLE IF NOT EXISTS visits (
    id VARCHAR(50) PRIMARY KEY,
    participantId VARCHAR(50) NOT NULL,
    standId VARCHAR(50) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_participant (participantId),
    INDEX idx_stand (standId)
);

-- ============================================
-- Tabla: surveys (encuestas de satisfacción)
-- ============================================
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
);

-- ============================================
-- Datos iniciales
-- ============================================

-- Admin por defecto (password: admin123)
INSERT INTO users (id, email, password, role) VALUES
    ('admin-local-001', 'admin@francofonia.com', '$2y$10$YourHashHere', 'admin')
ON DUPLICATE KEY UPDATE email = email;

-- Supervisor por defecto (password: super123)
INSERT INTO users (id, email, password, role) VALUES
    ('supervisor-local-001', 'supervisor@francofonia.com', '$2y$10$YourHashHere', 'supervisor')
ON DUPLICATE KEY UPDATE email = email;

-- Stands por defecto
INSERT INTO stands (id, nombre, responsable, descripcion, activo, usuarioId) VALUES
    ('stand-001', 'Crepê', 'Adriana García', 'Deliciosas crepas francesas', 1, ''),
    ('stand-002', 'Quiche Lorraine', 'Mildred Zoé', 'Clásico quiche de Lorena', 1, ''),
    ('stand-003', 'Croquembouche', 'José Emilio', 'Torre de profitroles con caramelo', 1, ''),
    ('stand-004', 'Crème Brûlée', 'Selina Maldonado', 'Postre de crema con azúcar quemada', 1, ''),
    ('stand-005', 'Croissant', 'Ivan Atzin', 'Pan de hojaldre mantecoso', 1, '')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
