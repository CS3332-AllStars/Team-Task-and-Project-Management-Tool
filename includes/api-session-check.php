<?php
// CS3332 AllStars Team Task & Project Management System
// API-specific session check that returns JSON instead of redirecting

require_once __DIR__ . '/session-manager.php';
require_once __DIR__ . '/rbac-helpers.php';

startSecureSession();

// For API endpoints, return JSON error instead of redirecting
if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.', 'redirect' => 'login.php']);
    exit;
}

// Project-specific access check (if project_id is present)
if (isset($_GET['project_id']) || isset($_POST['project_id'])) {
    $projectID = $_GET['project_id'] ?? $_POST['project_id'];
    $userID = $_SESSION['user_id'];
    
    if (!isProjectMember($userID, $projectID)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied: You are not a member of this project.']);
        exit;
    }
}
?>