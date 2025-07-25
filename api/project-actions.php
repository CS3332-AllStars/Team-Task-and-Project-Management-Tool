<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-12F: Project Archival & Deletion API Endpoints

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../includes/api-session-check.php';
require_once '../includes/csrf-protection.php';

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

// CSRF protection for all actions
if (!validateCSRFToken($data['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
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

// Verify project exists
$stmt = $mysqli->prepare("SELECT title, is_archived FROM projects WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

try {
    switch ($action) {
        case 'archive_project':
            // Check if already archived
            if ($project['is_archived']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project is already archived']);
                exit;
            }
            
            // Archive the project
            $stmt = $mysqli->prepare("UPDATE projects SET is_archived = TRUE, updated_at = NOW() WHERE project_id = ?");
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Project archived successfully'
            ]);
            break;
            
        case 'unarchive_project':
            // Check if actually archived
            if (!$project['is_archived']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project is not archived']);
                exit;
            }
            
            // Unarchive the project
            $stmt = $mysqli->prepare("UPDATE projects SET is_archived = FALSE, updated_at = NOW() WHERE project_id = ?");
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Project unarchived successfully'
            ]);
            break;
            
        case 'delete_project':
            // Begin transaction for cascading deletes
            $mysqli->begin_transaction();
            
            try {
                // Delete project (foreign key cascades will handle related data)
                $stmt = $mysqli->prepare("DELETE FROM projects WHERE project_id = ?");
                $stmt->bind_param("i", $project_id);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                
                if ($affected === 0) {
                    throw new Exception('Project not found or already deleted');
                }
                
                $mysqli->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Project deleted successfully'
                ]);
                
            } catch (Exception $e) {
                $mysqli->rollback();
                throw $e;
            }
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