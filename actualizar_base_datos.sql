-- Script simplificado para actualizar la base de datos existente con las nuevas funcionalidades de alarmas
-- Ejecutar este script en phpMyAdmin en la base de datos registro_llaves

-- Agregar columna tiene_alarma (si ya existe, dará error pero no importa)
ALTER TABLE llaves ADD COLUMN tiene_alarma tinyint(1) NOT NULL DEFAULT 0 AFTER observaciones;

-- Agregar columna codigo_alarma (si ya existe, dará error pero no importa)
ALTER TABLE llaves ADD COLUMN codigo_alarma varchar(100) DEFAULT NULL AFTER tiene_alarma;

-- Agregar columna baja (si ya existe, ignorar el error)
ALTER TABLE llaves ADD COLUMN baja tinyint(1) NOT NULL DEFAULT 0 AFTER codigo_alarma;

-- Mostrar mensaje de confirmación
SELECT 'Base de datos actualizada correctamente con las funcionalidades de alarmas' as resultado;
