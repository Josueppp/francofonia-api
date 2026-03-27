-- phpMyAdmin SQL Dump
-- Estructura de la base de datos para FrancofoniaApp

CREATE DATABASE IF NOT EXISTS `francofonia`;
USE `francofonia`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users` (Staff / Admin)
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','supervisor','usuario') NOT NULL DEFAULT 'usuario',
  `standId` varchar(100) NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `users`
-- Contraseña por defecto para admin es admin123 y supervisor es super123 (hasheadas previamente)
--

INSERT IGNORE INTO `users` (`id`, `email`, `password`, `role`, `standId`, `createdAt`) VALUES
('admin-1', 'admin@francofonia.com', '$2y$10$XmB0v9zGv5Z6lP9uYJ2a9.R1C7XkI1n2W8A7fR6Jk8zO0N3qE2.x.', 'admin', NULL, NOW()),
('supervisor-1', 'super@francofonia.com', '$2y$10$XmB0v9zGv5Z6lP9uYJ2a9.R1C7XkI1n2W8A7fR6Jk8zO0N3qE2.x.', 'supervisor', NULL, NOW());

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stands`
--

CREATE TABLE IF NOT EXISTS `stands` (
  `id` varchar(100) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `posicion` varchar(50) DEFAULT NULL,
  `estado` enum('disponible','ocupado','inactivo') NOT NULL DEFAULT 'disponible',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participants` (Invitados)
--

CREATE TABLE IF NOT EXISTS `participants` (
  `id` varchar(100) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `apellido_paterno` varchar(100) DEFAULT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `sexo` varchar(50) DEFAULT NULL,
  `correo` varchar(200) NOT NULL,
  `qrCode` text DEFAULT NULL,
  `tipoBoleto` varchar(50) DEFAULT 'Normal',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `correoEnviado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo_UNIQUE` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visits` (Escaneos)
--

CREATE TABLE IF NOT EXISTS `visits` (
  `id` varchar(100) NOT NULL,
  `participantId` varchar(100) NOT NULL,
  `standId` varchar(100) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_visit_participant` (`participantId`),
  KEY `fk_visit_stand` (`standId`),
  CONSTRAINT `fk_visit_participant` FOREIGN KEY (`participantId`) REFERENCES `participants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_visit_stand` FOREIGN KEY (`standId`) REFERENCES `stands` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `surveys` (Encuestas)
--

CREATE TABLE IF NOT EXISTS `surveys` (
  `id` varchar(100) NOT NULL,
  `participantId` varchar(100) NOT NULL,
  `standId` varchar(100) NOT NULL,
  `p1` varchar(10) NOT NULL COMMENT 'Calificacion 1-5',
  `p2` varchar(10) NOT NULL COMMENT 'Recomendacion',
  `p3` varchar(10) DEFAULT NULL COMMENT 'Aprendizaje',
  `p4` varchar(10) DEFAULT NULL COMMENT 'Atencion',
  `p5` text DEFAULT NULL COMMENT 'Comentarios',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_survey_participant` (`participantId`),
  KEY `fk_survey_stand` (`standId`),
  CONSTRAINT `fk_survey_stand` FOREIGN KEY (`standId`) REFERENCES `stands` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Limpieza forzada por orden de FK
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `visits`;
TRUNCATE TABLE `surveys`;
TRUNCATE TABLE `participants`;
TRUNCATE TABLE `stands`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Usuarios con credenciales hasheadas
INSERT INTO `users` (`id`, `email`, `password`, `role`, `standId`, `createdAt`) VALUES
('admin-1',          'admin@francofonia.com', '$2y$10$a0BtwcOmCNLQXB59SFwg2.oDg2ttVbHKu11ftdMVzYHVbq7GHNCVi', 'admin',      NULL,     NOW()),
('supervisor-stand1','stand1@francofonia.com','$2y$10$W.kqX4v8D7yUlfDiTD71J.yMMfMKreYGqyU81skMnydBRT7G.k4s2', 'supervisor', 'stand-1', NOW()),
('supervisor-stand2','stand2@francofonia.com','$2y$10$W.kqX4v8D7yUlfDiTD71J.yMMfMKreYGqyU81skMnydBRT7G.k4s2', 'supervisor', 'stand-2', NOW()),
('supervisor-stand3','stand3@francofonia.com','$2y$10$W.kqX4v8D7yUlfDiTD71J.yMMfMKreYGqyU81skMnydBRT7G.k4s2', 'supervisor', 'stand-3', NOW()),
('supervisor-stand4','stand4@francofonia.com','$2y$10$W.kqX4v8D7yUlfDiTD71J.yMMfMKreYGqyU81skMnydBRT7G.k4s2', 'supervisor', 'stand-4', NOW()),
('supervisor-stand5','stand5@francofonia.com','$2y$10$W.kqX4v8D7yUlfDiTD71J.yMMfMKreYGqyU81skMnydBRT7G.k4s2', 'supervisor', 'stand-5', NOW());

-- Stands (5 mesas gastronómicas reales)
INSERT INTO `stands` (`id`, `nombre`, `descripcion`, `usuarioId`, `activo`, `responsable`) VALUES
('stand-1', 'Crepê', 'Crepa', 'supervisor-stand1', 1, 'Adriana García Malpica'),
('stand-2', 'La Madeleine à la Veilleuse', 'Magdalena', 'supervisor-stand2', 1, 'Alexa Sinaí Santiago Villanueva'),
('stand-3', 'Quiche Lorraine', 'Pastel', 'supervisor-stand3', 1, 'Mildred Zoé Gómez Bautista'),
('stand-4', 'Croquenbouche', 'Profiterol', 'supervisor-stand4', 1, 'José Emilio Hernández Romero'),
('stand-5', 'Crème Brûlée', 'Crema flameada', 'supervisor-stand5', 1, 'Selina Maldonado López');

-- Participantes de prueba
INSERT INTO `participants` (`id`, `nombre`, `apellido_paterno`, `apellido_materno`, `ciudad`, `municipio`, `sexo`, `correo`, `correoEnviado`, `createdAt`) VALUES
('demo-p1','Camille','Dubois','Moreau','Ciudad de México','Cuauhtémoc','Femenino','camille.dubois@demo.com',1,NOW()),
('demo-p2','Jean-Pierre','Martin','Laurent','Guadalajara','Zapopan','Masculino','jpierre.martin@demo.com',1,NOW()),
('demo-p3','Sophie','Bernard','Girard','Monterrey','San Pedro','Femenino','sophie.bernard@demo.com',0,NOW()),
('demo-p4','François','Lefevre','Dupont','Ciudad de México','Benito Juárez','Masculino','francois.lefevre@demo.com',1,NOW()),
('demo-p5','Marie','Moreau','Blanc','Puebla','Puebla','Femenino','marie.moreau@demo.com',1,NOW()),
('demo-p6','Pierre','Rousseau','Faure','Ciudad de México','Miguel Hidalgo','Masculino','pierre.rousseau@demo.com',0,NOW()),
('demo-p7','Claire','Simon','Petit','Querétaro','Corregidora','Femenino','claire.simon@demo.com',1,NOW()),
('demo-p8','Antoine','Michel','Garnier','Mérida','Umán','Masculino','antoine.michel@demo.com',1,NOW()),
('demo-p9','Isabelle','Laurent','Richard','Toluca','Metepec','Femenino','isabelle.laurent@demo.com',0,NOW()),
('demo-p10','Remy','Durand','Picard','Ciudad de México','Coyoacán','Masculino','remy.durand@demo.com',1,NOW());

-- Visitas de prueba (escaneos)
INSERT INTO `visits` (`id`, `participantId`, `standId`, `fecha`) VALUES
('demo-v1',  'demo-p1', 'stand-1', DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
('demo-v2',  'demo-p1', 'stand-3', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('demo-v3',  'demo-p2', 'stand-2', DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
('demo-v4',  'demo-p3', 'stand-1', DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
('demo-v5',  'demo-p4', 'stand-4', DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
('demo-v6',  'demo-p4', 'stand-5', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
('demo-v7',  'demo-p5', 'stand-2', DATE_SUB(NOW(), INTERVAL 50 MINUTE)),
('demo-v8',  'demo-p6', 'stand-1', DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
('demo-v9',  'demo-p7', 'stand-3', DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
('demo-v10', 'demo-p8', 'stand-5', DATE_SUB(NOW(), INTERVAL 55 MINUTE)),
('demo-v11', 'demo-p9', 'stand-4', DATE_SUB(NOW(), INTERVAL 5  MINUTE)),
('demo-v12', 'demo-p10','stand-2', DATE_SUB(NOW(), INTERVAL 8  MINUTE));

-- Encuestas de prueba
INSERT INTO `surveys` (`id`, `participantId`, `standId`, `p1`, `p2`, `p3`, `p4`, `p5`, `fecha`) VALUES
('demo-s1','demo-p1','stand-1','5','5','4','5','Excelentes crêpes, muy auténtico!',DATE_SUB(NOW(), INTERVAL 28 MINUTE)),
('demo-s2','demo-p2','stand-2','4','4','5','4','El queso Brie estaba perfecto.',DATE_SUB(NOW(), INTERVAL 38 MINUTE)),
('demo-s3','demo-p4','stand-4','5','5','5','5','El vino de Burdeos fue una revelación.',DATE_SUB(NOW(), INTERVAL 22 MINUTE)),
('demo-s4','demo-p5','stand-2','3','4','3','4','Buen queso pero muy salado.',DATE_SUB(NOW(), INTERVAL 47 MINUTE)),
('demo-s5','demo-p7','stand-3','5','5','5','5','El croissant de mantequilla es el mejor que he probado.',DATE_SUB(NOW(), INTERVAL 8 MINUTE));
