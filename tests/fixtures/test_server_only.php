<?php
// Test MySQL server connection only (no database)
echo "🔧 Testing MySQL server connection (no database)...\n";

try {
    echo "🔌 Connecting to MySQL server only...\n";
    $pdo = new PDO("mysql:host=127.0.0.1;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ MySQL server connection successful!\n\n";
    
    // List databases
    echo "📋 Available databases:\n";
    $result = $pdo->query("SHOW DATABASES");
    while ($row = $result->fetch()) {
        echo "   - " . $row['Database'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ MySQL server connection failed: " . $e->getMessage() . "\n";
}
?>
