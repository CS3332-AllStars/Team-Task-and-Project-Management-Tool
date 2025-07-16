<?php
// Ultra simple connection test with minimal PDO options
echo "🔧 Minimal PDO connection test...\n";

set_time_limit(15); // 15 second script timeout
ini_set('default_socket_timeout', 10); // 10 second socket timeout

try {
    echo "🔌 Attempting minimal connection...\n";
    
    // Most basic PDO connection possible
    $pdo = new PDO("mysql:host=127.0.0.1", 'root', '');
    
    echo "✅ Minimal connection successful!\n";
    
} catch (Exception $e) {
    echo "❌ Minimal connection failed: " . $e->getMessage() . "\n";
}

echo "🏁 Test completed.\n";
?>
