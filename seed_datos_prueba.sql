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
