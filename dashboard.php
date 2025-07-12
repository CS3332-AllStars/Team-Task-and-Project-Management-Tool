<?php
// CS3332 AllStars Team Task & Project Management System
// Main Dashboard - Core Application Interface
// Requires user authentication

session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'src/config/database.php';
require_once 'src/models/User.php';
require_once 'src/models/Project.php';
require_once 'src/models/Task.php';

$user = new User($pdo);
$projectModel = new Project($pdo);
$taskModel = new Task($pdo);

// Get user data
$userData = $user->getUserById($_SESSION['user_id']);

// Get user's projects and tasks for dashboard overview
try {
    // Get projects user is member of
    $userProjects = $projectModel->getUserProjects($_SESSION['user_id']);
    
    // Get recent tasks assigned to user
    $userTasks = $taskModel->getUserTasks($_SESSION['user_id'], 5); // Limit to 5 recent
    
    // Get activity notifications (if notifications table exists)
    $notifications = [];
    
} catch (Exception $e) {
    $error = "Dashboard data loading failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Team Task & Project Management</title>
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .dashboard-header h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .user-info {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .btn-create {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .btn-create:hover {
            background: #218838;
        }
        
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .item-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-list li:last-child {
            border-bottom: none;
        }
        
        .item-title {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .item-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-todo {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-progress {
            background: #c