-- VetSalud — esquema y datos demo realistas
-- Importar: /Applications/XAMPP/xamppfiles/bin/mysql -u root < bd/vetsalud.sql

CREATE DATABASE IF NOT EXISTS vetsalud
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE vetsalud;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS cita;
DROP TABLE IF EXISTS mascota;
DROP TABLE IF EXISTS dueno;
DROP TABLE IF EXISTS raza;
DROP TABLE IF EXISTS especie;
DROP TABLE IF EXISTS servicio;
DROP TABLE IF EXISTS administrador;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE administrador (
  id_admin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE especie (
  id_especie INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE raza (
  id_raza INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  id_especie INT UNSIGNED NOT NULL,
  CONSTRAINT fk_raza_especie FOREIGN KEY (id_especie) REFERENCES especie(id_especie)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uk_raza_especie (nombre, id_especie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dueno (
  id_dueno INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  correo VARCHAR(150) NULL,
  direccion VARCHAR(255) NULL,
  UNIQUE KEY uk_dueno_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mascota (
  id_mascota INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  id_especie INT UNSIGNED NOT NULL,
  id_raza INT UNSIGNED NULL,
  id_dueno INT UNSIGNED NOT NULL,
  edad_anios TINYINT UNSIGNED NULL,
  notas TEXT NULL,
  CONSTRAINT fk_mascota_especie FOREIGN KEY (id_especie) REFERENCES especie(id_especie),
  CONSTRAINT fk_mascota_raza FOREIGN KEY (id_raza) REFERENCES raza(id_raza),
  CONSTRAINT fk_mascota_dueno FOREIGN KEY (id_dueno) REFERENCES dueno(id_dueno)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicio (
  id_servicio INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  descripcion TEXT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  duracion_min SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  imagen VARCHAR(120) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cita (
  id_cita INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_dueno INT UNSIGNED NOT NULL,
  id_mascota INT UNSIGNED NOT NULL,
  id_servicio INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  estado ENUM('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  notas TEXT NULL,
  telefono_contacto VARCHAR(20) NOT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cita_dueno FOREIGN KEY (id_dueno) REFERENCES dueno(id_dueno),
  CONSTRAINT fk_cita_mascota FOREIGN KEY (id_mascota) REFERENCES mascota(id_mascota),
  CONSTRAINT fk_cita_servicio FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio),
  KEY idx_cita_fecha (fecha),
  KEY idx_cita_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin: admin@demo.com / Admin123!
INSERT INTO administrador (nombre, correo, password_hash) VALUES
('Admin VetSalud', 'admin@demo.com', '$2y$10$xd4RXbPqhDTCVR4DOYzzS.ZF9gW40vMztCDPsalAtDAZ.7orwUeCW');

INSERT INTO especie (nombre) VALUES
('Perro'), ('Gato'), ('Conejo'), ('Hurón');

INSERT INTO raza (nombre, id_especie) VALUES
-- Perros
('Labrador Retriever', 1),
('Poodle / Caniche', 1),
('Golden Retriever', 1),
('Schnauzer Miniatura', 1),
('Bulldog Francés', 1),
('Yorkshire Terrier', 1),
('Pastor Alemán', 1),
('Beagle', 1),
('Shih Tzu', 1),
('Mestizo', 1),
-- Gatos
('Persa', 2),
('Siamés', 2),
('Maine Coon', 2),
('Británico de Pelo Corto', 2),
('Bengalí', 2),
('Mestizo', 2),
-- Conejos
('Holland Lop', 3),
('Mini Rex', 3),
('Angora', 3),
-- Hurones
('Estándar', 4);

INSERT INTO servicio (nombre, descripcion, precio, duracion_min, imagen, activo) VALUES
('Baño básico', 'Baño con shampoo hipoalergénico, secado profesional y cepillado. Ideal para mantenimiento quincenal.', 45000.00, 60, 'servicio-bano.jpg', 1),
('Baño + corte', 'Baño completo y corte de pelo según raza o estilo que indiques. Incluye cepillado final.', 75000.00, 90, 'servicio-corte.jpg', 1),
('Peluquería premium', 'Baño, corte de estilo, limpieza de oídos, corte de uñas y perfume pet-safe.', 95000.00, 120, 'servicio-premium.jpg', 1),
('Corte de uñas', 'Recorte y limado cuidadoso de uñas. Sesión rápida sin estrés.', 20000.00, 20, 'servicio-unas.jpg', 1),
('Limpieza dental básica', 'Limpieza superficial de dientes y encías con pasta enzimática para mascotas.', 35000.00, 30, 'servicio-dental.jpg', 1),
('Spa relajante', 'Baño aromático, masaje suave, acondicionador y acabado brillante.', 110000.00, 100, 'servicio-spa.jpg', 1),
('Deslanado profundo', 'Cepillado intensivo para eliminar pelo muerto y reducir muda en casa.', 65000.00, 75, 'servicio-deslanado.jpg', 1),
('Grooming para gatos', 'Baño o cepillado especializado para felinos, con manejo calmado y ambiente quieto.', 70000.00, 70, 'servicio-gato.jpg', 1),
('Consulta estética', 'Evaluación del pelaje y recomendación de corte/baño según raza, edad y clima.', 25000.00, 25, 'servicio-consulta.jpg', 1);

INSERT INTO dueno (nombre, telefono, correo, direccion) VALUES
('María López', '3001112233', 'maria.lopez@email.com', 'Calle 80 #12-40, Bogotá'),
('Andrés Gómez', '3104445566', 'andres.gomez@email.com', 'Carrera 15 #100-20, Bogotá'),
('Paula Ríos', '3207778899', 'paula.rios@email.com', 'Av. Suba #120-50, Bogotá'),
('Camila Torres', '3015556677', 'camila.t@email.com', 'Calle 100 #19-45'),
('Diego Vargas', '3158889900', 'diego.vargas@email.com', 'Carrera 7 #72-10'),
('Laura Méndez', '3002223344', 'laura.mendez@email.com', 'Calle 53 #10-30'),
('Santiago Ruiz', '3116667788', 'santiago.ruiz@email.com', 'Av. Boyacá #68-20');

INSERT INTO mascota (nombre, id_especie, id_raza, id_dueno, edad_anios, notas) VALUES
('Max', 1, 1, 1, 4, 'Tranquilo con el baño'),
('Luna', 2, 11, 1, 2, 'Sensible a ruidos fuertes'),
('Rocky', 1, 10, 2, 6, 'Pelaje enmarañado en cuello'),
('Michi', 2, 16, 3, 3, 'Prefiere turnos de mañana'),
('Nala', 1, 3, 4, 5, 'Le gusta el perfume suave'),
('Simba', 2, 13, 4, 1, 'Primera vez en grooming'),
('Toby', 1, 4, 5, 8, 'Uñas largas'),
('Coco', 3, 17, 6, 2, 'Conejo nervioso: manejo suave'),
('Kira', 1, 5, 7, 3, 'Alergia leve a champú citrus'),
('Bella', 1, 9, 7, 7, 'Corte estilo teddy');

INSERT INTO cita (id_dueno, id_mascota, id_servicio, fecha, hora, estado, notas, telefono_contacto, fecha_registro) VALUES
(1, 1, 2, '2026-10-17', '09:00:00', 'confirmada', 'Corte corto en cuerpo', '3001112233', '2026-10-14 09:00:00'),
(1, 2, 8, '2026-10-17', '11:00:00', 'pendiente', 'Solo cepillado si se estresa', '3001112233', '2026-10-15 10:00:00'),
(2, 3, 3, '2026-10-18', '10:30:00', 'pendiente', 'Pelaje enmarañado', '3104445566', '2026-10-16 08:30:00'),
(3, 4, 4, '2026-10-18', '15:00:00', 'confirmada', NULL, '3207778899', '2026-10-10 12:00:00'),
(4, 5, 6, '2026-10-20', '09:30:00', 'pendiente', 'Spa completo', '3015556677', '2026-10-15 16:20:00'),
(4, 6, 9, '2026-10-20', '11:30:00', 'pendiente', 'Primera visita', '3015556677', '2026-10-15 16:25:00'),
(5, 7, 4, '2026-10-21', '14:00:00', 'confirmada', NULL, '3158889900', '2026-10-12 11:00:00'),
(6, 8, 1, '2026-10-21', '16:00:00', 'pendiente', 'Champú suave para conejo', '3002223344', '2026-10-16 09:10:00'),
(7, 9, 7, '2026-10-22', '10:00:00', 'confirmada', 'Deslanado + perfume sin citrus', '3116667788', '2026-10-13 18:00:00'),
(7, 10, 2, '2026-10-22', '12:00:00', 'pendiente', 'Estilo teddy', '3116667788', '2026-10-14 08:45:00'),
(2, 3, 5, '2026-10-16', '09:00:00', 'completada', 'Limpieza rutinaria', '3104445566', '2026-10-08 10:00:00'),
(1, 1, 1, '2026-10-15', '15:30:00', 'completada', NULL, '3001112233', '2026-10-09 14:00:00'),
(5, 7, 1, '2026-10-14', '11:00:00', 'cancelada', 'Cliente reprogramó', '3158889900', '2026-10-07 09:30:00'),
(3, 4, 8, '2026-10-23', '09:00:00', 'pendiente', NULL, '3207778899', '2026-10-16 17:00:00'),
(6, 8, 9, '2026-10-24', '10:00:00', 'pendiente', 'Consulta de pelaje', '3002223344', '2026-10-16 17:15:00');
