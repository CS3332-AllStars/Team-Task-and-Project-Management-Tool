<?php
// Quick test to execute just the first INSERT statement and see what happens
echo "🔍 Testing single INSERT statement...\n";

// Use database config values directly  
$host = '127.0.0.1';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connected to database\n";
    
    // Clear users table
    echo "🗑️  Clearing users table...\n";
    $pdo->exec("DELETE FROM users");
    
    // Test a simple INSERT
    echo "📝 Testing simple INSERT...\n";
    $sql = "INSERT INTO users (user_id, username, email, password_hash, name) VALUES (1, 'test_user', 'test@example.com', 'hash123', 'Test User')";
    $pdo->exec($sql);
    
    // Check if it worked
    $result = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "📊 Users after simple INSERT: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "✅ Simple INSERT works!\n";
        
        // Now test the actual INSERT from sample_data.sql
        echo "📝 Testing actual sample_data INSERT...\n";
        $pdo->exec("DELETE FROM users");
        
        $actualInsert = "INSERT INTO users (user_id, username, email, password_hash, name) VALUES
        (1, 'james_ward', 'james.ward@allstars.edu', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Ward'),
        (2, 'summer_hill', 'summer.hill@allstars.edu', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Summer Hill')";
        
        $pdo->exec($actualInsert);
        
        $result = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
        echo "📊 Users after sample_data INSERT: " . $result['count'] . "\n";
        
        if ($result['count'] > 0) {
            echo "✅ Sample data INSERT works too!\n";
            echo "🤔 Issue must be in the file processing logic...\n";
        } else {
            echo "❌ Sample data INSERT failed - check the actual SQL syntax\n";
        }
    } else {
        echo "❌ Even simple INSERT failed - check database permissions\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
