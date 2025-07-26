-- CS3332 AllStars Team Task & Project Management System
-- Migration 002: Add is_archived column to projects table
-- CS3-12F: Project Archival & Deletion

USE ttpm_system;

-- Add is_archived column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ttpm_system' 
    AND TABLE_NAME = 'projects' 
    AND COLUMN_NAME = 'is_archived'
);

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

-- Mark this migration as completed
INSERT IGNORE INTO migrations (migration_name) VALUES ('002_add_is_archived_column');

SELECT 'Migration 002 completed: is_archived column added to projects table' as message;