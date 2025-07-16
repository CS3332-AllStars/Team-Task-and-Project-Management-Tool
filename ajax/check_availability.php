<?php
// CS3332 AllStars Team Task & Project Management System
// AJAX Endpoint - CS3-11A: Username/Email Availability Check
// Real-time validation for registration form

// Only set headers if not in test environment
if (!defined('PHPUNIT_COMPOSER_INSTALL') && !headers_sent()) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';

// Handle test environment where global $pdo might exist
if (!isset($pdo)) {
    global $pdo;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$type = $_POST['type'] ?? '';
$value = trim($_POST['value'] ?? '');

if (empty($type) || empty($value)) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$user = new User($pdo);

try {
    switch ($type) {
        case 'username':
            $result = $user->checkUsernameAvailability($value);
            break;
            
        case 'email':
            $result = $user->checkEmailAvailability($value);
            break;
            
        case 'password':
            $result = $user->validatePasswordStrength($value);
            break;
        default:
            echo json_encode(['error' => 'Invalid type']);
            exit;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error', 'available' => false]);
}
?>