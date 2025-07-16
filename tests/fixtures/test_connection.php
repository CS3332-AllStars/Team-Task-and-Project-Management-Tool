<?php
// Simple database connection test
echo "🔧 Testing database connection...\n";

echo "📋 Connection parameters:\n";
$host = 'localhost';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

echo "   Host: {$host}\n";
echo "   Database: {$dbname}\n";
echo "   User: {$username}\n";
echo "   Password: " . (empty($password) ? '(empty)' : '(set)') . "\n\n";

try {
    echo "🔌 Attempting connection...\n";
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    echo "   DSN: {$dsn}\n";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✅ Connection successful!\n\n";
    
    // Test a simple query
    echo "🧪 Testing simple query...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch();
    echo "✅ Query successful! User count: " . $row['count'] . "\n\n";
    
    echo "🎉 Database connection test PASSED!\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "\n🔍 Common issues:\n";
    echo "   1. XAMPP MySQL not running\n";
    echo "   2. Database 'ttpm_system' doesn't exist\n";
    echo "   3. Port 3306 blocked or in use\n";
    echo "   4. MySQL root user password not empty\n";
}
?>
