<?php
// CS3332 AllStars Team Task & Project Management System
// Role-Based Access Control - CS3-11C

require_once __DIR__ . '/../src/config/database.php';

function getUserRole($userID, $projectID) {
    global $pdo;
    $sql = "SELECT role FROM project_memberships WHERE user_id = ? AND project_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userID, $projectID]);
    $result = $stmt->fetch();
    
    return $result ? $result['role'] : null;
}

function isProjectAdmin($userID, $projectID) {
    return getUserRole($userID, $projectID) === 'admin';
}

function isProjectMember($userID, $projectID) {
    $role = getUserRole($userID, $projectID);
    return $role === 'admin' || $role === 'member';
}

function hasPermission($userID, $projectID, $action) {
    $role = getUserRole($userID, $projectID);
    
    switch ($action) {
        case 'view_project':
        case 'view_tasks':
        case 'view_comments':
        case 'create_task':
        case 'comment':
            return $role !== null; // Any member
            
        case 'manage_team':
        case 'edit_project':
        case 'delete_project':
            return $role === 'admin';
            
        case 'edit_task':
        case 'delete_task':
            // Task creator or project admin
            return $role === 'admin' || isTaskCreator($userID, $_GET['taskID'] ?? 0);
            
        default:
            return false;
    }
}

function isTaskCreator($userID, $taskID) {
    global $pdo;
    $sql = "SELECT assigned_by FROM tasks WHERE task_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskID]);
    $result = $stmt->fetch();
    
    return $result && $result['assigned_by'] == $userID;
}

function promoteToAdmin($adminUserID, $targetUserID, $projectID) {
    // Verify admin has permission
    if (!isProjectAdmin($adminUserID, $projectID)) {
        return false;
    }
    
    global $pdo;
    $sql = "UPDATE project_memberships SET role = 'admin' WHERE user_id = ? AND project_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$targetUserID, $projectID]);
}
?>