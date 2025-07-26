<?php
// CS3332 AllStars Team Task & Project Management System
// Migration Runner - Automated database migrations

class MigrationRunner {
    private $mysqli;
    private $migrations_dir;
    private $verbose;

    public function __construct($mysqli, $migrations_dir = null, $verbose = false) {
        $this->mysqli = $mysqli;
        $this->migrations_dir = $migrations_dir ?: __DIR__ . '/../../database/migrations';
        $this->verbose = $verbose;
    }

    /**
     * Run all pending migrations
     */
    public function runMigrations() {
        try {
            // Ensure migrations table exists first
            $this->ensureMigrationsTable();
            
            // Get list of migration files
            $migration_files = $this->getMigrationFiles();
            
            // Get already applied migrations
            $applied_migrations = $this->getAppliedMigrations();
            
            $pending_migrations = array_diff($migration_files, $applied_migrations);
            
            if (empty($pending_migrations)) {
                $this->log("No pending migrations to run.");
                return true;
            }
            
            $this->log("Found " . count($pending_migrations) . " pending migration(s).");
            
            // Run each pending migration
            foreach ($pending_migrations as $migration) {
                $this->runMigration($migration);
            }
            
            $this->log("All migrations completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->log("Migration failed: " . $e->getMessage(), true);
            return false;
        }
    }

    /**
     * Check if migrations need to be run
     */
    public function hasPendingMigrations() {
        try {
            $this->ensureMigrationsTable();
            $migration_files = $this->getMigrationFiles();
            $applied_migrations = $this->getAppliedMigrations();
            return count(array_diff($migration_files, $applied_migrations)) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ensure migrations tracking table exists
     */
    private function ensureMigrationsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_migration_name (migration_name)
            )
        ";
        
        if (!$this->mysqli->query($sql)) {
            throw new Exception("Failed to create migrations table: " . $this->mysqli->error);
        }
    }

    /**
     * Get list of migration files from filesystem
     */
    private function getMigrationFiles() {
        if (!is_dir($this->migrations_dir)) {
            throw new Exception("Migrations directory not found: " . $this->migrations_dir);
        }
        
        $files = glob($this->migrations_dir . '/*.sql');
        $migrations = [];
        
        foreach ($files as $file) {
            $basename = basename($file, '.sql');
            // Only include numbered migration files (e.g., 001_*, 002_*)
            if (preg_match('/^\d{3}_/', $basename)) {
                $migrations[] = $basename;
            }
        }
        
        sort($migrations);
        return $migrations;
    }

    /**
     * Get list of already applied migrations from database
     */
    private function getAppliedMigrations() {
        $sql = "SELECT migration_name FROM migrations ORDER BY migration_name";
        $result = $this->mysqli->query($sql);
        
        if (!$result) {
            return [];
        }
        
        $applied = [];
        while ($row = $result->fetch_assoc()) {
            $applied[] = $row['migration_name'];
        }
        
        return $applied;
    }

    /**
     * Run a single migration file
     */
    private function runMigration($migration_name) {
        $file_path = $this->migrations_dir . '/' . $migration_name . '.sql';
        
        if (!file_exists($file_path)) {
            throw new Exception("Migration file not found: " . $file_path);
        }
        
        $this->log("Running migration: " . $migration_name);
        
        $sql = file_get_contents($file_path);
        
        // Execute the migration (handle multiple statements)
        if ($this->mysqli->multi_query($sql)) {
            // Process all result sets
            do {
                if ($result = $this->mysqli->store_result()) {
                    $result->free();
                }
            } while ($this->mysqli->next_result());
            
            // Check for errors
            if ($this->mysqli->error) {
                throw new Exception("Migration failed: " . $this->mysqli->error);
            }
            
            $this->log("✅ Migration completed: " . $migration_name);
        } else {
            throw new Exception("Failed to execute migration: " . $this->mysqli->error);
        }
    }

    /**
     * Log messages
     */
    private function log($message, $is_error = false) {
        if ($this->verbose || $is_error) {
            $prefix = $is_error ? "[ERROR] " : "[INFO] ";
            echo $prefix . $message . "\n";
        }
    }

    /**
     * Get migration status for debugging
     */
    public function getMigrationStatus() {
        try {
            $this->ensureMigrationsTable();
            $migration_files = $this->getMigrationFiles();
            $applied_migrations = $this->getAppliedMigrations();
            
            $status = [];
            foreach ($migration_files as $migration) {
                $status[] = [
                    'migration' => $migration,
                    'applied' => in_array($migration, $applied_migrations),
                    'file_exists' => file_exists($this->migrations_dir . '/' . $migration . '.sql')
                ];
            }
            
            return $status;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>