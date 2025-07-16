<?php
// Test MySQL connection without database specification
echo "🔧 Testing MySQL server connection (no database)...\n";

try {
    echo "🔌 Connecting to MySQL server only...\n";
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5  // 5 second timeout
    ]);
    
    echo "✅ MySQL server connection successful!\n\n";
    
    // Check if database exists
    echo "🔍 Checking if ttpm_system database exists...\n";
    $result = $pdo->query("SHOW DATABASES LIKE 'ttpm_system'");
    $exists = $result->fetch();
    
    if ($exists) {
        echo "✅ Database 'ttpm_system' exists!\n";
    } else {
        echo "❌ Database 'ttmp_system' does NOT exist!\n";
        echo "📝 Available databases:\n";
        $result = $pdo->query("SHOW DATABASES");
        while ($row = $result->fetch()) {
            echo "   - " . $row['Database'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ MySQL server connection failed: " . $e->getMessage() . "\n";
    echo "\n🔍 This suggests:\n";
    echo "   1. MySQL is not running\n";
    echo "   2. Wrong port (not 3306)\n";
    echo "   3. MySQL configured for different host\n";
    echo "   4. Firewall blocking connection\n";
}
?>
