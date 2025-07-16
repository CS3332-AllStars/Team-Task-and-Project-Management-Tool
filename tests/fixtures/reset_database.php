<?php
/**
 * CS3332 AllStars - Database Reset Utility for Testing
 * 
 * Purpose: Provides reliable database reset functionality for PHPUnit tests
 * and manual testing scenarios. Supports both programmatic and CLI usage.
 * 
 * Usage:
 *   - Programmatic: DatabaseReset::resetToFixtures();
 *   - CLI: php reset_database.php [--fixtures] [--minimal] [--verify]
 *   - Web: /tests/fixtures/reset_database.php?token=dev_reset&fixtures=true
 * 
 * Security: Web access requires dev token and is disabled in production
 */

class DatabaseReset 
{
    private static $config = null;
    private static $connection = null;
    
    /**
     * Initialize database connection using existing config
     */
    private static function initConnection()
    {
        if (self::$connection !== null) {
            return;
        }
        
        // Load config from main application
        $configPath = __DIR__ . '/../../src/config/database.php';
        if (!file_exists($configPath)) {
            throw new Exception("Config file not found: {$configPath}");
        }
        
        // Use database config values directly (matches src/config/database.php)
        $host = 'localhost';
        $dbname = 'ttpm_system';
        $username = 'root';
        $password = '';
        
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Reset database to clean state with full test fixtures
     */
    public static function resetToFixtures($silent = null)
    {
        self::initConnection();
        
        if ($silent === null) {
            $silent = defined('TEST_ENVIRONMENT') && TEST_ENVIRONMENT;
        }
        
        if (!$silent) echo "🔄 Resetting database to test fixtures...\n";
        
        // Clear all data first
        self::clearAllData($silent);
        if (!$silent) echo "✅ Database cleared\n";
        
        // Execute main sample data
        self::executeSqlFile(__DIR__ . '/../../database/sample_data.sql', $silent);
        if (!$silent) echo "✅ Main sample data loaded\n";
        
        // Execute additional test fixtures
        self::executeSqlFile(__DIR__ . '/test_seed.sql', $silent);
        if (!$silent) echo "✅ Test-specific fixtures loaded\n";
        
        // Verify data integrity
        self::verifyFixtures($silent);
        if (!$silent) echo "✅ Database reset complete with fixtures\n";
    }
    
    /**
     * Reset database to minimal state (structure only)
     */
    public static function resetToMinimal($silent = null)
    {
        if ($silent === null) {
            $silent = defined('TEST_ENVIRONMENT') && TEST_ENVIRONMENT;
        }
        
        if (!$silent) echo "🔄 Starting minimal reset...\n";
        self::initConnection();
        
        // Clear all data
        self::clearAllData($silent);
        if (!$silent) echo "✅ All data cleared\n";
        
        // Add minimal test user for basic functionality
        self::addMinimalData($silent);
        if (!$silent) echo "✅ Minimal data added\n";
        
        if (!$silent) echo "✅ Database reset to minimal state complete\n";
    }
    
    /**
     * Clear all data from tables while preserving structure
     */
    private static function clearAllData($silent = false)
    {
        $tables = [
            'notifications',
            'task_assignments', 
            'comments',
            'tasks',
            'project_memberships',
            'projects',
            'users'
        ];
        
        // Disable foreign key checks
        self::$connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Use DELETE instead of TRUNCATE to avoid foreign key issues
        foreach ($tables as $table) {
            self::$connection->exec("DELETE FROM {$table}");
            // Reset auto-increment
            self::$connection->exec("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
        
        // Re-enable foreign key checks
        self::$connection->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    /**
     * Add minimal data for basic testing
     */
    private static function addMinimalData($silent = false)
    {
        // Single test user with known password
        $sql = "INSERT INTO users (user_id, username, email, password_hash, name) VALUES 
                (1, 'test_user', 'test@example.com', ?, 'Test User')";
        
        $stmt = self::$connection->prepare($sql);
        // Password is 'password123'
        $stmt->execute(['$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi']);
        
        // Reset auto-increment
        self::$connection->exec("ALTER TABLE users AUTO_INCREMENT = 2");
    }
    
    /**
     * Execute SQL file with proper error handling
     */
    private static function executeSqlFile($filePath, $silent = false)
    {
        if (!file_exists($filePath)) {
            throw new Exception("SQL file not found: {$filePath}");
        }
        
        $sql = file_get_contents($filePath);
        
        // If this is the sample_data.sql file, remove clearing statements
        if (strpos($filePath, 'sample_data.sql') !== false) {
            // Use regex to remove the clearing section more precisely
            $pattern = '/-- Clear existing data.*?SET FOREIGN_KEY_CHECKS = 1;/s';
            $sql = preg_replace($pattern, '', $sql);
            
            // Remove USE statements
            $sql = preg_replace('/^\s*USE\s+\w+\s*;\s*$/m', '', $sql);
            
            // Remove the header comment section but preserve the rest
            $lines = explode("\n", $sql);
            $filteredLines = [];
            $skipHeaderLines = true;
            
            foreach ($lines as $line) {
                $trimmed = trim($line);
                
                // Skip empty lines and header comments at the start
                if ($skipHeaderLines) {
                    if (empty($trimmed) || strpos($trimmed, '--') === 0) {
                        continue;
                    }
                    // Found first non-comment, non-empty line
                    $skipHeaderLines = false;
                }
                
                $filteredLines[] = $line;
            }
            
            $sql = implode("\n", $filteredLines);
        }
        
        // Remove SOURCE statements (not supported in PDO)
        if (strpos($sql, 'SOURCE') !== false) {
            $sql = preg_replace('/SOURCE[^;]*;?/i', '', $sql);
        }
        
        // Replace TRUNCATE statements with DELETE to avoid foreign key issues
        $sql = preg_replace('/TRUNCATE\s+TABLE\s+(\w+);?/i', 'DELETE FROM $1;', $sql);
        
        // Remove verification queries at the end (they cause PDO buffering issues)
        $sql = preg_replace('/SELECT\s+[^;]*\s+as\s+(info|metric)[^;]*;/i', '', $sql);
        
        // Split into individual statements using improved parsing
        $statements = self::splitSqlStatements($sql);
        
        $executedCount = 0;
        $insertCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            // Skip empty statements and comments
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                self::$connection->exec($statement);
                $executedCount++;
                
                // Count INSERT statements specifically
                if (stripos($statement, 'INSERT') === 0) {
                    $insertCount++;
                }
                
            } catch (PDOException $e) {
                // Log error but continue (some statements might fail on purpose)
                if (!$silent) {
                    error_log("SQL Warning: " . $e->getMessage() . " in statement: " . substr($statement, 0, 100) . "...");
                }
            }
        }
    }
    
    /**
     * Better SQL statement splitting that preserves multi-line statements
     */
    private static function splitSqlStatements($sql)
    {
        // First, remove comments more intelligently
        $lines = explode("\n", $sql);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip comment-only lines
            if (strpos($trimmed, '--') === 0) {
                continue;
            }
            // Remove inline comments
            $commentPos = strpos($line, '--');
            if ($commentPos !== false) {
                $line = substr($line, 0, $commentPos);
            }
            $cleanedLines[] = $line;
        }
        
        $sql = implode("\n", $cleanedLines);
        
        // Now split statements properly
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $current .= $char;
            
            // Handle string literals
            if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i-1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = '';
                }
            }
            
            // Look for statement terminators (semicolons) outside of strings
            if ($char === ';' && !$inString) {
                $statement = trim($current);
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $current = '';
            }
        }
        
        // Add any remaining content
        $remaining = trim($current);
        if (!empty($remaining)) {
            $statements[] = $remaining;
        }
        
        return $statements;
    }
    
    /**
     * Verify that fixtures loaded correctly
     */
    private static function verifyFixtures($silent = false)
    {
        $checks = [
            ['table' => 'users', 'min_count' => 7],
            ['table' => 'projects', 'min_count' => 5], 
            ['table' => 'tasks', 'min_count' => 10],
            ['table' => 'comments', 'min_count' => 10],
            ['table' => 'project_memberships', 'min_count' => 5]
        ];
        
        foreach ($checks as $check) {
            $sql = "SELECT COUNT(*) as count FROM {$check['table']}";
            $result = self::$connection->query($sql)->fetch();
            
            if ($result['count'] < $check['min_count']) {
                throw new Exception("Verification failed: {$check['table']} has {$result['count']} records, expected at least {$check['min_count']}");
            }
        }
    }
    
    /**
     * Get current database statistics for verification
     */
    public static function getDatabaseStats()
    {
        self::initConnection();
        
        $tables = ['users', 'projects', 'tasks', 'comments', 'project_memberships', 'task_assignments', 'notifications'];
        $stats = [];
        
        foreach ($tables as $table) {
            $sql = "SELECT COUNT(*) as count FROM {$table}";
            $result = self::$connection->query($sql)->fetch();
            $stats[$table] = $result['count'];
        }
        
        return $stats;
    }
}

// CLI Usage
if (php_sapi_name() === 'cli' && !defined('TEST_ENVIRONMENT')) {
    $options = getopt('', ['fixtures', 'minimal', 'verify', 'help']);
    
    if (isset($options['help'])) {
        echo "Database Reset Utility for CS3332 AllStars\n\n";
        echo "Usage: php reset_database.php [options]\n\n";
        echo "Options:\n";
        echo "  --fixtures    Reset with full test fixtures (default)\n";
        echo "  --minimal     Reset to minimal state only\n";
        echo "  --verify      Show current database statistics\n";
        echo "  --help        Show this help message\n\n";
        exit(0);
    }
    
    try {
        if (isset($options['verify'])) {
            $stats = DatabaseReset::getDatabaseStats();
            echo "📊 Current Database Statistics:\n";
            foreach ($stats as $table => $count) {
                echo "   {$table}: {$count} records\n";
            }
        } elseif (isset($options['minimal'])) {
            DatabaseReset::resetToMinimal(false); // Force verbose for CLI
        } else {
            // Default to fixtures
            DatabaseReset::resetToFixtures(false); // Force verbose for CLI
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Web Usage (Development Only)
if (isset($_GET['token']) && $_GET['token'] === 'dev_reset') {
    // Security check - only allow in development
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        http_response_code(403);
        die('Not allowed in production');
    }
    
    header('Content-Type: application/json');
    
    try {
        if (isset($_GET['minimal'])) {
            DatabaseReset::resetToMinimal(true); // Silent for web/JSON
            $action = 'minimal reset';
        } else {
            DatabaseReset::resetToFixtures(true); // Silent for web/JSON
            $action = 'full fixtures reset';
        }
        
        $stats = DatabaseReset::getDatabaseStats();
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'stats' => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
