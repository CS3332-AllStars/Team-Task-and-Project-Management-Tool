<?php
// Check PHP MySQL support and configuration
echo "🔍 PHP MySQL Diagnostics\n";
echo "========================\n\n";

// Check PDO MySQL support
echo "📋 PDO MySQL Support:\n";
if (extension_loaded('pdo_mysql')) {
    echo "   ✅ PDO MySQL extension loaded\n";
} else {
    echo "   ❌ PDO MySQL extension NOT loaded\n";
}

// Check available PDO drivers
echo "\n📋 Available PDO Drivers:\n";
$drivers = PDO::getAvailableDrivers();
foreach ($drivers as $driver) {
    echo "   - $driver\n";
}

// Check PHP version
echo "\n📋 PHP Version: " . phpversion() . "\n";

// Check socket/networking settings
echo "\n📋 PHP Network Settings:\n";
echo "   default_socket_timeout: " . ini_get('default_socket_timeout') . "\n";
echo "   mysql.default_host: " . ini_get('mysql.default_host') . "\n";
echo "   mysql.default_port: " . ini_get('mysql.default_port') . "\n";

// Check if we can resolve localhost
echo "\n📋 Network Resolution Test:\n";
$ip = gethostbyname('localhost');
echo "   localhost resolves to: $ip\n";

$ip2 = gethostbyname('127.0.0.1');
echo "   127.0.0.1 resolves to: $ip2\n";

echo "\n✅ PHP diagnostics complete\n";
?>
