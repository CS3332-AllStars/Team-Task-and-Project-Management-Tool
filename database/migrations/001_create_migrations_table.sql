-- CS3332 AllStars Team Task & Project Management System
-- Migration 001: Create migrations tracking table
-- This creates the infrastructure to track which migrations have been run

USE ttpm_system;

-- Create migrations table to track applied migrations
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_migration_name (migration_name)
);

-- Insert this migration into the tracking table
INSERT IGNORE INTO migrations (migration_name) VALUES ('001_create_migrations_table');

SELECT 'Migration tracking table created successfully' as message;