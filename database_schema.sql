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
