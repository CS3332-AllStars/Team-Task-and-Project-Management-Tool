<?php

// Define test-specific constants
if (!defined('TEST_DATABASE_NAME')) {
    define('TEST_DATABASE_NAME', 'your_test_db');
}
// Add other test constants as needed

// Setup database connection for testing
// (Example using PDO - adjust based on your project's DB layer)
try {
    $dsn = 'mysql:host=localhost;dbname=' . TEST_DATABASE_NAME;
    $pdo = new PDO($dsn, 'your_test_user', 'your_test_password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // You might want to make this connection globally accessible or pass it
    // to your classes/tests, depending on your architecture.
} catch (\PDOException $e) {
    echo "Database connection error: " . $e->getMessage();
    exit(1); // Exit if DB connection fails
}
