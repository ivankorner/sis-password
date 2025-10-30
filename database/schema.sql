-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sis_password CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sis_password;

-- Tabla de administradores del sistema
CREATE TABLE IF NOT EXISTS administradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  activo TINYINT DEFAULT 1,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de PCs
CREATE TABLE IF NOT EXISTS pcs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  oficina VARCHAR(100) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_oficina (oficina),
  INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios por PC
CREATE TABLE IF NOT EXISTS usuarios_pc (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pc_id INT NOT NULL,
  operario VARCHAR(150) NOT NULL,
  nombre_usuario VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  id_control_remoto VARCHAR(100),
  password_control_remoto VARCHAR(255),
  notas TEXT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (pc_id) REFERENCES pcs(id) ON DELETE CASCADE,
  INDEX idx_pc_id (pc_id),
  INDEX idx_nombre_usuario (nombre_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar administrador por defecto
-- Email: admin@test.com
-- Contraseña: Admin123! (hasheada con password_hash)
INSERT IGNORE INTO administradores (email, password, nombre) VALUES (
  'admin@test.com',
  '$2y$10$YOixf7jLWxqB5b/yGfqR.efxZp97SkGDQ.U8rGVwuuDKk8Y3VN9Ja',
  'Administrador'
);
