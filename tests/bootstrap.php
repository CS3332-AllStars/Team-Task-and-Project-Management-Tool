<?php
// CS3332 AllStars Test Bootstrap
// PHPUnit Test Environment Setup

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test environment
define('TEST_ENVIRONMENT', true);

// Load database configuration for testing
// Note: Adjusted paths for actual project structure
require_once __DIR__ . '/../src/config/database.php';

// Load models (if they exist)
if (file_exists(__DIR__ . '/../src/models/User.php')) {
    require_once __DIR__ . '/../src/models/User.php';
}
if (file_exists(__DIR__ . '/../src/models/Project.php')) {
    require_once __DIR__ . '/../src/models/Project.php';
}
if (file_exists(__DIR__ . '/../src/models/Task.php')) {
    require_once __DIR__ . '/../src/models/Task.php';
}
if (file_exists(__DIR__ . '/../src/models/Comment.php')) {
    require_once __DIR__ . '/../src/models/Comment.php';
}
if (file_exists(__DIR__ . '/../src/models/NotificationService.php')) {
    require_once __DIR__ . '/../src/models/NotificationService.php';
}

// Load testing infrastructure
require_once __DIR__ . '/fixtures/reset_database.php';

// Initialize database for tests
DatabaseReset::resetToFixtures(true); // Silent mode for PHPUnit

// Enhanced test database helper functions
class TestDatabaseHelper {
    
    /**
     * Reset database using new fixture system
     */
    public static function resetToFixtures() {
        return DatabaseReset::resetToFixtures();
    }
    
    /**
     * Reset database to minimal state
     */
    public static function resetToMinimal() {
        return DatabaseReset::resetToMinimal();
    }
    
    /**
     * Legacy reset method (maintained for backward compatibility)
     */
    public static function resetDatabase($pdo) {
        // Each test needs fresh database state - actually reset it
        return DatabaseReset::resetToFixtures(true); // Silent mode
    }
    
    /**
     * Create test user (enhanced with better defaults)
     */
    public static function createTestUser($pdo, $username = 'testuser', $email = 'test@example.com') {
        $user = new User($pdo);
        return $user->register($username, $email, 'Test123!@#', 'Test User');
    }
    
    /**
     * Get database statistics for verification
     */
    public static function getDatabaseStats() {
        return DatabaseReset::getDatabaseStats();
    }
    
    /**
     * Verify test environment is ready
     */
    public static function verifyTestEnvironment() {
        $stats = self::getDatabaseStats();
        $totalRecords = array_sum($stats);
        
        return [
            'ready' => $totalRecords > 0,
            'stats' => $stats,
            'total_records' => $totalRecords
        ];
    }
}

// Auto-include for PHPUnit (if using Composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
?>
