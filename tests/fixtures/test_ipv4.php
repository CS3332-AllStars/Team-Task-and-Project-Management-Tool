<?php
// Test with explicit IPv4 address
echo "🔧 Testing IPv4 connection explicitly...\n";

$host = '127.0.0.1';  // Explicit IPv4 instead of 'localhost'
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

echo "📋 Connection parameters:\n";
echo "   Host: {$host} (IPv4)\n";
echo "   Database: {$dbname}\n";

try {
    echo "🔌 Attempting IPv4 connection...\n";
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    echo "   DSN: {$dsn}\n";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    echo "✅ IPv4 connection successful!\n\n";
    
    // Test query
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch();
    echo "✅ Query successful! User count: " . $row['count'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ IPv4 connection failed: " . $e->getMessage() . "\n";
}
?>
