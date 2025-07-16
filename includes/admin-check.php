<?php
// CS3332 AllStars Team Task & Project Management System
// Admin-Only Route Guards - CS3-11D

require_once 'session-check.php';

// Verify admin access for current project
if (isset($_GET['project_id'])) {
    $projectID = $_GET['project_id'];
    $userID = $_SESSION['user_id'];
    
    if (!isProjectAdmin($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied: Admin privileges required.";
        exit;
    }
}
?>