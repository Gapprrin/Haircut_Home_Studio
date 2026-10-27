-- ============================================================
-- Haircut Studio — Base de datos para XAMPP / MySQL
-- Cómo usarla:
--   1) Abre phpMyAdmin (http://localhost/phpmyadmin)
--   2) Pestaña "Importar" y elige este archivo
--   O bien abre en el navegador: instalar.php
--
-- Cuentas de demo (después de importar / instalar):
--   Administrador: admin@admin.cl / admin123  (servicios y productos)
--   Peluquero:     Ricardo  ricardo@me.com / kako123  (agenda y reservas)
--   Cliente:       ana@haircut.cl / ana123
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
-- Usuarios (clientes, administrador de catálogo y peluquero)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('cliente', 'admin', 'peluquero') NOT NULL DEFAULT 'cliente',
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
  slug VARCHAR(80) NOT NULL DEFAULT '',
  imagen VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Servicios del catálogo
-- ------------------------------------------------------------
CREATE TABLE servicios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  descripcion TEXT,
  imagen VARCHAR(255) DEFAULT NULL,
  duracion_min INT UNSIGNED NOT NULL DEFAULT 60,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Horario general de atención (una sola fila)
-- dias_atencion: números 2=Martes … 6=Sábado (lunes y domingo siempre cerrados)
-- ------------------------------------------------------------
CREATE TABLE configuracion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hora_inicio TIME NOT NULL DEFAULT '09:00:00',
  hora_fin TIME NOT NULL DEFAULT '18:00:00',
  dias_atencion VARCHAR(20) NOT NULL DEFAULT '2,3,4,5,6'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Días marcados como "off" (no se puede reservar)
-- Relacionados con la configuración del estudio
-- ------------------------------------------------------------
CREATE TABLE dias_off (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  configuracion_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL UNIQUE,
  FOREIGN KEY (configuracion_id) REFERENCES configuracion(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Meses que el cliente ve en el calendario
-- Relacionados con la configuración del estudio
-- ------------------------------------------------------------
CREATE TABLE meses_visibles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  configuracion_id INT UNSIGNED NOT NULL,
  anio SMALLINT UNSIGNED NOT NULL,
  mes TINYINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_mes (anio, mes),
  FOREIGN KEY (configuracion_id) REFERENCES configuracion(id) ON DELETE CASCADE ON UPDATE CASCADE
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
  lugar ENUM('salon','domicilio') NOT NULL DEFAULT 'salon',
  estado ENUM('pendiente','confirmada','rechazada','cancelada','realizada','no_asistio') NOT NULL DEFAULT 'pendiente',
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
  categoria_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  imagen VARCHAR(255) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===================== DATOS INICIALES =====================

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@admin.cl', '$2y$10$VoCY9sMcCNHglwe.P/2DfumxW29mcf5OtcWHN/WVSuccjEPCmJmXa', 'admin'),
('Ricardo', 'ricardo@me.com', '$2y$10$EbBQ8mLf7lekhA.01lr6zeRX/..gby5ug6zb51oi5uiMQ9/DaP9Sq', 'peluquero'),
('Cliente Demo', 'cliente@haircut.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente'),
('Ana', 'ana@haircut.cl', '$2y$10$fj.pjC1kYGdi5vAOuKcts.XmZqJPaZrYrdZ4Gtxn8mUzlwKO4LbPK', 'cliente'),
('Camila Rojas', 'camila@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente'),
('Diego Pérez', 'diego@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente'),
('Fernanda López', 'fernanda@correo.cl', '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'cliente');

INSERT INTO categorias (id, nombre, slug, imagen) VALUES
(1, 'Cortes', 'corte', 'ico-cat-corte.png'),
(2, 'Color', 'color', 'ico-color.png'),
(3, 'Tratamientos', 'tratamientos', 'ico-tratamiento.png');

INSERT INTO servicios (categoria_id, nombre, descripcion, duracion_min, precio, imagen) VALUES
(1, 'Corte de damas', '15 a 30 minutos. En la agenda se reserva 1 hora.', 60, 15000, 'ico-corte.png'),
(1, 'Corte infantil', 'En la agenda se reserva 1 hora.', 60, 12000, 'ico-infantil.png'),
(1, 'Lavado y brushing', '25 a 40 minutos. En la agenda se reserva 1 hora.', 60, 16000, 'ico-brushing.png'),
(1, 'Peinado', '20 a 40 minutos. En la agenda se reserva 1 hora.', 60, 15000, 'ico-peinado.png'),
(1, 'Corte + lavado', 'A veces también incluye peinado. En la agenda se reserva 1 hora.', 60, 18000, 'ico-corte-lavado.png'),
(2, 'Cobertura de canas', '1 a 1,5 horas.', 120, 28000, 'ico-cobertura.png'),
(2, 'Retoque de crecimiento', '1 a 1,5 horas.', 120, 22000, 'ico-retoque.png'),
(2, 'Visos', '2 a 3 horas.', 180, 35000, 'ico-visos.png'),
(2, 'Mechas', '2 a 3 horas.', 180, 35000, 'ico-mechas.png'),
(2, 'Balayage', '3 a 5 horas, a veces hasta 6. El tiempo varía con el cabello.', 300, 45000, 'ico-balayage.png'),
(2, 'Baby lights', '4 a 5 horas, a veces hasta 6.', 300, 50000, 'ico-babylight.png'),
(2, 'Color fantasía', 'En la agenda se reservan 4 horas.', 240, 35000, 'ico-fantasia.png'),
(3, 'Masaje capilar', 'Aproximadamente 1 hora.', 60, 18000, 'ico-masaje.png'),
(3, 'Botox capilar', '1 a 2 horas.', 120, 30000, 'ico-botox.png'),
(3, 'Liso permanente', '2 a 3,5 horas. A veces se combina con retoque de color.', 240, 40000, 'ico-liso.png'),
(3, 'Maquillaje social', 'Para eventos, de forma ocasional, 30 minutos a 1 hora.', 60, 18000, 'ico-maquillaje.png'),
(3, 'Olaplex', 'Reparación de la fibra capilar. Aprox. 1 hora.', 60, 25000, 'ico-olaplex.png');

INSERT INTO productos (categoria_id, nombre, descripcion, precio, activo, orden) VALUES
(2, 'Shampoo de color', 'Cuidado para cabello teñido', 12990, 1, 1),
(1, 'Acondicionador nutritivo', 'Hidratación y brillo', 11990, 1, 2),
(3, 'Tratamiento Olaplex', 'Reparación de fibra capilar', 24990, 1, 3),
(1, 'Leave-in protector', 'Protección térmica diaria', 9990, 1, 4),
(3, 'Aceite capilar', 'Nutrición y anti-frizz', 14990, 1, 5),
(3, 'Ampolleta de reparación', 'Dosis de tratamiento intensivo', 7990, 1, 6);

INSERT INTO configuracion (hora_inicio, hora_fin, dias_atencion) VALUES
('10:30:00', '19:30:00', '2,3,4,5,6');

INSERT INTO meses_visibles (configuracion_id, anio, mes) VALUES
(1, 2026, 8),
(1, 2026, 9);

INSERT INTO dias_off (configuracion_id, fecha) VALUES
(1, '2026-08-17'),
(1, '2026-08-25');

-- Reservas de ejemplo
-- pendiente: solo en Solicitudes (no en Horas)
-- confirmada/realizada: en Horas
-- cancelada / no_asistio: no las ve la clienta; sí salen en el informe
INSERT INTO reservas (usuario_id, servicio_id, fecha, hora, lugar, estado) VALUES
(4, 1, '2026-09-15', '10:30:00', 'salon', 'confirmada'),
(5, 5, '2026-09-16', '11:30:00', 'salon', 'pendiente'),
(6, 12, '2026-09-17', '10:30:00', 'domicilio', 'pendiente'),
(4, 1, '2026-08-12', '11:30:00', 'salon', 'realizada'),
(7, 2, '2026-09-10', '15:30:00', 'salon', 'cancelada'),
(5, 10, '2026-09-08', '10:30:00', 'salon', 'no_asistio');
