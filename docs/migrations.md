# Database Migration System

## Overview

The TTPM system includes an automated database migration framework that ensures all team members have the same database structure when they pull changes from Git.

## How It Works

### 1. **Automatic Detection**
- When you visit the dashboard, the system automatically checks for pending migrations
- Admin users will see a banner if migrations are needed
- The system gracefully handles missing migration infrastructure

### 2. **Migration Files**
- Located in: `database/migrations/`
- Naming convention: `XXX_description.sql` (e.g., `002_add_is_archived_column.sql`)
- Each migration is tracked in the `migrations` table

### 3. **Migration Tracking**
- The `migrations` table tracks which migrations have been applied
- Prevents running the same migration twice
- Maintains application history

## For Developers

### Creating a New Migration

1. **Create the migration file:**
   ```bash
   # Create a new numbered migration file
   # Format: XXX_descriptive_name.sql
   touch database/migrations/003_add_new_feature.sql
   ```

2. **Write your migration:**
   ```sql
   -- Migration 003: Add new feature
   USE ttpm_system;
   
   -- Your schema changes here
   ALTER TABLE table_name ADD COLUMN new_column VARCHAR(255);
   
   -- Mark migration as completed (IMPORTANT!)
   INSERT IGNORE INTO migrations (migration_name) VALUES ('003_add_new_feature');
   
   SELECT 'Migration 003 completed: Description of changes' as message;
   ```

3. **Test your migration:**
   ```bash
   php migrate.php --status    # Check current status
   php migrate.php -v          # Run with verbose output
   ```

### Running Migrations

**Command Line (Recommended):**
```bash
# Run all pending migrations
php migrate.php

# Show detailed output
php migrate.php --verbose

# Check migration status
php migrate.php --status

# Show help
php migrate.php --help
```

**Automatic (Built-in):**
- Migrations run automatically during setup: `./setup.sh` or `setup.bat`
- Dashboard shows admin warnings for pending migrations

## For New Team Members

### Initial Setup
```bash
# 1. Clone the repository
git clone <repository-url>
cd Team-Task-and-Project-Management-Tool

# 2. Run setup (includes migrations)
chmod +x setup.sh
./setup.sh

# Or on Windows:
setup.bat
```

### When Pulling Updates
```bash
# 1. Pull latest changes
git pull

# 2. Run migrations to update database
php migrate.php

# 3. Check if successful
php migrate.php --status
```

## Migration System Files

```
database/
├── migrations/
│   ├── 001_create_migrations_table.sql    # Migration tracking table
│   ├── 002_add_is_archived_column.sql     # CS3-12F: Project archival
│   └── XXX_your_migration.sql              # Future migrations
├── schema.sql                              # Base schema
└── sample_data.sql                         # Test data

src/utils/
├── MigrationRunner.php                     # Core migration engine
└── AutoMigration.php                       # Auto-detection & banners

migrate.php                                 # Command-line runner
```

## Best Practices

### 1. **Safe Migrations**
- Always include existence checks: `IF NOT EXISTS`, `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`
- Use transactions for complex changes
- Test migrations on sample data first

### 2. **Naming Convention**
- Use sequential numbers: `001_`, `002_`, `003_`
- Descriptive names: `add_user_avatar`, `create_notifications_table`
- Keep file names under 50 characters

### 3. **Migration Content**
```sql
-- Always start with USE statement
USE ttpm_system;

-- Include safety checks
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ttpm_system' AND TABLE_NAME = 'table_name' 
    AND COLUMN_NAME = 'column_name');

-- Conditional execution
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE table_name ADD COLUMN column_name VARCHAR(255)',
    'SELECT "Column already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- REQUIRED: Mark migration as completed
INSERT IGNORE INTO migrations (migration_name) VALUES ('XXX_migration_name');

-- Optional: Success message
SELECT 'Migration XXX completed successfully' as message;
```

### 4. **Team Workflow**
- Create migration → Test locally → Commit with code changes
- Team members pull → Run `php migrate.php` → Continue development
- Production deploys → Run migrations as part of deployment process

## Troubleshooting

### Migration Failed
```bash
# Check what failed
php migrate.php --verbose

# Check current status
php migrate.php --status

# Manual database inspection
mysql -u root ttpm_system
SELECT * FROM migrations ORDER BY applied_at DESC;
```

### Missing Migration File
```bash
# Check file exists
ls -la database/migrations/

# Verify file naming
# Should be: XXX_name.sql (3 digits + underscore + name.sql)
```

### Permission Issues
```bash
# Ensure PHP can read migration files
chmod +r database/migrations/*.sql

# Ensure database user has permissions
mysql -u root -e "GRANT ALL PRIVILEGES ON ttpm_system.* TO 'your_user'@'localhost';"
```

## Examples

### Simple Column Addition
```sql
-- 004_add_user_avatar.sql
USE ttpm_system;

ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL AFTER email;

INSERT IGNORE INTO migrations (migration_name) VALUES ('004_add_user_avatar');
SELECT 'User avatar column added' as message;
```

### Complex Table Creation
```sql
-- 005_create_audit_log.sql
USE ttpm_system;

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_table ON audit_log(table_name, record_id);

INSERT IGNORE INTO migrations (migration_name) VALUES ('005_create_audit_log');
SELECT 'Audit log table created' as message;
```

This system ensures that database changes are automatically synchronized across all development environments and production deployments.