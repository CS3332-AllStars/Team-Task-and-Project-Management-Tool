<?php
// CS3332 AllStars Team Task & Project Management System
// Protected Route Guards - CS3-11D

require_once __DIR__ . '/session-manager.php';
require_once __DIR__ . '/rbac-helpers.php';

startSecureSession();

// Basic login check
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Project-specific access check (if project_id is present)
if (isset($_GET['project_id']) || isset($_POST['project_id'])) {
    $projectID = $_GET['project_id'] ?? $_POST['project_id'];
    $userID = $_SESSION['user_id'];
    
    if (!isProjectMember($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied: You are not a member of this project.";
        exit;
    }
}
?>