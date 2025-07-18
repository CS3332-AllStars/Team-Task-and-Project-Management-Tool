<?php
// CS3332 AllStars Team Task & Project Management System
// AJAX API Endpoint for Member Management Actions

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../includes/session-check.php';

// Database connection
$host = 'localhost';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && empty($_POST)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Use JSON input if available, otherwise fall back to POST
$data = $input ?: $_POST;

$action = $data['action'] ?? '';
$project_id = (int)($data['project_id'] ?? 0);

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit;
}

// Check if user has admin access to this project
$stmt = $mysqli->prepare("SELECT role FROM project_memberships WHERE user_id = ? AND project_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $project_id);
$stmt->execute();
$result = $stmt->get_result();
$membership = $result->fetch_assoc();
$stmt->close();

if (!$membership || $membership['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied: Admin privileges required']);
    exit;
}

try {
    switch ($action) {
        case 'add_member':
            $email = trim($data['email'] ?? '');
            
            if (empty($email)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email is required']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }
            
            // Find user by email
            $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found with this email']);
                exit;
            }
            
            // Check if user is already a member
            $stmt = $mysqli->prepare("SELECT user_id FROM project_memberships WHERE user_id = ? AND project_id = ?");
            $stmt->bind_param("ii", $user['user_id'], $project_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($existing) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'User is already a member of this project']);
                exit;
            }
            
            // Add user as member
            $stmt = $mysqli->prepare("INSERT INTO project_memberships (user_id, project_id, role) VALUES (?, ?, 'member')");
            $stmt->bind_param("ii", $user['user_id'], $project_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Member added successfully',
                'user_id' => $user['user_id']
            ]);
            break;
            
        case 'remove_member':
            $user_id = (int)($data['user_id'] ?? 0);
            
            if (!$user_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit;
            }
            
            // Prevent admin from removing themselves
            if ($user_id === $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot remove yourself from the project']);
                exit;
            }
            
            // Remove member
            $stmt = $mysqli->prepare("DELETE FROM project_memberships WHERE user_id = ? AND project_id = ?");
            $stmt->bind_param("ii", $user_id, $project_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($affected === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Member not found or already removed']);
                exit;
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Member removed successfully'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$mysqli->close();
?>