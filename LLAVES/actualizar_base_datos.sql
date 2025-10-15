-- Añadir columna requiere_reclamo a ubicacion si no existe
ALTER TABLE ubicacion ADD COLUMN IF NOT EXISTS requiere_reclamo TINYINT(1) NOT NULL DEFAULT 0;

-- Añadir columna correo a usuarios si no existe
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS correo VARCHAR(255) NULL AFTER rol;

