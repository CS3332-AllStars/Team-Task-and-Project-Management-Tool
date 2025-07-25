-- CS3332 AllStars Team Task & Project Management System
-- Migration: Add is_archived column to projects table
-- CS3-12F: Project Archival & Deletion

USE ttpm_system;

-- Check if the column already exists before adding it
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ttpm_system' 
    AND TABLE_NAME = 'projects' 
    AND COLUMN_NAME = 'is_archived'
);

-- Add the column only if it doesn't exist
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE projects ADD COLUMN is_archived BOOLEAN DEFAULT FALSE AFTER description',
    'SELECT "Column is_archived already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for better performance on archive filtering
SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'ttpm_system' 
    AND TABLE_NAME = 'projects' 
    AND INDEX_NAME = 'idx_projects_archived'
);

SET @sql_index = IF(@index_exists = 0, 
    'CREATE INDEX idx_projects_archived ON projects(is_archived)',
    'SELECT "Index idx_projects_archived already exists" as message'
);

PREPARE stmt FROM @sql_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the migration
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'ttpm_system' 
AND TABLE_NAME = 'projects' 
AND COLUMN_NAME = 'is_archived';