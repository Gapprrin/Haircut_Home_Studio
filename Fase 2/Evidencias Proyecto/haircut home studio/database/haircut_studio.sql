-- ============================================================
-- Haircut Studio — Base de datos para XAMPP / MySQL
-- Cómo usarla:
--   1) Abre phpMyAdmin (http://localhost/phpmyadmin)
--   2) Pestaña "Importar" y elige este archivo
--   O bien abre en el navegador: instalar.php
--
-- Cuentas de demo (después de importar / instalar):
--   Admin:   admin@haircut.cl   / admin123
--   Cliente: cliente@haircut.cl / cliente123
-- ============================================================

CREATE DATABASE IF NOT EXISTS haircut_studio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE haircut_studio;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS dias_off;
DROP TABLE IF EXISTS meses_visibles;
DROP TABLE IF EXISTS configuracion;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Usuarios (clientes y administrador)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
  es_invitado TINYINT(1) NOT NULL DEFAULT 0,
  origen VARCHAR(20) NOT NULL DEFAULT 'email',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Categorías de servicio (Corte, Color, …)
-- ------------------------------------------------------------
CREATE TABLE categorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Servicios del catálogo
-- ------------------------------------------------------------
CREATE TABLE servicios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  descripcion TEXT,
  duracion_min INT UNSIGNED NOT NULL DEFAULT 60,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Horario general de atención (una sola fila)
-- dias_atencion: números 1=Lunes … 7=Domingo, separados por coma
-- ------------------------------------------------------------
CREATE TABLE configuracion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hora_inicio TIME NOT NULL DEFAULT '09:00:00',
  hora_fin TIME NOT NULL DEFAULT '18:00:00',
  dias_atencion VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Días marcados como "off" (no se puede reservar)
-- ------------------------------------------------------------
CREATE TABLE dias_off (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Meses que el cliente ve en el calendario
-- ------------------------------------------------------------
CREATE TABLE meses_visibles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  anio SMALLINT UNSIGNED NOT NULL,
  mes TINYINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_mes (anio, mes)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Reservas / solicitudes
-- ------------------------------------------------------------
CREATE TABLE reservas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  foto VARCHAR(255) DEFAULT NULL,
  estado ENUM('pendiente','confirmada','rechazada','cancelada','realizada') NOT NULL DEFAULT 'pendiente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE RESTRICT,
  INDEX idx_fecha_hora (fecha, hora),
  INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Productos para venta presencial
-- imagen: archivo en uploads/fotos o vacío hasta cargar foto
-- ------------------------------------------------------------
CREATE TABLE productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  imagen VARCHAR(255) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ===================== DATOS INICIALES =====================

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@haircut.cl', '$2y$10$67YuJ9hdiuHAxQCSWoh9Ku.J1jmHtU15QQMzTT9tG5edxYa7yXTcS', 'admin'),
('Cliente Demo', 'cliente@haircut.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente'),
('Camila Rojas', 'camila@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente'),
('Diego Pérez', 'diego@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente'),
('Fernanda López', 'fernanda@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente');

INSERT INTO categorias (id, nombre, slug) VALUES
(1, 'Cortes', 'corte'),
(2, 'Color', 'color'),
(3, 'Tratamientos', 'tratamientos');

INSERT INTO servicios (categoria_id, nombre, descripcion, duracion_min, precio) VALUES
(1, 'Corte de damas', 'Corte personalizado según tu estilo y tipo de cabello', 60, 15000),
(1, 'Corte + brushing', 'Corte y peinado con brushing', 60, 18000),
(1, 'Corte infantil', 'Corte para niñas', 60, 12000),
(2, 'Color global', 'Tinte uniforme de raíz a puntas', 120, 28000),
(2, 'Balayage / Babylight', 'Iluminaciones suaves y degradado natural', 120, 45000),
(2, 'Retoque de raíz', 'Retoque de color en raíz', 120, 22000),
(2, 'Color fantasía', 'Tonos creativos y de fantasía', 120, 35000),
(3, 'Olaplex', 'Reparación de la fibra capilar', 60, 25000),
(3, 'Botox capilar', 'Tratamiento de brillo y suavidad', 120, 30000),
(3, 'Masaje capilar', 'Masaje y tratamiento para el cuero cabelludo', 60, 18000),
(3, 'Alisado / lisos', 'Liso duradero según evaluación', 120, 40000);

INSERT INTO productos (nombre, descripcion, precio, activo, orden) VALUES
('Shampoo de color', 'Cuidado para cabello teñido', 12990, 1, 1),
('Acondicionador nutritivo', 'Hidratación y brillo', 11990, 1, 2),
('Tratamiento Olaplex', 'Reparación de fibra capilar', 24990, 1, 3),
('Leave-in protector', 'Protección térmica diaria', 9990, 1, 4),
('Aceite capilar', 'Nutrición y anti-frizz', 14990, 1, 5),
('Ampolleta de reparación', 'Dosis de tratamiento intensivo', 7990, 1, 6);

INSERT INTO configuracion (hora_inicio, hora_fin, dias_atencion) VALUES
('10:30:00', '19:30:00', '2,3,4,5,6');

INSERT INTO meses_visibles (anio, mes) VALUES
(2026, 8),
(2026, 9);

INSERT INTO dias_off (fecha) VALUES
('2026-08-17'),
('2026-08-25');

-- Reservas de ejemplo (ajusta ids de servicio: 1 clásico, 2+barba, 4 color, 5 mechas, 6 retoque)
INSERT INTO reservas (usuario_id, servicio_id, fecha, hora, estado) VALUES
(2, 2, '2026-08-20', '15:00:00', 'confirmada'),
(2, 6, '2026-08-28', '11:30:00', 'pendiente'),
(2, 1, '2026-07-05', '10:00:00', 'realizada'),
(3, 5, '2026-08-26', '10:00:00', 'pendiente'),
(4, 2, '2026-08-27', '16:30:00', 'pendiente'),
(5, 4, '2026-08-28', '11:00:00', 'pendiente');
