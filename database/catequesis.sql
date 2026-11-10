-- MANOS QUE SIRVEN · Módulo Catequesis
-- Ejecutar en phpMyAdmin con la base manos_que_sirven seleccionada.
-- No altera las tablas existentes de beneficencia ni las de la tienda.
-- Las tablas se conservan al repetir la importación.

CREATE TABLE IF NOT EXISTS catequesis_niveles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(90) NOT NULL UNIQUE,
  orden TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO catequesis_niveles (nombre, orden) VALUES
 ('Primera infancia', 1), ('Primera comunión', 2), ('Confirmación', 3);

CREATE TABLE IF NOT EXISTS catequesis_catequistas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(150) NOT NULL,
  telefono VARCHAR(25) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_catequistas_nombre (nombre_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catequesis_alumnos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_completo VARCHAR(150) NOT NULL,
  contacto_responsable VARCHAR(25) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_alumnos_nombre (nombre_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catequesis_grupos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  nivel_id INT UNSIGNED NOT NULL,
  catequista_id INT UNSIGNED DEFAULT NULL,
  anio SMALLINT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_grupo (anio, nivel_id, nombre),
  INDEX idx_grupo_catequista (catequista_id),
  CONSTRAINT fk_c_grupos_nivel FOREIGN KEY (nivel_id) REFERENCES catequesis_niveles (id),
  CONSTRAINT fk_c_grupos_catequista FOREIGN KEY (catequista_id) REFERENCES catequesis_catequistas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catequesis_inscripciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alumno_id INT UNSIGNED NOT NULL,
  grupo_id INT UNSIGNED NOT NULL,
  estado ENUM('Activa','Retirada','Finalizada') NOT NULL DEFAULT 'Activa',
  fecha_inscripcion DATE NOT NULL,
  UNIQUE KEY uq_inscripcion_grupo (alumno_id, grupo_id),
  INDEX idx_inscripcion_grupo (grupo_id),
  CONSTRAINT fk_c_inscripcion_alumno FOREIGN KEY (alumno_id) REFERENCES catequesis_alumnos (id),
  CONSTRAINT fk_c_inscripcion_grupo FOREIGN KEY (grupo_id) REFERENCES catequesis_grupos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catequesis_evaluaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(140) NOT NULL,
  fecha DATE NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_eval (grupo_id, titulo, fecha),
  CONSTRAINT fk_c_eval_grupo FOREIGN KEY (grupo_id) REFERENCES catequesis_grupos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catequesis_notas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inscripcion_id INT UNSIGNED NOT NULL,
  evaluacion_id INT UNSIGNED NOT NULL,
  nota DECIMAL(4,2) NOT NULL,
  registrado_por INT UNSIGNED DEFAULT NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_nota (inscripcion_id, evaluacion_id),
  INDEX idx_nota_evaluacion (evaluacion_id),
  CONSTRAINT fk_c_nota_inscripcion FOREIGN KEY (inscripcion_id) REFERENCES catequesis_inscripciones (id),
  CONSTRAINT fk_c_nota_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES catequesis_evaluaciones (id),
  CONSTRAINT fk_c_nota_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT chk_c_nota_rango CHECK (nota >= 0 AND nota <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catequesis_asistencias (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inscripcion_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  estado ENUM('Presente','Ausente','Justificada') NOT NULL,
  registrado_por INT UNSIGNED DEFAULT NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_asistencia (inscripcion_id, fecha),
  INDEX idx_asistencia_fecha (fecha),
  CONSTRAINT fk_c_asistencia_inscripcion FOREIGN KEY (inscripcion_id) REFERENCES catequesis_inscripciones (id),
  CONSTRAINT fk_c_asistencia_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
