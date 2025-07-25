<?php
// CS3332 AllStars Team Task & Project Management System
// Command Line Migration Runner

require_once 'src/utils/MigrationRunner.php';

// Database configuration
$host = 'localhost';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

// Command line options
$options = getopt("hvs", ["help", "verbose", "status"]);

if (isset($options['h']) || isset($options['help'])) {
    showHelp();
    exit(0);
}

$verbose = isset($options['v']) || isset($options['verbose']);
$show_status = isset($options['s']) || isset($options['status']);

try {
    echo "🔧 TTPM Database Migration Runner\n";
    echo "================================\n\n";
    
    // Connect to database
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }
    
    if ($verbose) {
        echo "✅ Connected to database: $dbname\n\n";
    }
    
    $migrationRunner = new MigrationRunner($mysqli, null, $verbose);
    
    if ($show_status) {
        showMigrationStatus($migrationRunner);
    } else {
        runMigrations($migrationRunner);
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function runMigrations($migrationRunner) {
    if ($migrationRunner->hasPendingMigrations()) {
        echo "🚀 Running pending migrations...\n\n";
        if ($migrationRunner->runMigrations()) {
            echo "\n🎉 All migrations completed successfully!\n";
        } else {
            echo "\n❌ Migration failed!\n";
            exit(1);
        }
    } else {
        echo "✅ Database is up to date - no pending migrations.\n";
    }
}

function showMigrationStatus($migrationRunner) {
    echo "📊 Migration Status:\n";
    echo "==================\n\n";
    
    $status = $migrationRunner->getMigrationStatus();
    
    if (isset($status['error'])) {
        echo "❌ Error getting status: " . $status['error'] . "\n";
        return;
    }
    
    foreach ($status as $migration) {
        $icon = $migration['applied'] ? '✅' : '⏳';
        $fileStatus = $migration['file_exists'] ? '' : ' (file missing)';
        echo sprintf("%s %s%s\n", $icon, $migration['migration'], $fileStatus);
    }
    
    $pending = array_filter($status, function($m) { return !$m['applied']; });
    echo "\n" . count($pending) . " pending migration(s)\n";
}

function showHelp() {
    echo "TTPM Database Migration Runner\n";
    echo "=============================\n\n";
    echo "Usage: php migrate.php [options]\n\n";
    echo "Options:\n";
    echo "  -h, --help     Show this help message\n";
    echo "  -v, --verbose  Show detailed output\n";
    echo "  -s, --status   Show migration status instead of running\n\n";
    echo "Examples:\n";
    echo "  php migrate.php           # Run pending migrations\n";
    echo "  php migrate.php -v        # Run with verbose output\n";
    echo "  php migrate.php --status  # Show migration status\n";
}
?>