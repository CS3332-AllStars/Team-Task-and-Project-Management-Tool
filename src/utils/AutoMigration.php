<?php
// CS3332 AllStars Team Task & Project Management System
// Auto Migration Bootstrap - Runs migrations automatically when needed

require_once __DIR__ . '/MigrationRunner.php';

class AutoMigration {
    
    /**
     * Check and run migrations automatically (silent mode)
     * Returns true if successful, false if failed
     */
    public static function checkAndRunMigrations($mysqli) {
        try {
            $migrationRunner = new MigrationRunner($mysqli, null, false);
            
            if ($migrationRunner->hasPendingMigrations()) {
                // Log that migrations are being run
                error_log("[TTPM] Auto-running pending database migrations...");
                
                if ($migrationRunner->runMigrations()) {
                    error_log("[TTPM] Database migrations completed successfully.");
                    return true;
                } else {
                    error_log("[TTPM] Database migration failed!");
                    return false;
                }
            }
            
            return true; // No migrations needed
            
        } catch (Exception $e) {
            error_log("[TTPM] Auto-migration error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if migrations are needed (read-only check)
     */
    public static function hasPendingMigrations($mysqli) {
        try {
            $migrationRunner = new MigrationRunner($mysqli, null, false);
            return $migrationRunner->hasPendingMigrations();
        } catch (Exception $e) {
            error_log("[TTPM] Migration check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Show migration banner for admin users
     */
    public static function showMigrationBanner() {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            echo '<div class="alert alert-info migration-banner">';
            echo '<strong>⚠️ Database Update Required:</strong> ';
            echo 'New database migrations are pending. Please run <code>php migrate.php</code> to update your database.';
            echo '</div>';
        }
    }
}
?>